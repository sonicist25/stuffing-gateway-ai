<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_receive_payment extends MY_Model
{
    private $company_id = 1;
    private $column_cache = [];

    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // MAIN RECEIVE PAYMENT
    // =========================================================

public function receive($p)
{
    $posting_date       = trim((string)($p['posting_date'] ?? ''));
    $cash_account_id    = (int)($p['cash_account_id'] ?? 0);
    $remark             = trim((string)($p['remark'] ?? ''));
    $alloc              = $p['allocations'] ?? [];
    $adjustments        = $p['adjustments'] ?? [];
    $cash_received      = $this->num($p['cash_received'] ?? 0);
    $deposit_used       = $this->num($p['deposit_used'] ?? 0);
    $payment_kurs       = $this->num($p['payment_kurs'] ?? 0);
    $deposit_account_id = (int)($p['deposit_account_id'] ?? 0);
    $deposit_amount     = $this->num($p['deposit_amount'] ?? 0);

    $this->load->model('api/finance/ar/M_customer_deposit', 'mdep');

    $region_id = $this->active_region();

    $errs = [];

    if ($posting_date === '') {
        $errs['posting_date'] = 'required';
    }

    if ($cash_account_id <= 0) {
        $errs['cash_account_id'] = 'required';
    }

    if (!is_array($alloc) || count($alloc) < 1) {
        $errs['allocations'] = 'required';
    }

    if ($cash_received < 0) {
        $errs['cash_received'] = 'must be >= 0';
    }

    if ($deposit_used < 0) {
        $errs['deposit_used'] = 'must be >= 0';
    }

    if (!is_array($adjustments)) {
        $errs['adjustments'] = 'must be array';
    }

    if ($region_id === '') {
        $errs['region_id'] = 'required';
    }

    if ($errs) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Validation error',
            'errors'  => $errs,
        ];
    }

    if ($this->_is_locked($posting_date)) {
        return [
            'ok'      => false,
            'http'    => 409,
            'message' => 'PERIOD_LOCKED',
        ];
    }

    $cashAcc = $this->get_account_by_id($cash_account_id);

    if (!$cashAcc || (int)$cashAcc['is_postable'] !== 1 || (int)$cashAcc['is_active'] !== 1) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Invalid cash account',
        ];
    }

    // =====================================================
    // BUILD ALLOCATION ROWS FROM erp.ar_invoices
    // =====================================================

    $rows = [];
    $currSet = [];
    $partySet = [];
    $total_alloc_doc = 0.0;

    foreach ($alloc as $ix => $a) {
        /*
         * Backward compatible:
         * frontend lama mungkin masih kirim finance_id,
         * tapi sekarang nilainya harus dianggap sebagai ar_invoice_id.
         */
        $ar_invoice_id = (int)($a['ar_invoice_id'] ?? $a['finance_id'] ?? 0);
        $amt = $this->num($a['amount'] ?? 0);

        if ($ar_invoice_id <= 0) {
            $errs["allocations.$ix.ar_invoice_id"] = 'required';
            continue;
        }

        if ($amt <= 0) {
            $errs["allocations.$ix.amount"] = 'must be > 0';
            continue;
        }

        $inv = $this->get_ar_invoice($ar_invoice_id);

        if (!$inv) {
            $errs["allocations.$ix.ar_invoice_id"] = 'invoice not found';
            continue;
        }

        $status = strtoupper(trim((string)($inv['status'] ?? '')));

        if (in_array($status, ['CANCELLED', 'VOID'], true)) {
            $errs["allocations.$ix.ar_invoice_id"] = 'invoice cancelled';
            continue;
        }

        $invCurrencyRaw = $this->normalize_invoice_currency($inv['currency'] ?? '');
        $invCurrency = $this->currency_for_calc($invCurrencyRaw);

        $invRate = $this->num($inv['exchange_rate'] ?? 1, 1);
        if ($invRate <= 0) {
            $invRate = 1;
        }

        $gtDoc = $this->num($inv['grand_total'] ?? 0);
		$gtIdr = $this->num($inv['grand_total_idr'] ?? 0);

		if ($invCurrency === 'USD') {
			// Untuk USD, semua validasi receipt harus pakai document currency.
			$invoiceTotalDoc = $gtDoc;
		} else {
			// Untuk IDR, boleh fallback ke grand_total_idr kalau ada.
			$invoiceTotalDoc = $gtDoc > 0 ? $gtDoc : $gtIdr;
		}

		$paidDoc = $this->num($inv['paid_total'] ?? 0);

		// Jangan percaya outstanding_total untuk USD,
		// karena bisa saja tersimpan dalam IDR dari patch lama.
		$outDoc = max(0, $invoiceTotalDoc - $paidDoc);

		// Safety: kalau USD dan paid_total/outstanding_total lama ternyata IDR,
		// normalisasi balik ke USD.
		if ($invCurrency === 'USD' && $invRate > 0) {
			if ($paidDoc > $invoiceTotalDoc && $paidDoc > 1000) {
				$paidDoc = round($paidDoc / $invRate, 2);
				$outDoc  = max(0, $invoiceTotalDoc - $paidDoc);
			}
		}

        if ($outDoc <= 0.01) {
            $errs["allocations.$ix.ar_invoice_id"] = 'invoice already paid';
            continue;
        }

        if ($amt - $outDoc > 0.01) {
            $errs["allocations.$ix.amount"] = "amount exceeds outstanding ($outDoc)";
            continue;
        }

        $partyCode = trim((string)($inv['party_code'] ?? ''));
        $partyName = trim((string)($inv['party_name'] ?? ''));

        if ($partyCode === '') {
            $partyCode = $partyName;
        }

        if ($partyName === '') {
            $partyName = $partyCode;
        }

        // Kolom finance_id tetap diisi untuk backward compatibility schema lama,
        // tetapi nilai canonical sekarang adalah ar_invoice_id dari ERP.
        $legacyFinanceId = (int)$inv['ar_invoice_id'];

        $rows[] = [
            'ar_invoice_id' => (int)$inv['ar_invoice_id'],
            'finance_id'    => $legacyFinanceId,

            // Source untuk receipt/ref/GL sekarang selalu ke ERP AR Invoice,
            // jangan lagi membawa source table lama dari operational DB.
            'source_db'     => 'erp',
            'source_table'  => 'ar_invoices',
            'source_id'     => (string)$inv['ar_invoice_id'],

            'invoice_no'    => (string)$inv['invoice_no'],
            'job_no'        => trim((string)($inv['job_no'] ?? '')),

            'party_type'    => trim((string)($inv['party_type'] ?? '')),
            'party_code'    => $partyCode,
            'party_name'    => $partyName,

            'currency'      => $invCurrency,
            'currency_raw'  => $invCurrencyRaw,
            'invoice_kurs'  => $invRate,

            'gt'            => $invoiceTotalDoc,
            'paid'          => $paidDoc,
            'outstanding'   => $outDoc,
            'amount'        => $amt,
        ];

        $currSet[$invCurrency] = true;

        if ($partyCode !== '') {
            $partySet[$partyCode] = true;
        }

        $total_alloc_doc += $amt;
    }

    if ($errs) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Validation error',
            'errors'  => $errs,
        ];
    }

    if (count($rows) < 1) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'No valid allocations',
        ];
    }

    if (count($currSet) > 1) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'MIXED_CURRENCY_NOT_ALLOWED',
            'data'    => [
                'currencies' => array_keys($currSet),
            ],
        ];
    }

    $receiptCurrency = count($currSet) === 1 ? array_keys($currSet)[0] : 'IDR';

    if ($receiptCurrency === 'USD' && $payment_kurs <= 0) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Validation error',
            'errors'  => [
                'payment_kurs' => 'required for USD receipt',
            ],
        ];
    }

    if ($receiptCurrency !== 'USD') {
        $payment_kurs = 1;
    }

    if (count($partySet) > 1) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'MULTI_PARTY_NOT_ALLOWED_FOR_DEPOSIT',
            'data'    => [
                'parties' => array_keys($partySet),
            ],
        ];
    }

    $party_code = trim((string)($rows[0]['party_code'] ?? ''));
    $party_name = trim((string)($rows[0]['party_name'] ?? ''));

    if ($party_code === '') {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'DEPOSIT_PARTY_CODE_EMPTY',
            'data'    => [
                'first_row' => $rows[0],
            ],
        ];
    }

    // =====================================================
    // ADJUSTMENTS
    // =====================================================

    $total_adj_doc = 0.0;
    $adjRows = [];

    foreach ($adjustments as $ix => $a) {
        $adj_account_id = (int)($a['account_id'] ?? 0);
        $adj_amount     = $this->num($a['amount'] ?? 0);
        $adj_remark     = trim((string)($a['remark'] ?? ''));

        if ($adj_account_id <= 0 && $adj_amount > 0) {
            $errs["adjustments.$ix.account_id"] = 'required';
            continue;
        }

        if ($adj_amount < 0) {
            $errs["adjustments.$ix.amount"] = 'must be >= 0';
            continue;
        }

        if ($adj_amount == 0) {
            continue;
        }

        $adjAcc = $this->get_account_by_id($adj_account_id);

        if (!$adjAcc || (int)$adjAcc['is_postable'] !== 1 || (int)$adjAcc['is_active'] !== 1) {
            $errs["adjustments.$ix.account_id"] = 'invalid adjustment account';
            continue;
        }

        $adjRows[] = [
            'account_id' => $adj_account_id,
            'amount'     => $adj_amount,
            'remark'     => $adj_remark,
            'root_type'  => (string)($adjAcc['root_type'] ?? ''),
            'adj_id'     => null,
        ];

        $total_adj_doc += $adj_amount;
    }

    if ($errs) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Validation error',
            'errors'  => $errs,
        ];
    }

    // =====================================================
    // DEPOSIT VALIDATION
    // =====================================================

    if ($deposit_used > 0.01) {
        $depAvail = $this->mdep->available([
            'party_code' => $party_code,
            'currency'   => $receiptCurrency,
        ]);

        if (!$depAvail['ok']) {
            return $depAvail;
        }

        $availableDepositDoc = $this->num($depAvail['data']['available_doc'] ?? 0);

        if ($deposit_used - $availableDepositDoc > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'DEPOSIT_EXCEEDS_AVAILABLE',
                'data'    => [
                    'deposit_used'      => $deposit_used,
                    'available_deposit' => $availableDepositDoc,
                    'party_code'        => $party_code,
                    'currency'          => $receiptCurrency,
                ],
            ];
        }
    }

    $remaining = $total_alloc_doc - ($cash_received + $total_adj_doc + $deposit_used);

    if ($remaining > 0.01) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'RECEIPT_NOT_BALANCED',
            'data'    => [
                'cash_received'     => $cash_received,
                'total_adjustments' => $total_adj_doc,
                'deposit_used'      => $deposit_used,
                'total_allocations' => $total_alloc_doc,
                'gap'               => $remaining,
            ],
        ];
    }

    $overpayment = $remaining < -0.01 ? abs($remaining) : 0.0;

    if ($deposit_used > 0.01 && $overpayment > 0.01) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'OVERPAYMENT_NOT_ALLOWED_WHEN_USING_DEPOSIT',
            'data'    => [
                'gap' => $overpayment,
            ],
        ];
    }

    if ($overpayment > 0.01) {
        if ($deposit_account_id <= 0) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => [
                    'deposit_account_id' => 'required for overpayment',
                ],
            ];
        }

        if (abs($deposit_amount - $overpayment) > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'DEPOSIT_AMOUNT_MISMATCH',
                'data'    => [
                    'expected' => $overpayment,
                    'received' => $deposit_amount,
                ],
            ];
        }

        $depositAcc = $this->get_account_by_id($deposit_account_id);

        if (!$depositAcc || (int)$depositAcc['is_postable'] !== 1 || (int)$depositAcc['is_active'] !== 1) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Invalid deposit account',
            ];
        }
    } else {
        $deposit_amount = 0;
        $deposit_account_id = 0;
    }

    // =====================================================
    // ERP TRANSACTION
    // =====================================================

    $this->erp->trans_begin();

    $rcpt_no = $this->_next_series('RCPT', (int)date('Y', strtotime($posting_date)));

    if ($this->_gl_voucher_posted('RCPT', $rcpt_no)) {
        $this->erp->trans_rollback();

        return [
            'ok'      => false,
            'http'    => 409,
            'message' => 'GL_ALREADY_POSTED',
            'data'    => [
                'voucher_type' => 'RCPT',
                'voucher_no'   => $rcpt_no,
            ],
        ];
    }

    $hdrData = [
        'company_id'       => $this->company_id,
        'region_id'        => $region_id,
        'rcpt_no'          => $rcpt_no,
        'posting_date'     => $posting_date,
        'cash_account_id'  => $cash_account_id,
        'total_paid'       => $total_alloc_doc,
        'cash_received'    => $cash_received,
        'deposit_used'     => $deposit_used,
        'adjustment_total' => $total_adj_doc,
        'currency'         => $receiptCurrency,
        'payment_rate'     => $payment_kurs,
        'kurs'             => $payment_kurs,
        'remark'           => $remark ?: null,
        'status'           => 'SUBMITTED',
        'created_by'       => $this->auth_username(),
        'created_at'       => date('Y-m-d H:i:s'),
    ];

    $this->insert_filtered($this->erp, 'ar_receipts', $hdrData);

    $rcpt_id = (int)$this->erp->insert_id();

    if ($rcpt_id <= 0) {
        $err = $this->erp->error();
        $sql = $this->erp->last_query();
        $this->erp->trans_rollback();

        return [
            'ok'      => false,
            'http'    => 500,
            'message' => 'Failed to create receipt header',
            'data'    => [
                'db_error'   => $err,
                'last_query' => $sql,
            ],
        ];
    }

    $ar_account_id = $this->_coa_id('AR', $region_id);

    if ($ar_account_id <= 0) {
        $this->erp->trans_rollback();

        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Missing/invalid AR account (' . $this->_coa_code('AR') . ') in COA',
        ];
    }

    $fx_gain_account_id = $this->_coa_id('FX_GAIN_REALIZED', $region_id);
    $fx_loss_account_id = $this->_coa_id('FX_LOSS_REALIZED', $region_id);

    // =====================================================
    // RECEIPT ADJUSTMENT DETAILS
    // Simpan detail adjustment ke ar_receipt_adjustments.
    // =====================================================

    foreach ($adjRows as $idx => $a) {
        $adjDoc = (float)$a['amount'];
        $adjBase = ($receiptCurrency === 'USD')
            ? round($adjDoc * $payment_kurs, 2)
            : round($adjDoc, 2);

        $adjData = [
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'rcpt_id'      => $rcpt_id,
            'account_id'   => (int)$a['account_id'],
            'amount'       => round($adjDoc, 2),
            'currency'     => $receiptCurrency,
            'payment_rate' => $payment_kurs,
            'amount_base'  => $adjBase,
            'remark'       => $a['remark'] ?: null,
            'status'       => 'ACTIVE',
            'created_by'   => $this->auth_username(),
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->insert_filtered($this->erp, 'ar_receipt_adjustments', $adjData);

        $adj_id = (int)$this->erp->insert_id();

        if ($adj_id <= 0) {
            $err = $this->erp->error();
            $sql = $this->erp->last_query();
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'Failed to create receipt adjustment detail',
                'data'    => [
                    'db_error'   => $err,
                    'last_query' => $sql,
                    'row'        => $adjData,
                ],
            ];
        }

        $adjRows[$idx]['adj_id'] = $adj_id;
        $adjRows[$idx]['amount_base'] = $adjBase;
    }

    // =====================================================
    // RECEIPT REFS + UPDATE AR INVOICES
    // =====================================================

    foreach ($rows as $r) {
        $allocatedBase = ($receiptCurrency === 'USD')
            ? round((float)$r['amount'] * (float)$payment_kurs, 2)
            : round((float)$r['amount'], 2);

        $refData = [
            'company_id'             => $this->company_id,
            'region_id'              => $region_id,
            'rcpt_id'                => $rcpt_id,
            'finance_id'             => $r['finance_id'],
            'ar_invoice_id'          => $r['ar_invoice_id'],
            'invoice_no'             => $r['invoice_no'],
            'allocated_amount'       => $r['amount'],
            'allocated_amount_base'  => $allocatedBase,
            'currency'               => $receiptCurrency,
            'payment_rate'           => $payment_kurs,
            'kurs'                   => $payment_kurs,
            'source_db'              => $r['source_db'],
            'source_table'           => $r['source_table'],
            'source_id'              => $r['source_id'],
            'created_at'             => date('Y-m-d H:i:s'),
        ];

        $this->insert_filtered($this->erp, 'ar_receipt_refs', $refData);

        if ($this->erp->affected_rows() <= 0) {
            $err = $this->erp->error();
            $sql = $this->erp->last_query();
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'Failed to create receipt allocation',
                'data'    => [
                    'db_error'   => $err,
                    'last_query' => $sql,
                ],
            ];
        }

        $newPaid = (float)$r['paid'] + (float)$r['amount'];
        $newOut  = max(0, (float)$r['gt'] - $newPaid);

        $newStatus = ($newOut <= 0.01)
            ? 'PAID'
            : (($newPaid > 0.01) ? 'PARTIAL' : 'SUBMITTED');

        $updInv = [
            'paid_total'        => $newPaid,
            'outstanding_total' => $newOut,
            'status'            => $newStatus,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        $this->erp->where('ar_invoice_id', (int)$r['ar_invoice_id']);
        $this->update_filtered($this->erp, 'ar_invoices', $updInv);
    }

    // =====================================================
    // GL POSTING
    // =====================================================

    $total_cash_idr         = 0.0;
    $total_ar_idr           = 0.0;
    $total_adj_idr          = 0.0;
    $total_deposit_used_idr = 0.0;
    $total_deposit_idr      = 0.0;

    // Cr AR per invoice allocation
    foreach ($rows as $r) {
        $amtDoc  = (float)$r['amount'];
        $curr    = strtoupper(trim((string)$r['currency']));
        $invRate = (float)$r['invoice_kurs'];

        if ($invRate <= 0) {
            $invRate = 1;
        }

        $arIdr = ($curr === 'USD')
            ? round($amtDoc * $invRate, 2)
            : round($amtDoc, 2);

        $total_ar_idr += $arIdr;

        $this->insert_gl([
            'company_id'    => $this->company_id,
            'region_id'     => $region_id,
            'posting_date'  => $posting_date,
            'job_no'        => $r['job_no'] ?: null,
            'account_id'    => $ar_account_id,
            'party_id'      => null,
            'debit'         => 0,
            'credit'        => $arIdr,
            'voucher_type'  => 'RCPT',
            'voucher_no'    => $rcpt_no,
            'status'        => 'POSTED',
            'source_db'     => $r['source_db'],
            'source_table'  => $r['source_table'],
            'source_id'     => $r['source_id'],
            'source_ref'    => $r['invoice_no'],
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    // Dr adjustment
    foreach ($adjRows as $a) {
        $adjDoc = (float)$a['amount'];
        $adjIdr = ($receiptCurrency === 'USD')
            ? round($adjDoc * $payment_kurs, 2)
            : round($adjDoc, 2);

        $total_adj_idr += $adjIdr;

        $this->insert_gl([
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => (int)$a['account_id'],
            'party_id'     => null,
            'debit'        => $adjIdr,
            'credit'       => 0,
            'voucher_type' => 'RCPT',
            'voucher_no'   => $rcpt_no,
            'status'       => 'POSTED',
            'source_table' => 'ar_receipt_adjustments',
            'source_id'    => (string)($a['adj_id'] ?? ''),
            'source_ref'   => $a['remark'] ?: 'AR Receipt Adjustment',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // Dr Customer Deposit liability if using existing deposit
    if ($deposit_used > 0.01) {
        $depApply = $this->mdep->apply_fifo([
            'party_code'       => $party_code,
            'currency'         => $receiptCurrency,
            'amount_doc'       => $deposit_used,
            'apply_voucher_no' => $rcpt_no,
            'apply_rcpt_id'    => $rcpt_id,
            'posting_date'     => $posting_date,
            'remark'           => $remark ?: null,
            'created_by'       => $this->auth_username(),
        ]);

        if (!$depApply['ok']) {
            $this->erp->trans_rollback();
            return $depApply;
        }

        $deposit_apply_account_id = (int)($depApply['data']['deposit_account_id'] ?? 0);
        $total_deposit_used_idr   = $this->num($depApply['data']['total_used_idr'] ?? 0);

        if ($deposit_apply_account_id <= 0) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'INVALID_DEPOSIT_APPLY_ACCOUNT',
            ];
        }

        $this->insert_gl([
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => $deposit_apply_account_id,
            'party_id'     => null,
            'debit'        => $total_deposit_used_idr,
            'credit'       => 0,
            'voucher_type' => 'RCPT',
            'voucher_no'   => $rcpt_no,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // Dr Bank / Cash
    $total_cash_idr = ($receiptCurrency === 'USD')
        ? round($cash_received * $payment_kurs, 2)
        : round($cash_received, 2);

    if ($total_cash_idr > 0.01) {
        $this->insert_gl([
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => $cash_account_id,
            'party_id'     => null,
            'debit'        => $total_cash_idr,
            'credit'       => 0,
            'voucher_type' => 'RCPT',
            'voucher_no'   => $rcpt_no,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // Cr Customer Deposit liability if overpayment
    if ($deposit_amount > 0.01 && $deposit_account_id > 0) {
        $total_deposit_idr = ($receiptCurrency === 'USD')
            ? round($deposit_amount * $payment_kurs, 2)
            : round($deposit_amount, 2);

        $this->insert_gl([
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => $deposit_account_id,
            'party_id'     => null,
            'debit'        => 0,
            'credit'       => $total_deposit_idr,
            'voucher_type' => 'RCPT',
            'voucher_no'   => $rcpt_no,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $createDep = $this->mdep->create_from_overpayment([
            'party_code'          => $party_code,
            'party_name'          => $party_name,
            'currency'            => $receiptCurrency,
            'source_rate'         => ($receiptCurrency === 'USD' ? $payment_kurs : 1),
            'amount_doc'          => $deposit_amount,
            'amount_idr'          => $total_deposit_idr,
            'source_voucher_type' => 'RCPT',
            'source_voucher_no'   => $rcpt_no,
            'source_rcpt_id'      => $rcpt_id,
            'deposit_account_id'  => $deposit_account_id,
            'posting_date'        => $posting_date,
            'remark'              => $remark ?: null,
            'created_by'          => $this->auth_username(),
        ]);

        if (!$createDep['ok']) {
            $this->erp->trans_rollback();
            return $createDep;
        }
    }

    // FX difference
    $total_fx_diff = round(
        ($total_cash_idr + $total_adj_idr + $total_deposit_used_idr) -
        ($total_ar_idr + $total_deposit_idr),
        2
    );

    if (abs($total_fx_diff) > 0.01) {
        if ($total_fx_diff > 0) {
            if ($fx_gain_account_id <= 0) {
                $this->erp->trans_rollback();

                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Missing FX gain account (' . $this->_coa_code('FX_GAIN_REALIZED') . ')',
                ];
            }

            $this->insert_gl([
                'company_id'   => $this->company_id,
                'region_id'    => $region_id,
                'posting_date' => $posting_date,
                'job_no'       => null,
                'account_id'   => $fx_gain_account_id,
                'party_id'     => null,
                'debit'        => 0,
                'credit'       => abs($total_fx_diff),
                'voucher_type' => 'RCPT',
                'voucher_no'   => $rcpt_no,
                'status'       => 'POSTED',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } else {
            if ($fx_loss_account_id <= 0) {
                $this->erp->trans_rollback();

                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Missing FX loss account (' . $this->_coa_code('FX_LOSS_REALIZED') . ')',
                ];
            }

            $this->insert_gl([
                'company_id'   => $this->company_id,
                'region_id'    => $region_id,
                'posting_date' => $posting_date,
                'job_no'       => null,
                'account_id'   => $fx_loss_account_id,
                'party_id'     => null,
                'debit'        => abs($total_fx_diff),
                'credit'       => 0,
                'voucher_type' => 'RCPT',
                'voucher_no'   => $rcpt_no,
                'status'       => 'POSTED',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // =====================================================
    // GL BALANCE CHECK
    // =====================================================

    $sumDebit = 0.0;
    $sumCredit = 0.0;

    $this->erp->select('debit, credit')
        ->from('gl_entries')
        ->where('company_id', $this->company_id)
        ->where('voucher_type', 'RCPT')
        ->where('voucher_no', $rcpt_no)
        ->where('status', 'POSTED');

    $this->apply_erp_region_filter('gl_entries');

    $glRows = $this->erp->get()->result_array();

    foreach ($glRows as $g) {
        $sumDebit  += (float)$g['debit'];
        $sumCredit += (float)$g['credit'];
    }

    if (abs($sumDebit - $sumCredit) > 0.01) {
        $this->erp->trans_rollback();

        return [
            'ok'      => false,
            'http'    => 500,
            'message' => 'GL_NOT_BALANCED',
            'data'    => [
                'voucher_no' => $rcpt_no,
                'debit'      => $sumDebit,
                'credit'     => $sumCredit,
                'gap'        => round($sumDebit - $sumCredit, 2),
            ],
        ];
    }

    if ($this->erp->trans_status() === false) {
        $err = $this->erp->error();
        $sql = $this->erp->last_query();
        $this->erp->trans_rollback();

        return [
            'ok'      => false,
            'http'    => 500,
            'message' => 'DB error',
            'data'    => [
                'db_error'   => $err,
                'last_query' => $sql,
            ],
        ];
    }
	// =====================================================
	// HNS SYNC BEFORE COMMIT
	// Supaya kalau HNS gagal, payment ikut rollback.
	// =====================================================

	$tglBayar = date('Ymd', strtotime($posting_date));
	$payload = [];

	foreach ($rows as $r) {
		$newPaid = (float)$r['paid'] + (float)$r['amount'];
		$newOut  = max(0, (float)$r['gt'] - $newPaid);

		$statusHns = ($newOut <= 0.01)
			? 'paid'
			: (($newPaid > 0.01) ? 'partial' : 'unpaid');

		$payload[] = [
			'invoice_no'    => (string)$r['invoice_no'],
			'status'        => $statusHns,
			'tanggal_bayar' => $tglBayar,

			// Catatan:
			// Kalau field HNS "overdue" maksudnya jumlah terbayar, ini boleh.
			// Kalau maksudnya sisa outstanding, ganti ke $newOut.
			'overdue'       => $newPaid,
		];
	}

	$hns = $this->_post_status_invoice_hns($payload);

	if (empty($hns['ok'])) {
		$this->erp->trans_rollback();

		return [
			'ok'      => false,
			'http'    => 502,
			'message' => 'Payment dibatalkan karena gagal update status invoice HNS',
			'data'    => [
				'hns' => $hns,
			],
		];
	}

    $this->erp->trans_commit();
    return [
        'ok'      => true,
        'message' => $hns['ok'] ? 'Payment received' : 'Payment received with HNS warning',
        'data'    => [
            'rcpt_id'                 => $rcpt_id,
            'rcpt_no'                 => $rcpt_no,
            'posting_date'            => $posting_date,
            'tgl_bayar_saved'         => $tglBayar,
            'currency'                => $receiptCurrency,
            'payment_kurs'            => $payment_kurs,
            'party_code'              => $party_code,
            'party_name'              => $party_name,
            'cash_received'           => $cash_received,
            'deposit_used'            => $deposit_used,
            'total_alloc_doc'         => $total_alloc_doc,
            'total_adjustments'       => $total_adj_doc,
            'total_adj_doc'           => $total_adj_doc,
            'deposit_amount'          => $deposit_amount,
            'total_cash_idr'          => $total_cash_idr,
            'total_ar_idr'            => $total_ar_idr,
            'total_adj_idr'           => $total_adj_idr,
            'total_deposit_used_idr'  => $total_deposit_used_idr,
            'total_deposit_idr'       => $total_deposit_idr,
            'fx_diff_idr'             => $total_fx_diff,
            'hns'                     => $hns,
        ],
    ];
}

    // =========================================================
    // HELPERS
    // =========================================================

    private function active_region()
    {
        $region = '';

        if ($this->session->userdata('region_id')) {
            $region = trim((string)$this->session->userdata('region_id'));
        }

        if ($region === '' && $this->session->userdata('region')) {
            $region = trim((string)$this->session->userdata('region'));
        }

        if ($region === '' && method_exists($this, 'auth_region')) {
            $region = trim((string)$this->auth_region());
        }

        return $region;
    }

    private function num($v, $default = 0.0)
    {
        if ($v === null || $v === '') {
            return (float)$default;
        }

        if (is_numeric($v)) {
            return (float)$v;
        }

        return (float)preg_replace('/[^\d.\-]/', '', (string)$v);
    }

    private function table_exists($table)
    {
        return $this->erp->table_exists($table);
    }

    private function has_column($table, $column)
    {
        $key = $table . '.' . $column;

        if (array_key_exists($key, $this->column_cache)) {
            return $this->column_cache[$key];
        }

        if (!$this->erp->table_exists($table)) {
            $this->column_cache[$key] = false;
            return false;
        }

        $this->column_cache[$key] = $this->erp->field_exists($column, $table);
        return $this->column_cache[$key];
    }

    private function filter_columns($table, $data)
    {
        if (!$this->erp->table_exists($table)) {
            return [];
        }

        $fields = $this->erp->list_fields($table);
        $allowed = array_flip($fields);

        $out = [];

        foreach ($data as $k => $v) {
            if (isset($allowed[$k])) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    private function insert_filtered($db, $table, $data)
    {
        $row = $this->filter_columns($table, $data);

        if (empty($row)) {
            return false;
        }

        return $db->insert($table, $row);
    }

    private function update_filtered($db, $table, $data)
    {
        $row = $this->filter_columns($table, $data);

        if (empty($row)) {
            return false;
        }

        return $db->update($table, $row);
    }

   private function apply_erp_region_filter($table, $alias = null)
	{
		if ($this->is_super_admin()) {
			return;
		}

		if (!$this->has_column($table, 'region_id')) {
			return;
		}

		$region_id = $this->active_region();

		if ($region_id === '') {
			return;
		}

		$this->erp->where(($alias ? $alias . '.' : '') . 'region_id', $region_id);
	}

    private function normalize_invoice_currency($currency)
    {
        $currency = strtoupper(trim((string)$currency));

        if ($currency === '') {
            return 'RP.';
        }

        if ($currency === 'RP' || $currency === 'RUPIAH' || $currency === 'IDR') {
            return 'RP.';
        }

        return $currency;
    }

    private function currency_for_calc($currency)
    {
        $currency = strtoupper(trim((string)$currency));

        if ($currency === '' || $currency === 'RP' || $currency === 'RP.' || $currency === 'RUPIAH' || $currency === 'IDR') {
            return 'IDR';
        }

        return $currency;
    }

    private function get_account_by_id($account_id)
    {
        $this->erp->select('account_id, account_code, account_name, root_type, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', (int)$account_id);

        $this->apply_erp_region_filter('accounts');

        return $this->erp->get()->row_array();
    }

    private function get_ar_invoice($ar_invoice_id)
    {
        if (!$this->erp->table_exists('ar_invoices')) {
            return null;
        }

        $this->erp->select("
            ai.ar_invoice_id,
            ai.company_id,
            ai.region_id,
            ai.source_db,
            ai.source_table,
            ai.source_id,
            ai.source_ref,
            ai.invoice_no,
            ai.job_no,
            ai.posting_date,
            ai.party_type,
            ai.party_code,
            ai.party_name,
            ai.currency,
            ai.exchange_rate,
            ai.grand_total,
            ai.ppn_total,
            ai.dpp_total,
            ai.grand_total_idr,
            ai.ppn_total_idr,
            ai.dpp_total_idr,
            ai.paid_total,
            ai.outstanding_total,
            ai.status
        ", false);

        $this->erp->from('ar_invoices ai');
        $this->erp->where('ai.company_id', $this->company_id);
        $this->erp->where('ai.ar_invoice_id', (int)$ar_invoice_id);

        if (!$this->is_super_admin()) {
            $region_id = $this->active_region();
            if ($region_id !== '' && $this->has_column('ar_invoices', 'region_id')) {
                $this->erp->where('ai.region_id', $region_id);
            }
        }

        return $this->erp->get()->row_array();
    }

    private function insert_gl($row)
    {
        $this->insert_filtered($this->erp, 'gl_entries', $row);
    }

    private function _next_series($series_key, $year)
    {
        $row = $this->erp->get_where('number_series', [
            'series_key' => $series_key,
            'company_id' => $this->company_id,
            'year'       => $year,
        ])->row_array();

        if (!$row) {
            $prefix = $series_key . '-' . $year . '-';

            $this->erp->insert('number_series', [
                'series_key' => $series_key,
                'company_id' => $this->company_id,
                'year'       => $year,
                'prefix'     => $prefix,
                'counter'    => 0,
            ]);

            $row = [
                'prefix'  => $prefix,
                'counter' => 0,
            ];
        }

        $new_counter = (int)$row['counter'] + 1;

        $this->erp->where([
            'series_key' => $series_key,
            'company_id' => $this->company_id,
            'year'       => $year,
        ])->update('number_series', [
            'counter' => $new_counter,
        ]);

        return $row['prefix'] . str_pad((string)$new_counter, 5, '0', STR_PAD_LEFT);
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
      $region_id = trim((string)($region_id !== null ? $region_id : $this->active_region()));

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


    private function _is_locked($posting_date)
    {
        if (!$this->erp->table_exists('accounting_locks')) {
            return false;
        }

        $this->erp->select('MAX(lock_date) mx', false)
            ->from('accounting_locks')
            ->where('company_id', $this->company_id);

        $this->apply_erp_region_filter('accounting_locks');

        $row = $this->erp->get()->row_array();
        $mx = $row['mx'] ?? null;

        return ($mx && $posting_date <= $mx);
    }

    private function _gl_voucher_posted($voucher_type, $voucher_no)
    {
        if (!$this->erp->table_exists('gl_entries')) {
            return false;
        }

        $this->erp->select('gl_id')
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('voucher_type', $voucher_type)
            ->where('voucher_no', $voucher_no)
            ->where('status', 'POSTED')
            ->limit(1);

        $this->apply_erp_region_filter('gl_entries');

        $row = $this->erp->get()->row_array();

        return $row ? true : false;
    }

    // =========================================================
    // HNS status_invoice
    // =========================================================

    private function _post_status_invoice_hns($payloadArr)
{
    if (!$payloadArr || !is_array($payloadArr)) {
        return [
            'ok'      => true,
            'skipped' => true,
            'reason'  => 'empty_payload',
        ];
    }

    $url = 'http://localhost/api/status_invoice';

    log_message('debug', 'HNS status_invoice TRIGGERED');
    log_message('debug', 'HNS status_invoice payload: ' . json_encode($payloadArr));

    $jsonPayload = json_encode($payloadArr);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($jsonPayload),
    ];

    // Kalau endpoint HNS butuh API key, aktifkan ini:
    $headers[] = 'X-API-KEY: gateway-fms';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $jsonPayload,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_TIMEOUT        => 25,

        // penting supaya error HTTP tetap bisa dibaca body-nya
        CURLOPT_FAILONERROR    => false,
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $decoded = null;
    if ($resp !== false && $resp !== null && trim($resp) !== '') {
        $decoded = json_decode($resp, true);
    }

    $curlOk = ($err === '' || $err === null);
    $httpOk = ($code >= 200 && $code < 300);

    /**
     * Business OK:
     * Sesuaikan dengan response endpoint /api/status_invoice.
     * Kalau response-nya:
     * {"status":true}
     * maka ini benar.
     */
    $businessOk = true;

    if (is_array($decoded) && array_key_exists('status', $decoded)) {
        $businessOk = ($decoded['status'] === true || $decoded['status'] === 'success' || $decoded['status'] === 'OK');
    }

    // Kalau response bukan JSON, anggap gagal.
    if ($resp !== null && trim((string)$resp) !== '' && $decoded === null) {
        $businessOk = false;
    }

    $ok = ($curlOk && $httpOk && $businessOk);

    if (!$ok) {
        log_message('error', 'HNS status_invoice FAILED: ' . json_encode([
            'url'       => $url,
            'http_code' => $code,
            'curl_err'  => $err,
            'response'  => $resp,
        ]));
    } else {
        log_message('debug', 'HNS status_invoice OK: ' . json_encode([
            'http_code' => $code,
            'response'  => $decoded ?: $resp,
        ]));
    }

    return [
        'ok'             => $ok,
        'http_code'      => $code,
        'error'          => $err ?: null,
        'response'       => $resp ?: null,
        'json'           => $decoded,
        'sent_count'     => count($payloadArr),
        'payload_sample' => array_slice($payloadArr, 0, 3),
    ];
}
}