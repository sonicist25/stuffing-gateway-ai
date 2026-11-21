<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class M_quotations extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    /* =========================
     * LIST + DETAIL
     * ========================= */

    public function list($filters = []) {
        $page  = max(1, (int)($filters['page'] ?? 1));
        $limit = min(100, max(1, (int)($filters['limit'] ?? 20)));
        $off   = ($page - 1) * $limit;

        $q       = trim((string)($filters['q'] ?? ''));
        $region  = (string)($filters['region'] ?? '');
        $sales   = (string)($filters['sales'] ?? '');
        $status  = (string)($filters['status'] ?? '');
        $oppId   = $filters['opportunity_id'] ?? null;

        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'left');
        $this->dbx->join('refsales rs', 'rs.user_id = qt.created_by_sales_id', 'left');

        $this->dbx->select([
            'qt.quote_id','qt.quote_no','qt.opportunity_id','qt.account_name','qt.status',
            'qt.quote_date','qt.valid_until','qt.currency','qt.total_amount','qt.show_total','qt.multi_destination',
            'qt.cargo_type','qt.dg_class','qt.hs_code',
            'qt.last_sent_at','qt.created_by_sales_id','qt.created_at','qt.updated_at',
            'op.owner_sales_id','op.region_id','op.account_name AS opportunity_account_name',
            'op.shipment_type','op.transport_mode','op.sea_type','op.incoterm','op.service_level','op.origin','op.destination',
            'rs.fullname AS created_by_name'
        ]);

        $this->dbx->where('qt.deleted_at IS NULL', null, false);

        if ($oppId !== null && $oppId !== '') {
            $this->dbx->where('qt.opportunity_id', (int)$oppId);
        }

        if ($status !== '') {
            $this->dbx->where('qt.status', $status);
        }

        // region filter via opportunity (kalau quote tanpa opportunity, region null)
        if ($region !== '') {
            $this->dbx->where('op.region_id', $region);
        }

        // sales filter: pakai owner_sales_id dari opportunity, fallback ke created_by_sales_id
        if ($sales !== '' && strtoupper($sales) !== 'ALL') {
            $this->dbx->group_start();
            $this->dbx->where('op.owner_sales_id', (int)$sales);
            $this->dbx->or_where('qt.created_by_sales_id', (int)$sales);
            $this->dbx->group_end();
        }

        if ($q !== '') {
            $this->dbx->group_start();
            $this->dbx->like('qt.quote_no', $q);
            $this->dbx->or_like('qt.account_name', $q);
            $this->dbx->or_like('op.account_name', $q);
            $this->dbx->or_like('qt.status', $q);
            $this->dbx->or_like('qt.hs_code', $q);
            $this->dbx->or_like('qt.dg_class', $q);
            $this->dbx->or_like('qt.cargo_type', $q);
            $this->dbx->group_end();
        }

        // count
        $clone = clone $this->dbx;
        $total = (int)$clone->count_all_results();

        $this->dbx->order_by('qt.created_at', 'DESC');
        $rows = $this->dbx->limit($limit, $off)->get()->result_array();

        foreach ($rows as &$row) {
            $oppAccount = trim((string)($row['opportunity_account_name'] ?? ''));
            if ($oppAccount !== '') $row['account_name'] = $oppAccount;
        }
        unset($row);

        return [
            'status' => true,
            'page'   => $page,
            'limit'  => $limit,
            'total'  => $total,
            'data'   => $rows,
        ];
    }

    /* =========================
     * INCOME INTEGRATION (NON-JAKARTA)
     * ========================= */

    private function normalize_income_date($value)
    {
        $value = trim((string)$value);
        if ($value === '') return date('Y-m-d');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }

    private function income_destination_candidates($destination)
    {
        $destination = strtoupper(trim((string)$destination));
        if ($destination === '') return [];

        $candidates = [$destination];

        // Tambahkan token kota sebelum koma/slash bila jobsheet menyimpan format panjang.
        foreach (preg_split('/[,\/]/', $destination) as $part) {
            $part = strtoupper(trim($part));
            if (strlen($part) >= 3) $candidates[] = $part;
        }

        // Resolve nama <-> UN/LOCODE dari master lokasi yang juga dipakai modul tracking/rate.
        if ($this->dbx->table_exists('tableName')) {
            $esc = $this->dbx->escape($destination);
            $rows = $this->dbx
                ->select('name, code')
                ->from('tableName')
                ->group_start()
                    ->where('UPPER(name) = ' . $esc, null, false)
                    ->or_where('UPPER(code) = ' . $esc, null, false)
                ->group_end()
                ->limit(10)
                ->get()->result_array();

            foreach ($rows as $row) {
                foreach (['name','code'] as $k) {
                    $v = strtoupper(trim((string)($row[$k] ?? '')));
                    if ($v !== '') $candidates[] = $v;
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function apply_income_match_filters(array $filters, array $destCandidates = [])
    {
        $region      = trim((string)($filters['region_id'] ?? ''));
        $salesId     = (int)($filters['sales_id'] ?? 0);
        $shipperId   = (int)($filters['shipper_id'] ?? 0);
        $shipperName = trim((string)($filters['shipper'] ?? $filters['shipper_name'] ?? ''));
        $destination = trim((string)($filters['destination'] ?? ''));
        $validDate   = $this->normalize_income_date($filters['valid_date'] ?? '');

        // Region selalu berasal dari session controller, bukan dari data quotation/client.
        if ($region !== '') {
            $this->dbx->where('op.region_id', $region);
        }

        // Sales utama adalah owner Opportunity. created_by hanya fallback bila owner Opportunity kosong.
        if ($salesId > 0) {
            $this->dbx->where(
                'COALESCE(NULLIF(op.owner_sales_id,0), qt.created_by_sales_id) = ' . $salesId,
                null,
                false
            );
        }

        // Prioritas matching customer memakai id_shipper, nama hanya fallback untuk data lama.
        $cleanShipperName = preg_replace('/^(PT\.?|CV\.?|UD\.?)\s+/i', '', $shipperName);
        $cleanShipperName = trim((string)$cleanShipperName);

        if ($shipperId > 0) {
            $this->dbx->group_start();
                $this->dbx->where('op.id_shipper', $shipperId);
                // Fallback untuk quotation yang awalnya dibuat ketika account masih LEAD
                // dan id_shipper Opportunity belum terisi.
                if ($cleanShipperName !== '') {
                    $this->dbx->or_like('op.account_name', $cleanShipperName);
                    $this->dbx->or_like('qt.account_name', $cleanShipperName);
                }
            $this->dbx->group_end();
        } elseif ($cleanShipperName !== '') {
            $this->dbx->group_start()
                ->like('op.account_name', $cleanShipperName)
                ->or_like('qt.account_name', $cleanShipperName)
            ->group_end();
        }

        // Destination candidates SUDAH di-resolve sebelum query utama dibangun.
        // Jangan query tableName dari method ini, karena ->get() pada DB object yang sama
        // akan me-reset Query Builder CI3 dan menghilangkan FROM crm_quotations/opportunity.
        if ($destCandidates) {
            $this->dbx->group_start();
            foreach ($destCandidates as $i => $candidate) {
                if ($i === 0) {
                    $this->dbx->where('UPPER(TRIM(op.destination)) = ' . $this->dbx->escape($candidate), null, false);
                } else {
                    $this->dbx->or_where('UPPER(TRIM(op.destination)) = ' . $this->dbx->escape($candidate), null, false);
                }
            }
            // Fallback ringan untuk data lama yang menyimpan nama panjang, mis. "HO CHI MINH CITY".
            if (strlen($destination) >= 3) $this->dbx->or_like('op.destination', $destination);
            $this->dbx->group_end();
        }

        // Valid_until bersifat inclusive. Null/kosong dianggap tidak memiliki expiry eksplisit.
        $this->dbx->group_start()
            ->where('qt.valid_until IS NULL', null, false)
            ->or_where('qt.valid_until', '')
            ->or_where('qt.valid_until >=', $validDate)
        ->group_end();

        // Quotation yang sudah ditolak/dibatalkan/accepted tidak ditawarkan lagi.
        // DRAFT/SENT/APPROVED tetap diizinkan karena workflow internal belum
        // mewajibkan approval sebelum jobsheet dibuat.
        $this->dbx->where(
            'UPPER(COALESCE(qt.status, "")) NOT IN (' .
                $this->dbx->escape('REJECTED') . ',' .
                $this->dbx->escape('CANCELLED') . ',' .
                $this->dbx->escape('ACCEPTED') .
            ')',
            null,
            false
        );
    }

    public function list_for_income(array $filters = [])
    {
        // Jangan pernah mengembalikan list luas bila konteks utama belum tersedia.
        if (trim((string)($filters['region_id'] ?? '')) === ''
            || (int)($filters['sales_id'] ?? 0) <= 0
            || trim((string)($filters['destination'] ?? '')) === ''
            || ((int)($filters['shipper_id'] ?? 0) <= 0 && trim((string)($filters['shipper'] ?? '')) === '')) {
            return [];
        }

        // IMPORTANT: resolve destination alias SEBELUM membangun query utama.
        // income_destination_candidates() melakukan query lookup ke tableName dan CI3
        // akan me-reset state Query Builder setelah ->get().
        $destCandidates = $this->income_destination_candidates($filters['destination'] ?? '');

        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'inner');
        $this->dbx->join('refsales os', 'os.user_id = op.owner_sales_id', 'left');
        $this->dbx->join('refsales cs', 'cs.user_id = qt.created_by_sales_id', 'left');
        $this->dbx->select([
            'qt.quote_id','qt.quote_no','qt.opportunity_id','qt.status','qt.quote_date','qt.valid_until',
            'qt.currency','qt.created_by_sales_id','qt.created_at',
            'op.id_shipper','op.account_name','op.region_id','op.owner_sales_id','op.source_type','op.lead_id','op.stage AS opportunity_stage',
            'op.shipment_type','op.transport_mode','op.sea_type','op.container_size','op.incoterm',
            'op.origin','op.destination',
            'COALESCE(os.fullname, cs.fullname) AS owner_name'
        ], false);
        $this->dbx->where('qt.deleted_at IS NULL', null, false);
        $this->dbx->where('op.deleted_at IS NULL', null, false);
        $this->dbx->where('qt.quote_no !=', '');

        $this->apply_income_match_filters($filters, $destCandidates);

        return $this->dbx
            ->order_by('qt.created_at', 'DESC')
            ->limit(100)
            ->get()->result_array();
    }

    public function get_for_income_by_no($quoteNo, array $filters = [])
    {
        $quoteNo = trim((string)$quoteNo);
        if ($quoteNo === '') return null;

        // Resolve alias destination sebelum query quotation utama dibangun.
        // Ini mencegah lookup tableName mereset Query Builder CI3 di tengah query.
        $destCandidates = $this->income_destination_candidates($filters['destination'] ?? '');

        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'inner');
        $this->dbx->join('refsales os', 'os.user_id = op.owner_sales_id', 'left');
        $this->dbx->join('refsales cs', 'cs.user_id = qt.created_by_sales_id', 'left');
        $this->dbx->select([
            'qt.*',
            'op.id_shipper AS opportunity_shipper_id','op.account_name AS opportunity_account_name',
            'op.region_id','op.owner_sales_id','op.source_type AS opportunity_source_type','op.lead_id AS opportunity_lead_id','op.stage AS opportunity_stage','op.shipment_type','op.transport_mode','op.sea_type',
            'op.container_size','op.incoterm','op.origin','op.destination',
            'COALESCE(os.fullname, cs.fullname) AS owner_name'
        ], false);
        $this->dbx->where('qt.deleted_at IS NULL', null, false);
        $this->dbx->where('op.deleted_at IS NULL', null, false);
        $this->dbx->where('qt.quote_no', $quoteNo);

        $this->apply_income_match_filters($filters, $destCandidates);

        $quote = $this->dbx->limit(1)->get()->row_array();
        if (!$quote) return null;

        $quote['account_name'] = trim((string)($quote['opportunity_account_name'] ?? '')) !== ''
            ? $quote['opportunity_account_name']
            : ($quote['account_name'] ?? '');
        $quote['id_shipper'] = (int)($quote['opportunity_shipper_id'] ?? $quote['id_shipper'] ?? 0);

        // qt.source_type berarti OPPORTUNITY/MANUAL, sedangkan lifecycle membutuhkan
        // source Opportunity (LEAD/CUSTOMER). Hindari collision nama kolom qt.*.
        $quote['source_type'] = strtoupper(trim((string)($quote['opportunity_source_type'] ?? '')));
        $quote['lead_id'] = (int)($quote['opportunity_lead_id'] ?? $quote['lead_id'] ?? 0);

        $items = $this->dbx
            ->from('crm_quotation_items')
            ->where('quote_id', (int)$quote['quote_id'])
            ->order_by("FIELD(item_type,'FREIGHT','ORIGIN','DESTINATION')", '', false)
            ->order_by('dest_code', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('line_no', 'ASC')
            ->get()->result_array();

        return ['quote' => $quote, 'items' => $items];
    }


    public function get($quoteId) {
        $quoteId = (int)$quoteId;

        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'left');
        $this->dbx->join('refsales rs', 'rs.user_id = qt.created_by_sales_id', 'left');

        $this->dbx->select([
            'qt.*',
            'op.owner_sales_id','op.region_id','op.account_name AS opportunity_account_name',
            'op.shipment_type','op.transport_mode','op.sea_type','op.incoterm','op.service_level','op.origin','op.destination',
            'rs.fullname AS created_by_name'
        ]);

        $this->dbx->where('qt.deleted_at IS NULL', null, false);
        $this->dbx->where('qt.quote_id', $quoteId);

        $q = $this->dbx->get()->row_array();
        if (!$q) return null;

        $oppAccount = trim((string)($q['opportunity_account_name'] ?? ''));
        if ($oppAccount !== '') $q['account_name'] = $oppAccount;

        $items = $this->get_items($quoteId);

        return [
            'quote' => $q,
            'items' => $items,
        ];
    }

    /* =========================
     * ITEMS
     * ========================= */

    public function get_items($quoteId) {
	  $quoteId = (int)$quoteId;

	  $rows = $this->dbx
		->from('crm_quotation_items')
		->where('quote_id', $quoteId)
		->order_by("FIELD(item_type,'FREIGHT','ORIGIN','DESTINATION')", '', false)
		->order_by('dest_code', 'ASC')
		->order_by('sort_order', 'ASC')
		->order_by('line_no', 'ASC')
		->get()->result_array();

	  $group = ['FREIGHT' => [], 'ORIGIN' => [], 'DESTINATION' => []];
	  foreach ($rows as $r) {
		$t = strtoupper((string)$r['item_type']);
		if (!isset($group[$t])) $group[$t] = [];
		$group[$t][] = $r;
	  }

	  $sub = ['FREIGHT'=>0, 'ORIGIN'=>0, 'DESTINATION'=>0, 'TOTAL'=>0];
	  foreach (['FREIGHT','ORIGIN','DESTINATION'] as $t) {
		foreach ($group[$t] as $it) $sub[$t] += (float)$it['amount'];
		$sub['TOTAL'] += $sub[$t];
	  }

	  return ['groups' => $group, 'subtotal' => $sub];
	}


    public function replace_items($quoteId, $items = []) {
        $quoteId = (int)$quoteId;

        $quote = $this->dbx->where('quote_id', $quoteId)->where('deleted_at IS NULL', null, false)->get('crm_quotations')->row_array();
        if (!$quote) throw new Exception('Quotation not found');

        $currency = strtoupper((string)($quote['currency'] ?? 'IDR'));

        $this->dbx->trans_start();

        $this->dbx->where('quote_id', $quoteId)->delete('crm_quotation_items');

        $this->insert_items($quoteId, $currency, $items);

        // Recalculate safely. Mixed-currency quotations automatically hide grand total.
        $this->refresh_quote_totals($quoteId);

        $this->dbx->trans_complete();

        if ($this->dbx->trans_status() === FALSE) {
            throw new Exception('Failed to replace items');
        }

        return true;
    }

    private function normalize_pricing_basis($v) {
		$s = strtoupper(trim((string)$v));
		if ($s === 'ATCOST') $s = 'AT_COST';

        $allowed = [
            'FIXED', 'AT_COST', 'IF_ANY', 'PER_RECEIPT',
            'QUOTE_REQUIRED', 'COLLECT', 'EXCLUDED', 'ESTIMATE', 'INFO', 'AS_AGREED'
        ];

		if (!in_array($s, $allowed, true)) $s = 'FIXED';
		return $s;
	}


    private function opportunity_account_name($opportunityId) {
        $opportunityId = (int)$opportunityId;
        if ($opportunityId <= 0) return '';

        $row = $this->dbx
            ->select('account_name')
            ->where('opportunity_id', $opportunityId)
            ->where('deleted_at IS NULL', null, false)
            ->get('crm_opportunities')
            ->row_array();

        return trim((string)($row['account_name'] ?? ''));
    }

	private function safe_json($v) {
		if (is_string($v)) {
			$s = trim($v);
			if ($s === '') return '[]';
			// jika string json sudah valid, pakai apa adanya
			json_decode($s, true);
			if (json_last_error() === JSON_ERROR_NONE) return $s;
			return '[]';
		}
		if (is_array($v)) {
			return json_encode($v, JSON_UNESCAPED_UNICODE);
		}
		return '[]';
	}


    private function normalize_cargo_type($value) {
        $value = strtoupper(trim((string)$value));

        if (in_array($value, [
            'DG', 'DANGEROUS', 'DANGEROUS GOODS', 'HAZARDOUS', 'HAZARDOUS CARGO'
        ], true)) {
            return 'DG';
        }

        return 'GENERAL';
    }

	private function insert_items($quoteId, $currency, $items) {
		$quoteId = (int)$quoteId;
		if (!is_array($items)) $items = [];
		if (!$items) return;

		$now = date('Y-m-d H:i:s');

		$seq  = ['FREIGHT'=>1,'ORIGIN'=>1,'DESTINATION'=>1];
		$sort = 1;

		foreach ($items as $it) {
			if (!is_array($it)) continue;

			$type = strtoupper(trim((string)($it['item_type'] ?? 'FREIGHT')));
			if (!in_array($type, ['FREIGHT','ORIGIN','DESTINATION'], true)) $type = 'FREIGHT';

			// ✅ row boleh tetap disimpan selama ada minimal salah satu field utama terisi
			$desc   = trim((string)($it['description'] ?? ''));
			$detail = trim((string)($it['charge_detail'] ?? ''));
			$code   = trim((string)($it['charge_code'] ?? ''));
			$hasAny = ($desc !== '' || $detail !== '' || $code !== '');

			if (!$hasAny) continue;

			$destCode = trim((string)($it['dest_code'] ?? ''));
			$destName = trim((string)($it['dest_name'] ?? ''));
			$uom      = trim((string)($it['uom'] ?? ''));

			// ✅ qty boleh 0 (jangan dipaksa 1)
			$qty = (float)($it['qty'] ?? 0);

			$pricing = $this->normalize_pricing_basis($it['pricing_basis'] ?? 'FIXED');
			$unitPrice = (float)($it['unit_price'] ?? 0);
			$minCharge = (float)($it['min_charge'] ?? 0);

			$subItemsRaw = $it['sub_items'] ?? [];
			$subJson = $this->safe_json($subItemsRaw);
			$subArr = json_decode($subJson, true);
			$hasSub = is_array($subArr) && count($subArr) > 0;

			// IMPORTANT:
			// Kolom DB `min_charge` dipertahankan untuk kompatibilitas,
			// tetapi secara bisnis nilainya adalah MINIMUM QTY, bukan nominal uang.
			// Contoh qty=1, min_qty=3, unit_price=USD 50 -> amount=3*50=USD 150.
			$minQty = max(0, $minCharge);
			$billingQty = ($minQty > 0 && $qty < $minQty) ? $minQty : max(0, $qty);

			if (in_array($pricing, ['FIXED', 'ESTIMATE'], true)) {
				// Backend selalu hitung ulang agar amount FE yang stale/manipulated tidak tersimpan.
				$amount = $billingQty * $unitPrice;
			} elseif ($pricing === 'AT_COST') {
				$amount = 0;
			} else {
				// Basis non-total seperti IF_ANY/QUOTE_REQUIRED/AS_AGREED tidak punya amount pasti.
				$amount = 0;
			}

			// ✅ kalau ada sub => exclude totals & amount = 0 (sesuai “opsi harga”)
			$includeInTotal = (int)($it['include_in_total'] ?? 1);
			if ($hasSub) {
				$includeInTotal = 0;
				$amount = 0;
			}

			$row = [
				'quote_id'       => $quoteId,
				'item_type'      => $type,
				'line_no'        => isset($it['line_no']) ? (int)$it['line_no'] : $seq[$type]++,

				'dest_code'      => ($destCode !== '' ? $destCode : null),
				'dest_name'      => ($destName !== '' ? $destName : null),

				'charge_code'    => ($code !== '' ? $code : null),
				'description'    => ($desc !== '' ? $desc : null),
				'charge_detail'  => ($detail !== '' ? $detail : null),

				'pricing_basis'  => $pricing,
				'qty'            => $qty,
				'uom'            => ($uom !== '' ? $uom : null),

				'unit_price'     => ($pricing === 'AT_COST' ? 0 : $unitPrice),
				'min_charge'     => $minCharge,
				'amount'         => $amount,

				'include_in_total' => $includeInTotal,
				'sub_items'        => $subJson,  // ✅ pasti valid JSON

				'currency'      => $this->normalize_currency_code($it['currency'] ?? $currency, $currency),
				'sort_order'    => isset($it['sort_order']) ? (int)$it['sort_order'] : $sort++,

				'created_at'    => $now,
				'updated_at'    => $now,
			];

			$this->dbx->insert('crm_quotation_items', $row);
		}
	}



    private function normalize_currency_code($value, $fallback = 'IDR') {
        $value = strtoupper(trim((string)$value));
        $fallback = strtoupper(trim((string)$fallback));
        if ($fallback === '' || in_array($fallback, ['RP', 'RP.', 'RUPIAH'], true)) $fallback = 'IDR';
        if ($value === '' || in_array($value, ['RP', 'RP.', 'RUPIAH'], true)) return $fallback;
        return $value;
    }

    private function get_billable_currencies($quoteId) {
        $rows = $this->dbx
            ->select('currency')
            ->from('crm_quotation_items')
            ->where('quote_id', (int)$quoteId)
            ->where('include_in_total', 1)
            ->where_in('pricing_basis', ['FIXED', 'ESTIMATE'])
            ->group_by('currency')
            ->get()->result_array();

        $out = [];
        foreach ($rows as $row) {
            $curr = $this->normalize_currency_code($row['currency'] ?? 'IDR', 'IDR');
            if ($curr !== '') $out[$curr] = true;
        }
        return array_keys($out);
    }

    private function refresh_quote_totals($quoteId) {
        $quoteId = (int)$quoteId;
        $quote = $this->dbx
            ->select('show_total,currency')
            ->where('quote_id', $quoteId)
            ->get('crm_quotations')
            ->row_array();

        if (!$quote) return;

        $currencies = $this->get_billable_currencies($quoteId);
        if (count($currencies) > 1) {
            $this->dbx
                ->set('show_total', 0)
                ->set('total_amount', null)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('quote_id', $quoteId)
                ->update('crm_quotations');
            return;
        }

        $showTotal = (int)($quote['show_total'] ?? 0);
        $total = $showTotal === 1 ? $this->sum_amount($quoteId) : null;

        $this->dbx
            ->set('total_amount', $total)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('quote_id', $quoteId)
            ->update('crm_quotations');
    }

    private function sum_amount($quoteId) {
        $r = $this->dbx->select('COALESCE(SUM(amount),0) AS total', false)
            ->from('crm_quotation_items')
            ->where('quote_id', (int)$quoteId)
            ->where('include_in_total', 1)
            ->get()->row_array();
        return (float)($r['total'] ?? 0);
    }

    public function find_by_ai_draft_id($draftId) {
        $draftId = (int)$draftId;
        if ($draftId <= 0) return null;

        return $this->dbx
            ->where('source_ai_draft_id', $draftId)
            ->where('deleted_at IS NULL', null, false)
            ->get('crm_quotations')
            ->row_array();
    }

    /* =========================
     * CREATE / UPDATE / DELETE
     * ========================= */

    public function create($payload, $userId) {
        if (!is_array($payload)) $payload = [];

        $now = date('Y-m-d H:i:s');
        $items = $payload['items'] ?? [];
        unset($payload['items']);

        $currency = strtoupper((string)($payload['currency'] ?? 'IDR'));
        $showTotal = isset($payload['show_total']) ? (int)$payload['show_total'] : 1;

        // Account quotation wajib mengikuti Opportunity bila opportunity_id tersedia.
        $opportunityId = !empty($payload['opportunity_id']) ? (int)$payload['opportunity_id'] : 0;
        $accountName = trim((string)($payload['account_name'] ?? ''));
        if ($opportunityId > 0) {
            $accountName = $this->opportunity_account_name($opportunityId);
            if ($accountName === '') {
                throw new Exception('Opportunity account_name is empty');
            }
        }

        $cargoType = $this->normalize_cargo_type($payload['cargo_type'] ?? 'GENERAL');
        $dgClass = trim((string)($payload['dg_class'] ?? ''));
        $hsCode = trim((string)($payload['hs_code'] ?? ''));

        if ($cargoType !== 'DG') {
            $dgClass = '';
        }

        $row = [
            'quote_no'            => trim((string)($payload['quote_no'] ?? '')),
            'source_ai_draft_id'  => !empty($payload['source_ai_draft_id']) ? (int)$payload['source_ai_draft_id'] : null,
            'opportunity_id'      => $opportunityId > 0 ? $opportunityId : null,
            'account_name'        => $accountName,
            'status'              => trim((string)($payload['status'] ?? 'DRAFT')),
            'quote_date'          => !empty($payload['quote_date']) ? $payload['quote_date'] : date('Y-m-d'),
            'valid_until'         => !empty($payload['valid_until']) ? $payload['valid_until'] : null,
            'total_amount'        => null, // set after items
            'show_total'          => $showTotal,
            'multi_destination'   => isset($payload['multi_destination']) ? (int)$payload['multi_destination'] : 0,
            'currency'            => $currency,
            'cargo_type'          => $cargoType,
            'dg_class'            => $dgClass !== '' ? $dgClass : null,
            'hs_code'             => $hsCode !== '' ? $hsCode : null,
            'rate_information'    => $this->safe_json($payload['rate_information'] ?? []),
            'created_by_sales_id' => (int)$userId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->dbx->trans_start();

        $this->dbx->insert('crm_quotations', $row);
        $quoteId = (int)$this->dbx->insert_id();

        // auto quote_no jika kosong
        if ($row['quote_no'] === '') {
            $auto = sprintf('QT/%s/%05d', date('ymd'), $quoteId);
            $this->dbx->set('quote_no', $auto)->where('quote_id', $quoteId)->update('crm_quotations');
        }

        $this->insert_items($quoteId, $currency, $items);

        $this->refresh_quote_totals($quoteId);

        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === FALSE) throw new Exception('Failed to create quotation');

        return $quoteId;
    }

    public function update($quoteId, $payload) {
        $quoteId = (int)$quoteId;
        if (!is_array($payload)) $payload = [];

        $items = null;
        if (array_key_exists('items', $payload)) {
            $items = $payload['items'];
            unset($payload['items']);
        }

        $quote = $this->dbx->where('quote_id', $quoteId)->where('deleted_at IS NULL')->get('crm_quotations')->row_array();
        if (!$quote) throw new Exception('Quotation not found');

        $row = [];
        $allow = [
            'quote_no','opportunity_id','status','quote_date','valid_until',
            'show_total','multi_destination','currency','last_sent_at',
            'cargo_type','dg_class','hs_code','rate_information'
        ];
        foreach ($allow as $k) {
            if (array_key_exists($k, $payload)) $row[$k] = $payload[$k];
        }

        if (isset($row['currency'])) $row['currency'] = strtoupper((string)$row['currency']);
        if (isset($row['show_total'])) $row['show_total'] = (int)$row['show_total'];
        if (isset($row['multi_destination'])) $row['multi_destination'] = (int)$row['multi_destination'];
        if (array_key_exists('cargo_type', $row)) {
            $row['cargo_type'] = $this->normalize_cargo_type($row['cargo_type']);
        }
        if (array_key_exists('dg_class', $row)) {
            $row['dg_class'] = trim((string)$row['dg_class']);
            if ($row['dg_class'] === '') $row['dg_class'] = null;
        }
        if (array_key_exists('hs_code', $row)) {
            $row['hs_code'] = trim((string)$row['hs_code']);
            if ($row['hs_code'] === '') $row['hs_code'] = null;
        }
        if (array_key_exists('rate_information', $row)) {
            $row['rate_information'] = $this->safe_json($row['rate_information']);
        }

        $effectiveCargoType = $row['cargo_type'] ?? ($quote['cargo_type'] ?? 'GENERAL');
        if ($this->normalize_cargo_type($effectiveCargoType) !== 'DG') {
            $row['dg_class'] = null;
        }

        // Jangan izinkan account quotation berbeda dari Opportunity.
        $effectiveOpportunityId = array_key_exists('opportunity_id', $row)
            ? (int)$row['opportunity_id']
            : (int)($quote['opportunity_id'] ?? 0);

        if ($effectiveOpportunityId > 0) {
            $opportunityAccount = $this->opportunity_account_name($effectiveOpportunityId);
            if ($opportunityAccount === '') {
                throw new Exception('Opportunity account_name is empty');
            }
            $row['account_name'] = $opportunityAccount;
        } elseif (array_key_exists('account_name', $payload)) {
            // Hanya legacy quotation tanpa Opportunity yang boleh account manual.
            $row['account_name'] = trim((string)$payload['account_name']);
        }

        $row['updated_at'] = date('Y-m-d H:i:s');

        $this->dbx->trans_start();

        if ($row) $this->dbx->where('quote_id', $quoteId)->update('crm_quotations', $row);

        if ($items !== null) {
            $this->replace_items($quoteId, $items);
        } else {
            // Recalculate safely. Mixed-currency quotations automatically hide grand total.
            $this->refresh_quote_totals($quoteId);
        }

        $this->dbx->trans_complete();
        if ($this->dbx->trans_status() === FALSE) throw new Exception('Failed to update quotation');

        return true;
    }

    public function soft_delete($quoteId) {
        $quoteId = (int)$quoteId;
        $this->dbx->where('quote_id', $quoteId)->update('crm_quotations', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->dbx->affected_rows() > 0;
    }

    /* =========================
     * CLONE FROM OPPORTUNITY
     * ========================= */

    public function clone_from_opportunity($opportunityId, $userId, $opts = []) {
        $opportunityId = (int)$opportunityId;

        $op = $this->dbx->where('opportunity_id', $opportunityId)->get('crm_opportunities')->row_array();
        if (!$op) throw new Exception('Opportunity not found');

        $showTotal = isset($opts['show_total']) ? (int)$opts['show_total'] : 1;
        $multiDest = isset($opts['multi_destination']) ? (int)$opts['multi_destination'] : 0;

        $payload = [
            'opportunity_id'    => $opportunityId,
            'account_name'      => (string)($op['account_name'] ?? ''),
            'status'            => 'DRAFT',
            'quote_date'        => date('Y-m-d'),
            'valid_until'       => date('Y-m-d', strtotime('+14 day')),
            'currency'          => strtoupper((string)($op['currency'] ?? 'IDR')),
            'cargo_type'        => $this->normalize_cargo_type($op['commodity_type'] ?? 'GENERAL'),
            'dg_class'          => $op['dg_class'] ?? null,
            'hs_code'           => $op['hs_code'] ?? null,
            'rate_information'  => [],
            'show_total'        => $showTotal,
            'multi_destination' => $multiDest,
            'items'             => [], // default empty
        ];

        // optional: create skeleton items
        if (!empty($opts['with_skeleton_items'])) {
            $payload['items'] = [
                ['item_type'=>'FREIGHT','description'=>'Ocean/Air/Land Freight','qty'=>1,'unit_price'=>0,'amount'=>0,'pricing_basis'=>'AT_COST'],
                ['item_type'=>'ORIGIN','description'=>'Origin Charges','qty'=>1,'unit_price'=>0,'amount'=>0,'pricing_basis'=>'IF_ANY'],
                ['item_type'=>'DESTINATION','description'=>'Destination Charges','qty'=>1,'unit_price'=>0,'amount'=>0,'pricing_basis'=>'IF_ANY'],
            ];
        }

        $quoteId = $this->create($payload, $userId);
        return $quoteId;
    }
	
public function get_preview_data($quote_id, $user)
{
    $quote_id = (int)$quote_id;

    // 1) Header quotation
		$this->db->select('
		q.*, 
		o.opportunity_no, o.account_name AS opportunity_account_name, o.source_type, o.shipment_type, o.transport_mode, o.sea_type, o.origin, o.destination,
		CASE 
			WHEN o.source_type = "LEAD" THEN l.company_name 
			ELSE s.shipper_name 
		END AS client_company_name,
		CASE 
			WHEN o.source_type = "LEAD" THEN CONCAT_WS(", ", l.address_line1, l.address_line2, l.city) 
			ELSE s.shipper_address 
		END AS client_company_address
	', FALSE); // Tambahkan FALSE di sini untuk menghentikan auto-backtick
	$this->db->from('crm_quotations q');
	$this->db->join('crm_opportunities o', 'q.opportunity_id = o.opportunity_id', 'left');
	$this->db->join('crm_leads l', 'o.lead_id = l.id_lead AND o.source_type = "LEAD"', 'left');
	$this->db->join('mstshipper s', 'o.id_shipper = s.id_shipper AND o.source_type = "CUSTOMER"', 'left');
	$this->db->where('q.quote_id', $quote_id);

	$quote = $this->db->get()->row_array();

    if (!$quote) {
        throw new Exception("Quotation not found");
    }

    // Optional access rule
    if (empty($user['management']) || (int)$user['management'] === 0) {
        if (!empty($user['region_id']) && !empty($quote['region_id']) && $quote['region_id'] != $user['region_id']) {
            throw new Exception("Access denied");
        }
    }

    // 2) Fetch all items
    $rows = $this->db
        ->where('quote_id', $quote_id)
        ->order_by("FIELD(item_type,'FREIGHT','ORIGIN','DESTINATION')", '', false)
        ->order_by('sort_order', 'asc')
        ->order_by('line_no', 'asc')
        ->order_by('quote_item_id', 'asc')
        ->get('crm_quotation_items')
        ->result_array();

    // 3) Index by ID (untuk support parent-child legacy)
    $byId = [];
    foreach ($rows as $r) {
        $r['quote_item_id']    = (int)($r['quote_item_id'] ?? 0);
        $r['parent_item_id']   = ($r['parent_item_id'] !== null ? (int)$r['parent_item_id'] : null);

        $r['qty']              = (float)($r['qty'] ?? 0);
        $r['unit_price']       = (float)($r['unit_price'] ?? 0);
        $r['amount']           = (float)($r['amount'] ?? 0);
        $r['min_charge']       = (float)($r['min_charge'] ?? 0);

        $r['include_in_total'] = (int)($r['include_in_total'] ?? 1);
        $r['is_selected']      = (int)($r['is_selected'] ?? 0);

        // keep raw json text, will decode later
        $r['_children'] = [];
        $byId[$r['quote_item_id']] = $r;
    }

    // build children list (kalau ada row child sungguhan)
    foreach ($byId as $id => $r) {
        if (!empty($r['parent_item_id']) && isset($byId[$r['parent_item_id']])) {
            $byId[$r['parent_item_id']]['_children'][] = $r;
        }
    }

    // 4) Build groups (top-level only)
    $groups = ['FREIGHT'=>[], 'ORIGIN'=>[], 'DESTINATION'=>[]];

    foreach ($byId as $id => $r) {
        if (!empty($r['parent_item_id'])) continue; // top-level only

        $t = strtoupper((string)($r['item_type'] ?? 'FREIGHT'));
        if (!isset($groups[$t])) continue;

        // ---- SUB ITEMS SOURCE PRIORITY ----
        // A) If has children rows -> use those
        // B) Else decode JSON column sub_items
        // ---- SUB ITEMS SOURCE PRIORITY ----
// Prefer JSON sub_items if present (because your data stores amounts there)
// Fallback to legacy child rows only when JSON is empty

$subOut = [];

// Decode JSON sub_items from the parent row (if any)
$decoded = [];
$raw = $r['sub_items'] ?? null;

if (is_array($raw)) {
    $decoded = $raw;
} elseif (is_string($raw) && trim($raw) !== '') {
    $tmp = json_decode(trim($raw), true);
    if (is_array($tmp)) $decoded = $tmp;
}

// A) If decoded JSON has content -> use it
if (!empty($decoded)) {
    foreach ($decoded as $s) {
        if (!is_array($s)) continue;
        $subOut[] = [
            'description'       => (string)($s['description'] ?? ''),
            'charge_detail'     => (string)($s['charge_detail'] ?? ''),
            'pricing_basis'     => (string)($s['pricing_basis'] ?? 'FIXED'),
            'include_in_total'  => isset($s['include_in_total']) ? (int)$s['include_in_total'] : null,
            'qty'               => (float)($s['qty'] ?? 0),
            'uom'               => (string)($s['uom'] ?? ''),
            'unit_price'        => (float)($s['unit_price'] ?? 0),
            'min_charge'        => (float)($s['min_charge'] ?? 0),
            'amount'            => (float)($s['amount'] ?? 0),
            'currency'          => (string)($s['currency'] ?? $quote['currency'] ?? 'IDR'),
        ];
    }

// B) Else, fallback to child rows (legacy)
} elseif (!empty($r['_children'])) {
    foreach ($r['_children'] as $c) {
        $subOut[] = [
            'quote_item_id'     => (int)($c['quote_item_id'] ?? 0),
            'description'       => (string)($c['description'] ?? ''),
            'charge_detail'     => (string)($c['charge_detail'] ?? ''),
            'pricing_basis'     => (string)($c['pricing_basis'] ?? 'FIXED'),
            'include_in_total'  => isset($c['include_in_total']) ? (int)$c['include_in_total'] : null,
            'qty'               => (float)($c['qty'] ?? 0),
            'uom'               => (string)($c['uom'] ?? ''),
            'unit_price'        => (float)($c['unit_price'] ?? 0),
            'min_charge'        => (float)($c['min_charge'] ?? 0),
            'amount'            => (float)($c['amount'] ?? 0),
            'currency'          => (string)($c['currency'] ?? $quote['currency'] ?? 'IDR'),
        ];
    }
}

$r['sub_items'] = $subOut;


        // cleanup internal
        unset($r['_children']);

        $groups[$t][] = $r;
    }

    // 5) Subtotal: hanya include_in_total=1 pada top-level
    $subtotal = ['FREIGHT'=>0, 'ORIGIN'=>0, 'DESTINATION'=>0, 'TOTAL'=>0];

    foreach (['FREIGHT','ORIGIN','DESTINATION'] as $t) {
        $sum = 0.0;
        foreach ($groups[$t] as $r) {
            if ((int)($r['include_in_total'] ?? 1) !== 1) continue;
            $sum += (float)($r['amount'] ?? 0);
        }
        $subtotal[$t] = $sum;
    }
    $subtotal['TOTAL'] = $subtotal['FREIGHT'] + $subtotal['ORIGIN'] + $subtotal['DESTINATION'];

    return [
        'quote' => $quote,
        'items' => [
            'groups' => $groups,
            'subtotal' => $subtotal,
        ],
    ];
}

/* =========================
     * CUSTOMER PORTAL METHODS
     * ========================= */

   public function get_customer_list($idShipper, $filters = []) {
        $page  = max(1, (int)($filters['page'] ?? 1));
        $limit = min(100, max(1, (int)($filters['limit'] ?? 20)));
        $off   = ($page - 1) * $limit;

        $q       = trim((string)($filters['q'] ?? ''));
        $status  = (string)($filters['status'] ?? '');

        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'left');

        $this->dbx->select([
            'qt.quote_id','qt.quote_no','qt.status',
            'qt.quote_date','qt.valid_until','qt.currency',
            'qt.total_amount','qt.show_total','qt.multi_destination',
            'op.shipment_type','op.transport_mode','op.origin','op.destination'
        ]);

        $this->dbx->where('qt.deleted_at IS NULL', null, false);
        
        // Guard: Pastikan ini milik id_shipper yang bersangkutan
        // Cek dari qt.id_shipper atau op.id_shipper sebagai fallback
        $this->dbx->group_start();
        $this->dbx->where('qt.id_shipper', $idShipper);
        $this->dbx->or_where('op.id_shipper', $idShipper);
        $this->dbx->group_end();

        // Guard: Jangan pernah tampilkan dokumen DRAFT ke customer
        $this->dbx->where('qt.status !=', 'DRAFT');

        if ($status !== '') {
            $this->dbx->where('qt.status', $status);
        }

        if ($q !== '') {
            $this->dbx->group_start();
            $this->dbx->like('qt.quote_no', $q);
            $this->dbx->or_like('op.origin', $q);
            $this->dbx->or_like('op.destination', $q);
            $this->dbx->group_end();
        }

        // Count total for pagination
        $clone = clone $this->dbx;
        $total = (int)$clone->count_all_results();

        $this->dbx->order_by('qt.quote_date', 'DESC');
        $this->dbx->order_by('qt.created_at', 'DESC');
        $rows = $this->dbx->limit($limit, $off)->get()->result_array();

        // Jika show_total = 0, sembunyikan nominal total_amount dari respon API
        foreach ($rows as &$row) {
            if ((int)$row['show_total'] === 0) {
                $row['total_amount'] = null;
            }
        }

        return [
            'status' => true,
            'page'   => $page,
            'limit'  => $limit,
            'total'  => $total,
            'data'   => $rows,
        ];
    }

    public function get_customer_detail($quoteId, $idShipper) {
        $this->dbx->from('crm_quotations qt');
        $this->dbx->join('crm_opportunities op', 'op.opportunity_id = qt.opportunity_id', 'left');

        $this->dbx->select([
            'qt.quote_id','qt.quote_no','qt.account_name','op.account_name AS opportunity_account_name','qt.status',
            'qt.quote_date','qt.valid_until','qt.currency','qt.total_amount','qt.show_total','qt.multi_destination',
            'op.shipment_type','op.transport_mode','op.sea_type','op.incoterm','op.service_level','op.origin','op.destination'
        ]);

        $this->dbx->where('qt.deleted_at IS NULL', null, false);
        $this->dbx->where('qt.quote_id', (int)$quoteId);
        
        // Strict Guards
        $this->dbx->group_start();
        $this->dbx->where('qt.id_shipper', $idShipper);
        $this->dbx->or_where('op.id_shipper', $idShipper);
        $this->dbx->group_end();
        
        $this->dbx->where('qt.status !=', 'DRAFT');

        $q = $this->dbx->get()->row_array();
        if (!$q) return null;

        $oppAccount = trim((string)($q['opportunity_account_name'] ?? ''));
        if ($oppAccount !== '') $q['account_name'] = $oppAccount;

        // Gunakan fungsi get_items() yang sudah ada
        $items = $this->get_items((int)$quoteId);

        // Keamanan tambahan: Jika show_total = 0, bersihkan nominal price dari level item dan header
        if ((int)$q['show_total'] === 0) {
            $q['total_amount'] = null;
            $items['subtotal'] = ['FREIGHT'=>0, 'ORIGIN'=>0, 'DESTINATION'=>0, 'TOTAL'=>0];
            
            foreach (['FREIGHT', 'ORIGIN', 'DESTINATION'] as $group) {
                if (isset($items['groups'][$group])) {
                    foreach ($items['groups'][$group] as &$item) {
                        $item['unit_price'] = 0;
                        $item['amount'] = 0;
                        $item['min_charge'] = 0;
                    }
                }
            }
        }

        return [
            'quote' => $q,
            'items' => $items,
        ];
    }
	
	 /**
     * Menyimpan permintaan penawaran baru dari Customer Portal
     */
    public function create_request($idShipper, $payload) {
        $data = [
            'id_shipper'     => $idShipper,
            'transport_type' => $payload['transport_type'] ?? 'SEA', // <-- Tambahkan baris ini
            'origin'         => $payload['origin'] ?? null,
            'destination'    => $payload['destination'] ?? null,
            'service_type'   => $payload['service_type'] ?? null,
            'carrier'        => $payload['carrier'] ?? null,
            'rate_id'        => isset($payload['rate_id']) ? (int)$payload['rate_id'] : null,
            'quantity'       => (float)($payload['quantity'] ?? 0),
            'packaging'      => $payload['packaging'] ?? 'Carton',
            'weight'         => (float)($payload['weight'] ?? 0),
            'volume'         => (float)($payload['volume'] ?? 0),
            'ready_date'     => !empty($payload['ready_date']) ? $payload['ready_date'] : null,
            'expected_etd'   => !empty($payload['expected_etd']) ? $payload['expected_etd'] : null,
            'notes'          => $payload['notes'] ?? null,
            'status'         => 'NEW',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        $this->dbx->insert('crm_quotation_requests', $data);
        return $this->dbx->insert_id();
    }


}
