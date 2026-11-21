<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_posting extends MY_Model
{
    public $company_id = 1;

    private $column_cache = [];

    public function __construct()
    {
        parent::__construct();
        $this->config->load('finance_coa', false, true);
    }

    // =========================================================
    // BASIC HELPERS
    // =========================================================

    private function region_id($fallback = null)
	{
		$region = '';

		if ($fallback !== null && trim((string)$fallback) !== '') {
			$region = trim((string)$fallback);
		}

		if ($region === '' && $this->session->userdata('region_id')) {
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

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

   private function num($v, $default = 0.0)
	{
		if ($v === null || $v === '') {
			return (float)$default;
		}

		if (is_numeric($v)) {
			return (float)$v;
		}

		$s = trim((string)$v);
		$s = preg_replace('/[^\d,\.\-]/', '', $s);

		if ($s === '' || $s === '-') {
			return (float)$default;
		}

		$hasComma = strpos($s, ',') !== false;
		$hasDot   = strpos($s, '.') !== false;

		// Format Indonesia: 9.340.374,00
		if ($hasComma && $hasDot) {
			$lastComma = strrpos($s, ',');
			$lastDot   = strrpos($s, '.');

			if ($lastComma > $lastDot) {
				$s = str_replace('.', '', $s);
				$s = str_replace(',', '.', $s);
			} else {
				$s = str_replace(',', '', $s);
			}
		} elseif ($hasComma && !$hasDot) {
			// 9340374,00
			$s = str_replace(',', '.', $s);
		} elseif (!$hasComma && substr_count($s, '.') > 1) {
			// 9.340.374
			$s = str_replace('.', '', $s);
		}

		return is_numeric($s) ? (float)$s : (float)$default;
	}

    private function parse_posting_date($raw)
    {
        $raw = trim((string)$raw);

        if ($raw === '') {
            return date('Y-m-d');
        }

        // format yyyymmdd
        if (preg_match('/^\d{8}$/', $raw)) {
            return substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $ts);
    }

    private function table_exists_on($db, $table)
    {
        return $db->table_exists($table);
    }

    private function has_column_on($db, $table, $column)
    {
        $dbKey = ($db === $this->erp) ? 'erp' : 'default';
        $key = $dbKey . '.' . $table . '.' . $column;

        if (array_key_exists($key, $this->column_cache)) {
            return $this->column_cache[$key];
        }

        if (!$this->table_exists_on($db, $table)) {
            $this->column_cache[$key] = false;
            return false;
        }

        $this->column_cache[$key] = $db->field_exists($column, $table);
        return $this->column_cache[$key];
    }

    private function first_existing_column_on($db, $table, $candidates)
    {
        foreach ($candidates as $column) {
            if ($this->has_column_on($db, $table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function pick_value_from_array($row, $keys, $default = null)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }

        return $default;
    }

    private function filter_columns($db, $table, $data)
    {
        if (!$this->table_exists_on($db, $table)) {
            return [];
        }

        $fields = $db->list_fields($table);
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
        $row = $this->filter_columns($db, $table, $data);

        if (empty($row)) {
            return false;
        }

        return $db->insert($table, $row);
    }

    private function update_filtered($db, $table, $data)
    {
        $row = $this->filter_columns($db, $table, $data);

        if (empty($row)) {
            return false;
        }

        return $db->update($table, $row);
    }

    private function apply_region_if_column_on($db, $table, $alias = null, $column = 'region_id')
    {
        if ($this->is_super_admin()) {
            return;
        }

        if (!$this->has_column_on($db, $table, $column)) {
            return;
        }

        $region = $this->region_id();

        if ($region === '') {
            return;
        }

        $db->where(($alias ? $alias . '.' : '') . $column, $region);
    }

    private function apply_region_erp($table, $alias = null, $column = 'region_id')
    {
        $this->apply_region_if_column_on($this->erp, $table, $alias, $column);
    }

    private function apply_region_operational($table, $alias = null, $column = 'region_id')
    {
        $this->apply_region_if_column_on($this->db, $table, $alias, $column);
    }

    private function db_error_result($db, $message)
    {
        return [
            'ok'      => false,
            'http'    => 500,
            'message' => $message,
            'data'    => [
                'db_error'   => $db->error(),
                'last_query' => $db->last_query(),
            ],
        ];
    }

    // =========================================================
    // ACCOUNT HELPERS
    // =========================================================

    private function coa_config()
    {
        $cfg = $this->config->item('finance_coa');
        return is_array($cfg) ? $cfg : [];
    }

    private function coa_code($key)
    {
        $cfg = $this->coa_config();
        $accounts = $cfg['accounts'] ?? [];
        return trim((string)($accounts[$key] ?? ''));
    }

    private function coa_id($key, $region_id = null)
    {
        $code = $this->coa_code($key);
        $region_id = $this->region_id($region_id);

        if ($code === '' || $region_id === '') {
            return 0;
        }

        $row = $this->erp
            ->select('account_id, account_code, account_name, is_postable, is_active')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $region_id)
            ->where('account_code', $code)
            ->get()
            ->row_array();

        if (!$row) {
            return 0;
        }

        if ((int)$row['is_active'] !== 1 || (int)$row['is_postable'] !== 1) {
            return 0;
        }

        return (int)$row['account_id'];
    }

    private function require_coa(array $keys, $region_id)
    {
        $region_id = $this->region_id($region_id);
        $resolved = [];
        $missing = [];

        foreach ($keys as $key) {
            $code = $this->coa_code($key);
            $id = $this->coa_id($key, $region_id);

            if ($id <= 0) {
                $missing[] = [
                    'key'  => $key,
                    'code' => $code,
                ];
                continue;
            }

            $resolved[$key] = $id;
        }

        if ($missing) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Missing/invalid central COA mapping',
                'data'    => [
                    'region_id' => $region_id,
                    'missing'   => $missing,
                ],
            ];
        }

        return [
            'ok'   => true,
            'data' => $resolved,
        ];
    }

    // =========================================================
    // SOURCE INVOICE HELPERS
    // =========================================================

    /*
     * Sumber posting AR sekarang memakai tabel ERP ar_invoices/ar_invoice_items.
     * Parameter lama finance_id tetap diterima dari FE, tetapi diperlakukan sebagai invoice_id.
     */
    private function get_source_invoice($finance_id = 0, $invoice_no_in = '')
	{
		$finance_id   = (int)$finance_id;
		$invoice_no_in = trim((string)$invoice_no_in);
		$region_id    = trim((string)$this->region_id());

		if (!$this->table_exists_on($this->db, 'x_finance')) {
			return null;
		}

		if ($region_id === '') {
			return null;
		}

		$this->db->from('x_finance');
		$this->db->where('region_id', $region_id);

		if ($finance_id > 0) {
			$this->db->where('id', $finance_id);
		} elseif ($invoice_no_in !== '') {
			$this->db->where('number', $invoice_no_in);
		} else {
			return null;
		}

		$row = $this->db
			->limit(1)
			->get()
			->row_array();

		if (!$row) {
			return null;
		}

		/*
		 * Source invoice berasal dari operational DB.
		 * ERP ar_invoices adalah TARGET snapshot, bukan source.
		 */
		$row['_source_db']    = $this->db->database;
		$row['_source_table'] = 'x_finance';

		return $row;
	}

    private function get_source_items($finance_id)
	{
		$finance_id = (int)$finance_id;
		$region_id  = trim((string)$this->region_id());

		if ($finance_id <= 0) {
			return [];
		}

		if (!$this->table_exists_on($this->db, 'x_finance_item')) {
			return [];
		}

		$this->db->from('x_finance_item');
		$this->db->where('number', $finance_id);

		/*
		 * Exact region isolation.
		 */
		if (
			$region_id !== '' &&
			$this->has_column_on(
				$this->db,
				'x_finance_item',
				'region_id'
			)
		) {
			$this->db->where('region_id', $region_id);
		}

		$this->db->where('price !=', 0);

		return $this->db
			->order_by('id', 'ASC')
			->get()
			->result_array();
	}

    private function resolve_invoice_party_name($inv)
    {
        $kepada = strtoupper(trim((string)($inv['kepada'] ?? '')));
        $code   = trim((string)($inv['kepada_kode'] ?? ''));
        $number = trim((string)($inv['number'] ?? ''));

        if ($kepada === 'AGENT' && $code !== '' && $this->table_exists_on($this->db, 'refagentcompany')) {
            $row = $this->db
                ->select('agent_company_name AS name')
                ->where('agent_company_code', $code)
                ->get('refagentcompany')
                ->row_array();

            if ($row && !empty($row['name'])) {
                return (string)$row['name'];
            }
        }

        if ($kepada === 'WAREHOUSE' && $code !== '' && $this->table_exists_on($this->db, 'refwarehouse')) {
            $row = $this->db
                ->select('warehouse_name AS name')
                ->where('warehouse_code', $code)
                ->get('refwarehouse')
                ->row_array();

            if ($row && !empty($row['name'])) {
                return (string)$row['name'];
            }
        }

        if ($kepada === 'COLOADER' && $code !== '' && $this->table_exists_on($this->db, 'refshippingcompany')) {
            $row = $this->db
                ->select('shipping_company_name AS name')
                ->where('shipping_company_code', $code)
                ->get('refshippingcompany')
                ->row_array();

            if ($row && !empty($row['name'])) {
                return (string)$row['name'];
            }
        }

        if ($number !== '' && $this->table_exists_on($this->db, 'x_invoice')) {
            $this->db->select('nama, shipper_name');
            $this->db->where('number', $number);

            $row = $this->db->get('x_invoice')->row_array();

            if ($row) {
                if (!empty($row['nama'])) {
                    return (string)$row['nama'];
                }

                if (!empty($row['shipper_name'])) {
                    return (string)$row['shipper_name'];
                }
            }
        }

        if (!empty($inv['nama'])) {
            return (string)$inv['nama'];
        }

        if (!empty($inv['shipper_name'])) {
            return (string)$inv['shipper_name'];
        }

        return '';
    }
	
	private function normalize_currency($currency)
	{
		$currency = strtoupper(trim((string)$currency));

		if ($currency === '' || $currency === 'RP.' || $currency === 'RUPIAH' || $currency === 'RP') {
			return 'IDR';
		}

		return $currency;
	}

	private function parse_rate($v)
	{
		if ($v === null || $v === '') {
			return 0.0;
		}

		if (is_numeric($v)) {
			return (float)$v;
		}

		$s = trim((string)$v);
		$s = preg_replace('/[^\d,\.\-]/', '', $s);

		if ($s === '' || $s === '-') {
			return 0.0;
		}

		$hasComma = strpos($s, ',') !== false;
		$hasDot   = strpos($s, '.') !== false;

		// DB decimal: 16630.000000 harus tetap 16630, bukan 16630000000
		if ($hasDot && !$hasComma && substr_count($s, '.') === 1) {
			return is_numeric($s) ? (float)$s : 0.0;
		}

		// Format Indonesia: 16.630,00
		if ($hasComma && $hasDot) {
			$lastComma = strrpos($s, ',');
			$lastDot   = strrpos($s, '.');

			if ($lastComma > $lastDot) {
				$s = str_replace('.', '', $s);
				$s = str_replace(',', '.', $s);
			} else {
				$s = str_replace(',', '', $s);
			}
		} elseif ($hasComma && !$hasDot) {
			$s = str_replace(',', '.', $s);
		} elseif (!$hasComma && substr_count($s, '.') > 1) {
			$s = str_replace('.', '', $s);
		}

		return is_numeric($s) ? (float)$s : 0.0;
	}

	private function pick_num_from_array($row, $keys, $default = 0.0)
	{
		foreach ($keys as $k) {
			if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
				return $this->num($row[$k], $default);
			}
		}

		return (float)$default;
	}

	private function classify_and_amount($inv, $items = [], $posting_date = '')
{
    $currency = $this->normalize_currency($inv['currency'] ?? 'IDR');
    $kepada   = strtoupper(trim((string)($inv['kepada'] ?? '')));

    $kurs = $this->resolve_posting_rate($inv, $posting_date, $currency);

    if ($kurs <= 0) {
        $kurs = 1.0;
    }

    if (stripos($kepada, 'AGENT') !== false) {
        $type = 'AGENT';
    } else {
        $type = 'NON_TAX';
    }

    $itemGrand = 0.0;
    $itemPpn   = 0.0;
    $hasItemAmount = false;

    foreach ($items as $it) {
        $code = strtoupper(trim((string)($it['code'] ?? '')));
        $name = strtoupper(trim((string)($it['item'] ?? '')));

        $lineAmount = $this->pick_num_from_array($it, [
            'amount',
            'total',
            'subtotal',
            'line_total',
            'total_amount',
            'price',
            'selling_price',
            'unit_total',
            'nilai',
        ], 0);

        if (abs($lineAmount) > 0.00001) {
            $hasItemAmount = true;
        }

        $isTaxLine =
            strpos($code, 'TAX') !== false ||
            strpos($code, 'PPN') !== false ||
            strpos($name, 'PPN') !== false ||
            strpos($name, 'VAT') !== false ||
            strpos($name, 'TAX') !== false;

        if ($isTaxLine) {
            $itemPpn += $lineAmount;
        }

        $itemGrand += $lineAmount;
    }

    $headerGrand = $this->num($inv['grand_total'] ?? 0);
    $headerPpn   = $this->num($inv['ppn'] ?? 0);

    /*
     * Kunci perbaikan:
     * kalau item punya amount, gunakan total item.
     * Jangan percaya header grand_total karena bisa berisi total per job.
     */
    if ($hasItemAmount && abs($itemGrand) > 0.00001) {
        $grand_origin = round($itemGrand, 2);
        $ppn_origin   = round($itemPpn, 2);

        // Kalau tidak ada tax line tapi header ppn valid dan bukan AGENT, pakai header ppn.
        if ($ppn_origin <= 0.00001 && $headerPpn > 0.00001 && stripos($kepada, 'AGENT') === false) {
            $ppn_origin = round($headerPpn, 2);
        }
    } else {
        $grand_origin = round($headerGrand, 2);
        $ppn_origin   = round($headerPpn, 2);
    }

    if ($ppn_origin > 0.00001 && $type !== 'AGENT') {
        $type = 'TAX';
    }

    if ($type === 'AGENT') {
        $ppn_origin = 0.0;
    }

    $dpp_origin = $grand_origin - $ppn_origin;

    if ($dpp_origin < 0) {
        $dpp_origin = 0.0;
    }

    if ($currency === 'USD') {
        $grand_total_idr = round($grand_origin * $kurs, 2);
        $ppn_idr         = round($ppn_origin * $kurs, 2);
        $dpp_idr         = round($dpp_origin * $kurs, 2);
        $grand_total_usd = $grand_origin;
    } else {
        $grand_total_idr = round($grand_origin, 2);
        $ppn_idr         = round($ppn_origin, 2);
        $dpp_idr         = round($dpp_origin, 2);
        $grand_total_usd = 0.0;
    }

    if ($type === 'AGENT') {
        $ppn_idr = 0.0;
        $dpp_idr = $grand_total_idr;
    } elseif ($type !== 'TAX') {
        $ppn_idr = 0.0;
        $dpp_idr = $grand_total_idr;
        $ppn_origin = 0.0;
        $dpp_origin = $grand_origin;
    }

    return [
        'type'                 => $type,
        'currency'             => $currency,
        'kurs'                 => $kurs,

        // original invoice currency
        'grand_total_origin'   => round($grand_origin, 2),
        'ppn_origin'           => round($ppn_origin, 2),
        'dpp_origin'           => round($dpp_origin, 2),

        // base currency IDR for GL
        'grand_total_usd'      => $grand_total_usd,
        'grand_total_idr'      => $grand_total_idr,
        'ppn_idr'              => $ppn_idr,
        'dpp_idr'              => $dpp_idr,
    ];
}
    // =========================================================
    // PAID TOTAL HELPERS
    // =========================================================

    private function find_ar_invoice_snapshot($source_db, $source_table, $source_id, $invoice_no = '')
    {
        if (!$this->table_exists_on($this->erp, 'ar_invoices')) {
            return null;
        }

        $pkCol = $this->first_existing_column_on($this->erp, 'ar_invoices', [
            'ar_invoice_id',
            'invoice_id',
            'id',
        ]);

        $noCol = $this->first_existing_column_on($this->erp, 'ar_invoices', [
            'invoice_no',
            'ar_invoice_no',
            'number',
            'inv_no',
            'no_invoice',
        ]);

        $this->erp->from('ar_invoices');
        $this->erp->where('company_id', $this->company_id);

        if ($source_table === 'ar_invoices' && $pkCol && (int)$source_id > 0) {
            $this->erp->where($pkCol, (int)$source_id);
        } else {
            if ($this->has_column_on($this->erp, 'ar_invoices', 'source_db')) {
                $this->erp->where('source_db', $source_db);
            }

            if ($this->has_column_on($this->erp, 'ar_invoices', 'source_table')) {
                $this->erp->where('source_table', $source_table);
            }

            if ($this->has_column_on($this->erp, 'ar_invoices', 'source_id')) {
                $this->erp->where('source_id', (string)$source_id);
            } elseif ($invoice_no !== '' && $noCol) {
                $this->erp->where($noCol, $invoice_no);
            }
        }

        $this->apply_region_erp('ar_invoices');

        return $this->erp->get()->row_array();
    }

    private function get_paid_total($inv, $ar_invoice = null)
    {
        if (!$this->table_exists_on($this->erp, 'ar_receipt_refs') || !$this->table_exists_on($this->erp, 'ar_receipts')) {
            return 0.0;
        }

        $finance_id   = (int)($inv['id'] ?? 0);
        $source_db    = $inv['_source_db'] ?? $this->erp->database;
        $source_table = $inv['_source_table'] ?? 'ar_invoices';
        $source_id    = (string)$finance_id;

        $this->erp->select('COALESCE(SUM(rr.allocated_amount), 0) AS paid', false)
            ->from('ar_receipt_refs rr')
            ->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner')
            ->where('r.company_id', $this->company_id)
            ->where_in('r.status', ['POSTED', 'SUBMITTED']);

        $hasCondition = false;

        $this->erp->group_start();

        if ($ar_invoice && isset($ar_invoice['ar_invoice_id']) && $this->has_column_on($this->erp, 'ar_receipt_refs', 'ar_invoice_id')) {
            $this->erp->where('rr.ar_invoice_id', (int)$ar_invoice['ar_invoice_id']);
            $hasCondition = true;
        }

        if (
            $this->has_column_on($this->erp, 'ar_receipt_refs', 'source_db') &&
            $this->has_column_on($this->erp, 'ar_receipt_refs', 'source_table') &&
            $this->has_column_on($this->erp, 'ar_receipt_refs', 'source_id')
        ) {
            if ($hasCondition) {
                $this->erp->or_group_start()
                    ->where('rr.source_db', $source_db)
                    ->where('rr.source_table', $source_table)
                    ->where('rr.source_id', $source_id)
                ->group_end();
            } else {
                $this->erp->where('rr.source_db', $source_db)
                    ->where('rr.source_table', $source_table)
                    ->where('rr.source_id', $source_id);
            }

            $hasCondition = true;
        }

        if (!$hasCondition && $this->has_column_on($this->erp, 'ar_receipt_refs', 'finance_id')) {
            $this->erp->where('rr.finance_id', $finance_id);
            $hasCondition = true;
        }

        $this->erp->group_end();

        if (!$hasCondition) {
            return 0.0;
        }

        $this->apply_region_erp('ar_receipts', 'r');

        $q = $this->erp->get();

        if ($q === false) {
            return 0.0;
        }

        $row = $q->row_array();

        return (float)($row['paid'] ?? 0);
    }

    // =========================================================
    // SNAPSHOT AR INVOICE TO ERP
    // =========================================================

    private function upsert_ar_invoice_snapshot($inv, $items, $calc, $paidTotal, $posting_date, $job_no, $region_id, $now)
    {
        if (!$this->table_exists_on($this->erp, 'ar_invoices')) {
            return [
                'ok'      => true,
                'message' => 'SKIP_AR_INVOICE_SNAPSHOT_TABLE_NOT_EXISTS',
                'data'    => null,
            ];
        }

        $finance_id   = (int)$inv['id'];
        $invoice_no   = (string)$inv['number'];
        $source_db    = $inv['_source_db'] ?? $this->erp->database;
        $source_table = $inv['_source_table'] ?? 'ar_invoices';
        $source_id    = (string)$finance_id;
        $source_ref   = $invoice_no;

        $gt_idr  = (float)$calc['grand_total_idr'];
        $ppn_idr = (float)$calc['ppn_idr'];
        $dpp_idr = (float)$calc['dpp_idr'];

        $grand_total_origin = $this->num($calc['grand_total_origin'] ?? ($inv['grand_total'] ?? 0));
		$ppn_origin         = $this->num($calc['ppn_origin'] ?? ($inv['ppn'] ?? 0));
		$dpp_origin         = $this->num($calc['dpp_origin'] ?? ($grand_total_origin - $ppn_origin));
        if ($dpp_origin < 0) {
            $dpp_origin = 0;
        }

        $party_type = isset($inv['kepada']) ? (string)$inv['kepada'] : null;
        $party_code = isset($inv['kepada_kode']) ? (string)$inv['kepada_kode'] : null;
        $party_name = $this->resolve_invoice_party_name($inv);

        $status = 'SUBMITTED';
        /*
		 * outstanding_total disimpan dalam original invoice currency.
		 * GL tetap pakai IDR dari grand_total_idr.
		 */
		$outstanding = max(0, $grand_total_origin - $paidTotal);

        if ($outstanding <= 0.01 && $gt_idr > 0) {
            $status = 'PAID';
        } elseif ($paidTotal > 0.01) {
            $status = 'PARTIAL';
        }

        $payload = [
            'company_id'        => $this->company_id,
            'region_id'         => $region_id,

            'source_db'         => $source_db,
            'source_table'      => $source_table,
            'source_id'         => $source_id,
            'source_ref'        => $source_ref,

            'invoice_no'        => $invoice_no,
            'job_no'            => $job_no ?: null,
            'posting_date'      => $posting_date,

            'party_type'        => $party_type,
            'party_code'        => $party_code,
            'party_name'        => $party_name,

            'currency'          => $calc['currency'],
            'exchange_rate'     => $calc['kurs'],

            'grand_total'       => $grand_total_origin,
            'ppn_total'         => $ppn_origin,
            'dpp_total'         => $dpp_origin,

            'grand_total_idr'   => $gt_idr,
            'ppn_total_idr'     => $ppn_idr,
            'dpp_total_idr'     => $dpp_idr,

            'paid_total'        => $paidTotal,
            'outstanding_total' => $outstanding,

            'status'            => $status,
            'updated_at'        => $now,
        ];

        $sourceIsArInvoices = ($source_table === 'ar_invoices');
        $skipItemRefresh = false;

        if ($sourceIsArInvoices) {
            $pkCol = $inv['_pk_col'] ?? $this->first_existing_column_on($this->erp, 'ar_invoices', [
                'ar_invoice_id',
                'invoice_id',
                'id',
            ]);

            $ar_invoice_id = $finance_id;

            if ($pkCol && $ar_invoice_id > 0) {
                $this->erp->where($pkCol, $ar_invoice_id);
                $this->update_filtered($this->erp, 'ar_invoices', $payload);
            }

            // Source-nya sudah ar_invoice_items, jadi jangan delete + reinsert item yang sama.
            $skipItemRefresh = true;
        } else {
            $existing = $this->find_ar_invoice_snapshot($source_db, $source_table, $source_id, $invoice_no);

            if ($existing) {
                $ar_invoice_id = (int)$existing['ar_invoice_id'];

                $this->erp->where('ar_invoice_id', $ar_invoice_id);
                $this->update_filtered($this->erp, 'ar_invoices', $payload);

                if ($this->table_exists_on($this->erp, 'ar_invoice_items')) {
                    $this->erp->where('ar_invoice_id', $ar_invoice_id)->delete('ar_invoice_items');
                }
            } else {
                $payload['created_at'] = $now;

                $this->insert_filtered($this->erp, 'ar_invoices', $payload);
                $ar_invoice_id = (int)$this->erp->insert_id();
            }
        }

        if ($ar_invoice_id <= 0) {
            return [
                'ok'      => false,
                'message' => 'FAILED_UPSERT_AR_INVOICE',
                'data'    => [
                    'db_error'   => $this->erp->error(),
                    'last_query' => $this->erp->last_query(),
                ],
            ];
        }

        if (!$skipItemRefresh && $this->table_exists_on($this->erp, 'ar_invoice_items')) {
            foreach ($items as $it) {
                $code = strtoupper(trim((string)($it['code'] ?? '')));

                $itemPayload = [
                    'ar_invoice_id' => $ar_invoice_id,
                    'company_id'    => $this->company_id,
                    'region_id'     => $region_id,

                    'source_db'     => $source_db,
                    'source_table'  => 'ar_invoice_items',
                    'source_id'     => isset($it['id']) ? (string)$it['id'] : null,

                    'code'          => $it['code'] ?? null,
                    'item'          => $it['item'] ?? null,
                    'qty'           => $this->num($it['qty'] ?? 0),
                    'currency'      => $it['currency'] ?? 'IDR',
                    'unit_price'    => $this->num($it['unit_price'] ?? 0),
                    'price'         => $this->num($it['price'] ?? 0),
                    'remarks'       => $it['remarks'] ?? null,
                    'is_tax'        => (strpos($code, 'TAX') !== false) ? 1 : 0,

                    'created_at'    => $now,
                ];

                $this->insert_filtered($this->erp, 'ar_invoice_items', $itemPayload);
            }
        }

        return [
            'ok'      => true,
            'message' => 'AR_INVOICE_SNAPSHOT_OK',
            'data'    => [
                'ar_invoice_id' => $ar_invoice_id,
                'source_db'     => $source_db,
                'source_table'  => $source_table,
                'source_id'     => $source_id,
                'invoice_no'    => $invoice_no,
                'party_name'    => $party_name,
            ],
        ];
    }

    // =========================================================
    // DIRECT HPP POLICY
    // =========================================================

    private function recognize_cogs_on_arinv($job_no, $invoice_no, $posting_date)
    {
        /*
         * Direct-HPP mode:
         * COST/GLOBALCOST is already posted to HPP when AP Bill is created.
         * AR posting must NOT move WIP to HPP again.
         */
        return [
            'ok'      => true,
            'message' => 'Skipped: direct HPP mode',
            'data'    => [
                'job_no'     => trim((string)$job_no),
                'invoice_no'  => trim((string)$invoice_no),
                'posting_date'=> $posting_date,
            ],
        ];
    }

    // =========================================================
    // POST AR INVOICE TO ERP
    // =========================================================

    public function post_ar_to_gl($p)
    {
        $finance_id    = (int)($p['finance_id'] ?? 0);
        $invoice_no_in = trim((string)($p['invoice_no'] ?? ''));
        $force_repost  = !empty($p['force_repost']);

        if (!$finance_id && $invoice_no_in === '') {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'finance_id or invoice_no required',
            ];
        }

        $inv = $this->get_source_invoice($finance_id, $invoice_no_in);

        if (!$inv) {
            return [
                'ok'      => false,
                'http'    => 404,
                'message' => 'SOURCE_INVOICE_NOT_FOUND',
                'data'    => [
                    'finance_id' => $finance_id,
                    'invoice_no' => $invoice_no_in,
                    'source_db'  => $this->erp->database,
                ],
            ];
        }

        $finance_id = (int)$inv['id'];
        $invoice_no = (string)$inv['number'];
        $job_no     = trim((string)($inv['job_no'] ?? ''));

        $posting_date = $this->parse_posting_date($inv['date'] ?? '');

        $source_db    = $inv['_source_db'] ?? $this->erp->database;
        $source_table = $inv['_source_table'] ?? 'ar_invoices';
        $source_id    = (string)$finance_id;

        $items = $this->get_source_items($finance_id);

        if (empty($items)) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'SOURCE_INVOICE_ITEM_EMPTY',
                'data'    => [
                    'finance_id' => $finance_id,
                    'invoice_no' => $invoice_no,
                    'source_db'  => $source_db,
                ],
            ];
        }

        $existingAr = $this->find_ar_invoice_snapshot($source_db, $source_table, $source_id, $invoice_no);
        $paidTotal = $this->get_paid_total($inv, $existingAr);

        if ($force_repost && $paidTotal > 0.00001) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'INVOICE_ALREADY_PAID_CANNOT_REPOST',
                'data'    => [
                    'invoice_no'  => $invoice_no,
                    'source_db'   => $source_db,
                    'source_id'   => $source_id,
                    'paid_total'  => $paidTotal,
                    'grand_total' => $this->num($inv['grand_total'] ?? 0),
                ],
            ];
        }

        // ===== cek existing GL POSTED =====
        $this->erp->select('gl_id')
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('voucher_type', 'ARINV')
            ->where('voucher_no', $invoice_no)
            ->where('status', 'POSTED');

        $this->apply_region_erp('gl_entries');

        if ($this->has_column_on($this->erp, 'gl_entries', 'source_db')) {
            $this->erp->where('source_db', $source_db);
        }

        if ($this->has_column_on($this->erp, 'gl_entries', 'source_table')) {
            $this->erp->where('source_table', $source_table);
        }

        if ($this->has_column_on($this->erp, 'gl_entries', 'source_id')) {
            $this->erp->where('source_id', $source_id);
        }

        $existing = $this->erp->get()->result_array();

        if ($existing && !$force_repost) {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'ALREADY_POSTED',
                'data'    => [
                    'invoice_no'     => $invoice_no,
                    'source_db'      => $source_db,
                    'source_id'      => $source_id,
                    'existing_count' => count($existing),
                ],
            ];
        }

        // ===== CENTRAL COA =====
        $region_id = $this->region_id();

        if ($region_id === '') {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'REGION_ID_REQUIRED',
            ];
        }

        $coa = $this->require_coa([
            'AR',
            'REVENUE_NON_TAX',
            'REVENUE_TAX',
            'REVENUE_AGENT',
            'PPN_OUTPUT',
        ], $region_id);

        if (!$coa['ok']) {
            return $coa;
        }

        $accAR   = $coa['data']['AR'];
        $accRevN = $coa['data']['REVENUE_NON_TAX'];
        $accRevT = $coa['data']['REVENUE_TAX'];
        $accRevA = $coa['data']['REVENUE_AGENT'];
        $accPPN  = $coa['data']['PPN_OUTPUT'];

        // ===== Amount =====
        $calc = $this->classify_and_amount($inv, $items, $posting_date);

        $gt_origin  = (float)$calc['grand_total_origin'];
		$ppn_origin = (float)$calc['ppn_origin'];
		$dpp_origin = (float)$calc['dpp_origin'];

		$gt_idr  = (float)$calc['grand_total_idr'];
		$ppn_idr = (float)$calc['ppn_idr'];
		$dpp_idr = (float)$calc['dpp_idr'];

        if ($gt_idr <= 0.01) {
            return [
                'ok'      => false,
                'http'    => 422,
                'message' => 'Grand total is 0',
            ];
        }

        // safeguard DPP + PPN = AR
        if ($calc['type'] === 'TAX') {
            $sumCredit = round($dpp_idr + $ppn_idr, 2);
            $delta = round($gt_idr - $sumCredit, 2);

            if (abs($delta) >= 0.01) {
                $dpp_idr = round($dpp_idr + $delta, 2);
            }
        }

        $now = $this->now();

        $common = [
            'company_id'   => $this->company_id,
            'region_id'    => $region_id,
            'posting_date' => $posting_date,
            'job_no'       => $job_no ?: null,
            'party_id'     => null,
            'voucher_type' => 'ARINV',
            'voucher_no'   => $invoice_no,
            'status'       => 'POSTED',
            'source_db'    => $source_db,
            'source_table' => $source_table,
            'source_id'    => $source_id,
            'source_ref'   => $invoice_no,
            'created_at'   => $now,
        ];

        $this->erp->trans_begin();

        // ===== snapshot header + items dulu =====
        $snapshotRes = $this->upsert_ar_invoice_snapshot(
			$inv,
			$items,
			[
				'type'                 => $calc['type'],
				'currency'             => $calc['currency'],
				'kurs'                 => $calc['kurs'],

				'grand_total_origin'   => $gt_origin,
				'ppn_origin'           => $ppn_origin,
				'dpp_origin'           => $dpp_origin,

				'grand_total_usd'      => $calc['grand_total_usd'],
				'grand_total_idr'      => $gt_idr,
				'ppn_idr'              => $ppn_idr,
				'dpp_idr'              => $dpp_idr,
			],
            $paidTotal,
            $posting_date,
            $job_no,
            $region_id,
            $now
        );

        if (isset($snapshotRes['ok']) && $snapshotRes['ok'] === false) {
            $this->erp->trans_rollback();
            return [
                'ok'      => false,
                'http'    => 500,
                'message' => $snapshotRes['message'],
                'data'    => $snapshotRes['data'] ?? null,
            ];
        }

        $ar_invoice_id = null;
        if (!empty($snapshotRes['data']['ar_invoice_id'])) {
            $ar_invoice_id = (int)$snapshotRes['data']['ar_invoice_id'];
        }

        // ===== cancel posting lama kalau repost =====
        if ($existing && $force_repost) {
            $this->erp->where('company_id', $this->company_id)
                ->where('voucher_type', 'ARINV')
                ->where('voucher_no', $invoice_no)
                ->where('status', 'POSTED');

            $this->apply_region_erp('gl_entries');

            if ($this->has_column_on($this->erp, 'gl_entries', 'source_db')) {
                $this->erp->where('source_db', $source_db);
            }

            if ($this->has_column_on($this->erp, 'gl_entries', 'source_table')) {
                $this->erp->where('source_table', $source_table);
            }

            if ($this->has_column_on($this->erp, 'gl_entries', 'source_id')) {
                $this->erp->where('source_id', $source_id);
            }

            $this->update_filtered($this->erp, 'gl_entries', [
                'status'     => 'CANCELLED',
                'updated_at' => $now,
            ]);
        }

        // ===== Dr AR =====
        $this->insert_filtered($this->erp, 'gl_entries', $common + [
            'account_id' => $accAR,
            'debit'      => $gt_idr,
            'credit'     => 0,
        ]);

        // ===== Cr Revenue / PPN =====
        if ($calc['type'] === 'AGENT') {
            $this->insert_filtered($this->erp, 'gl_entries', $common + [
                'account_id' => $accRevA,
                'debit'      => 0,
                'credit'     => $gt_idr,
            ]);
        } elseif ($calc['type'] === 'TAX') {
            $this->insert_filtered($this->erp, 'gl_entries', $common + [
                'account_id' => $accRevT,
                'debit'      => 0,
                'credit'     => $dpp_idr,
            ]);

            if ($ppn_idr > 0.00001) {
                $this->insert_filtered($this->erp, 'gl_entries', $common + [
                    'account_id' => $accPPN,
                    'debit'      => 0,
                    'credit'     => $ppn_idr,
                ]);
            }
        } else {
            $this->insert_filtered($this->erp, 'gl_entries', $common + [
                'account_id' => $accRevN,
                'debit'      => 0,
                'credit'     => $gt_idr,
            ]);
        }

        if ($this->erp->trans_status() === false) {
            $err = $this->erp->error();
            $sql = $this->erp->last_query();
            $this->erp->trans_rollback();

            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error posting AR',
                'data'    => [
                    'db_error'   => $err,
                    'last_query' => $sql,
                ],
            ];
        }

        $this->erp->trans_commit();

        // ===== update source operational flag jika kolom ada =====
        $sourceUpdate = [
            'fin_proses'      => 'POSTED',
            'ar_posted'       => 1,
            'ar_posted_at'    => $now,
            'ar_posted_by'    => $this->session->userdata('fullname'),
            'ar_post_message' => 'POST_INVOICE_SUCCESS',
            'updated_at'      => $now,
        ];

        $pkCol = $inv['_pk_col'] ?? 'ar_invoice_id';
        $this->erp->where($pkCol, $finance_id);
        $this->apply_region_erp('ar_invoices');
        $this->update_filtered($this->erp, 'ar_invoices', $sourceUpdate);

        // ===== optional COGS =====
        $cogsRes = null;

        if ($job_no) {
            $this->erp->trans_begin();

            $cogsRes = $this->recognize_cogs_on_arinv($job_no, $invoice_no, $posting_date);

            if ($this->erp->trans_status() === false || (isset($cogsRes['ok']) && $cogsRes['ok'] === false)) {
                $err = $this->erp->error();
                $sql = $this->erp->last_query();

                $this->erp->trans_rollback();

                return [
                    'ok'      => true,
                    'message' => 'POSTED_WITH_COGS_WARNING',
                    'data'    => [
                        'ar_invoice_id'    => $ar_invoice_id,
                        'invoice_no'       => $invoice_no,
                        'job_no'           => $job_no,
                        'region_id'        => $region_id,
                        'source_db'        => $source_db,
                        'source_table'     => $source_table,
                        'source_id'        => $source_id,
                        'posting_date'     => $posting_date,
                        'type'             => $calc['type'],
                        'currency'         => $calc['currency'],
                        'kurs'             => $calc['kurs'],
                        'grand_total_usd'  => $calc['grand_total_usd'],
                        'grand_total_idr'  => $gt_idr,
                        'dpp_idr'          => $dpp_idr,
                        'ppn_idr'          => $ppn_idr,
                        'paid_total'       => $paidTotal,
                        'reposted'         => ($existing && $force_repost) ? 1 : 0,
                        'snapshot'         => $snapshotRes,
                        'cogs'             => $cogsRes,
                        'cogs_db_error'    => $err,
                        'cogs_last_query'  => $sql,
                    ],
                ];
            }

            $this->erp->trans_commit();
        }

        return [
            'ok'      => true,
            'message' => ($existing && $force_repost) ? 'REPOSTED' : 'POSTED',
            'data'    => [
                'ar_invoice_id'    => $ar_invoice_id,
                'invoice_no'       => $invoice_no,
                'job_no'           => $job_no,
                'region_id'        => $region_id,
                'source_db'        => $source_db,
                'source_table'     => $source_table,
                'source_id'        => $source_id,
                'posting_date'     => $posting_date,
                'type'             => $calc['type'],
                'currency'         => $calc['currency'],
                'kurs'             => $calc['kurs'],
                'grand_total_usd'  => $calc['grand_total_usd'],
                'grand_total_idr'  => $gt_idr,
                'dpp_idr'          => $dpp_idr,
                'ppn_idr'          => $ppn_idr,
                'paid_total'       => $paidTotal,
                'reposted'         => ($existing && $force_repost) ? 1 : 0,
                'snapshot'         => $snapshotRes,
                'cogs'             => $cogsRes,
            ],
        ];
    }

    // =========================================================
    // CHECK BEFORE REPOST / UPDATE
    // =========================================================

    public function can_repost_ar_invoice($p)
    {
        $finance_id    = (int)($p['finance_id'] ?? 0);
        $invoice_no_in = trim((string)($p['invoice_no'] ?? ''));

        if (!$finance_id && $invoice_no_in === '') {
            return [
                'ok'      => false,
                'message' => 'finance_id or invoice_no required',
            ];
        }

        $inv = $this->get_source_invoice($finance_id, $invoice_no_in);

        if (!$inv) {
            return [
                'ok'      => false,
                'message' => 'SOURCE_INVOICE_NOT_FOUND',
                'data'    => [
                    'finance_id' => $finance_id,
                    'invoice_no' => $invoice_no_in,
                    'source_db'  => $this->erp->database,
                ],
            ];
        }

        $source_db    = $inv['_source_db'] ?? $this->erp->database;
        $source_table = $inv['_source_table'] ?? 'ar_invoices';
        $source_id    = (string)$inv['id'];
        $invoice_no   = (string)$inv['number'];

        $existingAr = $this->find_ar_invoice_snapshot($source_db, $source_table, $source_id, $invoice_no);
        $paidTotal = $this->get_paid_total($inv, $existingAr);

        if ($paidTotal > 0.00001) {
            return [
                'ok'      => false,
                'message' => 'INVOICE_ALREADY_PAID_CANNOT_UPDATE',
                'data'    => [
                    'invoice_no'  => $invoice_no,
                    'region_id'   => $this->region_id(),
                    'source_db'   => $source_db,
                    'source_id'   => $source_id,
                    'paid_total'  => $paidTotal,
                    'grand_total' => $this->num($inv['grand_total'] ?? 0),
                ],
            ];
        }

        return [
            'ok'      => true,
            'message' => 'OK',
            'data'    => [
                'invoice_no'  => $invoice_no,
                'region_id'   => $this->region_id(),
                'source_db'   => $source_db,
                'source_id'   => $source_id,
                'paid_total'  => $paidTotal,
                'grand_total' => $this->num($inv['grand_total'] ?? 0),
            ],
        ];
    }
	
	private function get_rate_from_exchange_rate_table($posting_date, $currency = 'USD')
	{
		$currency = $this->normalize_currency($currency);

		if ($currency === 'IDR') {
			return 1.0;
		}

		if (!$this->table_exists_on($this->db, 'exchange_rate')) {
			return 0.0;
		}

		$fields = $this->db->list_fields('exchange_rate');
		$fieldMap = array_flip($fields);

		$dateCandidates = [
			'tanggal',
			'date',
			'rate_date',
			'exchange_date',
			'tgl',
			'posting_date',
			'created_date',
		];

		$currencyCandidates = [
			'currency',
			'curr',
			'code',
			'mata_uang',
			'currency_code',
		];

		$rateCandidates = [
			'kurs_jual',
			'selling_rate',
			'rate_jual',
			'jual',
			'sell',
			'sell_rate',
			'exchange_rate',
			'rate',
			'nilai',
			'value',
			strtolower($currency),
			strtoupper($currency),
			strtolower($currency) . '_jual',
			strtoupper($currency) . '_JUAL',
		];

		$dateCol = null;
		foreach ($dateCandidates as $c) {
			if (isset($fieldMap[$c])) {
				$dateCol = $c;
				break;
			}
		}

		$currencyCol = null;
		foreach ($currencyCandidates as $c) {
			if (isset($fieldMap[$c])) {
				$currencyCol = $c;
				break;
			}
		}

		$rateCol = null;
		foreach ($rateCandidates as $c) {
			if (isset($fieldMap[$c])) {
				$rateCol = $c;
				break;
			}
		}

		if (!$rateCol) {
			return 0.0;
		}

		$this->db->from('exchange_rate');

		if ($currencyCol) {
			$this->db->group_start()
				->where($currencyCol, $currency)
				->or_where($currencyCol, strtolower($currency))
				->group_end();
		}

		if ($dateCol && $posting_date !== '') {
			$this->db->where($dateCol . ' <=', $posting_date);
			$this->db->order_by($dateCol, 'DESC');
		}

		$this->db->limit(1);

		$row = $this->db->get()->row_array();

		if (!$row) {
			return 0.0;
		}

		return $this->parse_rate($row[$rateCol] ?? 0);
	}

private function resolve_posting_rate($inv, $posting_date, $currency)
	{
		$currency = $this->normalize_currency($currency);

		if ($currency === 'IDR') {
			return 1.0;
		}

		$candidates = [
			$inv['kurs'] ?? null,
			$inv['exchange_rate'] ?? null,
			$inv['kurs_jual'] ?? null,
			$inv['rate'] ?? null,
			$inv['payment_rate'] ?? null,
		];

		foreach ($candidates as $raw) {
			$rate = $this->parse_rate($raw);

			// Untuk USD-IDR, kurs valid harusnya ribuan.
			// Ini sengaja mengabaikan 1.000000.
			if ($rate > 100) {
				return $rate;
			}
		}

		$fallback = $this->get_rate_from_exchange_rate_table($posting_date, $currency);

		if ($fallback > 100) {
			return $fallback;
		}

		return 1.0;
	}
}