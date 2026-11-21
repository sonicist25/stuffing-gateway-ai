<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_bills extends MY_Model
{
  private $company_id = 1;

  
	private function _norm_item_text($v)
	{
		$v = trim((string)$v);
		$v = preg_replace('/\s+/', ' ', $v);
		return strtoupper($v);
	}

	private function _item_match_key($job_id, $memo)
	{
		return $this->_norm_item_text($job_id) . '||' . $this->_norm_item_text($memo);
	}

	private function _money_eq($a, $b, $tol = 0.01)
	{
		return abs(round((float)$a, 2) - round((float)$b, 2)) <= $tol;
	}

	private function _get_bill_items_with_paid($bill_id)
	{
		$bill_id = (int)$bill_id;

		if ($bill_id <= 0) {
			return [];
		}

		$paidSub = "
			SELECT
				r.bill_item_id,
				SUM(COALESCE(r.allocated_amount,0)) AS paid_amount
			FROM ap_payment_refs r
			INNER JOIN ap_payments p ON p.pay_id = r.pay_id
			WHERE p.status = 'SUBMITTED'
			GROUP BY r.bill_item_id
		";

		$this->erp->select("
			i.*,
			COALESCE(paid.paid_amount,0) AS paid_amount
		", false)
		->from('ap_bill_items i')
		->join("($paidSub) paid", 'paid.bill_item_id = i.item_id', 'left', false)
		->where('i.bill_id', $bill_id)
		->order_by('i.line_no', 'ASC');

		return $this->erp->get()->result_array();
	}

	private function _find_bill_by_source_vendor_currency($source_type, $source_key, $vendor_code, $currency, $region_id)
	{
		$this->erp
			->from('ap_bills')
			->where('company_id', $this->company_id)
			->where('source_type', $source_type)
			->where('source_key', $source_key)
			->where('vendor_code', $vendor_code)
			->where('currency', $currency)
			->where("COALESCE(status,'') !=", 'CANCELLED');

		if ($region_id !== '') {
			$this->erp->where('region_id', $region_id);
		}

		return $this->erp->get()->row_array();
	}

	private function _get_source_active_bills($source_type, $source_key, $region_id)
	{
		$this->erp
			->from('ap_bills')
			->where('company_id', $this->company_id)
			->where('source_type', $source_type)
			->where('source_key', $source_key)
			->where("COALESCE(status,'') !=", 'CANCELLED');

		if ($region_id !== '') {
			$this->erp->where('region_id', $region_id);
		}

		return $this->erp->get()->result_array();
	}

	private function _incoming_latest_item_rows($d)
	{
		$rows = [];

		foreach (($d['packs'] ?? []) as $packKey => $pack) {
			$vendor_code = trim((string)($pack['vendor_code'] ?? ''));
			$vendor_name = trim((string)($pack['vendor_name'] ?? $vendor_code));
			$vendor_type = strtoupper(trim((string)($pack['vendor_type'] ?? 'SUPPLIER')));
			$currency = $this->_norm_currency($pack['currency'] ?? 'IDR');
			$posting_kurs = $this->_valid_kurs($currency, $pack['posting_kurs'] ?? 1);

			foreach (($pack['lines'] ?? []) as $lineIndex => $l) {
				$gross = (float)($l['amount'] ?? 0);
				if ($gross <= 0) {
					continue;
				}

				$lineCurrency = $this->_norm_currency($l['currency'] ?? $currency);
				$lineKurs = $this->_valid_kurs($lineCurrency, $l['posting_kurs'] ?? $posting_kurs);
				$grossBase = (float)($l['base_amount'] ?? $l['amount_idr'] ?? $this->_base_amount($gross, $lineCurrency, $lineKurs));

				$withholdingFlag = (int)($l['withholding_pph23'] ?? 0);
				$withholdingRate = $this->_normalize_withholding_rate($l['withholding_rate'] ?? 0);

				[$pph, $net] = $this->_calc_withholding(
					$gross,
					$withholdingFlag,
					$withholdingRate
				);

				[$pphBase, $netBase] = $this->_calc_withholding(
					$grossBase,
					$withholdingFlag,
					$withholdingRate
				);

				$jobId = trim((string)($l['job_id'] ?? $d['source_key'] ?? ''));
				$memo = trim((string)($l['memo'] ?? ''));

				$rows[] = [
					'pack_key'      => $packKey,
					'vendor_code'   => $vendor_code,
					'vendor_name'   => $vendor_name,
					'vendor_type'   => $vendor_type,
					'currency'      => $lineCurrency,
					'posting_kurs'  => $lineKurs,
					'job_id'        => $jobId,
					'memo'          => $memo,
					'match_key'     => $this->_item_match_key($jobId, $memo),
					'gross'         => round($gross, 2),
					'gross_base'    => round($grossBase, 2),
					'pph23'         => round($pph, 2),
					'pph23_base'    => round($pphBase, 2),
					'net'           => round($net, 2),
					'net_base'      => round($netBase, 2),
					'withholding_pph23' => $withholdingFlag,
					'withholding_rate'  => $withholdingRate,
					'pph23_source'      => ($withholdingFlag === 1 && round($pph, 2) > 0) ? 'SYSTEM_COST' : null,
					'line_index'    => $lineIndex,
				];
			}
		}

		return $rows;
	}

	private function _preflight_paid_items_against_latest($d)
	{
		$incoming = $this->_incoming_latest_item_rows($d);
		$usedIncoming = [];

		$bills = $this->_get_source_active_bills($d['source_type'], $d['source_key'], $d['region_id']);

		foreach ($bills as $bill) {
			$items = $this->_get_bill_items_with_paid((int)$bill['bill_id']);

			foreach ($items as $old) {
				$paid = (float)($old['paid_amount'] ?? 0);

				if ($paid <= 0.01) {
					continue;
				}

				$oldKey = $this->_item_match_key($old['job_id'] ?? '', $old['memo'] ?? '');

				$matchedIdx = null;

				foreach ($incoming as $idx => $new) {
					if (isset($usedIncoming[$idx])) {
						continue;
					}

					if ($new['match_key'] === $oldKey) {
						$matchedIdx = $idx;
						break;
					}
				}

				if ($matchedIdx === null) {
					return [
						'ok'      => false,
						'http'    => 409,
						'message' => 'AP Bill item sudah dibayar dan tidak ditemukan lagi di data terbaru. Update ditolak.',
						'data'    => [
							'bill_id'   => $bill['bill_id'],
							'bill_no'   => $bill['bill_no'],
							'item_id'   => $old['item_id'],
							'job_id'    => $old['job_id'],
							'memo'      => $old['memo'],
							'paid'      => $paid,
						],
					];
				}

				$new = $incoming[$matchedIdx];
				$usedIncoming[$matchedIdx] = true;

				$oldAmount = round((float)($old['amount'] ?? 0), 2);
				$oldCurrency = $this->_norm_currency($old['currency'] ?? $bill['currency'] ?? 'IDR');

				if (!$this->_money_eq($oldAmount, $new['net'])) {
					return [
						'ok'      => false,
						'http'    => 409,
						'message' => 'AP Bill item sudah dibayar dan amount berubah. Update ditolak.',
						'data'    => [
							'bill_id'     => $bill['bill_id'],
							'bill_no'     => $bill['bill_no'],
							'item_id'     => $old['item_id'],
							'job_id'      => $old['job_id'],
							'memo'        => $old['memo'],
							'old_amount'  => $oldAmount,
							'new_amount'  => $new['net'],
							'paid'        => $paid,
						],
					];
				}

				if ($oldCurrency !== $new['currency']) {
					return [
						'ok'      => false,
						'http'    => 409,
						'message' => 'AP Bill item sudah dibayar dan currency berubah. Update ditolak.',
						'data'    => [
							'bill_id'      => $bill['bill_id'],
							'bill_no'      => $bill['bill_no'],
							'item_id'      => $old['item_id'],
							'job_id'       => $old['job_id'],
							'memo'         => $old['memo'],
							'old_currency' => $oldCurrency,
							'new_currency' => $new['currency'],
							'paid'         => $paid,
						],
					];
				}

				/*
				 * Vendor berubah untuk item yang sudah dibayar juga ditolak,
				 * supaya tidak terjadi item paid pindah vendor lalu dibuat bill baru.
				 */
				if (
					$this->_norm_item_text($bill['vendor_code'] ?? '') !== $this->_norm_item_text($new['vendor_code'] ?? '')
				) {
					return [
						'ok'      => false,
						'http'    => 409,
						'message' => 'AP Bill item sudah dibayar dan vendor berubah. Update ditolak.',
						'data'    => [
							'bill_id'       => $bill['bill_id'],
							'bill_no'       => $bill['bill_no'],
							'item_id'       => $old['item_id'],
							'job_id'        => $old['job_id'],
							'memo'          => $old['memo'],
							'old_vendor'    => $bill['vendor_code'],
							'new_vendor'    => $new['vendor_code'],
							'paid'          => $paid,
						],
					];
				}
			}
		}

		return ['ok' => true];
	}

	private function _cancel_bill_gl($bill_no, $region_id)
	{
		$this->erp
			->where('company_id', $this->company_id)
			->where('voucher_type', 'BILL')
			->where('voucher_no', $bill_no);

		if ($region_id !== '') {
			$this->erp->where('region_id', $region_id);
		}

		$this->erp->update('gl_entries', [
			'status'     => 'CANCELLED',
			'updated_at' => date('Y-m-d H:i:s'),
		]);
	}

	private function _delete_unpaid_bill_item($item_id)
	{
		$item_id = (int)$item_id;

		if ($item_id <= 0) {
			return;
		}

		$this->erp->where('item_id', $item_id)->delete('ap_bill_items');
	}

	private function _sync_bill_items_as_latest($bill_id, array $lines, $hpp, array $ctx)
	{
		$oldItems = $this->_get_bill_items_with_paid($bill_id);
		$usedOld = [];
		$lineNo = 1;

		foreach ($lines as $l) {
			$gross = (float)($l['amount'] ?? 0);
			if ($gross <= 0) {
				continue;
			}

			$currency = $this->_norm_currency($l['currency'] ?? $ctx['currency'] ?? 'IDR');
			$posting_kurs = $this->_valid_kurs($currency, $l['posting_kurs'] ?? $ctx['posting_kurs'] ?? 1);
			$grossBase = (float)($l['base_amount'] ?? $l['amount_idr'] ?? $this->_base_amount($gross, $currency, $posting_kurs));

			$withholdingFlag = (int)($l['withholding_pph23'] ?? 0);
			$withholdingRate = $this->_normalize_withholding_rate($l['withholding_rate'] ?? 0);

			[$pph, $net] = $this->_calc_withholding(
				$gross,
				$withholdingFlag,
				$withholdingRate
			);

			[$pphBase, $netBase] = $this->_calc_withholding(
				$grossBase,
				$withholdingFlag,
				$withholdingRate
			);

			$jobId = trim((string)($l['job_id'] ?? $ctx['job_no'] ?? ''));
			$memo = trim((string)($l['memo'] ?? ''));
			$newKey = $this->_item_match_key($jobId, $memo);

			$matchedOldIdx = null;

			foreach ($oldItems as $oldIdx => $old) {
				if (isset($usedOld[$oldIdx])) {
					continue;
				}

				$oldKey = $this->_item_match_key($old['job_id'] ?? '', $old['memo'] ?? '');

				if ($oldKey === $newKey) {
					$matchedOldIdx = $oldIdx;
					break;
				}
			}

			$pph23Source = ($withholdingFlag === 1 && round($pph, 2) > 0) ? 'SYSTEM_COST' : null;

			$itemData = [
				'line_no'      => $lineNo++,
				'expense_type' => 'HPP',
				'account_id'   => $hpp,
				'job_id'       => $jobId,
				'memo'         => $memo,
				'currency'     => $currency,
				'posting_kurs' => $posting_kurs,
				'amount'       => round($net, 2),
				'base_amount'  => round($netBase, 2),
				'gross_amount' => round($gross, 2),
				'gross_base_amount' => round($grossBase, 2),
				'net_amount'   => round($net, 2),
				'net_base_amount' => round($netBase, 2),
				'withholding_pph23' => $withholdingFlag,
				'withholding_rate'  => $withholdingRate,
				'pph23_amount'      => round($pph, 2),
				'pph23_base_amount' => round($pphBase, 2),
				'pph23_source'      => $pph23Source,
			];

			if ($matchedOldIdx === null) {
				$itemData['bill_id'] = $bill_id;
				$this->_insert_bill_item($itemData);
				continue;
			}

			$usedOld[$matchedOldIdx] = true;
			$old = $oldItems[$matchedOldIdx];
			$paid = (float)($old['paid_amount'] ?? 0);

			if ($paid > 0.01) {
				/*
				 * Paid item yang sama dibiarkan, hanya line_no boleh dirapikan.
				 * Amount/currency sudah dicek di preflight.
				 */
				$this->erp
					->where('item_id', (int)$old['item_id'])
					->update('ap_bill_items', $this->_filter_existing_fields('ap_bill_items', [
						'line_no'            => $itemData['line_no'],
						'gross_amount'       => $itemData['gross_amount'],
						'gross_base_amount'  => $itemData['gross_base_amount'],
						'net_amount'         => $itemData['net_amount'],
						'net_base_amount'    => $itemData['net_base_amount'],
						'withholding_pph23'  => $itemData['withholding_pph23'],
						'withholding_rate'   => $itemData['withholding_rate'],
						'pph23_amount'       => $itemData['pph23_amount'],
						'pph23_base_amount'  => $itemData['pph23_base_amount'],
						'pph23_source'       => $itemData['pph23_source'],
					]));

				continue;
			}

			$this->erp
				->where('item_id', (int)$old['item_id'])
				->update('ap_bill_items', $this->_filter_existing_fields('ap_bill_items', $itemData));
		}

		/*
		 * Item lama yang tidak ada di snapshot terbaru:
		 * - kalau paid: preflight sudah reject
		 * - kalau unpaid: hapus, karena data terbaru menggantikan data lama
		 */
		foreach ($oldItems as $oldIdx => $old) {
			if (isset($usedOld[$oldIdx])) {
				continue;
			}

			$paid = (float)($old['paid_amount'] ?? 0);

			if ($paid > 0.01) {
				return [
					'ok'      => false,
					'http'    => 409,
					'message' => 'AP Bill item sudah dibayar tetapi hilang dari data terbaru. Update ditolak.',
					'data'    => [
						'item_id' => $old['item_id'],
						'job_id'  => $old['job_id'],
						'memo'    => $old['memo'],
						'paid'    => $paid,
					],
				];
			}

			$this->_delete_unpaid_bill_item((int)$old['item_id']);
		}

		return ['ok' => true];
	}

	private function _cancel_obsolete_unpaid_source_bills($source_type, $source_key, $region_id, array $keepBillIds)
	{
		$bills = $this->_get_source_active_bills($source_type, $source_key, $region_id);

		foreach ($bills as $bill) {
			$billId = (int)$bill['bill_id'];

			if (in_array($billId, $keepBillIds, true)) {
				continue;
			}

			$items = $this->_get_bill_items_with_paid($billId);

			foreach ($items as $it) {
				if ((float)($it['paid_amount'] ?? 0) > 0.01) {
					return [
						'ok'      => false,
						'http'    => 409,
						'message' => 'Ada bill lama yang sudah dibayar tetapi tidak ada di snapshot terbaru. Update ditolak.',
						'data'    => [
							'bill_id' => $bill['bill_id'],
							'bill_no' => $bill['bill_no'],
						],
					];
				}
			}

			$this->_cancel_bill_gl($bill['bill_no'], $region_id);

			$this->erp
				->where('bill_id', $billId)
				->where('company_id', $this->company_id)
				->update('ap_bills', [
					'status'     => 'CANCELLED',
					'updated_by' => $this->auth_username(),
					'updated_at' => date('Y-m-d H:i:s'),
				]);

			$this->erp
				->where('company_id', $this->company_id)
				->where('source_type', $source_type)
				->where('source_key', $source_key)
				->where('bill_id', $billId)
				->delete('ap_bill_source_map');
		}

		return ['ok' => true];
	}
  
	private function _bill_has_payment_refs($bill_id)
	{
		if ($bill_id <= 0) {
			return false;
		}

		if (!$this->erp->table_exists('ap_payment_refs')) {
			return false;
		}

		$this->erp
			->from('ap_payment_refs')
			->where('bill_id', $bill_id);

		return $this->erp->count_all_results() > 0;
	}

  private function _field_expr($table, $alias, $field, $fallbackSql)
  {
    return $this->_field_exists($table, $field)
      ? ($alias . '.' . $field)
      : $fallbackSql;
  }

  private function _withholding_source_label($source)
  {
    $source = strtoupper(trim((string)$source));

    if ($source === 'SYSTEM_COST') {
      return 'System';
    }

    if ($source === 'MANUAL_PAYMENT') {
      return 'Manual';
    }

    return null;
  }

  private function _normalize_withholding_rate($rate)
  {
    $rate = (float)$rate;

    if ($rate <= 0) {
      return 0.02;
    }

    /*
     * Terima dua format:
     * - 0.02  = 2%
     * - 2     = 2%
     */
    if ($rate > 1) {
      return $rate / 100;
    }

    return $rate;
  }

  public function list($p)
  {
    $region_id = trim((string)($p['region_id'] ?? ''));

    if ($region_id === '') {
      return [];
    }

    $paidRegionSql = " AND p.region_id = " . $this->erp->escape($region_id);

    $billGrossSql       = $this->_field_expr('ap_bills', 'b', 'gross_total', 'b.total');
    $billPph23Sql       = $this->_field_expr('ap_bills', 'b', 'pph23_total', '0');
    $billPph23SystemSql = $this->_field_expr('ap_bills', 'b', 'pph23_system_total', $billPph23Sql);
    $billHasSystemSql   = $this->_field_expr('ap_bills', 'b', 'has_system_pph23', "CASE WHEN {$billPph23SystemSql} > 0 THEN 1 ELSE 0 END");

    $itemGrossSql       = $this->_field_expr('ap_bill_items', 'i', 'gross_amount', 'i.amount');
    $itemPph23Sql       = $this->_field_expr('ap_bill_items', 'i', 'pph23_amount', '0');
    $itemPph23SourceSql = $this->_field_expr('ap_bill_items', 'i', 'pph23_source', 'NULL');
    $itemWithFlagSql    = $this->_field_expr('ap_bill_items', 'i', 'withholding_pph23', "CASE WHEN {$itemPph23Sql} > 0 THEN 1 ELSE 0 END");
    $itemRateSql        = $this->_field_expr('ap_bill_items', 'i', 'withholding_rate', '0');
    $itemNetSql         = $this->_field_expr('ap_bill_items', 'i', 'net_amount', 'i.amount');

    $this->erp->select("
      b.bill_id,
      b.bill_no,
      b.posting_date,
      b.vendor_name,
      b.vendor_code,
      b.vendor_type,
      b.reference_job_no,
      b.source_key,
      b.source_type,
      {$billGrossSql} AS gross_total,
      {$billPph23Sql} AS pph23_total,
      {$billPph23SystemSql} AS pph23_system_total,
      {$billHasSystemSql} AS has_system_pph23,
      CASE WHEN {$billHasSystemSql} = 1 THEN 'PPh23 by System' ELSE NULL END AS pph23_label,

      COALESCE(NULLIF(b.currency, ''), 'IDR') AS currency,
      COALESCE(NULLIF(b.posting_kurs, 0), 1) AS posting_kurs,
      COALESCE(NULLIF(b.posting_kurs, 0), 1) AS bill_kurs,
      COALESCE(b.base_total, b.total) AS base_total,
      COALESCE(b.base_paid_total, b.paid_total) AS base_paid_total,

      b.total AS bill_total,
      b.paid_total AS bill_paid_total,
      (b.total - b.paid_total) AS bill_outstanding,

      i.item_id,
      i.line_no,
      i.expense_type,
      i.account_id,
      i.job_id,
      i.memo AS item_name,
      i.memo AS charge_summary,
      COALESCE(NULLIF(i.currency, ''), NULLIF(b.currency, ''), 'IDR') AS item_currency,
      COALESCE(NULLIF(i.currency, ''), NULLIF(b.currency, ''), 'IDR') AS currency,
      COALESCE(NULLIF(i.posting_kurs, 0), NULLIF(b.posting_kurs, 0), 1) AS item_posting_kurs,
      COALESCE(NULLIF(i.posting_kurs, 0), NULLIF(b.posting_kurs, 0), 1) AS bill_kurs,
      COALESCE(i.base_amount, i.amount) AS item_base_total,
      i.amount AS item_total,
      {$itemGrossSql} AS item_gross_amount,
      {$itemPph23Sql} AS item_pph23_amount,
      {$itemPph23SourceSql} AS item_pph23_source,
      {$itemWithFlagSql} AS item_withholding_pph23,
      {$itemRateSql} AS item_withholding_rate,
      {$itemNetSql} AS item_net_amount,
      CASE
        WHEN {$itemPph23SourceSql} = 'SYSTEM_COST' THEN 'PPh23 by System'
        WHEN {$itemPph23SourceSql} = 'MANUAL_PAYMENT' THEN 'PPh23 Manual'
        ELSE NULL
      END AS item_pph23_label,

      COALESCE(paid.item_paid_total, 0) AS item_paid_total,
      (i.amount - COALESCE(paid.item_paid_total, 0)) AS item_outstanding,

      i.amount AS total,
      COALESCE(paid.item_paid_total, 0) AS paid_total,
      (i.amount - COALESCE(paid.item_paid_total, 0)) AS outstanding
    ", false)
    ->from('ap_bills b')
    ->join('ap_bill_items i', 'i.bill_id = b.bill_id', 'inner')
    ->join("
      (
        SELECT
          r.bill_item_id,
          SUM(r.allocated_amount) AS item_paid_total
        FROM ap_payment_refs r
        INNER JOIN ap_payments p ON p.pay_id = r.pay_id
        WHERE p.status = 'SUBMITTED'
        {$paidRegionSql}
        GROUP BY r.bill_item_id
      ) paid
    ", 'paid.bill_item_id = i.item_id', 'left', false)
    ->where('b.company_id', $this->company_id)
    ->where('b.status !=', 'CANCELLED');

    $this->erp->where('b.region_id', $region_id);

    if (!empty($p['search'])) {
      $q = $p['search'];

      $this->erp->group_start()
        ->like('b.bill_no', $q)
        ->or_like('b.vendor_name', $q)
        ->or_like('b.vendor_code', $q)
        ->or_like('b.reference_job_no', $q)
        ->or_like('b.source_key', $q)
        ->or_like('i.memo', $q)
        ->or_like('i.job_id', $q)
      ->group_end();
    }

    if (!empty($p['date_from'])) {
      $this->erp->where('b.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
      $this->erp->where('b.posting_date <=', $p['date_to']);
    }

    if (!empty($p['vendor_type'])) {
      $this->erp->where('b.vendor_type', $p['vendor_type']);
    }

    if (!empty($p['currency'])) {
      $this->erp->where("COALESCE(NULLIF(b.currency, ''), 'IDR') =", $this->_norm_currency($p['currency']));
    }

    /*
     * Status filter per item, bukan per bill.
     */
    if (!empty($p['status'])) {
      $st = strtoupper($p['status']);

      if ($st === 'PAID') {
        $this->erp->where('(i.amount - COALESCE(paid.item_paid_total, 0)) <= 0.01', null, false);
      }

      if ($st === 'UNPAID') {
        $this->erp->where('COALESCE(paid.item_paid_total, 0) <= 0.01', null, false);
      }

      if ($st === 'PARTIAL') {
        $this->erp->where('COALESCE(paid.item_paid_total, 0) > 0.01', null, false);
        $this->erp->where('(i.amount - COALESCE(paid.item_paid_total, 0)) > 0.01', null, false);
      }
    }

    $rows = $this->erp
      ->order_by('b.posting_date', 'DESC')
      ->order_by('b.bill_id', 'DESC')
      ->order_by('i.line_no', 'ASC')
      ->get()
      ->result_array();

    foreach ($rows as &$r) {
      $currency = $this->_norm_currency($r['currency'] ?? 'IDR');
      $kurs = $this->_valid_kurs($currency, $r['bill_kurs'] ?? $r['posting_kurs'] ?? 1);

      $itemTotal = (float)($r['item_total'] ?? 0);
      $itemPaid  = (float)($r['item_paid_total'] ?? 0);
      $itemOut   = $itemTotal - $itemPaid;

      if ($itemOut < 0.01) {
        $itemOut = 0;
      }

      $r['currency']          = $currency;
      $r['item_currency']     = $currency;
      $r['posting_kurs']      = $kurs;
      $r['bill_kurs']         = $kurs;
      $r['item_posting_kurs'] = $kurs;

      $r['item_total']       = $itemTotal;
      $r['item_paid_total']  = $itemPaid;
      $r['item_outstanding'] = $itemOut;

      $r['gross_total']          = (float)($r['gross_total'] ?? $r['bill_total'] ?? 0);
      $r['pph23_total']          = (float)($r['pph23_total'] ?? 0);
      $r['pph23_system_total']   = (float)($r['pph23_system_total'] ?? $r['pph23_total']);
      $r['has_system_pph23']     = ((int)($r['has_system_pph23'] ?? 0) === 1 || $r['pph23_system_total'] > 0.01) ? 1 : 0;
      $r['pph23_label']          = $r['has_system_pph23'] ? 'PPh23 by System' : null;
      $r['manual_pph23_allowed'] = 1;
      $r['pph23_manual_note']    = $r['has_system_pph23']
        ? 'Bill ini sudah memiliki PPh23 dari system. Manual adjustment tetap boleh digunakan jika ini koreksi tambahan.'
        : 'Belum ada PPh23 dari system. Gunakan manual adjustment jika vendor memang dipotong PPh23.';

      $r['item_gross_amount']      = (float)($r['item_gross_amount'] ?? $itemTotal);
      $r['item_pph23_amount']      = (float)($r['item_pph23_amount'] ?? 0);
      $r['item_net_amount']        = (float)($r['item_net_amount'] ?? $itemTotal);
      $r['item_withholding_pph23'] = (int)($r['item_withholding_pph23'] ?? (($r['item_pph23_amount'] > 0.01) ? 1 : 0));
      $r['item_withholding_rate']  = (float)($r['item_withholding_rate'] ?? 0);
      $r['item_pph23_source']      = $r['item_pph23_source'] ?: null;
      $r['item_has_system_pph23']  = (strtoupper((string)$r['item_pph23_source']) === 'SYSTEM_COST') ? 1 : 0;
      $r['item_pph23_label']       = $this->_withholding_source_label($r['item_pph23_source']);

      $r['total']       = $itemTotal;
      $r['paid_total']  = $itemPaid;
      $r['outstanding'] = $itemOut;

      $r['item_base_total'] = (float)($r['item_base_total'] ?? $this->_base_amount($itemTotal, $currency, $kurs));
      $r['base_total']      = (float)($r['base_total'] ?? $this->_base_amount((float)($r['bill_total'] ?? 0), $currency, $kurs));
      $r['base_paid_total'] = (float)($r['base_paid_total'] ?? $this->_base_amount((float)($r['bill_paid_total'] ?? 0), $currency, $kurs));

      $r['pay_status'] = ($itemOut <= 0.01)
        ? 'PAID'
        : (($itemPaid > 0.01) ? 'PARTIAL' : 'UNPAID');

      $vt = strtoupper((string)($r['vendor_type'] ?? ''));

      $r['job_ref'] = ($vt === 'GLOBALCOST')
        ? ($r['source_key'] ?? '')
        : ($r['reference_job_no'] ?? '');

      if (empty($r['job_ref'])) {
        $r['job_ref'] = $r['job_id'] ?? '';
      }

      $r['row_key'] = $r['bill_id'] . '-' . ($r['item_id'] ?: $r['line_no']);
    }

    unset($r);

    return $rows;
  }

  public function get($bill_id, $region_id = null)
  {
    $bill_id = (int)$bill_id;
    $region_id = trim((string)$region_id);

    if ($region_id === '') {
      return null;
    }

    $billGrossSql       = $this->_field_expr('ap_bills', 'b', 'gross_total', 'b.total');
    $billPph23Sql       = $this->_field_expr('ap_bills', 'b', 'pph23_total', '0');
    $billPph23SystemSql = $this->_field_expr('ap_bills', 'b', 'pph23_system_total', $billPph23Sql);
    $billHasSystemSql   = $this->_field_expr('ap_bills', 'b', 'has_system_pph23', "CASE WHEN {$billPph23SystemSql} > 0 THEN 1 ELSE 0 END");

    $this->erp->select("
        b.*,
        {$billGrossSql} AS gross_total,
        {$billPph23Sql} AS pph23_total,
        {$billPph23SystemSql} AS pph23_system_total,
        {$billHasSystemSql} AS has_system_pph23,
        CASE WHEN {$billHasSystemSql} = 1 THEN 'PPh23 by System' ELSE NULL END AS pph23_label,
        COALESCE(NULLIF(b.currency, ''), 'IDR') AS currency,
        COALESCE(NULLIF(b.posting_kurs, 0), 1) AS posting_kurs,
        COALESCE(NULLIF(b.posting_kurs, 0), 1) AS bill_kurs,
        COALESCE(b.base_total, b.total) AS base_total,
        COALESCE(b.base_paid_total, b.paid_total) AS base_paid_total
      ", false)
      ->from('ap_bills b')
      ->where('b.company_id', $this->company_id)
      ->where('b.bill_id', $bill_id);

    $this->erp->where('b.region_id', $region_id);

    $hdr = $this->erp->get()->row_array();

    if (!$hdr) {
      return null;
    }

    $itemGrossSql       = $this->_field_expr('ap_bill_items', 'i', 'gross_amount', 'i.amount');
    $itemPph23Sql       = $this->_field_expr('ap_bill_items', 'i', 'pph23_amount', '0');
    $itemPph23SourceSql = $this->_field_expr('ap_bill_items', 'i', 'pph23_source', 'NULL');
    $itemWithFlagSql    = $this->_field_expr('ap_bill_items', 'i', 'withholding_pph23', "CASE WHEN {$itemPph23Sql} > 0 THEN 1 ELSE 0 END");
    $itemRateSql        = $this->_field_expr('ap_bill_items', 'i', 'withholding_rate', '0');
    $itemNetSql         = $this->_field_expr('ap_bill_items', 'i', 'net_amount', 'i.amount');

    $items = $this->erp->select("
        i.*,
        {$itemGrossSql} AS gross_amount,
        {$itemPph23Sql} AS pph23_amount,
        {$itemPph23SourceSql} AS pph23_source,
        {$itemWithFlagSql} AS withholding_pph23,
        {$itemRateSql} AS withholding_rate,
        {$itemNetSql} AS net_amount,
        CASE
          WHEN {$itemPph23SourceSql} = 'SYSTEM_COST' THEN 'PPh23 by System'
          WHEN {$itemPph23SourceSql} = 'MANUAL_PAYMENT' THEN 'PPh23 Manual'
          ELSE NULL
        END AS pph23_label,
        COALESCE(NULLIF(i.currency, ''), NULLIF(" . $this->erp->escape($hdr['currency']) . ", ''), 'IDR') AS currency,
        COALESCE(NULLIF(i.posting_kurs, 0), " . (float)$hdr['posting_kurs'] . ", 1) AS posting_kurs,
        COALESCE(i.base_amount, i.amount) AS base_amount
      ", false)
      ->from('ap_bill_items i')
      ->where('i.bill_id', $bill_id)
      ->order_by('i.line_no', 'ASC')
      ->get()
      ->result_array();

    foreach ($items as &$it) {
      $cur = $this->_norm_currency($it['currency'] ?? $hdr['currency']);
      $kurs = $this->_valid_kurs($cur, $it['posting_kurs'] ?? $hdr['posting_kurs']);
      $it['currency']     = $cur;
      $it['posting_kurs'] = $kurs;
      $it['bill_kurs']    = $kurs;
      $it['amount']       = (float)$it['amount'];
      $it['base_amount']  = (float)($it['base_amount'] ?? $this->_base_amount($it['amount'], $cur, $kurs));
      $it['gross_amount'] = (float)($it['gross_amount'] ?? $it['amount']);
      $it['pph23_amount'] = (float)($it['pph23_amount'] ?? 0);
      $it['net_amount']   = (float)($it['net_amount'] ?? $it['amount']);
      $it['withholding_pph23'] = (int)($it['withholding_pph23'] ?? (($it['pph23_amount'] > 0.01) ? 1 : 0));
      $it['withholding_rate']  = (float)($it['withholding_rate'] ?? 0);
      $it['pph23_source']      = $it['pph23_source'] ?: null;
      $it['has_system_pph23']  = (strtoupper((string)$it['pph23_source']) === 'SYSTEM_COST') ? 1 : 0;
      $it['pph23_label']       = $this->_withholding_source_label($it['pph23_source']);
    }

    unset($it);

    $hdr['currency']        = $this->_norm_currency($hdr['currency'] ?? 'IDR');
    $hdr['posting_kurs']    = $this->_valid_kurs($hdr['currency'], $hdr['posting_kurs'] ?? 1);
    $hdr['bill_kurs']       = $hdr['posting_kurs'];
    $hdr['total']           = (float)$hdr['total'];
    $hdr['paid_total']      = (float)$hdr['paid_total'];
    $hdr['gross_total']     = (float)($hdr['gross_total'] ?? $hdr['total']);
    $hdr['pph23_total']     = (float)($hdr['pph23_total'] ?? 0);
    $hdr['pph23_system_total'] = (float)($hdr['pph23_system_total'] ?? $hdr['pph23_total']);
    $hdr['has_system_pph23'] = ((int)($hdr['has_system_pph23'] ?? 0) === 1 || $hdr['pph23_system_total'] > 0.01) ? 1 : 0;
    $hdr['pph23_label']     = $hdr['has_system_pph23'] ? 'PPh23 by System' : null;
    $hdr['manual_pph23_allowed'] = 1;
    $hdr['pph23_manual_note'] = $hdr['has_system_pph23']
      ? 'Bill ini sudah memiliki PPh23 dari system. Manual adjustment tetap boleh digunakan jika ini koreksi tambahan.'
      : 'Belum ada PPh23 dari system. Gunakan manual adjustment jika vendor memang dipotong PPh23.';
    $hdr['base_total']      = (float)($hdr['base_total'] ?? $this->_base_amount($hdr['total'], $hdr['currency'], $hdr['posting_kurs']));
    $hdr['base_paid_total'] = (float)($hdr['base_paid_total'] ?? $this->_base_amount($hdr['paid_total'], $hdr['currency'], $hdr['posting_kurs']));
    $hdr['outstanding']     = $hdr['total'] - $hdr['paid_total'];
    $hdr['pay_status']      = ($hdr['outstanding'] <= 0.01)
      ? 'PAID'
      : (($hdr['paid_total'] > 0.01) ? 'PARTIAL' : 'UNPAID');

    $hdr['items'] = $items;

    return $hdr;
  }

  public function payments($bill_id, $region_id)
  {
    $bill_id = (int)$bill_id;
    $region_id = trim((string)$region_id);

    if ($bill_id <= 0 || $region_id === '') {
      return [];
    }

    $this->erp->select("
        p.pay_id,
        p.pay_no,
        p.posting_date,
        p.status,
        COALESCE(NULLIF(p.currency, ''), 'IDR') AS currency,
        COALESCE(NULLIF(p.payment_kurs, 0), 1) AS payment_kurs,
        p.total_paid,
        p.base_total_paid,
        r.allocated_amount,
        r.base_allocated_amount
      ", false)
      ->from('ap_payment_refs r')
      ->join('ap_payments p', 'p.pay_id = r.pay_id', 'inner')
      ->where('p.company_id', $this->company_id)
      ->where('p.region_id', $region_id)
      ->where('r.bill_id', $bill_id);

    $rows = $this->erp
      ->order_by('p.posting_date', 'DESC')
      ->order_by('p.pay_id', 'DESC')
      ->get()
      ->result_array();

    foreach ($rows as &$x) {
      $x['currency']              = $this->_norm_currency($x['currency'] ?? 'IDR');
      $x['payment_kurs']          = $this->_valid_kurs($x['currency'], $x['payment_kurs'] ?? 1);
      $x['allocated_amount']      = (float)$x['allocated_amount'];
      $x['base_allocated_amount'] = (float)($x['base_allocated_amount'] ?? $this->_base_amount($x['allocated_amount'], $x['currency'], $x['payment_kurs']));
      $x['total_paid']            = (float)($x['total_paid'] ?? 0);
      $x['base_total_paid']       = (float)($x['base_total_paid'] ?? $this->_base_amount($x['total_paid'], $x['currency'], $x['payment_kurs']));
    }

    unset($x);

    return $rows;
  }

  public function create($p)
  {
    $posting_date = (string)($p['posting_date'] ?? '');
    $vendor_type  = strtoupper(trim((string)($p['vendor_type'] ?? '')));
    $vendor_code  = trim((string)($p['vendor_code'] ?? ''));
    $vendor_name  = trim((string)($p['vendor_name'] ?? ''));
    $currency     = $this->_norm_currency($p['currency'] ?? 'IDR');
    $posting_kurs = $this->_valid_kurs($currency, $p['posting_kurs'] ?? $p['kurs'] ?? $p['bill_kurs'] ?? 1);
    $remark       = trim((string)($p['remark'] ?? ''));
    $items        = $p['items'] ?? [];
    $region_id    = trim((string)($p['region_id'] ?? ''));

    $errs = [];

    if ($region_id === '') {
      $errs['region_id'] = 'required';
    }

    if (!$posting_date) {
      $errs['posting_date'] = 'required';
    }

    if (!$vendor_type) {
      $errs['vendor_type'] = 'required';
    }

    if (!$vendor_name) {
      $errs['vendor_name'] = 'required';
    }

    if ($currency === 'USD' && $posting_kurs <= 1) {
      $errs['posting_kurs'] = 'required for USD bill';
    }

    if (!is_array($items) || count($items) < 1) {
      $errs['items'] = 'required';
    }

    $ap_account_id = $this->_coa_id('AP', $region_id);

    if ($ap_account_id <= 0) {
      $errs['setup'] = 'Missing/invalid AP account (' . $this->_coa_code('AP') . ')';
    }

    $total = 0.0;
    $base_total = 0.0;
    $cleanItems = [];

    if (!$errs) {
      $ln = 1;

      foreach ($items as $i => $it) {
        $etype = strtoupper((string)($it['expense_type'] ?? 'OPEX'));
        if ($etype === 'WIP') $etype = 'HPP';
        $accId = (int)($it['account_id'] ?? 0);
        $jobId = isset($it['job_id']) ? trim((string)$it['job_id']) : null;
        $memo  = trim((string)($it['memo'] ?? ''));
        $amt   = (float)($it['amount'] ?? 0);
        $baseAmt = $this->_base_amount($amt, $currency, $posting_kurs);

        if (!in_array($etype, ['HPP', 'OPEX', 'CAPEX'], true)) {
          $errs["items.$i.expense_type"] = 'invalid';
        }

        if ($accId <= 0) {
          $errs["items.$i.account_id"] = 'required';
        }

        if ($amt <= 0) {
          $errs["items.$i.amount"] = 'must be > 0';
        }

        if ($accId > 0) {
          $this->erp->select('account_id, root_type, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('account_id', $accId);

          $acc = $this->erp->get()->row_array();

          if (!$acc || (int)$acc['is_active'] !== 1 || (int)$acc['is_postable'] !== 1) {
            $errs["items.$i.account_id"] = 'account not postable/active';
          }
        }

        $total += $amt;
        $base_total += $baseAmt;

        $cleanItems[] = [
          'line_no'      => $ln++,
          'expense_type' => $etype,
          'account_id'   => $accId,
          'job_id'       => $jobId ?: null,
          'memo'         => $memo ?: null,
          'currency'     => $currency,
          'posting_kurs' => $posting_kurs,
          'amount'       => round($amt, 2),
          'base_amount'  => round($baseAmt, 2),
        ];
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

    $this->erp->trans_begin();

    $bill_no = $this->_next_series('BILL', (int)date('Y', strtotime($posting_date)));

    $this->_insert_bill_header([
      'company_id'       => $this->company_id,
      'region_id'        => $region_id,
      'bill_no'          => $bill_no,
      'vendor_type'      => $vendor_type,
      'vendor_code'      => $vendor_code ?: null,
      'vendor_name'      => $vendor_name,
      'posting_date'     => $posting_date,
      'currency'         => $currency,
      'posting_kurs'     => $posting_kurs,
      'base_total'       => round($base_total, 2),
      'base_paid_total'  => 0,
      'remark'           => $remark ?: null,
      'status'           => 'SUBMITTED',
      'total'            => round($total, 2),
      'paid_total'       => 0,
      'created_by'       => $this->auth_username(),
      'created_at'       => date('Y-m-d H:i:s'),
    ]);

    $bill_id = (int)$this->erp->insert_id();

    foreach ($cleanItems as $it) {
      $it['bill_id'] = $bill_id;
      $this->_insert_bill_item($it);
    }

    foreach ($cleanItems as $it) {
      $this->_insert_gl_entry([
        'company_id'    => $this->company_id,
        'region_id'     => $region_id,
        'posting_date'  => $posting_date,
        'account_id'    => $it['account_id'],
        'party_id'      => null,
        'debit'         => (float)$it['base_amount'],
        'credit'        => 0,
        'voucher_type'  => 'BILL',
        'voucher_no'    => $bill_no,
        'status'        => 'POSTED',
      ]);
    }

    $this->_insert_gl_entry([
      'company_id'    => $this->company_id,
      'region_id'     => $region_id,
      'posting_date'  => $posting_date,
      'account_id'    => $ap_account_id,
      'party_id'      => null,
      'debit'         => 0,
      'credit'        => round($base_total, 2),
      'voucher_type'  => 'BILL',
      'voucher_no'    => $bill_no,
      'status'        => 'POSTED',
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
      'message' => 'Bill created',
      'data'    => [
        'bill_id'       => $bill_id,
        'bill_no'       => $bill_no,
        'region_id'     => $region_id,
        'posting_date'  => $posting_date,
        'currency'      => $currency,
        'posting_kurs'  => $posting_kurs,
        'total'         => round($total, 2),
        'base_total'    => round($base_total, 2)
      ]
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

  private function _build_vendor_packs($p)
  {
    $source_type = strtoupper(trim((string)($p['source_type'] ?? '')));

    if ($source_type === 'GLOBALCOST') {
      $source_key = trim((string)($p['job_console_no'] ?? ''));
    } elseif ($source_type === 'COST') {
      $source_key = trim((string)($p['job_no'] ?? ''));
    } else {
      $source_key = '';
    }

    $region_id = trim((string)($p['region_id'] ?? ''));

    if ($region_id === '') {
      return [
        'ok'      => false,
        'http'    => 422,
        'message' => 'region_id is required',
      ];
    }

    $job_nos      = $p['job_nos'] ?? [];
    $posting_date = (string)($p['date'] ?? $p['posting_date'] ?? '');
    $kurs         = $this->_parse_number($p['kurs'] ?? $p['posting_kurs'] ?? 0);
    $costs        = $p['costs'] ?? [];

    $refJob = (is_array($job_nos) && count($job_nos) > 0)
      ? implode(',', $job_nos)
      : $source_key;

    $byVendor = [];

    foreach ($costs as $c) {
      $vendor_code = trim((string)($c['payable_to'] ?? ''));
      $vendor_name = trim((string)($c['payable_name'] ?? $vendor_code));
      $line_region_id = $region_id;
      $vendor_type = strtoupper(trim((string)($c['vendor_type'] ?? 'SUPPLIER')));

      if (!in_array($vendor_type, ['AGENT', 'CUSTOMER', 'WAREHOUSE', 'TRUCKING', 'CARRIER', 'EMPLOYEE', 'SUPPLIER'], true)) {
        $vendor_type = 'SUPPLIER';
      }

      if (!$vendor_code) {
        continue;
      }

      $currency = $this->_norm_currency($c['currency'] ?? 'IDR');
      $line_kurs = $this->_valid_kurs($currency, $c['posting_kurs'] ?? $c['kurs'] ?? $kurs);

      if ($currency !== 'IDR' && $line_kurs <= 1) {
        return [
          'ok'      => false,
          'http'    => 422,
          'message' => "Missing kurs for {$currency} cost lines",
        ];
      }

      $unit_price = $this->_parse_number($c['price'] ?? 0); // nilai asli, contoh USD 6
      $qty        = $this->_parse_number($c['quantity'] ?? 1);

      if ($qty <= 0) {
        $qty = 1;
      }

      $with_flag = (int)($c['withholding_pph23'] ?? 0);
      $with_rate = (float)($c['withholding_rate'] ?? 0);

      // Amount asli untuk AP Bill.
      $gross_original = $unit_price * $qty;

      // Amount IDR hanya untuk GL.
      $gross_idr = $this->_base_amount($gross_original, $currency, $line_kurs);

      if ($gross_original <= 0) {
        continue;
      }

      $memo = trim((string)($c['detail_of_charge'] ?? ''));
      $code = trim((string)($c['code'] ?? ''));

      if ($code) {
        $memo .= " ({$code})";
      }

      // Pecah bill per vendor + currency.
      $packKey = $vendor_code . '|' . $currency;

      if (!isset($byVendor[$packKey])) {
        $byVendor[$packKey] = [
          'pack_key'    => $packKey,
          'vendor_code' => $vendor_code,
          'vendor_name' => $vendor_name ?: $vendor_code,
          'vendor_type' => $vendor_type,
          'currency'    => $currency,
          'posting_kurs'=> $line_kurs,
          'region_id'   => $line_region_id,
          'lines'       => [],
        ];
      }

      $byVendor[$packKey]['lines'][] = [
        'job_id' => $source_key,
        'memo'   => $memo,

        'currency'     => $currency,
        'posting_kurs' => $line_kurs,
        'unit_price'   => $unit_price,
        'qty'          => $qty,
        'amount'       => (float)$gross_original,
        'base_amount'  => (float)$gross_idr,

        'kurs'       => $line_kurs,
        'amount_idr' => (float)$gross_idr,

        'withholding_pph23' => $with_flag,
        'withholding_rate'  => $with_rate,
      ];
    }

    return [
      'ok'   => true,
      'data' => [
        'source_type'  => $source_type,
        'source_key'   => $source_key,
        'region_id'    => $region_id,
        'posting_date' => $posting_date,
        'ref_job'      => $refJob,
        'packs'        => $byVendor,
      ],
    ];
  }

  public function create_from_existing($p)
  {
    $region_id = trim((string)($p['region_id'] ?? ''));

    if ($region_id === '') {
      return [
        'ok'      => false,
        'http'    => 422,
        'message' => 'region_id is required',
      ];
    }

    $hpp = $this->_coa_id('HPP', $region_id);
    $ap  = $this->_coa_id('AP', $region_id);

    if ($hpp <= 0 || $ap <= 0) {
      return [
        'ok'      => false,
        'http'    => 422,
        'message' => 'Missing/posting-disabled COA HPP/AP (' . $this->_coa_code('HPP') . '/' . $this->_coa_code('AP') . ')'
      ];
    }

    $built = $this->_build_vendor_packs($p);

    if (!$built['ok']) {
      return $built;
    }

    $d = $built['data'];

    if (!$d['source_type'] || !$d['source_key'] || !$d['posting_date']) {
      return [
        'ok'      => false,
        'http'    => 422,
        'message' => 'Validation error'
      ];
    }

    if (!$d['packs']) {
      return [
        'ok'      => false,
        'http'    => 422,
        'message' => 'No valid cost lines'
      ];
    }

    $this->erp->trans_begin();

    foreach ($d['packs'] as $packKey => $pack) {
      $vendor_code = $pack['vendor_code'];

      $this->erp->from('ap_bill_source_map')
        ->where('company_id', $this->company_id)
        ->where('source_type', $d['source_type'])
        ->where('source_key', $d['source_key'])
        ->where('region_id', $region_id)
        ->where('vendor_code', $packKey);

      $map = $this->erp->get()->row_array();

      if ($map) {
        $this->erp->select('bill_no, paid_total')
          ->from('ap_bills')
          ->where('company_id', $this->company_id)
          ->where('region_id', $region_id)
          ->where('bill_id', (int)$map['bill_id']);

        $bill = $this->erp->get()->row_array();

        $this->erp->trans_rollback();

        if ($bill && (float)$bill['paid_total'] > 0.01) {
          return [
            'ok'      => false,
            'http'    => 409,
            'message' => "ALREADY_PAID vendor {$vendor_code} bill {$bill['bill_no']}"
          ];
        }

        return [
          'ok'      => false,
          'http'    => 409,
          'message' => "ALREADY_CREATED vendor {$vendor_code}"
        ];
      }
    }

    $created = [];

    foreach ($d['packs'] as $packKey => $pack) {
      $lines = $pack['lines'] ?? [];

      if (!$lines) {
        continue;
      }

      $currency = $this->_norm_currency($pack['currency'] ?? 'IDR');
      $posting_kurs = $this->_valid_kurs($currency, $pack['posting_kurs'] ?? 1);

      $total = 0;
      $base_total = 0;

      foreach ($lines as $l) {
        $total += (float)$l['amount'];
        $base_total += (float)$l['base_amount'];
      }

      $bill_no = $this->_next_series('BILL', (int)date('Y', strtotime($d['posting_date'])));

      $this->_insert_bill_header([
        'company_id'        => $this->company_id,
        'region_id'         => $region_id,
        'bill_no'           => $bill_no,
        'source_key'        => $d['source_key'],
        'reference_job_no'  => $d['ref_job'],
        'source_type'       => $d['source_type'],
        'vendor_type'       => $pack['vendor_type'],
        'vendor_code'       => $pack['vendor_code'],
        'vendor_name'       => $pack['vendor_name'],
        'posting_date'      => $d['posting_date'],
        'currency'          => $currency,
        'posting_kurs'      => $posting_kurs,
        'base_total'        => round($base_total, 2),
        'base_paid_total'   => 0,
        'remark'            => "From {$d['source_type']} {$d['source_key']}",
        'status'            => 'SUBMITTED',
        'total'             => round($total, 2),
        'paid_total'        => 0,
        'created_by'        => $this->auth_username(),
        'created_at'        => date('Y-m-d H:i:s'),
      ]);

      $bill_id = (int)$this->erp->insert_id();

      $ln = 1;

      foreach ($lines as $l) {
        $amt = (float)$l['amount'];
        $baseAmt = (float)$l['base_amount'];

        $this->_insert_bill_item([
          'bill_id'       => $bill_id,
          'line_no'       => $ln++,
          'expense_type'  => 'HPP',
          'account_id'    => $hpp,
          'job_id'        => $l['job_id'],
          'memo'          => $l['memo'],
          'currency'      => $currency,
          'posting_kurs'  => $posting_kurs,
          'amount'        => round($amt, 2),
          'base_amount'   => round($baseAmt, 2),
        ]);

        $this->_insert_gl_entry([
          'company_id'    => $this->company_id,
          'region_id'     => $region_id,
          'posting_date'  => $d['posting_date'],
          'account_id'    => $hpp,
          'party_id'      => null,
          'debit'         => round($baseAmt, 2),
          'credit'        => 0,
          'voucher_type'  => 'BILL',
          'voucher_no'    => $bill_no,
          'status'        => 'POSTED',
        ]);
      }

      $this->_insert_gl_entry([
        'company_id'    => $this->company_id,
        'region_id'     => $region_id,
        'posting_date'  => $d['posting_date'],
        'account_id'    => $ap,
        'party_id'      => null,
        'debit'         => 0,
        'credit'        => round($base_total, 2),
        'voucher_type'  => 'BILL',
        'voucher_no'    => $bill_no,
        'status'        => 'POSTED',
      ]);

      $this->erp->insert('ap_bill_source_map', [
        'company_id'    => $this->company_id,
        'region_id'     => $region_id,
        'source_type'   => $d['source_type'],
        'source_key'    => $d['source_key'],
        'vendor_code'   => $packKey,
        'bill_id'       => $bill_id
      ]);

      $created[] = [
        'vendor_code'  => $pack['vendor_code'],
        'bill_no'      => $bill_no,
        'currency'     => $currency,
        'posting_kurs' => $posting_kurs,
        'total'        => round($total, 2),
        'base_total'   => round($base_total, 2)
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
        ]
      ];
    }

    $this->erp->trans_commit();

    return [
      'ok'      => true,
      'message' => 'OK',
      'data'    => [
        'source_type' => $d['source_type'],
        'source_key'  => $d['source_key'],
        'region_id'   => $region_id,
        'created'     => $created
      ]
    ];
  }
  
  private function _insert_pack_gl_only($bill_no, array $lines, $hpp, $ap, $pph23, array $ctx)
{
    foreach ($lines as $l) {
        $gross = (float)($l['amount'] ?? 0);

        if ($gross <= 0) {
            continue;
        }

        $currency = $this->_norm_currency($l['currency'] ?? $ctx['currency'] ?? 'IDR');
        $posting_kurs = $this->_valid_kurs($currency, $l['posting_kurs'] ?? $ctx['posting_kurs'] ?? 1);
        $gross_base = (float)($l['base_amount'] ?? $l['amount_idr'] ?? $this->_base_amount($gross, $currency, $posting_kurs));

        $this->_insert_gl_entry([
            'company_id'    => $this->company_id,
            'region_id'     => $ctx['region_id'],
            'posting_date'  => $ctx['posting_date'],
            'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
            'account_id'    => $hpp,
            'party_id'      => null,
            'debit'         => round($gross_base, 2),
            'credit'        => 0,
            'voucher_type'  => 'BILL',
            'voucher_no'    => $bill_no,
            'status'        => 'POSTED',
        ]);
    }

    $this->_insert_gl_entry([
        'company_id'    => $this->company_id,
        'region_id'     => $ctx['region_id'],
        'posting_date'  => $ctx['posting_date'],
        'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
        'account_id'    => $ap,
        'party_id'      => null,
        'debit'         => 0,
        'credit'        => round((float)$ctx['total_net_base'], 2),
        'voucher_type'  => 'BILL',
        'voucher_no'    => $bill_no,
        'status'        => 'POSTED',
    ]);

    if ((float)$ctx['total_pph23'] > 0) {
        $this->_insert_gl_entry([
            'company_id'    => $this->company_id,
            'region_id'     => $ctx['region_id'],
            'posting_date'  => $ctx['posting_date'],
            'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
            'account_id'    => $pph23,
            'party_id'      => null,
            'debit'         => 0,
            'credit'        => round((float)$ctx['total_pph23_base'], 2),
            'voucher_type'  => 'BILL',
            'voucher_no'    => $bill_no,
            'status'        => 'POSTED',
        ]);
    }
}

public function sync_from_existing($p)
{
    $region_id = trim((string)($p['region_id'] ?? ''));

    if ($region_id === '') {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'region_id is required',
        ];
    }

    $hpp   = $this->_coa_id('HPP', $region_id);
    $ap    = $this->_coa_id('AP', $region_id);
    $pph23 = $this->_coa_id('PPH23_PAYABLE', $region_id);

    if ($hpp <= 0 || $ap <= 0) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Missing/posting-disabled COA HPP/AP (' . $this->_coa_code('HPP') . '/' . $this->_coa_code('AP') . ')',
        ];
    }

    $built = $this->_build_vendor_packs($p);

    if (!$built['ok']) {
        return $built;
    }

    $d = $built['data'];

    if (empty($d['source_type']) || empty($d['source_key']) || empty($d['posting_date'])) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'Validation error',
        ];
    }

    if (empty($d['packs'])) {
        return [
            'ok'      => false,
            'http'    => 422,
            'message' => 'No valid cost lines',
        ];
    }

    $job_no    = $d['source_key'];
    $region_id = $d['region_id'];

    /*
     * Preflight:
     * - item lama yang sudah dibayar harus tetap ada di snapshot terbaru
     * - item paid tidak boleh berubah amount/currency/vendor
     * - item paid boleh tetap ada dan item lain boleh berubah
     */
    $preflight = $this->_preflight_paid_items_against_latest($d);

    if (!$preflight['ok']) {
        return $preflight;
    }

    $this->erp->trans_begin();

    $created = [];
    $updated = [];
    $processedBillIds = [];

    foreach ($d['packs'] as $packKey => $pack) {
        $vendor_code  = trim((string)($pack['vendor_code'] ?? ''));
        $vendor_name  = trim((string)($pack['vendor_name'] ?? $vendor_code));
        $vendor_type  = strtoupper(trim((string)($pack['vendor_type'] ?? 'SUPPLIER')));
        $currency     = $this->_norm_currency($pack['currency'] ?? 'IDR');
        $posting_kurs = $this->_valid_kurs($currency, $pack['posting_kurs'] ?? 1);

        $calc = $this->_calc_pack_totals($pack['lines'] ?? []);

        if ($calc['total_gross'] <= 0) {
            continue;
        }

        if ($calc['total_pph23'] > 0 && $pph23 <= 0) {
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Missing/posting-disabled PPh23 Payable account (' . $this->_coa_code('PPH23_PAYABLE') . ')',
            ];
        }

        /*
         * Cari bill existing:
         * 1. dari ap_bill_source_map
         * 2. fallback source_type + source_key + vendor_code + currency
         */
        $this->erp
            ->from('ap_bill_source_map')
            ->where('company_id', $this->company_id)
            ->where('source_type', $d['source_type'])
            ->where('source_key', $d['source_key'])
            ->where('vendor_code', $packKey);

        if ($region_id !== '') {
            $this->erp->where('region_id', $region_id);
        }

        $map = $this->erp->get()->row_array();

        $bill = null;

        if ($map && !empty($map['bill_id'])) {
            $this->erp
                ->from('ap_bills')
                ->where('company_id', $this->company_id)
                ->where('bill_id', (int)$map['bill_id'])
                ->where("COALESCE(status,'') !=", 'CANCELLED');

            if ($region_id !== '') {
                $this->erp->where('region_id', $region_id);
            }

            $bill = $this->erp->get()->row_array();
        }

        if (!$bill) {
            $bill = $this->_find_bill_by_source_vendor_currency(
                $d['source_type'],
                $d['source_key'],
                $vendor_code,
                $currency,
                $region_id
            );
        }

        /*
         * Header yang selalu mengikuti snapshot terbaru.
         * paid_total/base_paid_total TIDAK direset saat update.
         */
        $header = [
            'company_id'          => $this->company_id,
            'region_id'           => $region_id,
            'source_key'          => $d['source_key'],
            'reference_job_no'    => $d['ref_job'],
            'source_type'         => $d['source_type'],
            'vendor_type'         => $vendor_type,
            'vendor_code'         => $vendor_code,
            'vendor_name'         => $vendor_name,
            'posting_date'        => $d['posting_date'],
            'currency'            => $currency,
            'posting_kurs'        => $posting_kurs,

            'gross_total'         => round($calc['total_gross'], 2),
            'pph23_total'         => round($calc['total_pph23'], 2),
            'pph23_system_total'  => round($calc['total_pph23_system'] ?? $calc['total_pph23'], 2),
            'has_system_pph23'    => (($calc['total_pph23_system'] ?? $calc['total_pph23']) > 0.01) ? 1 : 0,
            'withholding_type'    => $calc['total_pph23'] > 0 ? 'PPH23' : null,
            'withholding_rate'    => $calc['total_pph23'] > 0 ? 2.0000 : 0.0000,
            'withholding_amount'  => round($calc['total_pph23'], 2),

            'total'               => round($calc['total_net'], 2),
            'base_total'          => round($calc['total_net_base'], 2),

            'remark'              => "From {$d['source_type']} {$d['source_key']} (SYNC)",
            'status'              => 'SUBMITTED',
            'updated_by'          => $this->auth_username(),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        $isNew = !$bill;

        if ($isNew) {
            $bill_no = $this->_next_series('BILL', (int)date('Y', strtotime($d['posting_date'])));

            $header['bill_no']         = $bill_no;
            $header['paid_total']      = 0;
            $header['base_paid_total'] = 0;
            $header['created_by']      = $this->auth_username();
            $header['created_at']      = date('Y-m-d H:i:s');

            $this->_insert_bill_header($header);

            $bill_id = (int)$this->erp->insert_id();

            if ($bill_id <= 0) {
                $this->erp->trans_rollback();

                return [
                    'ok'      => false,
                    'http'    => 500,
                    'message' => 'Failed to create AP Bill header',
                    'data'    => [
                        'vendor_code' => $vendor_code,
                        'source_key'  => $d['source_key'],
                    ],
                ];
            }

            /*
             * ITEM ONLY:
             * - insert item baru
             * - item paid tidak mungkin ada karena bill baru
             */
            $syncItems = $this->_sync_bill_items_as_latest($bill_id, $pack['lines'], $hpp, [
                'region_id'    => $region_id,
                'posting_date' => $d['posting_date'],
                'job_no'       => $job_no,
                'currency'     => $currency,
                'posting_kurs' => $posting_kurs,
            ]);

            if (!$syncItems['ok']) {
                $this->erp->trans_rollback();
                return $syncItems;
            }

            /*
             * GL ONLY:
             * Jangan pakai _insert_pack_items_and_gl(), karena itu insert item lagi.
             */
            $this->_cancel_bill_gl($bill_no, $region_id);

            $this->_insert_pack_gl_only($bill_no, $pack['lines'], $hpp, $ap, $pph23, [
                'region_id'        => $region_id,
                'posting_date'     => $d['posting_date'],
                'job_no'           => $job_no,
                'currency'         => $currency,
                'posting_kurs'     => $posting_kurs,
                'total_net_base'   => $calc['total_net_base'],
                'total_pph23'      => $calc['total_pph23'],
                'total_pph23_base' => $calc['total_pph23_base'],
            ]);

            $this->erp->insert('ap_bill_source_map', [
                'company_id'  => $this->company_id,
                'region_id'   => $region_id,
                'source_type' => $d['source_type'],
                'source_key'  => $d['source_key'],
                'vendor_code' => $packKey,
                'bill_id'     => $bill_id,
            ]);

            $processedBillIds[] = $bill_id;

            $created[] = [
                'vendor_code'  => $vendor_code,
                'bill_no'      => $bill_no,
                'currency'     => $currency,
                'posting_kurs' => $posting_kurs,
                'total'        => round($calc['total_net'], 2),
                'base_total'   => round($calc['total_net_base'], 2),
                'gross'        => round($calc['total_gross'], 2),
                'pph23'        => round($calc['total_pph23'], 2),
                'pph23_system_total' => round($calc['total_pph23_system'] ?? $calc['total_pph23'], 2),
                'has_system_pph23'   => (($calc['total_pph23_system'] ?? $calc['total_pph23']) > 0.01) ? 1 : 0,
                'pph23_label'        => (($calc['total_pph23_system'] ?? $calc['total_pph23']) > 0.01) ? 'PPh23 by System' : null,
            ];

            continue;
        }

        /*
         * UPDATE existing bill.
         * Item paid yang sama tetap dipertahankan.
         * Item unpaid yang berubah/hilang akan diganti mengikuti snapshot terbaru.
         */
        $bill_id = (int)$bill['bill_id'];
        $bill_no = $bill['bill_no'];

        $processedBillIds[] = $bill_id;

        $syncItems = $this->_sync_bill_items_as_latest($bill_id, $pack['lines'], $hpp, [
            'region_id'    => $region_id,
            'posting_date' => $d['posting_date'],
            'job_no'       => $job_no,
            'currency'     => $currency,
            'posting_kurs' => $posting_kurs,
        ]);

        if (!$syncItems['ok']) {
            $this->erp->trans_rollback();
            return $syncItems;
        }

        /*
         * Repost GL BILL dari snapshot terbaru.
         * GL lama dibuat CANCELLED, GL baru POSTED.
         */
        $this->_cancel_bill_gl($bill_no, $region_id);

        $this->_insert_pack_gl_only($bill_no, $pack['lines'], $hpp, $ap, $pph23, [
            'region_id'        => $region_id,
            'posting_date'     => $d['posting_date'],
            'job_no'           => $job_no,
            'currency'         => $currency,
            'posting_kurs'     => $posting_kurs,
            'total_net_base'   => $calc['total_net_base'],
            'total_pph23'      => $calc['total_pph23'],
            'total_pph23_base' => $calc['total_pph23_base'],
        ]);

        /*
         * Update header.
         * Jangan reset paid_total / base_paid_total.
         */
        $this->erp
            ->where('company_id', $this->company_id)
            ->where('bill_id', $bill_id);

        if ($region_id !== '') {
            $this->erp->where('region_id', $region_id);
        }

        $this->_safe_update('ap_bills', $header);

        /*
         * Pastikan source map menunjuk bill aktif yang benar.
         */
        $this->erp
            ->where('company_id', $this->company_id)
            ->where('source_type', $d['source_type'])
            ->where('source_key', $d['source_key'])
            ->where('vendor_code', $packKey);

        if ($region_id !== '') {
            $this->erp->where('region_id', $region_id);
        }

        $existingMap = $this->erp->get('ap_bill_source_map')->row_array();

		if ($existingMap) {
			$this->erp
				->where('company_id', $this->company_id)
				->where('source_type', $d['source_type'])
				->where('source_key', $d['source_key'])
				->where('vendor_code', $packKey);

			if ($region_id !== '') {
				$this->erp->where('region_id', $region_id);
			}

			$this->_upsert_bill_source_map(
				$d['source_type'],
				$d['source_key'],
				$packKey,
				$bill_id,
				$region_id
			);
		} else {
			$this->_upsert_bill_source_map(
				$d['source_type'],
				$d['source_key'],
				$packKey,
				$bill_id,
				$region_id
			);
		}

        $updated[] = [
            'vendor_code'  => $vendor_code,
            'bill_no'      => $bill_no,
            'currency'     => $currency,
            'posting_kurs' => $posting_kurs,
            'total'        => round($calc['total_net'], 2),
            'base_total'   => round($calc['total_net_base'], 2),
            'gross'        => round($calc['total_gross'], 2),
            'pph23'        => round($calc['total_pph23'], 2),
        ];
    }

    /*
     * Bill aktif lama dari source yang sama tapi tidak ada di snapshot terbaru:
     * - kalau ada paid item => reject
     * - kalau belum paid => cancel
     */
    $obsolete = $this->_cancel_obsolete_unpaid_source_bills(
        $d['source_type'],
        $d['source_key'],
        $region_id,
        $processedBillIds
    );

    if (!$obsolete['ok']) {
        $this->erp->trans_rollback();
        return $obsolete;
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

    $this->erp->trans_commit();

    /*
     * Direct HPP mode:
     * Cost dari COST/GLOBALCOST langsung diposting ke akun HPP semantic saat AP Bill dibuat/update.
     * Tidak perlu lagi proses release WIP / COGS adjustment.
     */
    $cogs_adj = [
        'ok'      => true,
        'message' => 'Skipped: direct HPP mode'
    ];

    return [
        'ok'      => true,
        'message' => 'OK',
        'data'    => [
            'source_type' => $d['source_type'],
            'source_key'  => $d['source_key'],
            'region_id'   => $region_id,
            'created'     => $created,
            'updated'     => $updated,
            'cogs_adj'    => $cogs_adj,
        ],
    ];
}

  private function _insert_pack_items_and_gl($bill_id, $bill_no, array $lines, $hpp, $ap, $pph23, array $ctx)
  {
    $ln = 1;

    foreach ($lines as $l) {
      $gross = (float)($l['amount'] ?? 0);

      if ($gross <= 0) {
        continue;
      }

      $currency = $this->_norm_currency($l['currency'] ?? $ctx['currency'] ?? 'IDR');
      $posting_kurs = $this->_valid_kurs($currency, $l['posting_kurs'] ?? $ctx['posting_kurs'] ?? 1);
      $gross_base = (float)($l['base_amount'] ?? $l['amount_idr'] ?? $this->_base_amount($gross, $currency, $posting_kurs));

      $withholdingFlag = (int)($l['withholding_pph23'] ?? 0);
      $withholdingRate = $this->_normalize_withholding_rate($l['withholding_rate'] ?? 0);

      [$pph, $net] = $this->_calc_withholding(
        $gross,
        $withholdingFlag,
        $withholdingRate
      );

      [$pph_base, $net_base] = $this->_calc_withholding(
        $gross_base,
        $withholdingFlag,
        $withholdingRate
      );

      $this->_insert_bill_item([
        'bill_id'       => $bill_id,
        'line_no'       => $ln++,
        'expense_type'  => 'HPP',
        'account_id'    => $hpp,
        'job_id'        => $l['job_id'],
        'memo'          => $l['memo'],
        'currency'      => $currency,
        'posting_kurs'  => $posting_kurs,
        'amount'        => round($net, 2),
        'base_amount'   => round($net_base, 2),
        'gross_amount'  => round($gross, 2),
        'gross_base_amount' => round($gross_base, 2),
        'net_amount'    => round($net, 2),
        'net_base_amount' => round($net_base, 2),
        'withholding_pph23' => $withholdingFlag,
        'withholding_rate'  => $withholdingRate,
        'pph23_amount'      => round($pph, 2),
        'pph23_base_amount' => round($pph_base, 2),
        'pph23_source'      => ($withholdingFlag === 1 && round($pph, 2) > 0) ? 'SYSTEM_COST' : null,
      ]);

      // Debit HPP uses gross base, because PPh23 payable is credited separately.
      $this->_insert_gl_entry([
        'company_id'    => $this->company_id,
        'region_id'     => $ctx['region_id'],
        'posting_date'  => $ctx['posting_date'],
        'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
        'account_id'    => $hpp,
        'party_id'      => null,
        'debit'         => round($gross_base, 2),
        'credit'        => 0,
        'voucher_type'  => 'BILL',
        'voucher_no'    => $bill_no,
        'status'        => 'POSTED',
      ]);
    }

    $this->_insert_gl_entry([
      'company_id'    => $this->company_id,
      'region_id'     => $ctx['region_id'],
      'posting_date'  => $ctx['posting_date'],
      'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
      'account_id'    => $ap,
      'party_id'      => null,
      'debit'         => 0,
      'credit'        => round((float)$ctx['total_net_base'], 2),
      'voucher_type'  => 'BILL',
      'voucher_no'    => $bill_no,
      'status'        => 'POSTED',
    ]);

    if ((float)$ctx['total_pph23'] > 0) {
      $this->_insert_gl_entry([
        'company_id'    => $this->company_id,
        'region_id'     => $ctx['region_id'],
        'posting_date'  => $ctx['posting_date'],
        'job_no'        => !empty($ctx['job_no']) ? $ctx['job_no'] : null,
        'account_id'    => $pph23,
        'party_id'      => null,
        'debit'         => 0,
        'credit'        => round((float)$ctx['total_pph23_base'], 2),
        'voucher_type'  => 'BILL',
        'voucher_no'    => $bill_no,
        'status'        => 'POSTED',
      ]);
    }
  }

  private function _calc_pack_totals(array $lines)
  {
    $total_gross = 0.0;
    $total_pph23 = 0.0;
    $total_pph23_system = 0.0;
    $total_net = 0.0;

    $total_gross_base = 0.0;
    $total_pph23_base = 0.0;
    $total_pph23_system_base = 0.0;
    $total_net_base = 0.0;

    foreach ($lines as $l) {
      $gross = (float)($l['amount'] ?? 0);
      $gross_base = (float)($l['base_amount'] ?? $l['amount_idr'] ?? 0);

      if ($gross <= 0) {
        continue;
      }

      if ($gross_base <= 0) {
        $currency = $this->_norm_currency($l['currency'] ?? 'IDR');
        $posting_kurs = $this->_valid_kurs($currency, $l['posting_kurs'] ?? $l['kurs'] ?? 1);
        $gross_base = $this->_base_amount($gross, $currency, $posting_kurs);
      }

      $withholdingFlag = (int)($l['withholding_pph23'] ?? 0);
      $withholdingRate = $this->_normalize_withholding_rate($l['withholding_rate'] ?? 0);

      [$pph, $net] = $this->_calc_withholding(
        $gross,
        $withholdingFlag,
        $withholdingRate
      );

      [$pph_base, $net_base] = $this->_calc_withholding(
        $gross_base,
        $withholdingFlag,
        $withholdingRate
      );

      $total_gross += $gross;
      $total_pph23 += $pph;
      if ($withholdingFlag === 1 && $pph > 0) {
        $total_pph23_system += $pph;
      }
      $total_net   += $net;

      $total_gross_base += $gross_base;
      $total_pph23_base += $pph_base;
      if ($withholdingFlag === 1 && $pph_base > 0) {
        $total_pph23_system_base += $pph_base;
      }
      $total_net_base   += $net_base;
    }

    return [
      'total_gross'      => $total_gross,
      'total_pph23'      => $total_pph23,
      'total_pph23_system' => $total_pph23_system,
      'total_net'        => $total_net,
      'total_gross_base' => $total_gross_base,
      'total_pph23_base' => $total_pph23_base,
      'total_pph23_system_base' => $total_pph23_system_base,
      'total_net_base'   => $total_net_base,
    ];
  }

  private function _job_has_arinv_posted($job_no, $region_id)
  {
    $job_no = trim((string)$job_no);

    if ($job_no === '') {
      return false;
    }

    $this->erp->select('1', false)
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('job_no', $job_no)
      ->where('voucher_type', 'ARINV')
      ->where('status', 'POSTED')
      ->where('region_id', $region_id)
      ->limit(1);

    $q = $this->erp->get();

    return $q->num_rows() > 0;
  }

  private function _gl_balance_by_job_account($job_no, $account_id, $region_id)
  {
    $job_no = trim((string)$job_no);

    if ($job_no === '' || (int)$account_id <= 0) {
      return 0.0;
    }

    $this->erp->select('COALESCE(SUM(debit - credit), 0) AS bal', false)
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('job_no', $job_no)
      ->where('account_id', (int)$account_id)
      ->where('region_id', $region_id)
      ->where('status', 'POSTED');

    $row = $this->erp->get()->row_array();

    return (float)($row['bal'] ?? 0);
  }

  private function _next_cogs_adj_voucher_no($job_no, $posting_date, $region_id)
  {
    $job_no = trim((string)$job_no);
    $ymd = date('Ymd', strtotime($posting_date));

    $base = "COGS-ADJ-{$job_no}-{$ymd}";

    $this->erp->select('voucher_no', false)
      ->from('gl_entries')
      ->where('company_id', $this->company_id)
      ->where('region_id', $region_id)
      ->where('voucher_type', 'JE')
      ->like('voucher_no', $base, 'after')
      ->order_by('voucher_no', 'DESC')
      ->limit(1);

    $row = $this->erp->get()->row_array();

    if (!$row || empty($row['voucher_no'])) {
      return $base;
    }

    $last = (string)$row['voucher_no'];

    if ($last === $base) {
      return $base . '-02';
    }

    $m = [];

    if (preg_match('/^' . preg_quote($base, '/') . '\-(\d{2})$/', $last, $m)) {
      $n = (int)$m[1] + 1;

      if ($n < 2) {
        $n = 2;
      }

      if ($n > 99) {
        $n = 99;
      }

      return $base . '-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    return $base . '-02';
  }

  private function _recognize_cogs_adjustment_if_needed($job_no, $posting_date, $region_id)
  {
    /*
     * Legacy WIP/COGS flow dinonaktifkan.
     * ERP sekarang memakai direct HPP mode: COST/GLOBALCOST langsung ke akun HPP semantic dari finance_coa.php.
     *
     * Jangan menghidupkan kembali lookup akun parent sebagai WIP:
     * pada COA saat ini WIP legacy sudah tidak digunakan dan parent account tidak boleh diposting.
     */
    return [
      'ok'      => true,
      'message' => 'Skipped: direct HPP mode (legacy WIP/COGS disabled)',
      'data'    => [
        'job_no'    => trim((string)$job_no),
        'region_id' => trim((string)$region_id),
      ],
    ];
  }

  private function _calc_withholding($gross, $flag, $rate)
  {
    $gross = (float)$gross;
    $flag  = (int)$flag;

    if ($flag !== 1) {
      return [0.0, $gross];
    }

    $rate = $this->_normalize_withholding_rate($rate);

    $pph = round($gross * $rate, 2);
    $net = $gross - $pph;

    return [
      (float)$pph,
      (float)$net
    ];
  }

  private function _norm_currency($currency)
  {
    $currency = strtoupper(trim((string)$currency));

    if (!in_array($currency, ['IDR', 'USD'], true)) {
      return 'IDR';
    }

    return $currency;
  }

  private function _valid_kurs($currency, $kurs)
  {
    $currency = $this->_norm_currency($currency);
    $kurs = $this->_parse_number($kurs);

    if ($currency === 'IDR') {
      return 1.0;
    }

    return $kurs > 0 ? $kurs : 0.0;
  }

  private function _base_amount($amount, $currency, $kurs)
  {
    $amount = (float)$amount;
    $currency = $this->_norm_currency($currency);
    $kurs = $this->_valid_kurs($currency, $kurs);

    if ($currency === 'USD') {
      return round($amount * $kurs, 2);
    }

    return round($amount, 2);
  }

  private function _parse_number($value)
  {
    if (is_numeric($value)) {
      return (float)$value;
    }

    $value = trim((string)$value);

    if ($value === '') {
      return 0.0;
    }

    // Handles "15,600.50" and "15.600,50" reasonably.
    if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
      if (strrpos($value, ',') > strrpos($value, '.')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
      } else {
        $value = str_replace(',', '', $value);
      }
    } else {
      $value = str_replace(',', '', $value);
    }

    $value = preg_replace('/[^\d\.\-]/', '', $value);

    return is_numeric($value) ? (float)$value : 0.0;
  }

  private function _field_exists($table, $field)
  {
    return $this->erp->field_exists($field, $table);
  }

  private function _filter_existing_fields($table, array $data)
  {
    $out = [];

    foreach ($data as $k => $v) {
      if ($this->_field_exists($table, $k)) {
        $out[$k] = $v;
      }
    }

    return $out;
  }

  private function _safe_update($table, array $data)
  {
    $this->erp->update($table, $this->_filter_existing_fields($table, $data));
  }

  private function _insert_bill_header(array $data)
  {
    $this->erp->insert('ap_bills', $this->_filter_existing_fields('ap_bills', $data));
  }

  private function _insert_bill_item(array $data)
  {
    $this->erp->insert('ap_bill_items', $this->_filter_existing_fields('ap_bill_items', $data));
  }

  private function _insert_gl_entry(array $data)
  {
    if (empty($data['created_at'])) {
      $data['created_at'] = date('Y-m-d H:i:s');
    }

    if ($this->_field_exists('gl_entries', 'updated_at') && empty($data['updated_at'])) {
      $data['updated_at'] = $data['created_at'];
    }

    $this->erp->insert('gl_entries', $this->_filter_existing_fields('gl_entries', $data));
  }
  
  private function _upsert_bill_source_map($source_type, $source_key, $vendor_code, $bill_id, $region_id)
{
    $source_type = strtoupper(trim((string)$source_type));
    $source_key  = trim((string)$source_key);
    $vendor_code = trim((string)$vendor_code);
    $bill_id     = (int)$bill_id;
    $region_id   = trim((string)$region_id);

    if ($source_type === '' || $source_key === '' || $vendor_code === '' || $bill_id <= 0) {
        return;
    }

    $this->erp
        ->from('ap_bill_source_map')
        ->where('company_id', $this->company_id)
        ->where('source_type', $source_type)
        ->where('source_key', $source_key)
        ->where('vendor_code', $vendor_code);

    if ($region_id !== '') {
        $this->erp->where('region_id', $region_id);
    }

    $existing = $this->erp->get()->row_array();

    if ($existing) {
        $this->erp
            ->where('company_id', $this->company_id)
            ->where('source_type', $source_type)
            ->where('source_key', $source_key)
            ->where('vendor_code', $vendor_code);

        if ($region_id !== '') {
            $this->erp->where('region_id', $region_id);
        }

        $this->erp->update('ap_bill_source_map', [
            'bill_id' => $bill_id,
        ]);

        return;
    }

    $this->erp->insert('ap_bill_source_map', [
        'company_id'  => $this->company_id,
        'region_id'   => $region_id,
        'source_type' => $source_type,
        'source_key'  => $source_key,
        'vendor_code' => $vendor_code,
        'bill_id'     => $bill_id,
    ]);
}
}
