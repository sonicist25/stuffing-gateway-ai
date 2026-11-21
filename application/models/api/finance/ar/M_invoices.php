<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_invoices extends MY_Model
{
  private $company_id = 1;
  private $m;

  public function __construct(){
    parent::__construct();
    $this->config->load('ar_map');
    $this->m = $this->config->item('ar_map');
  }

  public function list($p)
{
    $region_id = $this->auth_region();
	log_message('error', 'DEBUG auth_region=' . print_r($this->auth_region(), true));
log_message('error', 'DEBUG session region_id=' . print_r($this->session->userdata('region_id'), true));
log_message('error', 'DEBUG session region=' . print_r($this->session->userdata('region'), true));

    $this->erp->select("
        ai.ar_invoice_id,
        ai.ar_invoice_id AS finance_id,
        ai.invoice_no,
        ai.posting_date,
        ai.job_no,

        ai.party_type AS kepada,
        ai.party_code AS kepada_kode,
        ai.party_name AS customer_name,

        ai.currency,
        ai.exchange_rate AS kurs,

        ai.grand_total,
        ai.ppn_total AS ppn,
        ai.dpp_total,

        ai.grand_total_idr,
        ai.ppn_total_idr AS ppn_idr,
        ai.dpp_total_idr AS dpp_idr,

        COALESCE(ai.paid_total,0) AS paid_amount,

		CASE
			WHEN UPPER(ai.currency) = 'USD'
				THEN COALESCE(ai.paid_total,0) * COALESCE(NULLIF(ai.exchange_rate,0),1)
			ELSE COALESCE(ai.paid_total,0)
		END AS paid_amount_idr,

		GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) AS outstanding,

		CASE
			WHEN UPPER(ai.currency) = 'USD'
				THEN GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) * COALESCE(NULLIF(ai.exchange_rate,0),1)
			ELSE GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0)
		END AS outstanding_idr,

        ai.status,
        ai.source_db,
        ai.source_table,
        ai.source_id,
        ai.source_ref,
        ai.is_opening,

        CASE
            WHEN ai.party_type = 'AGENT' THEN 'AGENT'
            WHEN ai.ppn_total_idr > 0.01 THEN 'TAX'
            ELSE 'NON_TAX'
        END AS type,

        CASE
			WHEN GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) <= 0.01 THEN 'PAID'
			WHEN COALESCE(ai.paid_total,0) > 0.01 THEN 'PARTIAL'
			ELSE 'UNPAID'
		END AS pay_status
    ", false);

    $this->erp->from('ar_invoices ai');
    $this->erp->where('ai.company_id', $this->company_id);

    if (!$this->is_super_admin()) {
        $this->erp->where('ai.region_id', $region_id);
    }

    $this->erp->where_in('ai.status', ['SUBMITTED', 'POSTED', 'PARTIAL', 'PAID']);

    if (!empty($p['search'])) {
        $q = trim($p['search']);

        $this->erp->group_start()
            ->like('ai.invoice_no', $q)
            ->or_like('ai.job_no', $q)
            ->or_like('ai.party_type', $q)
            ->or_like('ai.party_code', $q)
            ->or_like('ai.party_name', $q)
            ->or_like('ai.source_db', $q)
            ->or_like('ai.source_ref', $q)
        ->group_end();
    }

    if (!empty($p['date_from'])) {
        $this->erp->where('ai.posting_date >=', $p['date_from']);
    }

    if (!empty($p['date_to'])) {
        $this->erp->where('ai.posting_date <=', $p['date_to']);
    }

    if (!empty($p['status'])) {
        $st = strtoupper(trim($p['status']));

        if ($st === 'PAID') {
            $this->erp->where("GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) <= 0.01", null, false);
        } elseif ($st === 'UNPAID') {
            $this->erp->where("COALESCE(ai.paid_total,0) <= 0.01", null, false);
            $this->erp->where("GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) > 0.01", null, false);
        } elseif ($st === 'PARTIAL') {
            $this->erp->where("COALESCE(ai.paid_total,0) > 0.01", null, false);
            $this->erp->where("GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) > 0.01", null, false);
        } elseif (in_array($st, ['SUBMITTED', 'POSTED', 'CANCELLED'], true)) {
            $this->erp->where('ai.status', $st);
        }
    }

    $this->erp->order_by('ai.posting_date', 'DESC');
    $this->erp->order_by('ai.ar_invoice_id', 'DESC');

    $q = $this->erp->get();

    if ($q === false) {
        return [[
            'error'    => true,
            'sql'      => $this->erp->last_query(),
            'db_error' => $this->erp->error(),
        ]];
    }

    return $q->result_array();
}

  public function detail($ar_invoice_id)
{
    $ar_invoice_id = (int)$ar_invoice_id;
    if ($ar_invoice_id <= 0) {
        return null;
    }

    $region_id = $this->auth_region();

    $this->erp->select("
        ai.ar_invoice_id,
        ai.ar_invoice_id AS finance_id,
        ai.invoice_no,
        ai.posting_date,
        ai.job_no,

        ai.party_type AS kepada,
        ai.party_code AS kepada_kode,
        ai.party_name AS customer_name,

        ai.currency,
        ai.exchange_rate AS kurs,

        ai.grand_total,
        ai.ppn_total AS ppn,
        ai.dpp_total,

        ai.grand_total_idr,
        ai.ppn_total_idr,
        ai.dpp_total_idr,

        COALESCE(ai.paid_total,0) AS paid_amount,

		GREATEST(ai.grand_total - COALESCE(ai.paid_total,0), 0) AS outstanding,

        ai.status,
        ai.source_db,
        ai.source_table,
        ai.source_id,
        ai.source_ref,
        ai.is_opening
    ", false);

    $this->erp->from('ar_invoices ai');
    $this->erp->where('ai.company_id', $this->company_id);
    $this->erp->where('ai.ar_invoice_id', $ar_invoice_id);

    if (!$this->is_super_admin()) {
        $this->erp->where('ai.region_id', $region_id);
    }

    $hdr = $this->erp->get()->row_array();

    if (!$hdr) {
        return null;
    }

    $items = $this->erp
        ->where('ar_invoice_id', $ar_invoice_id)
        ->order_by('ar_invoice_item_id', 'ASC')
        ->get('ar_invoice_items')
        ->result_array();

    $hdr['items'] = $items;

    $outstanding = (float)$hdr['outstanding'];
    $paid = (float)$hdr['paid_amount'];

    $hdr['pay_status'] = ($outstanding <= 0.01)
        ? 'PAID'
        : (($paid > 0.01) ? 'PARTIAL' : 'UNPAID');

    return $hdr;
}
  
  private function resolve_customer_names($rows)
{
  $agentCodes = [];
  $whCodes = [];
  $coCodes = [];
  $numbers = [];

  foreach ($rows as $r) {
    $kpd  = strtoupper((string)($r['kepada'] ?? ''));
    $code = (string)($r['kepada_kode'] ?? '');
    $no   = (string)($r['invoice_no'] ?? '');

    if ($no) $numbers[] = $no;

    if ($kpd === 'AGENT' && $code) $agentCodes[] = $code;
    if ($kpd === 'WAREHOUSE' && $code) $whCodes[] = $code;
    if ($kpd === 'COLOADER' && $code) $coCodes[] = $code;
  }

  $agentCodes = array_values(array_unique($agentCodes));
  $whCodes    = array_values(array_unique($whCodes));
  $coCodes    = array_values(array_unique($coCodes));
  $numbers    = array_values(array_unique($numbers));

  $mapAgent = [];
  if ($agentCodes) {
    $q = $this->erp->select('agent_company_code, agent_company_name')
      ->where_in('agent_company_code', $agentCodes)
      ->get('refagentcompany')->result_array();
    foreach ($q as $x) $mapAgent[$x['agent_company_code']] = $x['agent_company_name'];
  }

  $mapWh = [];
  if ($whCodes) {
    $q = $this->erp->select('warehouse_code, warehouse_name')
      ->where_in('warehouse_code', $whCodes)
      ->get('refwarehouse')->result_array();
    foreach ($q as $x) $mapWh[$x['warehouse_code']] = $x['warehouse_name'];
  }

  $mapCo = [];
  if ($coCodes) {
    $q = $this->erp->select('shipping_company_code, shipping_company_name')
      ->where_in('shipping_company_code', $coCodes)
      ->get('refshippingcompany')->result_array();
    foreach ($q as $x) $mapCo[$x['shipping_company_code']] = $x['shipping_company_name'];
  }

  $mapInv = [];
  if ($numbers) {
    // default fallback: x_invoice.nama else shipper_name
    $q = $this->erp->select('number, nama, shipper_name')
      ->where_in('number', $numbers)
      ->get('x_invoice')->result_array();

    foreach ($q as $x) {
      $mapInv[$x['number']] = !empty($x['nama']) ? $x['nama'] : ($x['shipper_name'] ?? '');
    }
  }

  foreach ($rows as &$r) {
    $kpd  = strtoupper((string)($r['kepada'] ?? ''));
    $code = (string)($r['kepada_kode'] ?? '');
    $no   = (string)($r['invoice_no'] ?? '');

    if ($kpd === 'AGENT') {
      $r['customer_name'] = $mapAgent[$code] ?? '';
    } elseif ($kpd === 'WAREHOUSE') {
      $r['customer_name'] = $mapWh[$code] ?? '';
    } elseif ($kpd === 'COLOADER') {
      $r['customer_name'] = $mapCo[$code] ?? '';
    } else {
      $r['customer_name'] = $mapInv[$no] ?? '';
    }
  }
  unset($r);

  return $rows;
}
  public function payments($ar_invoice_id)
{
    $ar_invoice_id = (int)$ar_invoice_id;

    $inv = $this->erp
        ->select('ar_invoice_id, source_db, source_table, source_id')
        ->from('ar_invoices')
        ->where('ar_invoice_id', $ar_invoice_id)
        ->get()
        ->row_array();

    if (!$inv) return [];

    $this->erp->select("
        r.rcpt_id,
        r.rcpt_no,
        r.posting_date,
        r.status,
        rr.allocated_amount
    ", false);

    $this->erp->from('ar_receipt_refs rr');
    $this->erp->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner');
    $this->erp->where('r.company_id', $this->company_id);

    $this->erp->group_start()
        ->where('rr.ar_invoice_id', $ar_invoice_id)
        ->or_group_start()
            ->where('rr.source_db', $inv['source_db'])
            ->where('rr.source_table', $inv['source_table'])
            ->where('rr.source_id', $inv['source_id'])
        ->group_end()
    ->group_end();

    $this->erp->order_by('r.posting_date', 'DESC');
    $this->erp->order_by('r.rcpt_id', 'DESC');

    return $this->erp->get()->result_array();
}

    private function _find_account_id_by_code($code)
  {
    $row = $this->erp->select('account_id,is_postable,is_active,root_type')
      ->get_where('accounts', [
        'company_id' => $this->company_id,
        'account_code' => $code
      ])->row_array();

    if (!$row) return 0;
    if ((int)$row['is_active'] !== 1 || (int)$row['is_postable'] !== 1) return 0;
    return (int)$row['account_id'];
  }

  private function _is_locked($posting_date){
    $row = $this->erp->select('MAX(lock_date) mx', false)
      ->get_where('accounting_locks',['company_id'=>$this->company_id])->row_array();
    $mx = $row['mx'] ?? null;
    return ($mx && $posting_date <= $mx);
  }
  
  public function payment_history($ar_invoice_id)
	{
		$ar_invoice_id = (int)$ar_invoice_id;
		if ($ar_invoice_id <= 0) {
			return [];
		}

		$invoice = $this->erp
			->select('ar_invoice_id, invoice_no, status, grand_total, paid_total, region_id')
			->from('ar_invoices')
			->where('ar_invoice_id', $ar_invoice_id)
			->where('company_id', $this->company_id)
			->get()
			->row_array();

		if (!$invoice) {
			log_message('error', 'DEBUG payment_history invoice not found ar_invoice_id='.$ar_invoice_id);
			return [];
		}

		$rows = $this->erp
			->select("
				rr.ref_id,
				rr.rcpt_id,
				rr.ar_invoice_id,
				rr.invoice_no,
				rr.allocated_amount,
				rr.currency AS ref_currency,
				r.rcpt_no,
				r.posting_date,
				r.status,
				r.remark,
				r.cash_account_id,
				r.currency,
				r.payment_rate,
				COALESCE(r.total_paid,0) AS total_paid,
				COALESCE(r.cash_received,0) AS cash_received,
				COALESCE(r.deposit_used,0) AS deposit_used,
				COALESCE(r.adjustment_total,0) AS adjustment_total,
				r.region_id
			", false)
			->from('ar_receipt_refs rr')
			->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner')
			->where('r.company_id', $this->company_id)
			->where('rr.ar_invoice_id', $ar_invoice_id)
			->order_by('r.posting_date', 'DESC')
			->order_by('r.rcpt_id', 'DESC')
			->get()
			->result_array();

		log_message('error', 'DEBUG payment_history ar_invoice_id='.$ar_invoice_id.' invoice_no='.$invoice['invoice_no'].' rows='.count($rows));
		log_message('error', 'DEBUG payment_history last_query='.$this->erp->last_query());

		return $rows ?: [];
	}
}