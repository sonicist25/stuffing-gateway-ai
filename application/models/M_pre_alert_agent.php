<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class M_pre_alert_agent extends CI_Model
{
    public $dbx;

    public function __construct()
    {
        parent::__construct();

        $tahun = $this->session->userdata('db');
        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    private function region()
    {
        return (string)$this->session->userdata('region_id');
    }

    public function get_header($job_no)
    {
        return $this->dbx
            ->where('kode_ref', trim((string)$job_no))
            ->where('region_id', $this->region())
            ->get('trnstuffingplan')
            ->row();
    }

    public function get_house_bls($job_no, $sp_id = null)
    {
        $this->dbx->select('
			spd.sp_detail_id,
			spd.sp_id,
			spd.num,
			spd.code,
			spd.kode_ref,
			spd.destination,
			spd.hb_l,
			spd.hs_code,
			spd.no_pack_qty,
			spd.no_pack_unit,
			spd.note,
			spd.shipper_print,
			spd.express_release,
			spd.incoterms,
			spd.agent_code,
			COALESCE(xfb.value, 1) AS form_bl
		', false);
        $this->dbx->from('trnstuffingplandetail spd');
        $this->dbx->join('trnstuffingplan sp', 'sp.sp_id = spd.sp_id', 'inner');
        $this->dbx->join('x_form_bl xfb', 'xfb.id_bl = spd.sp_detail_id', 'left');
        $this->dbx->where('spd.code', trim((string)$job_no));
        $this->dbx->where('sp.region_id', $this->region());
        $this->dbx->where('spd.sp_id !=', 0);

        if ($sp_id !== null && (int)$sp_id > 0) {
            $this->dbx->where('spd.sp_id', (int)$sp_id);
        }

        // num is usually A,B,C...; kode_ref is deterministic tie breaker.
        $this->dbx->order_by('spd.num', 'ASC');
        $this->dbx->order_by('spd.kode_ref', 'ASC');
        return $this->dbx->get()->result();
    }

    public function get_agents($job_no, $default_agent = '')
    {
        $codes = array();
        $default_agent = trim((string)$default_agent);
        if ($default_agent !== '') $codes[$default_agent] = $default_agent;

        $q1 = $this->dbx
            ->select('spd.agent_code')
            ->from('trnstuffingplandetail spd')
            ->join('trnstuffingplan sp', 'sp.sp_id = spd.sp_id', 'inner')
            ->where('spd.code', trim((string)$job_no))
            ->where('sp.region_id', $this->region())
            ->where("TRIM(COALESCE(spd.agent_code,'')) <> ''", null, false)
            ->group_by('spd.agent_code')
            ->get()->result();

        foreach ($q1 as $row) {
            $c = trim((string)$row->agent_code);
            if ($c !== '') $codes[$c] = $c;
        }

        $q2 = $this->dbx
            ->select('kepada_kode')
            ->where('job_no', trim((string)$job_no))
            ->where('region_id', $this->region())
            ->where('kepada', 'AGENT')
            ->where_in('jenis', array('DEBIT NOTE', 'CREDIT NOTE'))
            ->where("TRIM(COALESCE(kepada_kode,'')) <> ''", null, false)
            ->group_by('kepada_kode')
            ->get('x_finance')->result();

        foreach ($q2 as $row) {
            $c = trim((string)$row->kepada_kode);
            if ($c !== '') $codes[$c] = $c;
        }

        if (!$codes) return array();

        $rows = $this->dbx
            ->select('agent_company_code, agent_company_name, agent_company_address')
            ->where('region_id', $this->region())
            ->where_in('agent_company_code', array_values($codes))
            ->order_by('agent_company_code', 'ASC')
            ->get('refagentcompany')->result();

        $found = array();
        foreach ($rows as $r) $found[(string)$r->agent_company_code] = $r;

        // Preserve an agent code even if master agent data is incomplete.
        foreach ($codes as $code) {
            if (!isset($found[$code])) {
                $r = new stdClass();
                $r->agent_company_code = $code;
                $r->agent_company_name = '';
                $r->agent_company_address = '';
                $found[$code] = $r;
            }
        }

        ksort($found);
        return array_values($found);
    }

    public function get_agent($agent_code)
    {
        return $this->dbx
            ->where('agent_company_code', trim((string)$agent_code))
            ->where('region_id', $this->region())
            ->get('refagentcompany')
            ->row();
    }
    /**
     * Return all agent DN/CN for the job, regardless of Posted/Unposted status.
     * Compatibility keys posted/unposted are retained so older controller/view code
     * still works while including every DN/CN in the bundle.
     */
    public function get_finance_bundle($job_no, $agent_code)
    {
        $base = array(
            'job_no'      => trim((string)$job_no),
            'region_id'   => $this->region(),
            'kepada'      => 'AGENT',
            'kepada_kode' => trim((string)$agent_code),
            'fin_proses'  => 1,
        );

        $all = $this->dbx
            ->where($base)
            ->where_in('jenis', array('DEBIT NOTE', 'CREDIT NOTE'))
            ->order_by("CASE WHEN jenis='DEBIT NOTE' THEN 1 WHEN jenis='CREDIT NOTE' THEN 2 ELSE 3 END", '', false)
            ->order_by('id', 'ASC')
            ->get('x_finance')->result();

        return array(
            'all'      => $all,
            'posted'   => $all, // compatibility alias: intentionally includes ALL statuses
            'unposted' => array(),
        );
    }

    public function log_download($job_no, $agent_code, $instruction, $hbl_count, $dn_count, $cn_count, $filename)
    {
        $user = trim((string)$this->session->userdata('fullname'));
        if ($user === '') $user = trim((string)$this->session->userdata('username'));
        if ($user === '') $user = 'UNKNOWN';

        $payload = array(
            'agent_code'  => trim((string)$agent_code),
            'hbl_count'   => (int)$hbl_count,
            'dn_count'    => (int)$dn_count,
            'cn_count'    => (int)$cn_count,
            'instruction' => (string)$instruction,
            'filename'    => (string)$filename,
            'db_year'     => (string)$this->session->userdata('db'),
        );

        $data = array(
            'ref_type'       => 'MASTER',
            'ref_id'         => trim((string)$job_no),
            'event_code'     => 'PRE_ALERT_DOWNLOAD',
            'event_date'     => date('Y-m-d H:i:s'),
            'confirmed_by'   => substr($user, 0, 40),
            'remarks'        => 'Pre Alert created/downloaded for agent '.trim((string)$agent_code)
                                .' | HBL: '.(int)$hbl_count
                                .' | DN: '.(int)$dn_count
                                .' | CN: '.(int)$cn_count,
            'old_value'      => null,
            'new_value'      => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'location_name'  => substr($this->region(), 0, 100),
            'ip_address'     => substr((string)$this->input->ip_address(), 0, 45),
            'proof_document' => substr((string)$filename, 0, 255),
			'region_id' => $this->region(),
        );

        return $this->dbx->insert('x_timeline_audit', $data);
    }
}
