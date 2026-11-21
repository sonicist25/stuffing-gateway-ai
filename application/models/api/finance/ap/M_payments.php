<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_payments extends MY_Model
{
  private $company_id = 1;
  public function __construct()
  {
    parent::__construct();
    $this->load->model('api/finance/ap/M_vendor_deposits', 'mvd');
  }
  /**
   * IMPORTANT PATCH NOTES
   * - Payment aktif di file ini memakai status SUBMITTED, bukan POSTED.
   * - Recalc paid_total wajib dari ap_payment_refs JOIN ap_payments status SUBMITTED.
   * - Untuk status per item, ap_payment_refs harus punya kolom bill_item_id.
   *   Jika belum ada, tambahkan:
   *   ALTER TABLE ap_payment_refs ADD COLUMN bill_item_id BIGINT UNSIGNED NULL AFTER bill_id;
   */

  public function list($p)
  {
    $this->erp->select("p.pay_id,p.pay_no,p.posting_date,p.cash_account_id,p.currency,p.total_paid,p.remark,p.status", false)
      ->from('ap_payments p')
      ->where('p.company_id', $this->company_id);
    $this->apply_region_filter_alias('p', 'region_id');

    if (!empty($p['status'])) $this->erp->where('p.status', strtoupper($p['status']));
    if (!empty($p['search'])) {
      $q = $p['search'];
      $this->erp->group_start()->like('p.pay_no', $q)->or_like('p.remark', $q)->group_end();
    }
    if (!empty($p['date_from'])) $this->erp->where('p.posting_date >=', $p['date_from']);
    if (!empty($p['date_to']))   $this->erp->where('p.posting_date <=', $p['date_to']);

    $this->erp->order_by('p.posting_date', 'DESC')->order_by('p.pay_id', 'DESC');
    $rows = $this->erp->get()->result_array();
    $payIds = array_values(array_unique(array_map(function($r){ return (int)$r['pay_id']; }, $rows)));

    $vendorMap = [];
    if ($payIds) {
      $vrows = $this->erp->select("r.pay_id, GROUP_CONCAT(DISTINCT b.vendor_name ORDER BY b.vendor_name SEPARATOR ', ') AS vendors", false)
        ->from('ap_payment_refs r')
        ->join('ap_bills b', 'b.bill_id = r.bill_id', 'inner')
        ->where_in('r.pay_id', $payIds)
        ->group_by('r.pay_id')
        ->get()->result_array();

      foreach ($vrows as $v) $vendorMap[(int)$v['pay_id']] = (string)$v['vendors'];
    }

    foreach ($rows as &$r) $r['vendors'] = $vendorMap[(int)$r['pay_id']] ?? '';
    unset($r);

    if ($rows) {
      $ids = array_values(array_unique(array_map(function($x){ return (int)$x['cash_account_id']; }, $rows)));
      $map = [];
      if ($ids) {
        $this->erp->select('account_id,account_code,account_name')
          ->from('accounts')
          ->where('company_id', $this->company_id)
          ->where_in('account_id', $ids);
        $this->apply_region_filter('region_id');
        $acc = $this->erp->get()->result_array();
        foreach ($acc as $a) $map[(int)$a['account_id']] = $a['account_code'].' - '.$a['account_name'];
      }
      foreach ($rows as &$r) {
        $r['cash_account_label'] = $map[(int)$r['cash_account_id']] ?? null;
        $r['total_paid'] = (float)$r['total_paid'];
      }
      unset($r);
    }

    return $rows;
  }

  public function get($pay_id)
  {
    $hdr = $this->erp->where('company_id',$this->company_id)->where('pay_id',(int)$pay_id)->where('region_id',$this->auth_region())->get('ap_payments')->row_array();
    if (!$hdr) return null;

    $select = 'ref_id,pay_id,bill_id,bill_no,allocated_amount';
    if ($this->erp->field_exists('bill_item_id', 'ap_payment_refs')) $select .= ',bill_item_id';
    if ($this->erp->field_exists('currency', 'ap_payment_refs')) $select .= ',currency';
    if ($this->erp->field_exists('posting_kurs', 'ap_payment_refs')) $select .= ',posting_kurs';
    if ($this->erp->field_exists('bill_posting_kurs', 'ap_payment_refs')) $select .= ',bill_posting_kurs';
    if ($this->erp->field_exists('base_allocated_amount', 'ap_payment_refs')) $select .= ',base_allocated_amount';
    if ($this->erp->field_exists('ap_base_amount', 'ap_payment_refs')) $select .= ',ap_base_amount';
    if ($this->erp->field_exists('cash_base_amount', 'ap_payment_refs')) $select .= ',cash_base_amount';
    if ($this->erp->field_exists('forex_gain_loss', 'ap_payment_refs')) $select .= ',forex_gain_loss';

    $refs = $this->erp->select($select, false)
      ->from('ap_payment_refs')
      ->where('pay_id', (int)$pay_id)
      ->order_by('ref_id', 'ASC')
      ->get()->result_array();

    foreach ($refs as &$r) {
      $r['allocated_amount'] = (float)$r['allocated_amount'];
      if (isset($r['bill_item_id'])) $r['bill_item_id'] = (int)$r['bill_item_id'];
      if (empty($r['currency'])) $r['currency'] = 'IDR';
      foreach (['posting_kurs','bill_posting_kurs','base_allocated_amount','ap_base_amount','cash_base_amount','forex_gain_loss'] as $moneyKey) {
        if (isset($r[$moneyKey])) $r[$moneyKey] = (float)$r[$moneyKey];
      }
    }
    unset($r);

    $hdr['total_paid'] = (float)$hdr['total_paid'];
    if (empty($hdr['currency'])) $hdr['currency'] = 'IDR';
    if (isset($hdr['payment_kurs'])) $hdr['payment_kurs'] = (float)$hdr['payment_kurs'];
    if (isset($hdr['base_total_paid'])) $hdr['base_total_paid'] = (float)$hdr['base_total_paid'];
    $hdr['refs'] = $refs;
    return $hdr;
  }

  public function create($p)
  {
    $posting_date = (string)($p['posting_date'] ?? '');
    $cash_account_id = (int)($p['cash_account_id'] ?? 0);
    $remark = trim((string)($p['remark'] ?? ''));
    $region_id = (string)$this->auth_region();
    $now = date('Y-m-d H:i:s');

    $alloc = $this->_normalize_payment_refs($p);
    $adjustments = (!empty($p['adjustments']) && is_array($p['adjustments']))
      ? $p['adjustments']
      : [];

    $errs = [];

    if (!$posting_date) $errs['posting_date'] = 'required';
    if ($cash_account_id <= 0) $errs['cash_account_id'] = 'required';
    if (!is_array($alloc) || count($alloc) < 1) $errs['allocations'] = 'required';

    if ($errs) {
      return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
    }

    if ($this->_is_locked($posting_date)) {
      return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
    }

    $this->erp->select('account_id,is_postable,is_active,root_type')
      ->from('accounts')
      ->where('company_id', $this->company_id)
      ->where('account_id', $cash_account_id);
    $this->apply_region_filter('region_id');
    $acc = $this->erp->get()->row_array();

    if (!$acc || (int)$acc['is_postable'] !== 1 || (int)$acc['is_active'] !== 1) {
      return ['ok'=>false,'http'=>422,'message'=>'Invalid cash account'];
    }

    $ap_account_id = $this->_coa_id('AP', $region_id);
    if ($ap_account_id <= 0) {
      return ['ok'=>false,'http'=>422,'message'=>'Missing/invalid AP account (' . $this->_coa_code('AP') . ') in COA'];
    }

    $vendor_deposit_account_id = $this->_coa_id('VENDOR_DEPOSIT', $region_id);
    $hasBillItemRef = $this->erp->field_exists('bill_item_id', 'ap_payment_refs');

    $payloadCurrency = strtoupper(trim((string)($p['currency'] ?? '')));
    if ($payloadCurrency !== '' && !in_array($payloadCurrency, ['IDR', 'USD'], true)) {
      return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>['currency'=>'currency must be IDR or USD']];
    }

    $paymentKurs = (float)($p['payment_kurs'] ?? ($p['kurs'] ?? 1));

    $total_allocated = 0.0;       // original payment currency amount
    $ap_base_total = 0.0;         // AP value at bill posting kurs, IDR
    $cash_settlement_base = 0.0;  // allocation value at payment kurs, IDR
    $rows = [];
    $billMap = [];
    $allocByBill = [];
    $mainCurrency = '';

    $billSelect = 'bill_id,bill_no,vendor_type,vendor_code,vendor_name,total,paid_total,status,currency';
    if ($this->erp->field_exists('posting_kurs', 'ap_bills')) $billSelect .= ',posting_kurs';
    if ($this->erp->field_exists('base_total', 'ap_bills')) $billSelect .= ',base_total';

    $itemSelect = 'item_id,bill_id,amount,memo';
    if ($this->erp->field_exists('currency', 'ap_bill_items')) $itemSelect .= ',currency';
    if ($this->erp->field_exists('posting_kurs', 'ap_bill_items')) $itemSelect .= ',posting_kurs';
    if ($this->erp->field_exists('base_amount', 'ap_bill_items')) $itemSelect .= ',base_amount';

    foreach ($alloc as $i => $a) {
      $bid = (int)($a['bill_id'] ?? 0);
      $billItemId = (int)($a['bill_item_id'] ?? ($a['item_id'] ?? 0));
      $amt = round((float)($a['amount'] ?? ($a['allocated_amount'] ?? 0)), 2);

      if ($bid <= 0) {
        $errs["allocations.$i.bill_id"] = 'required';
        continue;
      }

      if ($amt <= 0) {
        $errs["allocations.$i.amount"] = 'must be > 0';
        continue;
      }

      $bill = $this->erp->select($billSelect, false)
        ->get_where('ap_bills', [
          'company_id'=>$this->company_id,
          'region_id'=>$region_id,
          'bill_id'=>$bid
        ])->row_array();

      if (!$bill) {
        $errs["allocations.$i.bill_id"] = 'bill not found';
        continue;
      }

      if (strtoupper((string)$bill['status']) === 'CANCELLED') {
        $errs["allocations.$i.bill_id"] = 'bill cancelled';
        continue;
      }

      $billCurrency = strtoupper(trim((string)($bill['currency'] ?? 'IDR')));
      if (!in_array($billCurrency, ['IDR', 'USD'], true)) $billCurrency = 'IDR';

      if ($mainCurrency === '') $mainCurrency = $payloadCurrency ?: $billCurrency;
      if ($billCurrency !== $mainCurrency) {
        $errs["allocations.$i.currency"] = 'Payment cannot mix bill currency. Selected currency is '.$mainCurrency.', but this bill is '.$billCurrency;
        continue;
      }
      if ($payloadCurrency !== '' && $payloadCurrency !== $billCurrency) {
        $errs["allocations.$i.currency"] = 'Payment currency must match bill currency';
        continue;
      }

      $billPostingKurs = (float)($bill['posting_kurs'] ?? 1);
      if ($billCurrency === 'IDR') $billPostingKurs = 1;
      if ($billCurrency === 'USD' && $billPostingKurs <= 1) {
        $errs["allocations.$i.posting_kurs"] = 'Bill USD belum punya posting_kurs yang valid';
        continue;
      }

      $billMap[$bid] = $bill;

      $linePostingKurs = $billPostingKurs;
      $lineCurrency = $billCurrency;
      $lineBaseAmount = 0.0;

      if ($billItemId > 0) {
        if (!$hasBillItemRef) {
          $errs["allocations.$i.bill_item_id"] = 'ap_payment_refs.bill_item_id column is required for item-level AP payment';
          continue;
        }

        $item = $this->erp->select($itemSelect, false)
          ->get_where('ap_bill_items', [
            'bill_id'=>$bid,
            'item_id'=>$billItemId
          ])->row_array();

        if (!$item) {
          $errs["allocations.$i.bill_item_id"] = 'bill item not found';
          continue;
        }

        $itemCurrency = strtoupper(trim((string)($item['currency'] ?? $billCurrency)));
        if (!in_array($itemCurrency, ['IDR', 'USD'], true)) $itemCurrency = $billCurrency;
        if ($itemCurrency !== $billCurrency) {
          $errs["allocations.$i.currency"] = 'Bill item currency tidak sama dengan bill header';
          continue;
        }

        $lineCurrency = $itemCurrency;
        $linePostingKurs = (float)($item['posting_kurs'] ?? $billPostingKurs);
        if ($lineCurrency === 'IDR') $linePostingKurs = 1;
        if ($lineCurrency === 'USD' && $linePostingKurs <= 1) {
          $errs["allocations.$i.posting_kurs"] = 'Bill item USD belum punya posting_kurs yang valid';
          continue;
        }

        $itemPaid = $this->_sum_paid_item($billItemId);
        $out = round((float)$item['amount'] - $itemPaid, 2);

        if ($out <= 0.01) {
          $errs["allocations.$i.bill_item_id"] = 'item already paid';
          continue;
        }

        if ($amt - $out > 0.01) {
          $errs["allocations.$i.amount"] = "amount exceeds item outstanding ($out)";
          continue;
        }

        $lineBaseAmount = ($lineCurrency === 'USD')
          ? round($amt * $linePostingKurs, 2)
          : $amt;

        $rows[] = [
          'bill_id' => $bid,
          'bill_no' => $bill['bill_no'],
          'bill_item_id' => $billItemId,
          'amount' => $amt,
          'currency' => $lineCurrency,
          'bill_posting_kurs' => $linePostingKurs,
          'ap_base_amount' => $lineBaseAmount,
          'cash_base_amount' => 0.0,
          'forex_gain_loss' => 0.0,
        ];
      } else {
        $billPaid = $this->_sum_paid_bill($bid);
        $out = round((float)$bill['total'] - $billPaid, 2);

        if ($out <= 0.01) {
          $errs["allocations.$i.bill_id"] = 'bill already paid';
          continue;
        }

        if ($amt - $out > 0.01) {
          $errs["allocations.$i.amount"] = "amount exceeds outstanding ($out)";
          continue;
        }

        $lineBaseAmount = ($lineCurrency === 'USD')
          ? round($amt * $linePostingKurs, 2)
          : $amt;

        $rows[] = [
          'bill_id' => $bid,
          'bill_no' => $bill['bill_no'],
          'bill_item_id' => null,
          'amount' => $amt,
          'currency' => $lineCurrency,
          'bill_posting_kurs' => $linePostingKurs,
          'ap_base_amount' => $lineBaseAmount,
          'cash_base_amount' => 0.0,
          'forex_gain_loss' => 0.0,
        ];
      }

      if (!isset($allocByBill[$bid])) $allocByBill[$bid] = 0.0;
      $allocByBill[$bid] += $amt;
      $total_allocated += $amt;
      $ap_base_total += $lineBaseAmount;
    }

    if ($errs) {
      return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
    }

    $firstBill = reset($billMap);
    $main_vendor_code = $firstBill['vendor_code'] ?? null;
    $main_vendor_name = $firstBill['vendor_name'] ?? null;
    $main_vendor_type = $firstBill['vendor_type'] ?? null;
    $currency = strtoupper($mainCurrency ?: ($firstBill['currency'] ?? 'IDR'));
    if (!in_array($currency, ['IDR', 'USD'], true)) $currency = 'IDR';

    if ($currency === 'IDR') {
      $paymentKurs = 1;
    } elseif ($paymentKurs <= 1) {
      return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>['payment_kurs'=>'Payment kurs wajib diisi untuk pembayaran USD']];
    }

    foreach ($rows as &$r) {
      $r['cash_base_amount'] = ($currency === 'USD')
        ? round((float)$r['amount'] * $paymentKurs, 2)
        : (float)$r['amount'];
      $r['forex_gain_loss'] = round($r['cash_base_amount'] - $r['ap_base_amount'], 2);
      $cash_settlement_base += $r['cash_base_amount'];
    }
    unset($r);

    foreach ($billMap as $b) {
      if ((string)($b['vendor_code'] ?? '') !== (string)($main_vendor_code ?? '')) {
        return [
          'ok'=>false,
          'http'=>422,
          'message'=>'Payment cannot mix multiple vendors'
        ];
      }
    }

    $cleanAdjustments = [];
    $total_reduce_cash = 0.0;
    $total_add_cash = 0.0;
    $total_reduce_cash_base = 0.0;
    $total_add_cash_base = 0.0;

    foreach ($adjustments as $i => $a) {
      $type = strtoupper(trim((string)($a['type'] ?? 'OTHER')));
      $effect = strtoupper(trim((string)($a['effect'] ?? '')));
      $amount = round((float)($a['amount'] ?? 0), 2);
      $account_id = (int)($a['account_id'] ?? 0);
      $memo = trim((string)($a['memo'] ?? $type));
      $deposit_id = (int)($a['deposit_id'] ?? 0);

      if ($amount <= 0) {
        $errs["adjustments.$i.amount"] = 'must be > 0';
        continue;
      }

      if (in_array($type, ['PPH23', 'USE_VENDOR_DEPOSIT', 'WRITE_OFF', 'ROUNDING'], true)) {
        $effect = 'REDUCE_CASH';
      } elseif (in_array($type, ['CREATE_VENDOR_DEPOSIT', 'BANK_CHARGE'], true)) {
        $effect = 'ADD_CASH';
      } else {
        if (!in_array($effect, ['REDUCE_CASH', 'ADD_CASH', 'NONE'], true)) {
          $effect = 'NONE';
        }
      }

      if ($type === 'CREATE_VENDOR_DEPOSIT' || $type === 'USE_VENDOR_DEPOSIT') {
        if ($vendor_deposit_account_id <= 0) {
          $errs["adjustments.$i.account_id"] = 'Missing Vendor Deposit account (' . $this->_coa_code('VENDOR_DEPOSIT') . ')';
          continue;
        }

        $account_id = $vendor_deposit_account_id;
      }

      if ($type === 'USE_VENDOR_DEPOSIT' && $deposit_id <= 0) {
        $errs["adjustments.$i.deposit_id"] = 'deposit_id required for USE_VENDOR_DEPOSIT';
        continue;
      }

      if ($account_id <= 0) {
        $errs["adjustments.$i.account_id"] = 'required';
        continue;
      }

      $this->erp->select('account_id,is_postable,is_active')
        ->from('accounts')
        ->where('company_id', $this->company_id)
        ->where('account_id', $account_id);
      $this->apply_region_filter('region_id');
      $adjAcc = $this->erp->get()->row_array();

      if (!$adjAcc || (int)$adjAcc['is_postable'] !== 1 || (int)$adjAcc['is_active'] !== 1) {
        $errs["adjustments.$i.account_id"] = 'account not active/postable';
        continue;
      }

      $baseAmount = ($currency === 'USD') ? round($amount * $paymentKurs, 2) : $amount;

      if ($effect === 'REDUCE_CASH') {
        $total_reduce_cash += $amount;
        $total_reduce_cash_base += $baseAmount;
      }
      if ($effect === 'ADD_CASH') {
        $total_add_cash += $amount;
        $total_add_cash_base += $baseAmount;
      }

      $cleanAdjustments[] = [
        'type' => $type,
        'effect' => $effect,
        'account_id' => $account_id,
        'amount' => $amount,
        'base_amount' => $baseAmount,
        'memo' => $memo,
        'deposit_id' => $deposit_id,
      ];
    }

    if ($errs) {
      return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
    }

    $cash_paid = round($total_allocated - $total_reduce_cash + $total_add_cash, 2);
    $cash_paid_base = ($currency === 'USD') ? round($cash_paid * $paymentKurs, 2) : $cash_paid;

    if ($cash_paid < -0.01) {
      return [
        'ok'=>false,
        'http'=>422,
        'message'=>'Validation error',
        'errors'=>['cash_paid'=>'cash paid cannot be negative']
      ];
    }

    // AP settlement foreign exchange:
    // AP debit uses bill posting kurs, while cash/deposit/tax side uses payment kurs.
    // Positive = loss (payment side larger); negative = gain.
    $fxDiff = round(($cash_paid_base + $total_reduce_cash_base) - ($ap_base_total + $total_add_cash_base), 2);
    $forexGainAccountId = 0;
    $forexLossAccountId = 0;

    if ($currency === 'USD' && abs($fxDiff) >= 0.01) {
      $forexGainAccountId = (int)($p['forex_gain_account_id'] ?? 0);
      $forexLossAccountId = (int)($p['forex_loss_account_id'] ?? 0);

      if ($forexGainAccountId <= 0) $forexGainAccountId = $this->_coa_id('FX_GAIN_REALIZED', $region_id);
      if ($forexLossAccountId <= 0) $forexLossAccountId = $this->_coa_id('FX_LOSS_REALIZED', $region_id);

      if ($forexGainAccountId <= 0 && $fxDiff < -0.01) {
        return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>['forex_gain_account_id'=>'Missing Forex Gain account (' . $this->_coa_code('FX_GAIN_REALIZED') . ') or send forex_gain_account_id']];
      }
      if ($forexLossAccountId <= 0 && $fxDiff > 0.01) {
        return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>['forex_loss_account_id'=>'Missing Forex Loss account (' . $this->_coa_code('FX_LOSS_REALIZED') . ') or send forex_loss_account_id']];
      }
    }

    $this->erp->trans_begin();

    $pay_no = $this->_next_series('VPAY', (int)date('Y', strtotime($posting_date)));

    $payIns = [
      'company_id' => $this->company_id,
      'region_id' => $region_id,
      'pay_no' => $pay_no,
      'posting_date' => $posting_date,
      'cash_account_id' => $cash_account_id,
      'total_paid' => $cash_paid,
      'remark' => $remark ?: null,
      'status' => 'SUBMITTED',
      'created_by' => $this->auth_username(),
      'created_at' => $now,
    ];

    if ($this->erp->field_exists('currency', 'ap_payments')) $payIns['currency'] = $currency;
    if ($this->erp->field_exists('payment_kurs', 'ap_payments')) $payIns['payment_kurs'] = $paymentKurs;
    if ($this->erp->field_exists('base_total_paid', 'ap_payments')) $payIns['base_total_paid'] = $cash_paid_base;
    if ($this->erp->field_exists('updated_at', 'ap_payments')) $payIns['updated_at'] = $now;

    $this->erp->insert('ap_payments', $payIns);
    $pay_id = (int)$this->erp->insert_id();

    foreach ($rows as $r) {
      $ins = [
        'pay_id' => $pay_id,
        'bill_id' => $r['bill_id'],
        'bill_no' => $r['bill_no'],
        'allocated_amount' => $r['amount'],
      ];

      if ($hasBillItemRef) $ins['bill_item_id'] = $r['bill_item_id'];
      if ($this->erp->field_exists('currency', 'ap_payment_refs')) $ins['currency'] = $r['currency'];
      if ($this->erp->field_exists('posting_kurs', 'ap_payment_refs')) $ins['posting_kurs'] = $paymentKurs;
      if ($this->erp->field_exists('bill_posting_kurs', 'ap_payment_refs')) $ins['bill_posting_kurs'] = $r['bill_posting_kurs'];
      if ($this->erp->field_exists('base_allocated_amount', 'ap_payment_refs')) $ins['base_allocated_amount'] = $r['ap_base_amount'];
      if ($this->erp->field_exists('ap_base_amount', 'ap_payment_refs')) $ins['ap_base_amount'] = $r['ap_base_amount'];
      if ($this->erp->field_exists('cash_base_amount', 'ap_payment_refs')) $ins['cash_base_amount'] = $r['cash_base_amount'];
      if ($this->erp->field_exists('forex_gain_loss', 'ap_payment_refs')) $ins['forex_gain_loss'] = $r['forex_gain_loss'];

      $this->erp->insert('ap_payment_refs', $ins);
    }

    $refs = $this->_get_payment_refs($pay_id);

    if ($this->_gl_voucher_posted('VPAY', $pay_no)) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>409,'message'=>'GL_ALREADY_POSTED','data'=>[
        'voucher_type'=>'VPAY',
        'voucher_no'=>$pay_no
      ]];
    }

    // Dr AP at original bill posting kurs / base amount.
    $this->_insert_gl_entry([
      'posting_date' => $posting_date,
      'account_id' => $ap_account_id,
      'debit' => round($ap_base_total, 0),
      'credit' => 0,
      'voucher_type' => 'VPAY',
      'voucher_no' => $pay_no,
      'region_id' => $region_id,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $createdDeposits = [];
    $usedDeposits = [];

    foreach ($cleanAdjustments as $a) {
      $type = $a['type'];
      $effect = $a['effect'];
      $amount = round((float)$a['amount'], 2);
      $baseAmount = round((float)$a['base_amount'], 2);
      $account_id = (int)$a['account_id'];

      if ($effect === 'REDUCE_CASH') {
        $this->_insert_gl_entry([
          'posting_date' => $posting_date,
          'account_id' => $account_id,
          'debit' => 0,
          'credit' => round($baseAmount, 0),
          'voucher_type' => 'VPAY',
          'voucher_no' => $pay_no,
          'region_id' => $region_id,
          'created_at' => $now,
          'updated_at' => $now,
        ]);

        if ($type === 'USE_VENDOR_DEPOSIT') {
          $firstRow = $rows[0];
          $resUse = $this->mvd->use_deposit([
            'deposit_id' => (int)$a['deposit_id'],
            'pay_id' => $pay_id,
            'pay_no' => $pay_no,
            'bill_id' => (int)$firstRow['bill_id'],
            'bill_no' => $firstRow['bill_no'],
            'posting_date' => $posting_date,
            'amount' => $amount,
            'base_amount' => $baseAmount,
            'currency' => $currency,
            'kurs' => $paymentKurs,
            'remark' => $a['memo'],
          ]);

          if (!$resUse['ok']) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>422,'message'=>$resUse['message'],'errors'=>$resUse];
          }

          $usedDeposits[] = $resUse['data'];
        }
      }

      if ($effect === 'ADD_CASH') {
        $this->_insert_gl_entry([
          'posting_date' => $posting_date,
          'account_id' => $account_id,
          'debit' => round($baseAmount, 0),
          'credit' => 0,
          'voucher_type' => 'VPAY',
          'voucher_no' => $pay_no,
          'region_id' => $region_id,
          'created_at' => $now,
          'updated_at' => $now,
        ]);

        if ($type === 'CREATE_VENDOR_DEPOSIT') {
          $firstRow = $rows[0];
          $resDep = $this->mvd->create_from_payment([
            'vendor_type' => $main_vendor_type,
            'vendor_code' => $main_vendor_code,
            'vendor_name' => $main_vendor_name,
            'posting_date' => $posting_date,
            'currency' => $currency,
            'kurs' => $paymentKurs,
            'amount' => $amount,
            'base_amount' => $baseAmount,
            'source_type' => 'APPAY',
            'source_pay_id' => $pay_id,
            'source_pay_no' => $pay_no,
            'source_bill_id' => (int)$firstRow['bill_id'],
            'source_bill_no' => $firstRow['bill_no'],
            'remark' => $a['memo'],
          ]);

          if (!$resDep['ok']) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>422,'message'=>$resDep['message'],'errors'=>$resDep];
          }

          $createdDeposits[] = $resDep['data'];
        }
      }
    }

    // Forex loss/gain is posted after adjustments so the voucher balances.
    if ($currency === 'USD' && $fxDiff > 0.01) {
      $this->_insert_gl_entry([
        'posting_date' => $posting_date,
        'account_id' => $forexLossAccountId,
        'debit' => round(abs($fxDiff), 0),
        'credit' => 0,
        'voucher_type' => 'VPAY',
        'voucher_no' => $pay_no,
        'region_id' => $region_id,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    } elseif ($currency === 'USD' && $fxDiff < -0.01) {
      $this->_insert_gl_entry([
        'posting_date' => $posting_date,
        'account_id' => $forexGainAccountId,
        'debit' => 0,
        'credit' => round(abs($fxDiff), 0),
        'voucher_type' => 'VPAY',
        'voucher_no' => $pay_no,
        'region_id' => $region_id,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    }

    if ($cash_paid_base > 0.01) {
      $this->_insert_gl_entry([
        'posting_date' => $posting_date,
        'account_id' => $cash_account_id,
        'debit' => 0,
        'credit' => round($cash_paid_base, 0),
        'voucher_type' => 'VPAY',
        'voucher_no' => $pay_no,
        'region_id' => $region_id,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    }

    $affectedBillIds = [];
    foreach ($refs as $r) $affectedBillIds[(int)$r['bill_id']] = true;
    foreach (array_keys($affectedBillIds) as $bill_id) {
      $this->_recalc_bill_payment_status($bill_id);
    }

    $payload = [];
    foreach (array_keys($affectedBillIds) as $bill_id) {
      $payload = array_merge($payload, $this->_build_status_payable_payload_by_bill($bill_id, $posting_date, $pay_no));
    }

    $hns = $this->_post_status_payable_hns($payload);
    if (!$hns['ok']) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>502,'message'=>'XLT_UPDATE_FAILED','data'=>['hns'=>$hns]];
    }

    if ($this->erp->trans_status() === false) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error'];
    }

    $this->erp->trans_commit();

    return ['ok'=>true,'message'=>'Vendor payment posted','data'=>[
      'pay_id' => $pay_id,
      'pay_no' => $pay_no,
      'posting_date' => $posting_date,
      'currency' => $currency,
      'payment_kurs' => $paymentKurs,
      'total_paid' => $cash_paid,
      'base_total_paid' => $cash_paid_base,
      'total_allocated' => $total_allocated,
      'ap_base_total' => $ap_base_total,
      'cash_settlement_base' => $cash_settlement_base,
      'total_reduce_cash' => $total_reduce_cash,
      'total_add_cash' => $total_add_cash,
      'forex_gain_loss' => $fxDiff,
      'created_deposits' => $createdDeposits,
      'used_deposits' => $usedDeposits,
      'hns' => $hns,
    ]];
  }

  public function cancel($pay_id)
  {
    $pay_id = (int)$pay_id;
    $hdr = $this->erp->get_where('ap_payments', [
      'company_id' => $this->company_id,
      'region_id' => (string)$this->auth_region(),
      'pay_id' => $pay_id,
    ])->row_array();

    if (!$hdr) return ['ok'=>false,'http'=>404,'message'=>'Payment not found'];
    if (strtoupper((string)$hdr['status']) === 'CANCELLED') {
      return ['ok'=>true,'message'=>'Payment already cancelled','data'=>[
        'pay_id' => $pay_id,
        'pay_no' => $hdr['pay_no'] ?? null,
        'already_cancelled' => true,
      ]];
    }

    if ($this->_is_locked($hdr['posting_date'])) {
      return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
    }

    $refs = $this->_get_payment_refs($pay_id);
    if (!$refs) return ['ok'=>false,'http'=>409,'message'=>'Payment has no refs'];

    $pay_no = $hdr['pay_no'];
    $lock = $this->_get_reconciled_lock('VPAY', $pay_no);
    if ($lock) {
      return ['ok'=>false,'http'=>409,'message'=>'CANNOT_CANCEL_RECONCILED','data'=>['lock'=>$lock]];
    }

    $affectedBillIds = [];
    foreach ($refs as $r) $affectedBillIds[(int)$r['bill_id']] = true;

    $this->erp->trans_begin();

    $resUse = $this->mvd->cancel_uses_by_payment($pay_id, $pay_no);
    if (!$resUse['ok']) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>409,'message'=>$resUse['message'],'data'=>$resUse];
    }

    $resCreated = $this->mvd->cancel_created_by_payment($pay_id, $pay_no);
    if (!$resCreated['ok']) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>409,'message'=>$resCreated['message'],'data'=>$resCreated];
}

    $this->erp->where([
      'company_id' => $this->company_id,
      'voucher_type' => 'VPAY',
      'voucher_no' => $pay_no,
    ])->update('gl_entries', ['status'=>'CANCELLED']);

    // Mark cancelled first, then recalc from active payments only.
    // This is the key fix so the same bill/item becomes payable again.
    $payUpd = [
      'status' => 'CANCELLED',
      'cancelled_at' => date('Y-m-d H:i:s'),
      'cancelled_by' => $this->auth_username(),
    ];
    if ($this->erp->field_exists('updated_at', 'ap_payments')) {
      $payUpd['updated_at'] = date('Y-m-d H:i:s');
    }

    $this->erp->where(['company_id'=>$this->company_id,'region_id'=>$this->auth_region(),'pay_id'=>$pay_id])->update('ap_payments', $payUpd);

    foreach (array_keys($affectedBillIds) as $bill_id) {
      $this->_recalc_bill_payment_status($bill_id);
    }

    if ($this->erp->trans_status() === false) {
      $this->erp->trans_rollback();
      return ['ok'=>false,'http'=>500,'message'=>'DB error'];
    }

    $this->erp->trans_commit();

    // HNS update is intentionally done after local commit.
    // If HNS is down/timeout, local AP status stays correct and user can submit payment again.
    // The response still reports HNS failure so it can be retried/checked.
    $payload = [];
    foreach (array_keys($affectedBillIds) as $bill_id) {
      $payload = array_merge($payload, $this->_build_status_payable_payload_by_bill($bill_id, date('Y-m-d'), $pay_no));
    }
    $hns = $this->_post_status_payable_hns($payload);

    return ['ok'=>true,'message'=>'Payment cancelled','data'=>[
      'pay_id' => $pay_id,
      'pay_no' => $pay_no,
      'hns' => $hns,
      'hns_ok' => !empty($hns['ok']),
    ]];
  }

  // ===== helpers =====

  private function _active_payment_statuses()
  {
    // Existing AP payment uses SUBMITTED. POSTED is included for backward compatibility
    // if any older data/API writes payment status as POSTED. CANCELLED must never be counted.
    return ['SUBMITTED', 'POSTED'];
  }

  private function _get_payment_refs($pay_id)
  {
    $select = 'ref_id,pay_id,bill_id,bill_no,allocated_amount';
    if ($this->erp->field_exists('bill_item_id', 'ap_payment_refs')) $select .= ',bill_item_id';
    if ($this->erp->field_exists('currency', 'ap_payment_refs')) $select .= ',currency';
    if ($this->erp->field_exists('posting_kurs', 'ap_payment_refs')) $select .= ',posting_kurs';
    if ($this->erp->field_exists('bill_posting_kurs', 'ap_payment_refs')) $select .= ',bill_posting_kurs';
    if ($this->erp->field_exists('base_allocated_amount', 'ap_payment_refs')) $select .= ',base_allocated_amount';
    if ($this->erp->field_exists('ap_base_amount', 'ap_payment_refs')) $select .= ',ap_base_amount';
    if ($this->erp->field_exists('cash_base_amount', 'ap_payment_refs')) $select .= ',cash_base_amount';
    if ($this->erp->field_exists('forex_gain_loss', 'ap_payment_refs')) $select .= ',forex_gain_loss';

    return $this->erp->select($select, false)
      ->from('ap_payment_refs')
      ->where('pay_id', (int)$pay_id)
      ->order_by('ref_id', 'ASC')
      ->get()->result_array();
  }

  private function _sum_paid_bill($bill_id)
  {
    $row = $this->erp->select('COALESCE(SUM(r.allocated_amount),0) AS paid_total', false)
      ->from('ap_payment_refs r')
      ->join('ap_payments p', 'p.pay_id = r.pay_id AND p.company_id = '.$this->erp->escape($this->company_id).' AND p.region_id = '.$this->erp->escape((string)$this->auth_region()), 'inner')
      ->where('r.bill_id', (int)$bill_id)
      ->where_in('p.status', $this->_active_payment_statuses())
      ->get()->row_array();

    return (float)($row['paid_total'] ?? 0);
  }

  private function _sum_paid_item($bill_item_id)
  {
    if (!$this->erp->field_exists('bill_item_id', 'ap_payment_refs')) return 0.0;

    $row = $this->erp->select('COALESCE(SUM(r.allocated_amount),0) AS paid_total', false)
      ->from('ap_payment_refs r')
      ->join('ap_payments p', 'p.pay_id = r.pay_id AND p.company_id = '.$this->erp->escape($this->company_id).' AND p.region_id = '.$this->erp->escape((string)$this->auth_region()), 'inner')
      ->where('r.bill_item_id', (int)$bill_item_id)
      ->where_in('p.status', $this->_active_payment_statuses())
      ->get()->row_array();

    return (float)($row['paid_total'] ?? 0);
  }

  private function _recalc_bill_payment_status($bill_id)
  {
    $bill_id = (int)$bill_id;
    $bill = $this->erp->select('bill_id,total,status')
      ->get_where('ap_bills', [
        'company_id'=>$this->company_id,
        'region_id'=>(string)$this->auth_region(),
        'bill_id'=>$bill_id
      ])
      ->row_array();
    if (!$bill) return;

    $hasItemPaid = $this->erp->field_exists('paid_total', 'ap_bill_items');
    $hasItemStatus = $this->erp->field_exists('status', 'ap_bill_items');
    $hasItemUpdatedAt = $this->erp->field_exists('updated_at', 'ap_bill_items');
    $hasBillItemRef = $this->erp->field_exists('bill_item_id', 'ap_payment_refs');

    $items = $this->erp->where('bill_id', $bill_id)
      ->order_by('line_no', 'ASC')
      ->get('ap_bill_items')->result_array();

    if ($items && $hasBillItemRef) {
      foreach ($items as $item) {
        $item_id = (int)$item['item_id'];
        $item_total = (float)$item['amount'];
        $paid_total = $this->_sum_paid_item($item_id);
        $status = $this->_pay_status_from_amount($item_total, $paid_total);

        $upd = [];
        if ($hasItemPaid) $upd['paid_total'] = $paid_total;
        if ($hasItemStatus) $upd['status'] = $status;
        if ($hasItemUpdatedAt) $upd['updated_at'] = date('Y-m-d H:i:s');
        if ($upd) $this->erp->where('item_id', $item_id)->update('ap_bill_items', $upd);
      }
    }

    // Header always recalculated from active refs, not incremental rollback.
    $headerPaid = $this->_sum_paid_bill($bill_id);
    $headerStatus = $this->_header_status_from_items_or_amount($bill_id, (float)$bill['total'], $headerPaid);

    $this->_update_bill_header_payment_fields($bill_id, $headerPaid, $headerStatus);
  }

  private function _header_status_from_items_or_amount($bill_id, $billTotal, $paidTotal)
  {
    $hasItemStatus = $this->erp->field_exists('status', 'ap_bill_items');
    if ($hasItemStatus) {
      $row = $this->erp->select("COUNT(*) AS total_items, SUM(CASE WHEN status='PAID' THEN 1 ELSE 0 END) AS paid_items, SUM(CASE WHEN status='PARTIAL' THEN 1 ELSE 0 END) AS partial_items", false)
        ->from('ap_bill_items')
        ->where('bill_id', (int)$bill_id)
        ->get()->row_array();

      $totalItems = (int)($row['total_items'] ?? 0);
      $paidItems = (int)($row['paid_items'] ?? 0);
      $partialItems = (int)($row['partial_items'] ?? 0);

      if ($totalItems > 0) {
        if ($paidItems === 0 && $partialItems === 0) return 'UNPAID';
        if ($paidItems === $totalItems) return 'PAID';
        return 'PARTIAL';
      }
    }

    return $this->_pay_status_from_amount($billTotal, $paidTotal);
  }

  private function _pay_status_from_amount($total, $paid)
  {
    $total = (float)$total;
    $paid = (float)$paid;
    $tol = 0.01;

    if ($paid <= $tol) return 'UNPAID';
    if (($paid + $tol) >= $total && $total > $tol) return 'PAID';
    return 'PARTIAL';
  }

  private function _update_bill_header_payment_fields($bill_id, $paidTotal, $payStatus)
  {
    $bill_id = (int)$bill_id;
    $payStatus = strtoupper((string)$payStatus);

    $upd = ['paid_total' => (float)$paidTotal];

    if ($this->erp->field_exists('pay_status', 'ap_bills')) {
      $upd['pay_status'] = $payStatus;
    }
    if ($this->erp->field_exists('paid_at', 'ap_bills')) {
      $upd['paid_at'] = ($payStatus === 'PAID') ? date('Y-m-d H:i:s') : null;
    }
    if ($this->erp->field_exists('updated_at', 'ap_bills')) {
      $upd['updated_at'] = date('Y-m-d H:i:s');
    }

    // Jika ap_bills.status memang dipakai sebagai payment status, update juga.
    // Jangan timpa bill yang CANCELLED.
    if ($this->erp->field_exists('status', 'ap_bills')) {
      $cur = $this->erp->select('status')->get_where('ap_bills', [
        'company_id'=>$this->company_id,
        'region_id'=>(string)$this->auth_region(),
        'bill_id'=>$bill_id,
      ])->row_array();
      $currentStatus = strtoupper((string)($cur['status'] ?? ''));
      if ($currentStatus !== 'CANCELLED') $upd['status'] = $payStatus;
    }

    $this->erp->where([
      'company_id'=>$this->company_id,
      'region_id'=>(string)$this->auth_region(),
      'bill_id'=>$bill_id
    ])->update('ap_bills', $upd);
  }


  private function _coa_config()
  {
    $this->config->load('finance_coa', false, true);
    $cfg = $this->config->item('finance_coa');
    return is_array($cfg) ? $cfg : [];
  }

  private function _coa_code($key)
  {
    $cfg = $this->_coa_config();
    $key = strtoupper(trim((string)$key));
    return trim((string)($cfg['accounts'][$key] ?? ''));
  }

  private function _coa_id($key, $region_id = null)
  {
    $code = $this->_coa_code($key);
    $region_id = trim((string)($region_id !== null ? $region_id : (string)$this->auth_region()));

    if ($code === '' || $region_id === '') {
      return 0;
    }

    $row = $this->erp
      ->select('account_id')
      ->from('accounts')
      ->where('company_id', $this->company_id)
      ->where('region_id', $region_id)
      ->where('account_code', $code)
      ->where('is_active', 1)
      ->where('is_postable', 1)
      ->limit(1)
      ->get()
      ->row_array();

    return $row ? (int)$row['account_id'] : 0;
  }

private function _get_account($account_id)
{
  $this->erp->select('account_id,account_code,account_name,root_type,is_postable,is_active')
    ->from('accounts')
    ->where('company_id', $this->company_id)
    ->where('account_id', (int)$account_id);

  $this->apply_region_filter('region_id');

  return $this->erp->get()->row_array();
}

private function _money($n)
{
  return round((float)$n, 2);
}

  private function _insert_gl_entry(array $row)
  {
    $now = date('Y-m-d H:i:s');
    $ins = [
      'company_id' => $this->company_id,
      'region_id' => (string)($row['region_id'] ?? $this->auth_region()),
      'posting_date' => $row['posting_date'],
      'job_no' => $row['job_no'] ?? null,
      'account_id' => (int)$row['account_id'],
      'party_id' => $row['party_id'] ?? null,
      'debit' => (float)($row['debit'] ?? 0),
      'credit' => (float)($row['credit'] ?? 0),
      'voucher_type' => strtoupper((string)$row['voucher_type']),
      'voucher_no' => (string)$row['voucher_no'],
      'status' => $row['status'] ?? 'POSTED',
      'created_at' => $row['created_at'] ?? $now,
    ];

    if ($this->erp->field_exists('updated_at', 'gl_entries')) {
      $ins['updated_at'] = $row['updated_at'] ?? $now;
    }
    if ($this->erp->field_exists('source_table', 'gl_entries') && isset($row['source_table'])) {
      $ins['source_table'] = $row['source_table'];
    }
    if ($this->erp->field_exists('source_id', 'gl_entries') && isset($row['source_id'])) {
      $ins['source_id'] = $row['source_id'];
    }
    if ($this->erp->field_exists('source_ref', 'gl_entries') && isset($row['source_ref'])) {
      $ins['source_ref'] = $row['source_ref'];
    }

    $this->erp->insert('gl_entries', $ins);
  }

  private function _post_gl_vpay($posting_date, $pay_no, $ap_account_id, $cash_account_id, $amount, $refs)
  {
    foreach ($refs as $r) {
      $bill_id = (int)($r['bill_id'] ?? 0);
      $amt = (float)($r['allocated_amount'] ?? 0);
      if ($bill_id <= 0 || $amt <= 0) continue;

      $bill = $this->erp->select('source_key')
        ->get_where('ap_bills', [
          'company_id'=>$this->company_id,
          'region_id'=>(string)$this->auth_region(),
          'bill_id'=>$bill_id
        ])
        ->row_array();
      $job_no = trim((string)($bill['source_key'] ?? ''));

      $this->erp->insert('gl_entries', [
        'company_id' => $this->company_id,
        'region_id' => (string)$this->auth_region(),
        'posting_date' => $posting_date,
        'job_no' => $job_no ?: null,
        'account_id' => $ap_account_id,
        'party_id' => null,
        'debit' => $amt,
        'credit' => 0,
        'voucher_type' => 'VPAY',
        'voucher_no' => $pay_no,
        'status' => 'POSTED',
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    $this->erp->insert('gl_entries', [
      'company_id' => $this->company_id,
      'region_id' => (string)$this->auth_region(),
      'posting_date' => $posting_date,
      'job_no' => null,
      'account_id' => $cash_account_id,
      'party_id' => null,
      'debit' => 0,
      'credit' => $amount,
      'voucher_type' => 'VPAY',
      'voucher_no' => $pay_no,
      'status' => 'POSTED',
      'created_at' => date('Y-m-d H:i:s'),
    ]);
  }

  private function _is_locked($posting_date)
  {
    $row = $this->erp->select('MAX(lock_date) mx', false)
      ->get_where('accounting_locks', ['company_id'=>$this->company_id])->row_array();
    $mx = $row['mx'] ?? null;
    return ($mx && $posting_date <= $mx);
  }

  private function _next_series($series_key, $year)
  {
    $row = $this->erp->get_where('number_series', [
      'series_key'=>$series_key,
      'company_id'=>$this->company_id,
      'year'=>$year,
    ])->row_array();

    if (!$row) {
      $prefix = $series_key.'-'.$year.'-';
      $this->erp->insert('number_series', [
        'series_key'=>$series_key,
        'company_id'=>$this->company_id,
        'year'=>$year,
        'prefix'=>$prefix,
        'counter'=>0,
      ]);
      $row = ['prefix'=>$prefix,'counter'=>0];
    }

    $new_counter = (int)$row['counter'] + 1;
    $this->erp->where(['series_key'=>$series_key,'company_id'=>$this->company_id,'year'=>$year])
      ->update('number_series', ['counter'=>$new_counter]);

    return $row['prefix'] . str_pad((string)$new_counter, 5, '0', STR_PAD_LEFT);
  }

  private function _bill_pay_status($bill_id)
  {
    $paid = $this->_sum_paid_bill((int)$bill_id);
    $bill = $this->erp->select('total')->get_where('ap_bills', [
      'company_id'=>$this->company_id,
      'region_id'=>(string)$this->auth_region(),
      'bill_id'=>(int)$bill_id,
    ])->row_array();
    if (!$bill) return 'unpaid';

    return ($this->_pay_status_from_amount((float)$bill['total'], $paid) === 'PAID') ? 'paid' : 'unpaid';
  }

  // ===== HNS status_payable helpers =====

  private function _parse_service_from_memo($memo)
  {
    $memo = trim((string)$memo);
    $code = '';

    if (preg_match('/\(([A-Z0-9\-\_]+)\)/', $memo, $m)) {
      $code = strtoupper($m[1]);
      $memo = trim(str_replace($m[0], '', $memo));
    }

    $memo = preg_replace('/\bqty\s+[0-9\.\,]+\b/i', '', $memo);
    $memo = preg_replace('/\(\s*USD[^\)]*\)/i', '', $memo);
    $memo = trim(preg_replace('/\s+/', ' ', $memo));

    return [$code, $memo];
  }

  private function _bill_reference_number($billRow, $itemRow=null)
  {
    if ($itemRow && !empty($itemRow['job_id'])) return (string)$itemRow['job_id'];
    if (!empty($billRow['source_key'])) return (string)$billRow['source_key'];
    if (!empty($billRow['reference_job_no'])) return (string)$billRow['reference_job_no'];
    return (string)$billRow['bill_no'];
  }

  /**
   * Build HNS payload per item.
   * Ini yang memperbaiki bug: 1 item dibayar tidak membuat semua item jadi partial/paid.
   */
  private function _build_status_payable_payload_by_bill($bill_id, $tanggal_bayar, $journal_no)
  {
    $bill = $this->erp->select('bill_id,bill_no,source_key,reference_job_no,source_type')
      ->get_where('ap_bills', [
        'company_id'=>$this->company_id,
        'region_id'=>(string)$this->auth_region(),
        'bill_id'=>(int)$bill_id
      ])
      ->row_array();
    if (!$bill) return [];

    $items = $this->erp->order_by('line_no', 'ASC')
      ->get_where('ap_bill_items', ['bill_id'=>(int)$bill_id])
      ->result_array();

    $hasJob = !empty($bill['source_key']);
    if (!$hasJob) {
      foreach ($items as $it) {
        if (!empty($it['job_id'])) { $hasJob = true; break; }
      }
    }
    if (!$hasJob) return [];

    $out = [];
    foreach ($items as $it) {
      [$code, $name] = $this->_parse_service_from_memo($it['memo'] ?? '');

      $itemStatus = strtoupper((string)($it['status'] ?? 'UNPAID'));
      if ($this->erp->field_exists('bill_item_id', 'ap_payment_refs')) {
        $paid = $this->_sum_paid_item((int)$it['item_id']);
        $itemStatus = $this->_pay_status_from_amount((float)$it['amount'], $paid);
      }

      $hnsStatus = ($itemStatus === 'PAID') ? 'paid' : 'unpaid';

      $out[] = [
        'reference_number' => $this->_bill_reference_number($bill, $it),
        'code_service'     => $code ?: 'UNKNOWN',
        'item_service'     => $name ?: trim((string)($it['memo'] ?? '')),
        'status'           => $hnsStatus,
        'tanggal_bayar'    => $tanggal_bayar,
        'journal_no'       => $journal_no,
      ];
    }

    return $out;
  }

  private function _post_status_payable_hns($payloadArr)
  {
    if (!$payloadArr || !is_array($payloadArr)) {
      return ['ok'=>true,'skipped'=>true,'reason'=>'empty_payload'];
    }

    $url = 'http://localhost/api/status_payable';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: gateway-fms',
      ],
      CURLOPT_POSTFIELDS => json_encode($payloadArr),
      CURLOPT_CONNECTTIMEOUT => 7,
      CURLOPT_TIMEOUT => 25,
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $ok = (($err === '' || $err === null) && ($code >= 200 && $code < 300));

    return [
      'ok' => $ok,
      'http_code' => $code,
      'error' => $err ?: null,
      'response' => $resp ?: null,
      'sent_count' => count($payloadArr),
      'payload_sample' => array_slice($payloadArr, 0, 3),
    ];
  }

  private function _gl_voucher_posted($voucher_type, $voucher_no)
  {
    return (bool)$this->erp->select('gl_id')
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('region_id', (string)$this->auth_region())
      ->where('voucher_type', $voucher_type)
      ->where('voucher_no', $voucher_no)
      ->where('status', 'POSTED')
      ->limit(1)->get()->row_array();
  }

  private function _is_reconciled_voucher($voucher_type, $voucher_no)
  {
    $row = $this->erp->select('l.line_id', false)
      ->from('bank_statement_lines l')
      ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
      ->where('s.company_id', $this->company_id)
      ->where('s.status', 'RECONCILED')
      ->where_in('l.match_status', ['MATCHED','RECONCILED'])
      ->where('l.matched_voucher_type', strtoupper($voucher_type))
      ->where('l.matched_voucher_no', $voucher_no)
      ->limit(1)->get()->row_array();

    return $row ? true : false;
  }

  private function _get_reconciled_lock($voucher_type, $voucher_no)
  {
    $voucher_type = strtoupper(trim((string)$voucher_type));
    $voucher_no = trim((string)$voucher_no);
    if (!$voucher_type || !$voucher_no) return null;

    return $this->erp->select('s.stmt_id,s.period_month,s.statement_date,l.line_id', false)
      ->from('bank_statement_lines l')
      ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
      ->where('s.company_id', $this->company_id)
      ->where('s.status', 'RECONCILED')
      ->where_in('l.match_status', ['MATCHED','RECONCILED'])
      ->where('l.matched_voucher_type', $voucher_type)
      ->where('l.matched_voucher_no', $voucher_no)
      ->limit(1)->get()->row_array();
  }

  private function _normalize_payment_refs($p)
  {
    $refs = [];

    if (!empty($p['refs']) && is_array($p['refs'])) {
      foreach ($p['refs'] as $r) {
        $refs[] = [
          'row_key' => $r['row_key'] ?? null,
          'bill_id' => (int)($r['bill_id'] ?? 0),
          'item_id' => isset($r['item_id']) ? (int)$r['item_id'] : null,
          'line_no' => isset($r['line_no']) ? (int)$r['line_no'] : null,
          'allocated_amount' => (float)($r['allocated_amount'] ?? $r['amount'] ?? 0),
          'item_total' => (float)($r['item_total'] ?? 0),
          'memo' => $r['memo'] ?? null,
        ];
      }
    } elseif (!empty($p['allocations']) && is_array($p['allocations'])) {
      foreach ($p['allocations'] as $r) {
        $refs[] = [
          'row_key' => $r['row_key'] ?? null,
          'bill_id' => (int)($r['bill_id'] ?? 0),
          'item_id' => isset($r['item_id']) ? (int)$r['item_id'] : null,
          'line_no' => isset($r['line_no']) ? (int)$r['line_no'] : null,
          'allocated_amount' => (float)($r['allocated_amount'] ?? $r['amount'] ?? 0),
          'item_total' => (float)($r['item_total'] ?? 0),
          'memo' => $r['memo'] ?? null,
        ];
      }
    }

    return array_values(array_filter($refs, function($r) {
      return (int)$r['bill_id'] > 0 && (float)$r['allocated_amount'] > 0;
    }));
  }
}
