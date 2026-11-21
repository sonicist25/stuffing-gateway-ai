<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_customer_deposit extends MY_Model
{
    protected $company_id = 1;

    public function available($p)
	{
		$party_code = trim((string)($p['party_code'] ?? ''));
		$currency   = strtoupper(trim((string)($p['currency'] ?? 'IDR')));

		if ($currency === 'RP.' || $currency === 'RP' || $currency === 'RUPIAH') {
			$currency = 'IDR';
		}

		$errs = [];
		if ($party_code === '') $errs['party_code'] = 'required';
		if ($currency === '') $errs['currency'] = 'required';

		if ($errs) {
			return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
		}

		$this->erp
			->select("
				COALESCE(SUM(balance_doc),0) AS available_doc,
				COALESCE(SUM(balance_idr),0) AS available_idr
			", false)
			->from('customer_deposits')
			->where('company_id', $this->company_id)
			->where('party_code', $party_code)
			->where('currency', $currency)
			->where('balance_doc >', 0, false)
			->where("COALESCE(status,'OPEN') <>", 'CANCELLED');

		$this->_apply_region_filter();

		$row = $this->erp->get()->row_array();

		return [
			'ok'      => true,
			'message' => 'OK',
			'data'    => [
				'party_code'    => $party_code,
				'currency'      => $currency,
				'available_doc' => (float)($row['available_doc'] ?? 0),
				'available_idr' => (float)($row['available_idr'] ?? 0),
			]
		];
	}

    public function create_from_overpayment($p)
    {
        $party_code          = trim((string)($p['party_code'] ?? ''));
        $party_name          = trim((string)($p['party_name'] ?? ''));
        $currency            = strtoupper(trim((string)($p['currency'] ?? 'IDR')));
        $source_rate         = (float)($p['source_rate'] ?? 1);
        $amount_doc          = (float)($p['amount_doc'] ?? 0);
        $amount_idr          = (float)($p['amount_idr'] ?? 0);
        $source_voucher_type = trim((string)($p['source_voucher_type'] ?? ''));
        $source_voucher_no   = trim((string)($p['source_voucher_no'] ?? ''));
        $source_rcpt_id      = (int)($p['source_rcpt_id'] ?? 0);
        $deposit_account_id  = (int)($p['deposit_account_id'] ?? 0);
        $posting_date        = trim((string)($p['posting_date'] ?? ''));
        $remark              = trim((string)($p['remark'] ?? ''));
        $created_by          = $p['created_by'] ?? $this->auth_username();

        $errs = [];
        if ($party_code === '') $errs['party_code'] = 'required';
        if ($currency === '') $errs['currency'] = 'required';
        if ($amount_doc <= 0) $errs['amount_doc'] = 'must be > 0';
        if ($amount_idr <= 0) $errs['amount_idr'] = 'must be > 0';
        if ($source_voucher_type === '') $errs['source_voucher_type'] = 'required';
        if ($source_voucher_no === '') $errs['source_voucher_no'] = 'required';
        if ($deposit_account_id <= 0) $errs['deposit_account_id'] = 'required';
        if ($posting_date === '') $errs['posting_date'] = 'required';

        if (!$this->_valid_postable_account($deposit_account_id)) {
            $errs['deposit_account_id'] = 'invalid / not postable';
        }

        if ($errs) {
            return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
        }

        $this->erp->from('customer_deposits')
            ->where('company_id', $this->company_id)
            ->where('source_voucher_type', $source_voucher_type)
            ->where('source_voucher_no', $source_voucher_no);

        $this->_apply_region_filter();
        $exists = $this->erp->get()->row_array();

        if ($exists) {
            return [
                'ok'      => true,
                'message' => 'Deposit already exists',
                'data'    => ['deposit_id' => (int)$exists['deposit_id']]
            ];
        }

        $data = [
            'company_id'          => $this->company_id,
            'region_id'           => $this->_region_id(),
            'party_type'          => 'CUSTOMER',
            'party_code'          => $party_code,
            'party_name'          => $party_name ?: $party_code,
            'currency'            => $currency,
            'source_rate'         => ($source_rate > 0 ? $source_rate : 1),
            'amount_doc'          => $amount_doc,
            'used_doc'            => 0,
            'balance_doc'         => $amount_doc,
            'amount_idr'          => $amount_idr,
            'used_idr'            => 0,
            'balance_idr'         => $amount_idr,
            'source_voucher_type' => $source_voucher_type,
            'source_voucher_no'   => $source_voucher_no,
            'source_rcpt_id'      => ($source_rcpt_id > 0 ? $source_rcpt_id : null),
            'deposit_account_id'  => $deposit_account_id,
            'posting_date'        => $posting_date,
            'status'              => 'OPEN',
            'remark'              => ($remark !== '' ? $remark : null),
            'created_by'          => $created_by,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        $this->erp->insert('customer_deposits', $data);
        $deposit_id = (int)$this->erp->insert_id();

        if ($deposit_id <= 0) {
            return ['ok'=>false,'http'=>500,'message'=>'FAILED_CREATE_DEPOSIT'];
        }

        return [
            'ok'      => true,
            'message' => 'Deposit created',
            'data'    => [
                'deposit_id' => $deposit_id,
                'region_id'  => $this->_region_id(),
            ]
        ];
    }

    public function apply_fifo($p)
	{
		$party_code       = trim((string)($p['party_code'] ?? ''));
		$currency         = strtoupper(trim((string)($p['currency'] ?? 'IDR')));
		$amount_doc       = (float)($p['amount_doc'] ?? 0);
		$apply_voucher_no = trim((string)($p['apply_voucher_no'] ?? ''));
		$apply_rcpt_id    = (int)($p['apply_rcpt_id'] ?? 0);
		$posting_date     = trim((string)($p['posting_date'] ?? ''));
		$remark           = trim((string)($p['remark'] ?? ''));
		$created_by       = $p['created_by'] ?? $this->auth_username();

		if ($currency === 'RP.' || $currency === 'RP' || $currency === 'RUPIAH') {
			$currency = 'IDR';
		}

		$errs = [];
		if ($party_code === '') $errs['party_code'] = 'required';
		if ($currency === '') $errs['currency'] = 'required';
		if ($amount_doc <= 0) $errs['amount_doc'] = 'must be > 0';
		if ($apply_voucher_no === '') $errs['apply_voucher_no'] = 'required';
		if ($posting_date === '') $errs['posting_date'] = 'required';

		if ($errs) {
			return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];
		}

		/**
		 * Penting:
		 * Jangan trans_begin/trans_commit di sini.
		 * Function ini harus ikut transaksi parent dari M_receive_payment::receive().
		 */

		$region_id = $this->_region_id();

		if ($region_id === '') {
			return [
				'ok'      => false,
				'http'    => 422,
				'message' => 'REGION_ID_REQUIRED',
			];
		}

		/**
		 * Ambil deposit dengan lock.
		 * Ini mencegah 2 proses memakai deposit yang sama bersamaan.
		 *
		 * Catatan:
		 * FOR UPDATE efektif kalau caller sudah memulai transaction:
		 * $this->erp->trans_begin();
		 */
		$sql = "
			SELECT *
			FROM customer_deposits
			WHERE company_id = ?
			  AND region_id = ?
			  AND party_code = ?
			  AND currency = ?
			  AND balance_doc > 0
			  AND COALESCE(status,'OPEN') <> 'CANCELLED'
			ORDER BY posting_date ASC, deposit_id ASC
			FOR UPDATE
		";

		$lots = $this->erp
			->query($sql, [
				$this->company_id,
				$region_id,
				$party_code,
				$currency,
			])
			->result_array();

		$available_doc = 0.0;

		foreach ($lots as $lot) {
			$available_doc += (float)($lot['balance_doc'] ?? 0);
		}

		if ($amount_doc - $available_doc > 0.01) {
			return [
				'ok'      => false,
				'http'    => 422,
				'message' => 'DEPOSIT_EXCEEDS_AVAILABLE',
				'data'    => [
					'deposit_used'      => round($amount_doc, 2),
					'available_deposit' => round($available_doc, 2),
					'party_code'        => $party_code,
					'currency'          => $currency,
				]
			];
		}

		if (!$lots) {
			return ['ok'=>false,'http'=>422,'message'=>'NO_DEPOSIT_AVAILABLE'];
		}

		$remaining = $amount_doc;
		$total_used_doc = 0.0;
		$total_used_idr = 0.0;
		$deposit_account_id = 0;
		$applied_rows = [];

		foreach ($lots as $lot) {
			if ($remaining <= 0.01) {
				break;
			}

			$deposit_id = (int)$lot['deposit_id'];
			$lot_balance_doc = (float)$lot['balance_doc'];

			if ($lot_balance_doc <= 0.01) {
				continue;
			}

			$use_doc = min($remaining, $lot_balance_doc);

			$lot_amount_doc = (float)$lot['amount_doc'];
			$lot_amount_idr = (float)$lot['amount_idr'];

			/**
			 * Pakai rate lot, bukan payment rate.
			 */
			$rate = ($lot_amount_doc > 0)
				? ($lot_amount_idr / $lot_amount_doc)
				: 1;

			if ($rate <= 0) {
				$rate = 1;
			}

			$use_idr = round($use_doc * $rate, 2);

			if ($deposit_account_id <= 0) {
				$deposit_account_id = (int)$lot['deposit_account_id'];
			}

			/**
			 * Insert allocation dulu.
			 * Kalau proses receipt gagal setelah ini, parent transaction akan rollback.
			 */
			$this->erp->insert('customer_deposit_allocations', [
				'company_id'          => $this->company_id,
				'region_id'           => $region_id,
				'deposit_id'          => $deposit_id,
				'apply_voucher_type'  => 'RCPT',
				'apply_voucher_no'    => $apply_voucher_no,
				'apply_rcpt_id'       => ($apply_rcpt_id > 0 ? $apply_rcpt_id : null),
				'currency'            => $currency,
				'amount_doc'          => round($use_doc, 2),
				'amount_idr'          => round($use_idr, 2),
				'posting_date'        => $posting_date,
				'status'              => 'APPLIED',
				'remark'              => ($remark !== '' ? $remark : null),
				'created_by'          => $created_by,
				'created_at'          => date('Y-m-d H:i:s'),
				'updated_at'          => date('Y-m-d H:i:s'),
			]);

			if ($this->erp->affected_rows() <= 0) {
				return [
					'ok'      => false,
					'http'    => 500,
					'message' => 'FAILED_INSERT_DEPOSIT_ALLOCATION',
					'data'    => [
						'db_error'   => $this->erp->error(),
						'last_query' => $this->erp->last_query(),
					],
				];
			}

			/**
			 * Update lot secara atomic.
			 */
			$this->erp
				->where('company_id', $this->company_id)
				->where('region_id', $region_id)
				->where('deposit_id', $deposit_id)
				->set('used_doc', 'used_doc + ' . $this->erp->escape(round($use_doc, 2)), false)
				->set('balance_doc', 'balance_doc - ' . $this->erp->escape(round($use_doc, 2)), false)
				->set('used_idr', 'used_idr + ' . $this->erp->escape(round($use_idr, 2)), false)
				->set('balance_idr', 'balance_idr - ' . $this->erp->escape(round($use_idr, 2)), false)
				->set('updated_at', $this->erp->escape(date('Y-m-d H:i:s')), false)
				->update('customer_deposits');

			if ($this->erp->affected_rows() <= 0) {
				return [
					'ok'      => false,
					'http'    => 500,
					'message' => 'FAILED_UPDATE_DEPOSIT_LOT',
					'data'    => [
						'deposit_id' => $deposit_id,
						'db_error'   => $this->erp->error(),
						'last_query' => $this->erp->last_query(),
					],
				];
			}

			/**
			 * Set status berdasarkan balance terbaru.
			 * Dipisah supaya tidak tergantung expression update di atas.
			 */
			$this->erp
				->where('company_id', $this->company_id)
				->where('region_id', $region_id)
				->where('deposit_id', $deposit_id)
				->where('balance_doc <=', 0.01)
				->update('customer_deposits', [
					'balance_doc' => 0,
					'balance_idr' => 0,
					'status'      => 'USED',
					'updated_at'  => date('Y-m-d H:i:s'),
				]);

			$this->erp
				->where('company_id', $this->company_id)
				->where('region_id', $region_id)
				->where('deposit_id', $deposit_id)
				->where('balance_doc >', 0.01)
				->update('customer_deposits', [
					'status'     => 'OPEN',
					'updated_at' => date('Y-m-d H:i:s'),
				]);

			$remaining -= $use_doc;
			$total_used_doc += $use_doc;
			$total_used_idr += $use_idr;

			$applied_rows[] = [
				'deposit_id' => $deposit_id,
				'amount_doc' => round($use_doc, 2),
				'amount_idr' => round($use_idr, 2),
				'rate'       => round($rate, 6),
			];
		}

		if ($remaining > 0.01) {
			/**
			 * Karena function ini ikut transaksi parent,
			 * caller wajib rollback jika menerima ok=false.
			 */
			return [
				'ok'      => false,
				'http'    => 422,
				'message' => 'DEPOSIT_EXCEEDS_AVAILABLE',
				'data'    => [
					'deposit_used'      => round($amount_doc, 2),
					'available_deposit' => round($total_used_doc, 2),
					'remaining_doc'     => round($remaining, 2),
					'party_code'        => $party_code,
					'currency'          => $currency,
				],
			];
		}

		return [
			'ok'      => true,
			'message' => 'Deposit applied',
			'data'    => [
				'party_code'         => $party_code,
				'currency'           => $currency,
				'used_doc'           => round($total_used_doc, 2),
				'total_used_doc'     => round($total_used_doc, 2),
				'total_used_idr'     => round($total_used_idr, 2),
				'deposit_account_id' => $deposit_account_id,
				'region_id'          => $region_id,
				'rows'               => $applied_rows,
			]
		];
	}

    public function refund($p)
    {
        $company_id      = $this->company_id;
        $party_code      = trim((string)($p['party_code'] ?? ''));
        $posting_date    = trim((string)($p['posting_date'] ?? ''));
        $cash_account_id = (int)($p['cash_account_id'] ?? 0);
        $currency        = strtoupper(trim((string)($p['currency'] ?? 'IDR')));
        $amount_doc      = (float)($p['amount_doc'] ?? 0);
        $remark          = trim((string)($p['remark'] ?? ''));
        $created_by      = $p['created_by'] ?? $this->auth_username();

        $errs = [];
        if ($party_code === '') $errs['party_code'] = 'required';
        if ($posting_date === '') $errs['posting_date'] = 'required';
        if ($cash_account_id <= 0) $errs['cash_account_id'] = 'required';
        if ($currency === '') $errs['currency'] = 'required';
        if ($amount_doc <= 0) $errs['amount_doc'] = 'must be > 0';

        if ($errs) return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>$errs];

        if ($this->_is_locked($posting_date)) {
            return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
        }

        if (!$this->_valid_postable_account($cash_account_id)) {
            return ['ok'=>false,'http'=>422,'message'=>'Invalid cash account'];
        }

        $avail = $this->available(['party_code' => $party_code, 'currency' => $currency]);
        if (!$avail['ok']) return $avail;

        $available_doc = (float)($avail['data']['available_doc'] ?? 0);
        if ($amount_doc - $available_doc > 0.01) {
            return [
                'ok'=>false,'http'=>422,'message'=>'REFUND_EXCEEDS_AVAILABLE_DEPOSIT',
                'data'=>['party_code'=>$party_code,'currency'=>$currency,'refund_amount'=>$amount_doc,'available_doc'=>$available_doc]
            ];
        }

        $this->erp->from('customer_deposits')
            ->where('company_id', $company_id)
            ->where('party_code', $party_code)
            ->where('currency', $currency)
            ->where('status', 'OPEN')
            ->where('balance_doc >', 0, false);

        $this->_apply_region_filter();

        $lots = $this->erp->order_by('posting_date', 'ASC')
            ->order_by('deposit_id', 'ASC')
            ->get()->result_array();

        if (!$lots) return ['ok'=>false,'http'=>422,'message'=>'NO_DEPOSIT_AVAILABLE'];

        $deposit_account_id = (int)($lots[0]['deposit_account_id'] ?? 0);
        if ($deposit_account_id <= 0) return ['ok'=>false,'http'=>422,'message'=>'INVALID_DEPOSIT_ACCOUNT'];

        $year = (int)date('Y', strtotime($posting_date));
        $prefix = 'DREF-' . $year . '-';

        $this->erp->select("MAX(CAST(RIGHT(refund_no,4) AS UNSIGNED)) AS mx", false)
            ->from('customer_deposit_refunds')
            ->where('company_id', $company_id)
            ->like('refund_no', $prefix, 'after');
        $this->_apply_region_filter();
        $rowNo = $this->erp->get()->row_array();

        $nextNo = (int)($rowNo['mx'] ?? 0) + 1;
        $refund_no = $prefix . str_pad($nextNo, 4, '0', STR_PAD_LEFT);

        $this->erp->trans_begin();

        $remaining = $amount_doc;
        $total_refund_idr = 0.0;
        $party_name = '';

        foreach ($lots as $lot) {
            if ($remaining <= 0.01) break;

            $deposit_id   = (int)$lot['deposit_id'];
            $balance_doc  = (float)$lot['balance_doc'];
            $amountLotDoc = (float)$lot['amount_doc'];
            $amountLotIdr = (float)$lot['amount_idr'];

            if ($balance_doc <= 0.01) continue;

            $use_doc = min($remaining, $balance_doc);
            $rate = ($amountLotDoc > 0) ? ($amountLotIdr / $amountLotDoc) : 1;
            if ($rate <= 0) $rate = 1;

            $use_idr = round($use_doc * $rate, 2);

            $new_used_doc    = (float)$lot['used_doc'] + $use_doc;
            $new_balance_doc = (float)$lot['balance_doc'] - $use_doc;
            $new_used_idr    = (float)$lot['used_idr'] + $use_idr;
            $new_balance_idr = (float)$lot['balance_idr'] - $use_idr;
            if ($new_balance_doc < 0) $new_balance_doc = 0;
            if ($new_balance_idr < 0) $new_balance_idr = 0;

            $new_status = ($new_balance_doc <= 0.01) ? 'USED' : 'OPEN';

            $this->erp->where('company_id', $company_id)
                ->where('deposit_id', $deposit_id);
            $this->_apply_region_filter();
            $this->erp->update('customer_deposits', [
                'used_doc'    => round($new_used_doc, 2),
                'balance_doc' => round($new_balance_doc, 2),
                'used_idr'    => round($new_used_idr, 2),
                'balance_idr' => round($new_balance_idr, 2),
                'status'      => $new_status,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $party_name = (string)($lot['party_name'] ?? $party_code);

            $this->erp->insert('customer_deposit_refunds', [
                'company_id'          => $company_id,
                'region_id'           => $this->_region_id(),
                'deposit_id'          => $deposit_id,
                'party_code'          => $party_code,
                'party_name'          => $party_name,
                'refund_no'           => $refund_no,
                'refund_voucher_type' => 'DREF',
                'refund_voucher_no'   => $refund_no,
                'posting_date'        => $posting_date,
                'cash_account_id'     => $cash_account_id,
                'currency'            => $currency,
                'refund_rate'         => round($rate, 6),
                'amount_doc'          => round($use_doc, 2),
                'amount_idr'          => round($use_idr, 2),
                'status'              => 'POSTED',
                'remark'              => ($remark !== '' ? $remark : null),
                'created_by'          => $created_by,
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            $total_refund_idr += $use_idr;
            $remaining -= $use_doc;
        }

        if ($remaining > 0.01) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'FAILED_TO_APPLY_FULL_REFUND','data'=>['remaining_doc'=>round($remaining,2)]];
        }

        $this->erp->insert('gl_entries', [
            'company_id'   => $company_id,
            'region_id'    => $this->_region_id(),
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => $deposit_account_id,
            'party_id'     => null,
            'debit'        => round($total_refund_idr, 2),
            'credit'       => 0,
            'voucher_type' => 'DREF',
            'voucher_no'   => $refund_no,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->erp->insert('gl_entries', [
            'company_id'   => $company_id,
            'region_id'    => $this->_region_id(),
            'posting_date' => $posting_date,
            'job_no'       => null,
            'account_id'   => $cash_account_id,
            'party_id'     => null,
            'debit'        => 0,
            'credit'       => round($total_refund_idr, 2),
            'voucher_type' => 'DREF',
            'voucher_no'   => $refund_no,
            'status'       => 'POSTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'DB error'];
        }

        $this->erp->trans_commit();

        return [
            'ok'=>true,
            'message'=>'Deposit refunded',
            'data'=>[
                'refund_no'=>$refund_no,
                'region_id'=>$this->_region_id(),
                'party_code'=>$party_code,
                'party_name'=>$party_name,
                'currency'=>$currency,
                'amount_doc'=>round($amount_doc,2),
                'amount_idr'=>round($total_refund_idr,2),
                'cash_account_id'=>$cash_account_id,
                'deposit_account_id'=>$deposit_account_id,
            ]
        ];
    }

    public function list_refunds($p)
    {
        $this->erp->select("refund_no, posting_date, party_code, party_name, cash_account_id, currency, SUM(amount_doc) AS amount_doc, SUM(amount_idr) AS amount_idr, status, MAX(created_at) AS created_at", false)
            ->from('customer_deposit_refunds')
            ->where('company_id', $this->company_id);

        $this->_apply_region_filter();

        if (!empty($p['search'])) {
            $q = trim((string)$p['search']);
            $this->erp->group_start()
                ->like('refund_no', $q)
                ->or_like('party_code', $q)
                ->or_like('party_name', $q)
            ->group_end();
        }

        if (!empty($p['status'])) {
            $this->erp->where('status', strtoupper((string)$p['status']));
        }

        if (!empty($p['date_from'])) $this->erp->where('posting_date >=', $p['date_from']);
        if (!empty($p['date_to'])) $this->erp->where('posting_date <=', $p['date_to']);

        $this->erp->group_by(['refund_no','posting_date','party_code','party_name','cash_account_id','currency','status']);
        $this->erp->order_by('posting_date', 'DESC')->order_by('refund_no', 'DESC');

        $rows = $this->erp->get()->result_array();

        if ($rows) {
            $ids = array_values(array_unique(array_map(function($x){ return (int)$x['cash_account_id']; }, $rows)));
            $map = [];

            if ($ids) {
                $this->erp->select('account_id,account_code,account_name')
                    ->from('accounts')
                    ->where('company_id', $this->company_id)
                    ->where_in('account_id', $ids);
                $this->_apply_region_filter();
                $acc = $this->erp->get()->result_array();

                foreach ($acc as $a) {
                    $map[(int)$a['account_id']] = $a['account_code'].' - '.$a['account_name'];
                }
            }

            foreach ($rows as &$r) {
                $r['cash_account_label'] = $map[(int)$r['cash_account_id']] ?? null;
                $r['amount_doc'] = (float)$r['amount_doc'];
                $r['amount_idr'] = (float)$r['amount_idr'];
            }
            unset($r);
        }

        return $rows;
    }

    public function get_refund($refund_no)
    {
        $this->erp->select("refund_no, posting_date, party_code, party_name, cash_account_id, currency, status, remark, MAX(created_at) AS created_at", false)
            ->from('customer_deposit_refunds')
            ->where('company_id', $this->company_id)
            ->where('refund_no', $refund_no);

        $this->_apply_region_filter();

        $hdr = $this->erp->group_by(['refund_no','posting_date','party_code','party_name','cash_account_id','currency','status','remark'])
            ->get()->row_array();

        if (!$hdr) return null;

        $this->erp->select("refund_id, deposit_id, refund_voucher_type, refund_voucher_no, refund_rate, amount_doc, amount_idr, status, remark, created_at", false)
            ->from('customer_deposit_refunds')
            ->where('company_id', $this->company_id)
            ->where('refund_no', $refund_no);

        $this->_apply_region_filter();

        $rows = $this->erp->order_by('refund_id', 'ASC')->get()->result_array();

        foreach ($rows as &$r) {
            $r['refund_rate'] = (float)$r['refund_rate'];
            $r['amount_doc']  = (float)$r['amount_doc'];
            $r['amount_idr']  = (float)$r['amount_idr'];
        }
        unset($r);

        $this->erp->select('account_code,account_name')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', (int)$hdr['cash_account_id']);
        $this->_apply_region_filter();
        $acc = $this->erp->get()->row_array();

        $hdr['cash_account_label'] = $acc ? ($acc['account_code'].' - '.$acc['account_name']) : null;
        $hdr['rows'] = $rows;

        return $hdr;
    }

    public function cancel_refund($refund_no)
    {
        $refund_no = trim((string)$refund_no);
        if ($refund_no === '') {
            return ['ok'=>false,'http'=>422,'message'=>'Validation error','errors'=>['refund_no'=>'required']];
        }

        $this->erp->from('customer_deposit_refunds')
            ->where('company_id', $this->company_id)
            ->where('refund_no', $refund_no);
        $this->_apply_region_filter();
        $rows = $this->erp->get()->result_array();

        if (!$rows) return ['ok'=>false,'http'=>404,'message'=>'Refund not found'];

        $status = strtoupper((string)($rows[0]['status'] ?? 'POSTED'));
        if ($status === 'CANCELLED') return ['ok'=>false,'http'=>409,'message'=>'Refund already cancelled'];

        $posting_date = (string)($rows[0]['posting_date'] ?? '');
        if ($posting_date !== '' && $this->_is_locked($posting_date)) {
            return ['ok'=>false,'http'=>409,'message'=>'PERIOD_LOCKED'];
        }

        $lock = $this->_get_reconciled_lock('DREF', $refund_no);
        if ($lock) {
            return ['ok'=>false,'http'=>409,'message'=>'CANNOT_CANCEL_RECONCILED','data'=>['lock'=>$lock]];
        }

        $this->erp->trans_begin();

        foreach ($rows as $r) {
            $deposit_id = (int)($r['deposit_id'] ?? 0);
            $amount_doc = (float)($r['amount_doc'] ?? 0);
            $amount_idr = (float)($r['amount_idr'] ?? 0);
            $refund_id  = (int)($r['refund_id'] ?? 0);

            if ($deposit_id <= 0 || $refund_id <= 0) {
                $this->erp->trans_rollback();
                return ['ok'=>false,'http'=>500,'message'=>'INVALID_REFUND_ROW','data'=>['refund_id'=>$refund_id,'deposit_id'=>$deposit_id]];
            }

            $this->erp->from('customer_deposits')
                ->where('company_id', $this->company_id)
                ->where('deposit_id', $deposit_id);
            $this->_apply_region_filter();
            $lot = $this->erp->get()->row_array();

            if (!$lot) {
                $this->erp->trans_rollback();
                return ['ok'=>false,'http'=>500,'message'=>'DEPOSIT_LOT_NOT_FOUND','data'=>['deposit_id'=>$deposit_id]];
            }

            $new_used_doc    = max(0, (float)$lot['used_doc'] - $amount_doc);
            $new_balance_doc = (float)$lot['balance_doc'] + $amount_doc;
            $new_used_idr    = max(0, (float)$lot['used_idr'] - $amount_idr);
            $new_balance_idr = (float)$lot['balance_idr'] + $amount_idr;
            $new_status = ($new_balance_doc > 0.01) ? 'OPEN' : 'USED';

            $this->erp->where('company_id', $this->company_id)
                ->where('deposit_id', $deposit_id);
            $this->_apply_region_filter();
            $this->erp->update('customer_deposits', [
                'used_doc'    => round($new_used_doc, 2),
                'balance_doc' => round($new_balance_doc, 2),
                'used_idr'    => round($new_used_idr, 2),
                'balance_idr' => round($new_balance_idr, 2),
                'status'      => $new_status,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $this->erp->where('company_id', $this->company_id)
                ->where('refund_id', $refund_id);
            $this->_apply_region_filter();
            $this->erp->update('customer_deposit_refunds', [
                'status'       => 'CANCELLED',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => $this->auth_username(),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $this->erp->where('company_id', $this->company_id)
            ->where('voucher_type', 'DREF')
            ->where('voucher_no', $refund_no);
        $this->_apply_region_filter();
        $this->erp->update('gl_entries', [
            'status'     => 'CANCELLED',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'DB error'];
        }

        $this->erp->trans_commit();

        return ['ok'=>true,'message'=>'Refund cancelled','data'=>['refund_no'=>$refund_no,'region_id'=>$this->_region_id()]];
    }

    public function list_available_parties($p = [])
	{
		$search   = trim((string)($p['search'] ?? ''));
		$currency = strtoupper(trim((string)($p['currency'] ?? '')));

		if ($currency === 'RP.' || $currency === 'RP' || $currency === 'RUPIAH') {
			$currency = 'IDR';
		}

		$this->erp
			->select("
				party_code,
				party_name,
				currency,
				COALESCE(SUM(balance_doc),0) AS available_doc,
				COALESCE(SUM(balance_idr),0) AS available_idr
			", false)
			->from('customer_deposits')
			->where('company_id', $this->company_id)
			->where('balance_doc >', 0, false)
			->where("COALESCE(status,'OPEN') <>", 'CANCELLED');

		$this->_apply_region_filter();

		if ($currency !== '') {
			$this->erp->where('currency', $currency);
		}

		if ($search !== '') {
			$this->erp->group_start()
				->like('party_code', $search)
				->or_like('party_name', $search)
			->group_end();
		}

		$rows = $this->erp
			->group_by(['party_code', 'party_name', 'currency'])
			->order_by('party_name', 'ASC')
			->order_by('party_code', 'ASC')
			->get()
			->result_array();

		foreach ($rows as &$r) {
			$r['available_doc'] = (float)$r['available_doc'];
			$r['available_idr'] = (float)$r['available_idr'];
		}
		unset($r);

		return $rows;
	}

    private function _is_locked($posting_date)
    {
        $this->erp->select('MAX(lock_date) mx', false)
            ->from('accounting_locks')
            ->where('company_id', $this->company_id);

        if ($this->erp->field_exists('region_id', 'accounting_locks')) {
            $this->_apply_region_filter();
        }

        $row = $this->erp->get()->row_array();
        $mx = $row['mx'] ?? null;

        return ($mx && $posting_date <= $mx);
    }

    private function _get_reconciled_lock($voucher_type, $voucher_no)
    {
        $voucher_type = strtoupper(trim((string)$voucher_type));
        $voucher_no   = trim((string)$voucher_no);
        if (!$voucher_type || !$voucher_no) return null;

        $this->erp->select('s.stmt_id,s.period_month,s.statement_date,l.line_id', false)
            ->from('bank_statement_lines l')
            ->join('bank_statements s', 's.stmt_id = l.stmt_id', 'inner')
            ->where('s.company_id', $this->company_id)
            ->where('s.status', 'RECONCILED')
            ->where_in('l.match_status', ['MATCHED','RECONCILED'])
            ->where('l.matched_voucher_type', $voucher_type)
            ->where('l.matched_voucher_no', $voucher_no)
            ->limit(1);

        if ($this->erp->field_exists('region_id', 'bank_statements')) {
            $this->_apply_region_filter('s');
        }

        return $this->erp->get()->row_array();
    }

    private function _valid_postable_account($account_id)
    {
        $account_id = (int)$account_id;
        if ($account_id <= 0) return false;

        $this->erp->select('account_id,is_postable,is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('account_id', $account_id);

        $this->_apply_region_filter();

        $acc = $this->erp->get()->row_array();

        return ($acc && (int)$acc['is_postable'] === 1 && (int)$acc['is_active'] === 1);
    }

    private function _region_id()
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

    private function _apply_region_filter($alias = null)
    {
        if ($this->is_super_admin()) {
            return;
        }

        $region_id = $this->_region_id();
        if ($region_id === '') {
            return;
        }

        $column = $alias ? ($alias . '.region_id') : 'region_id';
        $this->erp->where($column, $region_id);
    }
}
