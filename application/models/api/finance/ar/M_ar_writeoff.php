<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_ar_writeoff extends MY_Model
{
    private $company_id = 1;
    private $invoice_table = 'ar_invoices';

    public function create($p)
    {
        // Tetap support finance_id dari FE lama, tapi query sudah memakai ar_invoices.
        $invoice_id         = (int)($p['invoice_id'] ?? $p['ar_invoice_id'] ?? $p['finance_id'] ?? 0);
        $posting_date       = (string)($p['posting_date'] ?? '');
        $amount             = (float)($p['amount'] ?? 0);
        $expense_account_id = (int)($p['expense_account_id'] ?? 0);
        $remark             = trim((string)($p['remark'] ?? ''));

        $errs = [];

        if ($invoice_id <= 0) {
            $errs['invoice_id'] = 'required';
        }

        if (!$posting_date) {
            $errs['posting_date'] = 'required';
        }

        if ($amount <= 0) {
            $errs['amount'] = 'must be > 0';
        }

        if ($expense_account_id <= 0) {
            $errs['expense_account_id'] = 'required';
        }

        if ($errs) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => $errs
            ];
        }

        if ($this->_is_locked($posting_date)) {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'PERIOD_LOCKED'
            ];
        }

        $inv = $this->_load_ar_invoice($invoice_id);

        if (!$inv) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Invoice not found'
            ];
        }

        $this->erp->select('account_id, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', $expense_account_id);

        $this->apply_region_filter('region_id');

        $expenseAcc = $this->erp->get()->row_array();

        if (!$expenseAcc || (int)$expenseAcc['is_postable'] !== 1 || (int)$expenseAcc['is_active'] !== 1) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Invalid write-off account'
            ];
        }

        $ar_account_id = $this->_coa_id('AR', (string)$this->auth_region());

        if ($ar_account_id <= 0) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Missing/invalid AR account (' . $this->_coa_code('AR') . ')'
            ];
        }

        $gt   = (float)($inv['grand_total'] ?? 0);
        $paid = (float)($inv['paid_total'] ?? 0);
        $outstanding = $gt - $paid;

        if ($outstanding < 0) {
            $outstanding = 0;
        }

        if ($outstanding <= 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Invoice already paid'
            ];
        }

        if ($amount - $outstanding > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Write-off amount exceeds outstanding'
            ];
        }

        $currency = strtoupper(trim((string)($inv['currency'] ?? 'IDR')));

        if ($currency === 'RP.' || $currency === '') {
            $currency = 'IDR';
        }

        $maxWriteoff = ($currency === 'USD') ? 10 : 250000;

        if ($amount - $maxWriteoff > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'WRITE_OFF_EXCEEDS_THRESHOLD',
                'data'    => [
                    'max_writeoff' => $maxWriteoff,
                    'currency'     => $currency,
                ]
            ];
        }

        $je_no = $this->_next_series('JE', (int)date('Y', strtotime($posting_date)));

        $job_no = trim((string)($inv['job_no'] ?? ''));
        $newPaid = $paid + $amount;
        $newOut = $gt - $newPaid;

        if ($newOut < 0) {
            $newOut = 0;
        }

        $newStatus = ($newOut <= 0.01) ? 'paid' : (($newPaid > 0.01) ? 'partial' : 'unpaid');
        $tglBayar = ($newPaid > 0.01) ? date('Ymd', strtotime($posting_date)) : null;
        $region_id = (string)$this->auth_region();
        $invoice_no = trim((string)($inv['invoice_no'] ?? $invoice_id));

        $this->erp->trans_begin();

        $memo = $remark !== ''
            ? $remark
            : 'AR Write-off ' . $invoice_no;

        // Dr expense/writeoff account
        $this->erp->insert('gl_entries', [
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => $job_no ?: null,
            'account_id'   => $expense_account_id,
            'party_id'     => null,
            'debit'        => $amount,
            'credit'       => 0,
            'voucher_type' => 'JE',
            'voucher_no'   => $je_no,
            'remarks'      => $memo,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Cr AR
        $this->erp->insert('gl_entries', [
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => $job_no ?: null,
            'account_id'   => $ar_account_id,
            'party_id'     => null,
            'debit'        => 0,
            'credit'       => $amount,
            'voucher_type' => 'JE',
            'voucher_no'   => $je_no,
            'remarks'      => $memo,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $upd = $this->_build_invoice_paid_update($newPaid, $newOut, $newStatus, $tglBayar);

        $this->erp->where($inv['_pk_field'], $invoice_id);

        if ($this->_field_exists($this->invoice_table, 'region_id')) {
            $this->apply_region_filter('region_id');
        }

        $this->erp->update($this->invoice_table, $upd);

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
                ]
            ];
        }

        $this->erp->trans_commit();

        return [
            'ok'      => true,
            'message' => 'Write-off posted',
            'data'    => [
                'invoice_id'   => $invoice_id,
                // Backward compatibility untuk FE lama yang masih membaca finance_id.
                'finance_id'   => $invoice_id,
                'invoice_no'   => $invoice_no,
                'region_id'    => $region_id,
                'je_no'        => $je_no,
                'amount'       => $amount,
                'currency'     => $currency,
                'new_status'   => $newStatus,
                'outstanding'  => $newOut,
            ]
        ];
    }

    private function _load_ar_invoice($invoice_id)
    {
        $table = $this->invoice_table;

        $pkField = $this->_pick_field($table, ['invoice_id', 'ar_invoice_id', 'id']);
        $noField = $this->_pick_field($table, ['invoice_no', 'ar_invoice_no', 'number', 'inv_no']);
        $jobField = $this->_pick_field($table, ['job_no', 'job_id', 'source_key', 'kode_ref']);
        $totalField = $this->_pick_field($table, ['grand_total', 'total', 'base_total', 'net_total']);
        $paidField = $this->_pick_field($table, ['paid_total', 'amount_paid', 'paid_amount', 'overdue']);
        $currencyField = $this->_pick_field($table, ['currency', 'currency_code']);
        $kursField = $this->_pick_field($table, ['kurs', 'conversion_rate', 'payment_rate', 'exchange_rate']);
        $statusField = $this->_pick_field($table, ['status', 'payment_status']);

        if (!$pkField || !$totalField) {
            return null;
        }

        $select = [
            $pkField . ' AS invoice_id',
            $totalField . ' AS grand_total',
        ];

        if ($noField) {
            $select[] = $noField . ' AS invoice_no';
        }

        if ($jobField) {
            $select[] = $jobField . ' AS job_no';
        }

        if ($paidField) {
            $select[] = $paidField . ' AS paid_total';
        }

        if ($currencyField) {
            $select[] = $currencyField . ' AS currency';
        }

        if ($kursField) {
            $select[] = $kursField . ' AS kurs';
        }

        if ($statusField) {
            $select[] = $statusField . ' AS status';
        }

        $this->erp->select(implode(', ', array_unique($select)), false)
            ->from($table)
            ->where($pkField, $invoice_id);

        if ($this->_field_exists($table, 'region_id')) {
            $this->apply_region_filter('region_id');
        }

        $row = $this->erp->get()->row_array();

        if (!$row) {
            return null;
        }

        $row['_pk_field'] = $pkField;
        $row['_paid_field'] = $paidField;
        $row['_status_field'] = $statusField;

        if (!isset($row['paid_total']) || $row['paid_total'] === null || $row['paid_total'] === '') {
            $row['paid_total'] = 0;
        }

        if (!isset($row['currency']) || $row['currency'] === null || $row['currency'] === '') {
            $row['currency'] = 'IDR';
        }

        if (!isset($row['job_no'])) {
            $row['job_no'] = '';
        }

        if (!isset($row['invoice_no']) || $row['invoice_no'] === null || $row['invoice_no'] === '') {
            $row['invoice_no'] = (string)$invoice_id;
        }

        return $row;
    }

    private function _build_invoice_paid_update($newPaid, $newOut, $newStatus, $tglBayar)
    {
        $table = $this->invoice_table;
        $upd = [];

        $paidField = $this->_pick_field($table, ['paid_total', 'amount_paid', 'paid_amount', 'overdue']);
        if ($paidField) {
            $upd[$paidField] = $newPaid;
        }

        $outstandingField = $this->_pick_field($table, ['outstanding_total', 'outstanding', 'balance_due', 'remaining_total']);
        if ($outstandingField) {
            $upd[$outstandingField] = $newOut;
        }

        $statusField = $this->_pick_field($table, ['status', 'payment_status']);
        if ($statusField) {
            $upd[$statusField] = $newStatus;
        }

        if ($this->_field_exists($table, 'tgl_bayar')) {
            $upd['tgl_bayar'] = $tglBayar;
        }

        if ($this->_field_exists($table, 'paid_at') && $newOut <= 0.01) {
            $upd['paid_at'] = date('Y-m-d H:i:s');
        }

        if ($this->_field_exists($table, 'updated_at')) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($this->_field_exists($table, 'updated_by')) {
            $upd['updated_by'] = (int)($this->session->userdata('user_id') ?? 0);
        }

        return $upd;
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

    private function _is_locked($posting_date)
    {
        $this->erp->select('MAX(lock_date) mx', false)
            ->from('accounting_locks')
            ->where('company_id', $this->company_id);

        if ($this->_field_exists('accounting_locks', 'region_id')) {
            $this->apply_region_filter('region_id');
        }

        $row = $this->erp->get()->row_array();

        $mx = $row['mx'] ?? null;

        return ($mx && $posting_date <= $mx);
    }

    private function _next_series($series_key, $year)
    {
        $row = $this->erp->get_where('number_series', [
            'series_key' => $series_key,
            'company_id' => $this->company_id,
            'year'       => $year
        ])->row_array();

        if (!$row) {
            $prefix = $series_key . '-' . $year . '-';

            $this->erp->insert('number_series', [
                'series_key' => $series_key,
                'company_id' => $this->company_id,
                'year'       => $year,
                'prefix'     => $prefix,
                'counter'    => 0
            ]);

            $row = [
                'prefix'  => $prefix,
                'counter' => 0
            ];
        }

        $new_counter = (int)$row['counter'] + 1;

        $this->erp->where([
            'series_key' => $series_key,
            'company_id' => $this->company_id,
            'year'       => $year
        ])->update('number_series', [
            'counter' => $new_counter
        ]);

        return $row['prefix'] . str_pad((string)$new_counter, 5, '0', STR_PAD_LEFT);
    }

    private function _pick_field($table, $candidates)
    {
        foreach ($candidates as $field) {
            if ($this->_field_exists($table, $field)) {
                return $field;
            }
        }

        return null;
    }

    private function _field_exists($table, $field)
    {
        return $this->erp->field_exists($field, $table);
    }
}
