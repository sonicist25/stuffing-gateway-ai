<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class M_leads extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    public function __construct()
    {
        parent::__construct();

        // CRM Lead saat ini berada pada database operasional tahun berjalan.
        // Pertahankan pola existing, tetapi seluruh query WAJIB menggunakan $this->dbx.
        $tahun = '2026';
        $this->dbx = ($tahun === '' || !is_numeric($tahun))
            ? $this->load->database('master', true)
            : $this->load->database(switch_db($tahun), true);
    }

    /* =========================================================
     * COMMON SCOPE
     * ========================================================= */

    private function apply_lead_scope($region_id, $sales_id = null, $q = null, $hide_accepted = true)
    {
        $region_id = trim((string)$region_id);

        // Fail closed: endpoint Lead tidak boleh pernah menjadi unscoped.
        if ($region_id === '') {
            $this->dbx->where('1 = 0', null, false);
            return;
        }

        $this->dbx->where('crm_leads.region_id', $region_id);

        if ($sales_id !== null && $sales_id !== '' && strtoupper((string)$sales_id) !== 'ALL') {
            $this->dbx->where('crm_leads.sales_id', (int)$sales_id);
        }

        $q = trim((string)$q);
        if ($q !== '') {
            $this->dbx->group_start()
                ->like('crm_leads.company_name', $q)
                ->or_like('crm_leads.contact_name', $q)
                ->or_like('crm_leads.cargo_description', $q)
                ->or_like('refsales.fullname', $q)
            ->group_end();
        }

        if ($hide_accepted) {
            $this->apply_hide_accepted_leads();
        }
    }

    /**
     * Lead yang Opportunity-nya sudah menerima rate tidak lagi ditampilkan pada
     * Lead list. Data Lead TIDAK dihapus/dikonversi, sehingga bila quotation
     * dibatalkan dan Opportunity dikembalikan ke stage sebelumnya, Lead otomatis
     * muncul lagi.
     */
    private function apply_hide_accepted_leads()
    {
        if (!$this->dbx->table_exists('crm_opportunities')) {
            return;
        }

        $wonStages = ['Rate Accepted', 'Space Confirmed', 'Booking Received'];
        $stageSql  = implode(',', array_map([$this->dbx, 'escape'], $wonStages));

        $wonCondition = "op.stage IN ($stageSql)";
        if ($this->dbx->field_exists('is_closed_won', 'crm_opportunities')) {
            $wonCondition = "($wonCondition OR COALESCE(op.is_closed_won,0)=1)";
        }

        $deletedCondition = $this->dbx->field_exists('deleted_at', 'crm_opportunities')
            ? 'AND op.deleted_at IS NULL'
            : '';

        $this->dbx->where(
            "NOT EXISTS (
                SELECT 1
                FROM crm_opportunities op
                WHERE op.source_type = 'LEAD'
                  AND op.lead_id = crm_leads.id_lead
                  AND op.region_id = crm_leads.region_id
                  $deletedCondition
                  AND $wonCondition
            )",
            null,
            false
        );
    }

    private function base_lead_query()
    {
        $this->dbx->from('crm_leads');
        // Konsisten: refsales direlasikan dengan user_id, bukan id.
        $this->dbx->join('refsales', 'crm_leads.sales_id = refsales.user_id', 'left');
    }

    public function sales_belongs_to_region($sales_id, $region_id)
    {
        $sales_id = (int)$sales_id;
        $region_id = trim((string)$region_id);

        if ($sales_id <= 0 || $region_id === '') {
            return false;
        }

        $this->dbx->from('refsales');
        $this->dbx->where('user_id', $sales_id);
        $this->dbx->where('region_id', $region_id);

        if ($this->dbx->field_exists('is_active', 'refsales')) {
            $this->dbx->where('is_active', 1);
        }

        return $this->dbx->count_all_results() > 0;
    }

    /* =========================================================
     * CREATE
     * ========================================================= */

    public function insert_lead(array $data)
    {
        if (isset($data['company_name']) && $this->dbx->field_exists('normalized_name', 'crm_leads')) {
            $data['normalized_name'] = $this->normalize_company_name($data['company_name']);
        }

        $this->dbx->insert('crm_leads', $data);
        return (int)$this->dbx->insert_id();
    }

    /* =========================================================
     * READ
     * ========================================================= */

    public function get_lead_by_id($id, $region_id, $sales_id = null)
    {
        $id = (int)$id;
        if ($id <= 0) return null;

        $this->dbx->select('crm_leads.*, refsales.fullname');
        $this->base_lead_query();
        $this->dbx->where('crm_leads.id_lead', $id);
        $this->apply_lead_scope($region_id, $sales_id, null, false);

        return $this->dbx->limit(1)->get()->row_array();
    }

    /**
     * $sales_id = null  => semua sales DI REGION TERSEBUT (Branch Manager)
     * $sales_id = user  => hanya lead milik user (Sales biasa)
     */
    public function get_leads($sales_id, $region_id, $limit = 20, $offset = 0, $q = null)
    {
        $limit  = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        // COUNT
        $this->base_lead_query();
        $this->apply_lead_scope($region_id, $sales_id, $q, true);
        $countDb = clone $this->dbx;
        $total = (int)$countDb->count_all_results('', false);

        // DATA
        $this->dbx->select('crm_leads.*, refsales.fullname');
        $this->dbx->order_by('crm_leads.id_lead', 'DESC');
        $rows = $this->dbx
            ->limit($limit, $offset)
            ->get()
            ->result();

        return [
            'data'  => $rows,
            'total' => $total,
        ];
    }

    /* =========================================================
     * UPDATE / DELETE — region scoped
     * ========================================================= */

    public function update_lead($id, array $data, $region_id, $sales_id = null)
    {
        $id = (int)$id;
        $region_id = trim((string)$region_id);
        if ($id <= 0 || $region_id === '') return false;

        if (isset($data['company_name']) && $this->dbx->field_exists('normalized_name', 'crm_leads')) {
            $data['normalized_name'] = $this->normalize_company_name($data['company_name']);
        }

        $this->dbx->where('id_lead', $id);
        $this->dbx->where('region_id', $region_id);
        if ($sales_id !== null && $sales_id !== '') {
            $this->dbx->where('sales_id', (int)$sales_id);
        }

        return (bool)$this->dbx->update('crm_leads', $data);
    }

    public function delete_lead($id, $region_id, $sales_id = null)
    {
        $id = (int)$id;
        $region_id = trim((string)$region_id);
        if ($id <= 0 || $region_id === '') return false;

        $this->dbx->where('id_lead', $id);
        $this->dbx->where('region_id', $region_id);
        if ($sales_id !== null && $sales_id !== '') {
            $this->dbx->where('sales_id', (int)$sales_id);
        }

        return (bool)$this->dbx->delete('crm_leads');
    }

    /* =========================================================
     * DUPLICATE CHECK — same region only
     * ========================================================= */

    public function check_duplicate($company_name, $region_id)
    {
        $normalized = $this->normalize_company_name($company_name);
        $region_id  = trim((string)$region_id);

        if ($normalized === '' || $region_id === '') {
            return [];
        }

        $this->dbx->select('id_lead, company_name, normalized_name');
        $this->dbx->where('region_id', $region_id);
        $leads = $this->dbx->get('crm_leads')->result();

        $duplicates = [];
        foreach ($leads as $lead) {
            $existingNormalized = trim((string)($lead->normalized_name ?? ''));
            if ($existingNormalized === '') {
                $existingNormalized = $this->normalize_company_name($lead->company_name ?? '');
            }

            $percent = 0;
            similar_text($normalized, $existingNormalized, $percent);
            if ($percent >= 80) {
                $duplicates[] = [
                    'id_lead'         => (int)$lead->id_lead,
                    'company_name'    => (string)$lead->company_name,
                    'normalized_name' => $existingNormalized,
                    'similarity'      => round($percent, 2),
                ];
            }
        }

        return $duplicates;
    }

    private function normalize_company_name($name)
    {
        $name = strtoupper(trim((string)$name));
        $name = preg_replace('/[\.\s]+/', ' ', $name);

        $prefixesPerson = ['MR ', 'MRS ', 'BAPAK ', 'IBU '];
        foreach ($prefixesPerson as $prefix) {
            if (strpos($name, $prefix) === 0) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        $prefixesCompany = ['PT ', 'CV ', 'UD ', 'FIRMA ', 'KOPERASI '];
        foreach ($prefixesCompany as $prefix) {
            if (strpos($name, $prefix) === 0) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        return trim($name);
    }
}
