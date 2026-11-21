<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_cash_advances extends MY_Model
{
    private $company_id = 1;

    // ===== Public =====

    public function list($status = '', $date_from = '', $date_to = '')
    {
        $this->erp->from('cash_advances')
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        if ($status !== '') {
            $this->erp->where('status', $status);
        }

        if ($date_from !== '') {
            $this->erp->where('posting_date >=', $date_from);
        }

        if ($date_to !== '') {
            $this->erp->where('posting_date <=', $date_to);
        }

        return $this->erp->order_by('advance_id', 'DESC')
            ->get()
            ->result_array();
    }

    public function get($advance_id)
    {
        $advance_id = (int)$advance_id;

        $this->erp->from('cash_advances')
            ->where('company_id', $this->company_id)
            ->where('advance_id', $advance_id);

        $this->apply_region_filter('region_id');

        $hdr = $this->erp->get()->row_array();

        if (!$hdr) {
            return null;
        }

        $lines = $this->erp->order_by('line_id', 'ASC')
            ->get_where('cash_advance_lines', [
                'advance_id' => $advance_id
            ])
            ->result_array();

        $hdr['lines'] = $lines;

        return $hdr;
    }

    public function issue($p)
    {
        $posting_date = (string)($p['posting_date'] ?? '');
        $employee_id  = isset($p['employee_id']) ? (int)$p['employee_id'] : null;
        $purpose      = trim((string)($p['purpose'] ?? ''));
        $amount       = (float)($p['amount'] ?? 0);
        $cash_account_id = (int)($p['cash_account_id'] ?? 0);

        $errs = [];

        if (!$posting_date) {
            $errs['posting_date'] = 'required';
        }

        if ($amount <= 0) {
            $errs['amount'] = 'must be > 0';
        }

        if (!$purpose) {
            $errs['purpose'] = 'required';
        }

        if ($cash_account_id <= 0) {
            $errs['cash_account_id'] = 'required';
        }

        if (!$errs) {
            $acc = $this->_get_account($cash_account_id);

            if (!$acc) {
                $errs['cash_account_id'] = 'account not found';
            } elseif ($acc['root_type'] !== 'Asset') {
                $errs['cash_account_id'] = 'must be Asset (Cash/Bank/Petty)';
            } elseif ((int)$acc['is_postable'] !== 1) {
                $errs['cash_account_id'] = 'must be postable';
            }
        }

        $advance_acc_id = $this->_coa_id('CASH_ADVANCE', (string)$this->auth_region());

        if (!$advance_acc_id) {
            $errs['setup'] = 'Missing Cash Advance account (' . $this->_coa_code('CASH_ADVANCE') . ')';
        }

        if ($errs) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => $errs
            ];
        }

        $region_id = (string)$this->auth_region();

        $this->erp->trans_begin();

        $cashAdvanceData = [
            'company_id'       => $this->company_id,
            'region_id'        => $region_id,
            'posting_date'     => $posting_date,
            'employee_id'      => $employee_id ?: null,
            'purpose'          => $purpose,
            'amount'           => $amount,
            'status'           => 'OPEN',
            'cash_account_id'  => $cash_account_id,
            'created_by'       => $this->auth_username(),
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $this->erp->insert('cash_advances', $cashAdvanceData);

        $advance_id = (int)$this->erp->insert_id();

        $je_no = $this->_next_series('CAI', (int)date('Y', strtotime($posting_date)));
        $je_id = $this->_create_je($je_no, $posting_date, "Cash Advance #{$advance_id} - {$purpose}");

        $this->_add_je_line($je_id, 1, $advance_acc_id, 'Cash Advance issued', $amount, 0);
        $this->_add_je_line($je_id, 2, $cash_account_id, 'Paid from cash/bank', 0, $amount);

        $val = $this->_submit_and_post_gl($je_id, $je_no, $posting_date, 'JE');

        if (!$val['ok']) {
            $this->erp->trans_rollback();
            return $val;
        }

        $this->erp->where('advance_id', $advance_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('cash_advances', [
            'je_issue_id' => $je_id,
            'updated_by'  => $this->auth_username(),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error'
            ];
        }

        $this->erp->trans_commit();

        return [
            'ok'      => true,
            'message' => 'Cash advance issued',
            'data'    => [
                'advance_id'       => $advance_id,
                'region_id'        => $region_id,
                'je_id'            => $je_id,
                'je_no'            => $je_no,
                'amount'           => $amount,
                'cash_account_id'  => $cash_account_id
            ]
        ];
    }

    public function settle($p)
    {
        $advance_id   = (int)($p['advance_id'] ?? 0);
        $posting_date = (string)($p['posting_date'] ?? '');
        $lines        = $p['lines'] ?? [];
        $return_amount    = (float)($p['return_amount'] ?? 0);
        $shortfall_amount = (float)($p['shortfall_amount'] ?? 0);
        $shortfall_to     = strtoupper((string)($p['shortfall_to'] ?? 'DUE_TO_EMPLOYEE'));

        $errs = [];

        if ($advance_id <= 0) {
            $errs['advance_id'] = 'required';
        }

        if (!$posting_date) {
            $errs['posting_date'] = 'required';
        }

        if (!is_array($lines) || count($lines) < 1) {
            $errs['lines'] = 'required';
        }

        $hdr = null;

        if (!$errs) {
            $this->erp->from('cash_advances')
                ->where('company_id', $this->company_id)
                ->where('advance_id', $advance_id);

            $this->apply_region_filter('region_id');

            $hdr = $this->erp->get()->row_array();

            if (!$hdr) {
                $errs['advance_id'] = 'not found';
            } elseif ($hdr['status'] !== 'OPEN') {
                $errs['advance_id'] = 'must be OPEN';
            }
        }

        $advance_acc_id = $this->_coa_id('CASH_ADVANCE', (string)$this->auth_region());
        $due_to_emp_id  = $this->_coa_id('EMPLOYEE_PAYABLE', (string)$this->auth_region());

        if (!$advance_acc_id) {
            $errs['setup'] = 'Missing Cash Advance account (' . $this->_coa_code('CASH_ADVANCE') . ')';
        }

        if (!$due_to_emp_id) {
            $errs['setup2'] = 'Missing Employee Payable account (' . $this->_coa_code('EMPLOYEE_PAYABLE') . ')';
        }

        $sum = 0.0;

        if (!$errs) {
            foreach ($lines as $i => $ln) {
                $t = $this->_normalize_allocation_type($ln['allocation_type'] ?? '');
                $acc_id = (int)($ln['account_id'] ?? 0);
                $amt = (float)($ln['amount'] ?? 0);

                if (!in_array($t, ['OPEX', 'CAPEX', 'HPP'], true)) {
                    $errs["lines.$i.allocation_type"] = 'must be OPEX|CAPEX|HPP';
                }

                if ($acc_id <= 0) {
                    $errs["lines.$i.account_id"] = 'required';
                }

                if ($amt <= 0) {
                    $errs["lines.$i.amount"] = 'must be > 0';
                }

                if ($acc_id) {
                    $acc = $this->_get_account($acc_id);

                    if (!$acc) {
                        $errs["lines.$i.account_id"] = 'account not found';
                    } elseif ((int)$acc['is_postable'] !== 1) {
                        $errs["lines.$i.account_id"] = 'account must be postable';
                    }
                }

                $sum += $amt;
            }
        }

        if ($return_amount < 0) {
            $errs['return_amount'] = 'must be >= 0';
        }

        if ($shortfall_amount < 0) {
            $errs['shortfall_amount'] = 'must be >= 0';
        }

        if (!in_array($shortfall_to, ['CASH', 'DUE_TO_EMPLOYEE'], true)) {
            $errs['shortfall_to'] = 'must be CASH|DUE_TO_EMPLOYEE';
        }

        if (!$errs && $hdr) {
            $expected = (float)$hdr['amount'];
            $left = $sum + $return_amount - $shortfall_amount;

            if (abs($left - $expected) > 0.01) {
                $errs['balance'] = "Settlement not balanced. Need: sum(lines)+return-shortfall = {$expected}. Now: {$left}";
            }
        }

        if ($errs) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => $errs
            ];
        }

        $cash_account_id = (int)$hdr['cash_account_id'];

        $this->erp->trans_begin();

        foreach ($lines as $ln) {
            $lineData = [
                'advance_id'       => $advance_id,
                'allocation_type'  => $this->_normalize_allocation_type($ln['allocation_type'] ?? ''),
                'account_id'       => (int)$ln['account_id'],
                'job_id'           => isset($ln['job_id']) ? trim((string)$ln['job_id']) : null,
                'memo'             => isset($ln['memo']) ? trim((string)$ln['memo']) : null,
                'amount'           => (float)$ln['amount'],
            ];

            $this->erp->insert('cash_advance_lines', $lineData);
        }

        $je_no = $this->_next_series('CAS', (int)date('Y', strtotime($posting_date)));
        $je_id = $this->_create_je($je_no, $posting_date, "Cash Advance Settlement #{$advance_id}");

        $line_no = 1;

        foreach ($lines as $ln) {
            $acc_id = (int)$ln['account_id'];
            $amt = (float)$ln['amount'];
            $memo = trim((string)($ln['memo'] ?? 'Settlement'));

            $this->_add_je_line($je_id, $line_no++, $acc_id, $memo, $amt, 0);
            $this->_add_je_line($je_id, $line_no++, $advance_acc_id, 'Reduce Cash Advance', 0, $amt);
        }

        if ($return_amount > 0) {
            $this->_add_je_line($je_id, $line_no++, $cash_account_id, 'Return remaining cash advance', $return_amount, 0);
            $this->_add_je_line($je_id, $line_no++, $advance_acc_id, 'Reduce Cash Advance (return)', 0, $return_amount);
        }

        if ($shortfall_amount > 0) {
            $credit_acc = ($shortfall_to === 'CASH') ? $cash_account_id : $due_to_emp_id;

            $this->_add_je_line($je_id, $line_no++, $advance_acc_id, 'Cash Advance shortfall adjustment', $shortfall_amount, 0);
            $this->_add_je_line(
                $je_id,
                $line_no++,
                $credit_acc,
                ($shortfall_to === 'CASH') ? 'Paid additional from cash/bank' : 'Due to employee',
                0,
                $shortfall_amount
            );
        }

        $val = $this->_submit_and_post_gl($je_id, $je_no, $posting_date, 'JE');

        if (!$val['ok']) {
            $this->erp->trans_rollback();
            return $val;
        }

        $this->erp->where('advance_id', $advance_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('cash_advances', [
            'je_settle_id' => $je_id,
            'status'       => 'CLOSED',
            'closed_at'    => date('Y-m-d H:i:s'),
            'closed_by'    => $this->auth_username(),
            'updated_by'   => $this->auth_username(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error'
            ];
        }

        $this->erp->trans_commit();

        return [
            'ok'      => true,
            'message' => 'Cash advance settled',
            'data'    => [
                'advance_id'        => $advance_id,
                'region_id'         => $hdr['region_id'] ?? $this->auth_region(),
                'je_id'             => $je_id,
                'je_no'             => $je_no,
                'total_allocated'   => $sum,
                'return_amount'     => $return_amount,
                'shortfall_amount'  => $shortfall_amount,
                'shortfall_to'      => $shortfall_to
            ]
        ];
    }

    // ===== Internal helpers =====

    private function _get_account($account_id)
    {
        $this->erp->select('account_id, root_type, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', (int)$account_id);

        $this->apply_region_filter('region_id');

        return $this->erp->get()->row_array();
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

    private function _normalize_allocation_type($type)
    {
        $type = strtoupper(trim((string)$type));

        // Backward compatibility: FE lama boleh masih mengirim WIP.
        // Pada direct-HPP mode, simpan sebagai HPP agar tidak membentuk WIP baru.
        if ($type === 'WIP') {
            return 'HPP';
        }

        return $type;
    }

    private function _create_je($je_no, $posting_date, $remark)
    {
        $data = [
            'company_id'    => $this->company_id,
            'region_id'     => (string)$this->auth_region(),
            'je_no'         => $je_no,
            'posting_date'  => $posting_date,
            'remark'        => $remark,
            'status'        => 'DRAFT',
            'created_at'    => date('Y-m-d H:i:s'),
            'created_by'    => $this->auth_username()
        ];

        $this->erp->insert('journal_entries', $data);

        return (int)$this->erp->insert_id();
    }

    private function _add_je_line($je_id, $line_no, $account_id, $memo, $debit, $credit)
    {
        $this->erp->insert('journal_entry_lines', [
            'je_id'      => $je_id,
            'line_no'    => $line_no,
            'account_id' => $account_id,
            'memo'       => $memo,
            'debit'      => (float)$debit,
            'credit'     => (float)$credit
        ]);
    }

    private function _submit_and_post_gl($je_id, $voucher_no, $posting_date, $voucher_type = 'JE')
    {
        $lines = $this->erp->order_by('line_no', 'ASC')
            ->get_where('journal_entry_lines', [
                'je_id' => $je_id
            ])
            ->result_array();

        if (count($lines) < 2) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'JE invalid: need >=2 lines'
            ];
        }

        $td = 0.0;
        $tc = 0.0;

        foreach ($lines as $ln) {
            $d = (float)$ln['debit'];
            $c = (float)$ln['credit'];

            if (($d > 0 && $c > 0) || ($d <= 0 && $c <= 0)) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'JE invalid: each line must have either debit or credit'
                ];
            }

            $acc = $this->_get_account((int)$ln['account_id']);

            if (!$acc || (int)$acc['is_active'] !== 1 || (int)$acc['is_postable'] !== 1) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'JE invalid: account not postable/active'
                ];
            }

            $td += $d;
            $tc += $c;
        }

        if (abs($td - $tc) > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'JE invalid: debit != credit'
            ];
        }

        $this->erp->where('je_id', $je_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('journal_entries', [
            'status'        => 'SUBMITTED',
            'submitted_at'  => date('Y-m-d H:i:s'),
            'submitted_by'  => $this->auth_username(),
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => $this->auth_username(),
        ]);

        $region_id = (string)$this->auth_region();

        foreach ($lines as $ln) {
            $this->erp->insert('gl_entries', [
                'company_id'     => $this->company_id,
                'region_id'      => $region_id,
                'posting_date'   => $posting_date,
                'account_id'     => (int)$ln['account_id'],
                'party_id'       => null,
                'debit'          => (float)$ln['debit'],
                'credit'         => (float)$ln['credit'],
                'voucher_type'   => $voucher_type,
                'voucher_no'     => $voucher_no,
                'status'         => 'POSTED'
            ]);
        }

        return [
            'ok' => true
        ];
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
                'prefix'     => $prefix,
                'year'       => $year,
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
}