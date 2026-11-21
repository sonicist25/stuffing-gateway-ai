<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_year_end extends MY_Model
{
    private $company_id = 1;

    public function preview($year, $retained_account_id, $closing_date)
    {
        $year = (int)$year;
        $retained_account_id = (int)$retained_account_id;

        if ($year <= 0) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => [
                    'year' => 'required'
                ]
            ];
        }

        if ($retained_account_id <= 0) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Validation error',
                'errors'  => [
                    'retained_account_id' => 'required'
                ]
            ];
        }

        if (empty($closing_date)) {
            $closing_date = $year . '-12-31';
        }

        $fy = $this->_ensure_fiscal_year($year);

        $check = $this->_validate_retained_account($retained_account_id);

        if (!$check['ok']) {
            return $check;
        }

        $calc = $this->_calc_income_expense_net($fy['start_date'], $fy['end_date']);
        $lines = $this->_build_closing_lines($calc, $retained_account_id);

        return [
            'ok'      => true,
            'message' => 'OK',
            'data'    => [
                'region_id'             => (string)$this->auth_region(),
                'fiscal_year'           => $year,
                'period'                => [
                    'start' => $fy['start_date'],
                    'end'   => $fy['end_date']
                ],
                'closing_date'          => $closing_date,
                'retained_account_id'   => $retained_account_id,
                'summary'               => $calc['summary'],
                'lines'                 => $lines
            ]
        ];
    }

    public function close_year($year, $retained_account_id, $closing_date)
    {
        $year = (int)$year;
        $retained_account_id = (int)$retained_account_id;

        $prev = $this->preview($year, $retained_account_id, $closing_date);

        if (!$prev['ok']) {
            return $prev;
        }

        $this->erp->from('year_end_closings')
            ->where('company_id', $this->company_id)
            ->where('fiscal_year', $year);

        $this->apply_region_filter('region_id');

        $exists = $this->erp->get()->row_array();

        if ($exists && $exists['status'] !== 'CANCELLED') {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Year already closed',
                'errors'  => [
                    'closing_id' => $exists['closing_id']
                ]
            ];
        }

        $data = $prev['data'];
        $lines = $data['lines'];

        if (count($lines) < 2) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Nothing to close',
                'errors'  => [
                    'lines' => 'no income/expense movement'
                ]
            ];
        }

        $region_id = (string)$this->auth_region();

        $this->erp->trans_begin();

        $this->erp->insert('year_end_closings', [
            'company_id'                      => $this->company_id,
            'region_id'                       => $region_id,
            'fiscal_year'                     => $year,
            'closing_date'                    => $data['closing_date'],
            'retained_earnings_account_id'    => $retained_account_id,
            'net_profit'                      => (float)$data['summary']['net_profit'],
            'status'                          => 'DRAFT',
            'created_by'                      => $this->auth_username(),
            'created_at'                      => date('Y-m-d H:i:s'),
        ]);

        $closing_id = (int)$this->erp->insert_id();

        $je_no = $this->_next_series('JE', (int)date('Y', strtotime($data['closing_date'])));

        $this->erp->insert('journal_entries', [
            'company_id'    => $this->company_id,
            'region_id'     => $region_id,
            'je_no'         => $je_no,
            'posting_date'  => $data['closing_date'],
            'remark'        => 'Year End Closing ' . $year,
            'status'        => 'DRAFT',
            'created_by'    => $this->auth_username(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $je_id = (int)$this->erp->insert_id();

        $line_no = 1;

        foreach ($lines as $ln) {
            $this->erp->insert('journal_entry_lines', [
                'je_id'      => $je_id,
                'line_no'    => $line_no++,
                'account_id' => (int)$ln['account_id'],
                'party_id'   => null,
                'memo'       => $ln['memo'],
                'debit'      => (float)$ln['debit'],
                'credit'     => (float)$ln['credit'],
            ]);
        }

        $val = $this->_validate_je_lines($je_id);

        if (!$val['ok']) {
            $this->erp->trans_rollback();
            return $val;
        }

        $this->erp->where('je_id', $je_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('journal_entries', [
            'status'        => 'SUBMITTED',
            'submitted_at'  => date('Y-m-d H:i:s'),
            'submitted_by'  => $this->auth_username(),
            'updated_by'    => $this->auth_username(),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->erp->from('journal_entries')
            ->where('je_id', $je_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $je = $this->erp->get()->row_array();

        $jel = $this->erp->order_by('line_no', 'ASC')
            ->get_where('journal_entry_lines', [
                'je_id' => $je_id
            ])
            ->result_array();

        foreach ($jel as $ln) {
            $this->erp->insert('gl_entries', [
                'company_id'    => $this->company_id,
                'region_id'     => $region_id,
                'posting_date'  => $je['posting_date'],
                'account_id'    => (int)$ln['account_id'],
                'party_id'      => null,
                'debit'         => (float)$ln['debit'],
                'credit'        => (float)$ln['credit'],
                'voucher_type'  => 'JE',
                'voucher_no'    => $je['je_no'],
                'status'        => 'POSTED',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $this->erp->where('closing_id', $closing_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('year_end_closings', [
            'je_id'         => $je_id,
            'status'        => 'SUBMITTED',
            'submitted_at'  => date('Y-m-d H:i:s'),
            'submitted_by'  => $this->auth_username(),
        ]);

        $this->erp->where('company_id', $this->company_id)
            ->where('year', $year);

        $this->apply_region_filter('region_id');

        $this->erp->update('fiscal_years', [
            'is_closed' => 1,
            'closed_at' => date('Y-m-d H:i:s'),
            'closed_by' => $this->auth_username(),
        ]);

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
            'message' => 'Year closed',
            'data'    => [
                'closing_id'          => $closing_id,
                'region_id'           => $region_id,
                'je_id'               => $je_id,
                'je_no'               => $je_no,
                'net_profit'          => (float)$data['summary']['net_profit'],
                'closing_date'        => $data['closing_date'],
                'retained_account_id' => $retained_account_id
            ]
        ];
    }

    public function cancel_closing($closing_id)
    {
        $closing_id = (int)$closing_id;

        $this->erp->from('year_end_closings')
            ->where('closing_id', $closing_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $row = $this->erp->get()->row_array();

        if (!$row) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Closing not found'
            ];
        }

        if ($row['status'] !== 'SUBMITTED') {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Only SUBMITTED closing can be cancelled'
            ];
        }

        $this->erp->trans_begin();

        $this->erp->from('journal_entries')
            ->where('je_id', (int)$row['je_id'])
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $je = $this->erp->get()->row_array();

        if ($je) {
            $this->erp->where('je_id', (int)$je['je_id'])
                ->where('company_id', $this->company_id);

            $this->apply_region_filter('region_id');

            $this->erp->update('journal_entries', [
                'status'        => 'CANCELLED',
                'cancelled_at'  => date('Y-m-d H:i:s'),
                'cancelled_by'  => $this->auth_username(),
                'updated_by'    => $this->auth_username(),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->erp->where('company_id', $this->company_id)
                ->where('voucher_type', 'JE')
                ->where('voucher_no', $je['je_no']);

            $this->apply_region_filter('region_id');

            $this->erp->update('gl_entries', [
                'status' => 'CANCELLED'
            ]);
        }

        $this->erp->where('closing_id', $closing_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('year_end_closings', [
            'status'        => 'CANCELLED',
            'cancelled_at'  => date('Y-m-d H:i:s'),
            'cancelled_by'  => $this->auth_username(),
        ]);

        $this->erp->where('company_id', $this->company_id)
            ->where('year', (int)$row['fiscal_year']);

        $this->apply_region_filter('region_id');

        $this->erp->update('fiscal_years', [
            'is_closed' => 0,
            'closed_at' => null,
            'closed_by' => null
        ]);

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
            'message' => 'Closing cancelled',
            'data'    => [
                'closing_id' => $closing_id,
                'region_id'  => $row['region_id']
            ]
        ];
    }

    private function _ensure_fiscal_year($year)
    {
        $year = (int)$year;
        $region_id = (string)$this->auth_region();

        $this->erp->from('fiscal_years')
            ->where('company_id', $this->company_id)
            ->where('year', $year);

        $this->apply_region_filter('region_id');

        $row = $this->erp->get()->row_array();

        if ($row) {
            return $row;
        }

        $start = $year . '-01-01';
        $end   = $year . '-12-31';

        $this->erp->insert('fiscal_years', [
            'company_id'  => $this->company_id,
            'region_id'   => $region_id,
            'year'        => $year,
            'start_date'  => $start,
            'end_date'    => $end,
            'is_closed'   => 0
        ]);

        $this->erp->from('fiscal_years')
            ->where('company_id', $this->company_id)
            ->where('year', $year);

        $this->apply_region_filter('region_id');

        return $this->erp->get()->row_array();
    }

    private function _validate_retained_account($account_id)
    {
        $account_id = (int)$account_id;

        $this->erp->select('account_id, root_type, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', $account_id);

        $this->apply_region_filter('region_id');

        $acc = $this->erp->get()->row_array();

        if (!$acc) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Retained earnings account not found'
            ];
        }

        if ($acc['root_type'] !== 'Equity') {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Retained earnings must be Equity'
            ];
        }

        if ((int)$acc['is_postable'] !== 1 || (int)$acc['is_active'] !== 1) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Retained earnings account must be active & postable'
            ];
        }

        return [
            'ok' => true
        ];
    }

    private function _calc_income_expense_net($start_date, $end_date)
    {
        $join = "
            g.account_id = a.account_id
            AND g.company_id = a.company_id
            AND g.status = 'POSTED'
            AND g.posting_date >= " . $this->erp->escape($start_date) . "
            AND g.posting_date <= " . $this->erp->escape($end_date) . "
        ";

        if (!$this->is_super_admin()) {
            $region_id = (string)$this->auth_region();

            if ($region_id !== '') {
                $join .= " AND g.region_id = " . $this->erp->escape($region_id);
            }
        }

        $this->erp->select("
                a.account_id,
                a.account_code,
                a.account_name,
                a.root_type,
                COALESCE(SUM(g.debit), 0) AS debit,
                COALESCE(SUM(g.credit), 0) AS credit
            ", false)
            ->from('accounts a')
            ->join('gl_entries g', $join, 'left', false)
            ->where('a.company_id', $this->company_id)
            ->where('a.is_active', 1)
            ->where('a.is_postable', 1)
            ->where_in('a.root_type', ['Income', 'Expense']);

        $this->apply_region_filter_alias('a', 'region_id');

        $rows = $this->erp->group_by('a.account_id')
            ->order_by('a.root_type', 'ASC')
            ->order_by('a.account_code', 'ASC')
            ->get()
            ->result_array();

        $income = [];
        $expense = [];
        $totIncome = 0.0;
        $totExpense = 0.0;

        foreach ($rows as $r) {
            $d = (float)$r['debit'];
            $c = (float)$r['credit'];

            if ($r['root_type'] === 'Income') {
                $net = $c - $d;

                if (abs($net) < 0.01) {
                    continue;
                }

                $income[] = [
                    'account_id'    => (int)$r['account_id'],
                    'account_code'  => $r['account_code'],
                    'account_name'  => $r['account_name'],
                    'net'           => $net
                ];

                $totIncome += $net;
            } else {
                $net = $d - $c;

                if (abs($net) < 0.01) {
                    continue;
                }

                $expense[] = [
                    'account_id'    => (int)$r['account_id'],
                    'account_code'  => $r['account_code'],
                    'account_name'  => $r['account_name'],
                    'net'           => $net
                ];

                $totExpense += $net;
            }
        }

        return [
            'income'  => $income,
            'expense' => $expense,
            'summary' => [
                'total_income'  => $totIncome,
                'total_expense' => $totExpense,
                'net_profit'    => $totIncome - $totExpense
            ]
        ];
    }

    private function _build_closing_lines($calc, $retained_account_id)
    {
        $lines = [];

        foreach ($calc['income'] as $inc) {
            $lines[] = [
                'account_id' => (int)$inc['account_id'],
                'debit'      => (float)$inc['net'],
                'credit'     => 0,
                'memo'       => 'Close Income: ' . $inc['account_code'] . ' ' . $inc['account_name']
            ];
        }

        foreach ($calc['expense'] as $exp) {
            $lines[] = [
                'account_id' => (int)$exp['account_id'],
                'debit'      => 0,
                'credit'     => (float)$exp['net'],
                'memo'       => 'Close Expense: ' . $exp['account_code'] . ' ' . $exp['account_name']
            ];
        }

        $net = (float)$calc['summary']['net_profit'];

        if (abs($net) < 0.01) {
            return $lines;
        }

        if ($net > 0) {
            $lines[] = [
                'account_id' => (int)$retained_account_id,
                'debit'      => 0,
                'credit'     => $net,
                'memo'       => 'Transfer Net Profit to Retained Earnings'
            ];
        } else {
            $lines[] = [
                'account_id' => (int)$retained_account_id,
                'debit'      => abs($net),
                'credit'     => 0,
                'memo'       => 'Transfer Net Loss to Retained Earnings'
            ];
        }

        return $lines;
    }

    private function _validate_je_lines($je_id)
    {
        $je_id = (int)$je_id;

        $lines = $this->erp->get_where('journal_entry_lines', [
            'je_id' => $je_id
        ])->result_array();

        if (count($lines) < 2) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'JE lines invalid'
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
                    'message' => 'JE line invalid'
                ];
            }

            $this->erp->select('is_postable, is_active')
                ->from('accounts')
                ->where('account_id', (int)$ln['account_id'])
                ->where('company_id', $this->company_id);

            $this->apply_region_filter('region_id');

            $acc = $this->erp->get()->row_array();

            if (!$acc || (int)$acc['is_active'] !== 1 || (int)$acc['is_postable'] !== 1) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Account not postable'
                ];
            }

            $td += $d;
            $tc += $c;
        }

        if (abs($td - $tc) > 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Debit != Credit'
            ];
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