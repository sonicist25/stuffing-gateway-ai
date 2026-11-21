<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Install as:
 *   application/models/finance/M_income_billing_bridge.php
 *
 * Purpose:
 * - Called ONLY after AR Invoice / Debit Note has been posted successfully to ERP.
 * - Reads exact source references stored on x_finance_item.
 * - Marks matching Revenue Order items BILLED in ERP.
 * - Mirrors invoice_no + BILLED status back to the operational Income rows.
 */
class M_income_billing_bridge extends CI_Model
{
    public $dbx;
    private $allowed_sources = [
        'inf_income_selling_price',
        'inf_income_from_agent',
        'inf_income_from_coloader',
        'x_glbincome',
    ];

    public function __construct()
    {
        parent::__construct();

        // Follow the same yearly operational DB selection used by Afg_m_income.
        $tahun = $this->session->userdata('db');
        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }

        $this->load->helper('revenue_order');
    }

    private function source_meta($table)
    {
        $map = [
            'inf_income_selling_price' => ['doc_col'=>'ship_co'],
            'inf_income_from_agent'     => ['doc_col'=>'dn_no'],
            'inf_income_from_coloader'  => ['doc_col'=>'dn_no'],
            'x_glbincome'               => ['doc_col'=>'dn_no'],
        ];
        return $map[$table] ?? null;
    }

    private function source_db_name()
    {
        return isset($this->dbx->database) ? (string)$this->dbx->database : '';
    }

    /**
     * Mark all traceable source rows on one x_finance document as BILLED.
     * Tax/manual lines without income_source_* are intentionally ignored.
     */
    public function mark_finance_document_billed($finance_id, $invoice_no, $ar_invoice_id = 0, $document_type = '')
    {
        $finance_id = (int)$finance_id;
        $invoice_no = trim((string)$invoice_no);
        $ar_invoice_id = (int)$ar_invoice_id;
        $region = trim((string)$this->session->userdata('region_id'));
        $actor = trim((string)$this->session->userdata('fullname'));

        if ($finance_id <= 0 || $invoice_no === '' || $region === '') {
            return ['status'=>0,'http'=>422,'message'=>'INVALID_BILLING_BRIDGE_PAYLOAD'];
        }

        if (!$this->dbx->field_exists('income_source_table', 'x_finance_item') ||
            !$this->dbx->field_exists('income_source_id', 'x_finance_item')) {
            return ['status'=>0,'http'=>500,'message'=>'X_FINANCE_ITEM_SOURCE_COLUMNS_NOT_INSTALLED'];
        }

        $rows = $this->dbx
            ->select('id,income_source_table,income_source_id,price')
            ->from('x_finance_item')
            ->where('number', $finance_id)
            ->where('region_id', $region)
            ->where('income_source_table IS NOT NULL', null, false)
            ->where("TRIM(COALESCE(income_source_table,'')) != ''", null, false)
            ->where('income_source_id IS NOT NULL', null, false)
            ->get()->result_array();

        $items = [];
        foreach ($rows as $row) {
            $table = trim((string)$row['income_source_table']);
            $sid = (int)$row['income_source_id'];
            if (!in_array($table, $this->allowed_sources, true) || $sid <= 0) continue;

            $items[] = [
                'source_db'       => $this->source_db_name(),
                'source_table'    => $table,
                'source_id'       => (string)$sid,
                'billed_amount'   => (float)str_replace([',',' '], '', (string)($row['price'] ?? 0)),
                // ar_invoice_item_id is optional. ERP endpoint can resolve it
                // from ar_invoice_items.source_table/source_id after M_posting patch.
            ];
        }

        if (!$items) {
            return ['status'=>1,'message'=>'NO_TRACEABLE_INCOME_ITEMS','data'=>['count'=>0]];
        }

        $api = finance_revenue_mark_billed([
            'company_id'    => 1,
            'region_id'     => $region,
            'invoice_no'    => $invoice_no,
            'ar_invoice_id' => $ar_invoice_id,
            'document_type' => trim((string)$document_type),
            'items'         => $items,
        ]);

        if (empty($api['ok'])) {
            return [
                'status'=>0,
                'http'=>(int)($api['code'] ?? 500),
                'message'=>$api['response']['message'] ?? 'MARK_REVENUE_ITEMS_BILLED_FAILED',
                'data'=>$api['response'] ?? null,
            ];
        }

        // Mirror ONLY after ERP accepted the BILLED transition.
        $this->dbx->trans_begin();
        foreach ($items as $item) {
            $table = $item['source_table'];
            $sid = (int)$item['source_id'];
            $meta = $this->source_meta($table);
            if (!$meta || !$this->dbx->table_exists($table)) continue;

            $upd = [$meta['doc_col'] => $invoice_no];
            if ($this->dbx->field_exists('billing_status', $table)) $upd['billing_status'] = 'BILLED';
            if ($this->dbx->field_exists('billed_at', $table)) $upd['billed_at'] = date('Y-m-d H:i:s');
            if ($this->dbx->field_exists('billed_by', $table)) $upd['billed_by'] = $actor;
            if ($this->dbx->field_exists('ditarik', $table)) $upd['ditarik'] = 1;

            $this->dbx->where('id', $sid);
            if ($this->dbx->field_exists('region_id', $table)) $this->dbx->where('region_id', $region);
            $this->dbx->update($table, $upd);
        }

        if ($this->dbx->trans_status() === false) {
            $this->dbx->trans_rollback();
            return ['status'=>0,'http'=>500,'message'=>'MIRROR_BILLED_TO_OPERATIONAL_FAILED'];
        }
        $this->dbx->trans_commit();

        return [
            'status'=>1,
            'message'=>'INCOME_ITEMS_BILLED',
            'data'=>[
                'invoice_no'=>$invoice_no,
                'count'=>count($items),
                'erp'=>$api['response']['data'] ?? null,
            ],
        ];
    }
}
