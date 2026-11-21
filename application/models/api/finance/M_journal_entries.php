<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_journal_entries extends MY_Model
{
    private $company_id = 1;

    public function list($filters = [])
    {
        $this->erp
            ->select('je_id, company_id, region_id, je_no, posting_date, remark, status, created_by, created_at, updated_by, updated_at, submitted_by, submitted_at, cancelled_by, cancelled_at')
            ->from('journal_entries')
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        if (!empty($filters['status'])) {
            $this->erp->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $this->erp->where('posting_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->erp->where('posting_date <=', $filters['date_to']);
        }

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);

            $this->erp->group_start()
                ->like('je_no', $q)
                ->or_like('remark', $q)
                ->group_end();
        }

        $rows = $this->erp
            ->order_by('posting_date', 'DESC')
            ->order_by('je_id', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $totals = $this->_get_totals((int)$row['je_id']);
            $row['total_debit']  = $totals['total_debit'];
            $row['total_credit'] = $totals['total_credit'];
            $row['difference']   = $totals['difference'];
        }

        return $rows;
    }

    public function get($id)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return [
                'ok'      => false,
                'http'    => 400,
                'message' => 'Invalid Journal Entry ID',
                'errors'  => [
                    'je_id' => 'invalid'
                ],
            ];
        }

        $je = $this->_find_header($id);

        if (!$je) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Journal Entry not found',
                'data'    => null,
            ];
        }

        $lines = $this->_get_lines($id);
        $totals = $this->_calculate_totals($lines);

        $je['lines']        = $lines;
        $je['total_debit']  = $totals['total_debit'];
        $je['total_credit'] = $totals['total_credit'];
        $je['difference']   = $totals['difference'];
        $je['is_balanced']  = $totals['is_balanced'];

        return [
            'ok'      => true,
            'http'    => 200,
            'message' => 'OK',
            'data'    => $je,
        ];
    }

    public function create_draft($payload)
    {
        $posting_date = $this->_clean_date($payload['posting_date'] ?? date('Y-m-d'));
        $remark       = isset($payload['remark']) ? trim((string)$payload['remark']) : null;
        $lines        = $this->_normalize_lines($payload['lines'] ?? []);

        $val = $this->_validate_lines_for_save($lines);

        if (!$val['ok']) {
            return $val;
        }

        $region_id = (string)$this->auth_region();

        if ($region_id === '') {
            return [
                'ok'      => false,
                'http'    => 401,
                'message' => 'REGION_REQUIRED_RELOGIN',
                'errors'  => [
                    'region_id' => 'region kosong, silakan login ulang'
                ],
            ];
        }

        $year = (int)date('Y', strtotime($posting_date));
        $je_no = $this->_next_series('JE', $year);

        $this->erp->trans_begin();

        $this->erp->insert('journal_entries', [
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'je_no'        => $je_no,
            'posting_date' => $posting_date,
            'remark'       => $remark,
            'status'       => 'DRAFT',
            'created_by'   => $this->auth_username(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $je_id = (int)$this->erp->insert_id();

        if ($je_id <= 0) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'Failed to create Journal Entry header',
                'data'    => null,
            ];
        }

        $this->_insert_lines($je_id, $lines);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error while creating Journal Entry',
                'data'    => null,
            ];
        }

        $this->erp->trans_commit();

		return [
			'ok'      => true,
			'http'    => 201,
			'message' => 'Draft created',
			'data'    => [
				'je_id'        => $je_id,
				'id'           => $je_id,
				'je_no'        => $je_no,
				'posting_date' => $posting_date,
				'remark'       => $remark,
				'status'       => 'DRAFT',
			],
		];
    }

    public function update_draft($id, $payload)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return [
                'ok'      => false,
                'http'    => 400,
                'message' => 'Invalid Journal Entry ID',
                'errors'  => [
                    'je_id' => 'invalid'
                ],
            ];
        }

        $je = $this->_find_header($id);

        if (!$je) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Journal Entry not found',
                'data'    => null,
            ];
        }

        if ($je['status'] !== 'DRAFT') {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Only DRAFT can be updated',
                'data'    => null,
            ];
        }

        $posting_date = $this->_clean_date($payload['posting_date'] ?? $je['posting_date']);
        $remark       = array_key_exists('remark', $payload)
            ? trim((string)$payload['remark'])
            : $je['remark'];

        $has_lines = array_key_exists('lines', $payload);
        $lines = [];

        if ($has_lines) {
            $lines = $this->_normalize_lines($payload['lines'] ?? []);

            $val = $this->_validate_lines_for_save($lines);

            if (!$val['ok']) {
                return $val;
            }
        }

        $this->erp->trans_begin();

        $this->erp
            ->where('je_id', $id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('journal_entries', [
            'posting_date' => $posting_date,
            'remark'       => $remark,
            'updated_by'   => $this->auth_username(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($has_lines) {
            $this->erp->where('je_id', $id)->delete('journal_entry_lines');
            $this->_insert_lines($id, $lines);
        }

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error while updating Journal Entry',
                'data'    => null,
            ];
        }

        $this->erp->trans_commit();

        $res = $this->get($id);
        $res['message'] = 'Draft updated';

        return $res;
    }

    public function submit($id)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return [
                'ok'      => false,
                'http'    => 400,
                'message' => 'Invalid Journal Entry ID',
                'errors'  => [
                    'je_id' => 'invalid'
                ],
            ];
        }

        $je = $this->_find_header($id);

        if (!$je) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Journal Entry not found',
                'data'    => null,
            ];
        }

        if ($je['status'] !== 'DRAFT') {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Only DRAFT can be submitted',
                'data'    => null,
            ];
        }

        $lines = $this->_get_lines($id);

        $val = $this->_validate_lines_for_submit($lines);

        if (!$val['ok']) {
            return $val;
        }

        $this->erp->trans_begin();

        $this->erp
            ->where('je_id', $id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('journal_entries', [
            'status'       => 'SUBMITTED',
            'submitted_at' => date('Y-m-d H:i:s'),
            'submitted_by' => $this->auth_username(),
            'updated_by'   => $this->auth_username(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        foreach ($lines as $ln) {
            $debit  = (float)$ln['debit'];
            $credit = (float)$ln['credit'];

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $this->erp->insert('gl_entries', [
                'company_id'   => $this->company_id,
                'region_id'    => (string)$je['region_id'],
                'posting_date' => $je['posting_date'],
                'account_id'   => (int)$ln['account_id'],
                'party_id'     => !empty($ln['party_id']) ? (int)$ln['party_id'] : null,
                'debit'        => $debit,
                'credit'       => $credit,
                'voucher_type' => 'JE',
                'voucher_no'   => $je['je_no'],
                'status'       => 'POSTED',
            ]);
        }

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error while submitting Journal Entry',
                'data'    => null,
            ];
        }

        $this->erp->trans_commit();

        $res = $this->get($id);
        $res['message'] = 'Submitted';

        return $res;
    }

    public function cancel($id)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return [
                'ok'      => false,
                'http'    => 400,
                'message' => 'Invalid Journal Entry ID',
                'errors'  => [
                    'je_id' => 'invalid'
                ],
            ];
        }

        $je = $this->_find_header($id);

        if (!$je) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'Journal Entry not found',
                'data'    => null,
            ];
        }

        if ($je['status'] !== 'SUBMITTED') {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Only SUBMITTED can be cancelled',
                'data'    => null,
            ];
        }

        $this->erp->trans_begin();

        $this->erp
            ->where('je_id', $id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $this->erp->update('journal_entries', [
            'status'       => 'CANCELLED',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $this->auth_username(),
            'updated_by'   => $this->auth_username(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->erp
            ->where('company_id', $this->company_id)
            ->where('voucher_type', 'JE')
            ->where('voucher_no', $je['je_no']);

        $this->apply_region_filter('region_id');

        $this->erp->update('gl_entries', [
            'status' => 'CANCELLED',
        ]);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error while cancelling Journal Entry',
                'data'    => null,
            ];
        }

        $this->erp->trans_commit();

        $res = $this->get($id);
        $res['message'] = 'Cancelled';

        return $res;
    }

    private function _find_header($id)
    {
        $this->erp
            ->from('journal_entries')
            ->where('je_id', (int)$id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        return $this->erp->get()->row_array();
    }

    private function _get_lines($je_id)
    {
        return $this->erp
            ->select('jel.*, a.account_code, a.account_name')
            ->from('journal_entry_lines jel')
            ->join('accounts a', 'a.account_id = jel.account_id', 'left')
            ->where('jel.je_id', (int)$je_id)
            ->order_by('jel.line_no', 'ASC')
            ->get()
            ->result_array();
    }

    private function _insert_lines($je_id, $lines)
    {
        $line_no = 1;

        foreach ($lines as $ln) {
            $debit  = (float)($ln['debit'] ?? 0);
            $credit = (float)($ln['credit'] ?? 0);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $this->erp->insert('journal_entry_lines', [
                'je_id'      => (int)$je_id,
                'line_no'    => $line_no++,
                'account_id' => (int)($ln['account_id'] ?? 0),
                'party_id'   => !empty($ln['party_id']) ? (int)$ln['party_id'] : null,
                'memo'       => !empty($ln['memo']) ? trim((string)$ln['memo']) : null,
                'debit'      => $debit,
                'credit'     => $credit,
            ]);
        }
    }

    private function _normalize_lines($lines)
    {
        if (!is_array($lines)) {
            return [];
        }

        $out = [];

        foreach ($lines as $ln) {
            if (!is_array($ln)) {
                continue;
            }

            $account_id = (int)($ln['account_id'] ?? 0);
            $debit      = $this->_num($ln['debit'] ?? 0);
            $credit     = $this->_num($ln['credit'] ?? 0);

            if ($account_id <= 0 && $debit <= 0 && $credit <= 0) {
                continue;
            }

            $out[] = [
                'account_id' => $account_id,
                'party_id'   => !empty($ln['party_id']) ? (int)$ln['party_id'] : null,
                'memo'       => !empty($ln['memo']) ? trim((string)$ln['memo']) : null,
                'debit'      => $debit,
                'credit'     => $credit,
            ];
        }

        return $out;
    }

    private function _validate_lines_for_save($lines)
    {
        if (!is_array($lines) || count($lines) < 2) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Lines minimal 2',
                'errors'  => [
                    'lines' => 'minimal 2 baris account dengan nominal debit atau credit'
                ],
            ];
        }

        foreach ($lines as $i => $ln) {
            $line_no = $i + 1;
            $account_id = (int)($ln['account_id'] ?? 0);
            $debit  = (float)($ln['debit'] ?? 0);
            $credit = (float)($ln['credit'] ?? 0);

            if ($account_id <= 0) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Account wajib diisi',
                    'errors'  => [
                        'line_' . $line_no => 'account wajib diisi'
                    ],
                ];
            }

            if ($debit > 0 && $credit > 0) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Line invalid',
                    'errors'  => [
                        'line_' . $line_no => 'debit dan credit tidak boleh sama-sama terisi'
                    ],
                ];
            }

            if ($debit <= 0 && $credit <= 0) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Line invalid',
                    'errors'  => [
                        'line_' . $line_no => 'debit atau credit harus diisi'
                    ],
                ];
            }

            $acc = $this->_find_account($account_id);

            if (!$acc) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Account not found',
                    'errors'  => [
                        'line_' . $line_no => 'akun tidak ditemukan'
                    ],
                ];
            }

            if ((int)$acc['is_active'] !== 1 || (int)$acc['is_postable'] !== 1) {
                return [
                    'ok'      => false,
                    'http'    => 422,
                    'message' => 'Account not postable',
                    'errors'  => [
                        'line_' . $line_no => 'akun tidak aktif / tidak postable'
                    ],
                ];
            }
        }

        return [
            'ok' => true
        ];
    }

    private function _validate_lines_for_submit($lines)
    {
        $val = $this->_validate_lines_for_save($lines);

        if (!$val['ok']) {
            return $val;
        }

        $totals = $this->_calculate_totals($lines);

        if ($totals['total_debit'] <= 0 || $totals['total_credit'] <= 0) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Debit dan Credit harus lebih dari 0',
                'errors'  => [
                    'total_debit'  => $totals['total_debit'],
                    'total_credit' => $totals['total_credit'],
                ],
            ];
        }

        if (!$totals['is_balanced']) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Debit tidak sama dengan Credit',
                'errors'  => [
                    'total_debit'  => $totals['total_debit'],
                    'total_credit' => $totals['total_credit'],
                    'difference'   => $totals['difference'],
                ],
            ];
        }

        return [
            'ok' => true
        ];
    }

    private function _find_account($account_id)
    {
        $this->erp
            ->select('account_id, is_postable, is_active')
            ->from('accounts')
            ->where('account_id', (int)$account_id)
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        return $this->erp->get()->row_array();
    }

    private function _get_totals($je_id)
    {
        $row = $this->erp
            ->select('COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit', false)
            ->from('journal_entry_lines')
            ->where('je_id', (int)$je_id)
            ->get()
            ->row_array();

        $total_debit  = round((float)($row['total_debit'] ?? 0), 2);
        $total_credit = round((float)($row['total_credit'] ?? 0), 2);
        $difference   = round($total_debit - $total_credit, 2);

        return [
            'total_debit'  => $total_debit,
            'total_credit' => $total_credit,
            'difference'   => $difference,
        ];
    }

    private function _calculate_totals($lines)
    {
        $total_debit  = 0.0;
        $total_credit = 0.0;

        foreach ((array)$lines as $ln) {
            $total_debit  += (float)($ln['debit'] ?? 0);
            $total_credit += (float)($ln['credit'] ?? 0);
        }

        $total_debit  = round($total_debit, 2);
        $total_credit = round($total_credit, 2);
        $difference   = round($total_debit - $total_credit, 2);

        return [
            'total_debit'  => $total_debit,
            'total_credit' => $total_credit,
            'difference'   => $difference,
            'is_balanced'  => abs($difference) <= 0.01,
        ];
    }

    private function _clean_date($date)
    {
        $date = trim((string)$date);

        if ($date === '') {
            return date('Y-m-d');
        }

        $ts = strtotime($date);

        if (!$ts) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $ts);
    }

    private function _num($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return round((float)$value, 2);
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