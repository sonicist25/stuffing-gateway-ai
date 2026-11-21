<?php
class Afg_m_job_sheet extends CI_Model
{
	
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

	function get_no_cn($data)
	{
		$data[2] = substr($data[2], 0, 15);
		$q = $this->dbx->get_where("x_finance_item",array(
			"kepada"=>$data[0],
			"item"=>$data[1],
			"kode_ref"=>$data[2],
			"jenis"=>"CREDIT NOTE",
		));
		if($q->num_rows()>0)
		{
			$query = $this->dbx->get_where("x_finance",array("id"=>$q->row()->number,'region_id'=>$this->session->userdata("region_id")));
			if($query->num_rows()>0)
			{
				return $query->row()->number;
			}
			else 
			{
				return "";
			}
		}
		else 
		{
			return "";
		}
	}  
	
	function get_no_dn($data)
	{
		$q = $this->dbx->get_where("x_finance_item",array(
			"anak_job"=>$data[1],
			"item"=>$data[2],
			"jenis"=>"DEBIT NOTE",
		));
		if($q->num_rows()>0)
		{
			$query = $this->dbx->get_where("x_finance",array("id"=>$q->row()->number,'region_id'=>$this->session->userdata("region_id")));
			if($query->num_rows()>0)
			{
				return $query->row()->number;
			}
			else 
			{
				return "";
			}
		}
		else 
		{
			return $data[0];
		}
	}

	function get_sp_spd_by($kode_ref,$spd)
	{
		$this->dbx->select('*');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');	
		$this->dbx->where('trnstuffingplandetail.kode_ref',$kode_ref);	
		$this->dbx->where('trnstuffingplandetail.sp_detail_id',$spd);	
		
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query->row_array();
		} else {
			return null;	
		}
	}

	function get_sp_spd_by_post_jobsheet($kode_ref, $spd)
	{
		return $this->dbx
			->select('
				shipper_print,
				sales,
				destination,
				bl_meas,
				weight
			')
			->from('trnstuffingplandetail')
			->where([
				'kode_ref'    => $kode_ref,
				'sp_detail_id'=> $spd,
				'region_id'   => $this->session->userdata('region_id')
			])
			->get()
			->row_array();
	}
	
	function get_count_double($sp_id) 
	{
		return $this->dbx->get_where('count_double_seal',array('id_sp'=>$sp_id));
	}

	function get_hb_l($kode_ref)
	{
		$this->dbx->select('trnstuffingplandetail.hb_l');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where('trnstuffingplandetail.kode_ref', $kode_ref);
		$q = $this->dbx->get();

		if ($q->num_rows() > 0) {
			return $q->row()->hb_l;
		} else {
			return null; 
		}
	}

	function get_final_destination($kode_ref)
	{
		$this->dbx->select('trnstuffingplandetail.destination');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where("trnstuffingplandetail.kode_ref",$kode_ref);
		$q = $this->dbx->get();

		return $q->row()->destination;
	}

	function get_no_invoice($data){
		$a = $data[0];
		$this->dbx->join('x_finance','x_finance.id=x_finance_item.number','left');
		$this->dbx->where('x_finance_item.anak_job',$a);
		$q=$this->dbx->get_where("x_finance_item",array(
			"x_finance_item.item"=>$data[1],
			"x_finance.group"=>$data[2],
			'x_finance.region_id'=>$this->session->userdata("region_id")
		));
		
		if($q->num_rows()>0) {
			return $q->row()->number;
		} else {
			$b = substr($data[0],0,15);
			$this->dbx->join('x_finance','x_finance.id=x_finance_item.number','left');
			$q1=$this->dbx->get_where("x_finance_item",array(
				"x_finance_item.kode_ref"=>$b,
				"x_finance_item.item"=>$data[1],
				"x_finance.group"=>$data[2],
				'x_finance.region_id'=>$this->session->userdata("region_id")
			));
			
			if($q1->num_rows()>0) {
				return $q1->row()->number;
			}
		}		
	}

	function get_location($id)
	{
		$q = $this->dbx->get_where('refstuffinglocation',array('stuffing_location_id'=>$id));
		if($q->num_rows()>0) 
		{
			return strtoupper($q->row()->stuffing_location_company);
		}
		else 
		{
			return '';	
		}
	}
	
	function get_trucking($id)
	{
		$q = $this->dbx->get_where('reftruckingcompany',array('trucking_company_id'=>$id));
		if($q->num_rows()>0) 
		{
			return strtoupper($q->row()->trucking_company_name);
		}
		else 
		{
			return '';	
		}
	}

	function get_detail_job($id)
	{
		$data = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
		if($data->num_rows() > 0) {
			return $data->row();
		}
		else 
		{
			$data = new stdClass();
			$data->location = "";
			$data->trucking_co = "";
			$data->last_vessel = "";
			$data->feeder_vessel = "";
			$data->feeder_vessel_etd = "";
			$data->feeder_vessel_eta = "";
			$data->feeder_vessel_etd_date = "";
			$data->feeder_vessel_eta_date = "";
			$data->last_snd_vessel = "";
			$data->snd_vessel = "";
			$data->snd_vessel_etd = "";
			$data->snd_vessel_eta = "";
			$data->snd_vessel_etd_date = "";
			$data->snd_vessel_eta_date = "";
			$data->snd_vessel_eta_date = "";
			return $data;
		}
	}

	/**
	 * Detail job + nama trucking dalam satu query.
	 */
	function get_detail_job_for_pdf($id)
	{
		$q = $this->dbx
			->select('tbl_detail.*, reftruckingcompany.trucking_company_name AS trucking_name')
			->from('tbl_detail')
			->join('reftruckingcompany', 'reftruckingcompany.trucking_company_id = tbl_detail.trucking_co', 'left')
			->where('tbl_detail.detail_id', $id)
			->get();

		if ($q->num_rows() > 0) {
			return $q->row();
		}

		$data = new stdClass();
		$data->location = '';
		$data->trucking_co = '';
		$data->trucking_name = '';
		$data->last_vessel = '';
		$data->last_vessel_etd = '';
		$data->last_vessel_eta = '';
		$data->last_vessel_etd_date = '';
		$data->last_vessel_eta_date = '';
		$data->feeder_vessel = '';
		$data->feeder_vessel_etd = '';
		$data->feeder_vessel_eta = '';
		$data->feeder_vessel_etd_date = '';
		$data->feeder_vessel_eta_date = '';
		$data->last_snd_vessel = '';
		$data->last_snd_vessel_etd = '';
		$data->last_snd_vessel_eta = '';
		$data->last_snd_vessel_etd_date = '';
		$data->last_snd_vessel_eta_date = '';
		$data->snd_vessel = '';
		$data->snd_vessel_etd = '';
		$data->snd_vessel_eta = '';
		$data->snd_vessel_etd_date = '';
		$data->snd_vessel_eta_date = '';
		return $data;
	}

	/**
	 * Ambil seluruh Credit Note untuk service pada satu job dalam satu query.
	 * Key hasil adalah item_service.
	 */
	function get_cn_map_for_pdf($agent, $kode_ref, $items)
	{
		$items = array_values(array_unique(array_filter(array_map('strval', (array) $items))));
		if ($agent === '' || empty($items)) return array();

		$job = substr((string) $kode_ref, 0, 15);
		$q = $this->dbx
			->select('x_finance_item.item, x_finance.number')
			->from('x_finance_item')
			->join('x_finance', 'x_finance.id = x_finance_item.number', 'inner')
			->where('x_finance_item.kepada', $agent)
			->where('x_finance_item.kode_ref', $job)
			->where('x_finance_item.jenis', 'CREDIT NOTE')
			->where_in('x_finance_item.item', $items)
			->get();

		$out = array();
		foreach ($q->result() as $row) {
			if (!isset($out[$row->item])) $out[$row->item] = $row->number;
		}
		return $out;
	}

	/** Ambil nomor Debit Note satu kali per halaman/job. */
	function get_dn_for_pdf($agent, $kode_ref)
	{
		if ($agent === '') return '';

		$job = substr((string) $kode_ref, 0, 15);
		$q = $this->dbx
			->select('x_finance.number')
			->from('x_finance_item')
			->join('x_finance', 'x_finance.id = x_finance_item.number', 'inner')
			->where('x_finance_item.kepada', $agent)
			->where('x_finance_item.kode_ref', $job)
			->where('x_finance_item.jenis', 'DEBIT NOTE')
			->limit(1)
			->get();

		return ($q->num_rows() > 0) ? $q->row()->number : '';
	}

	function save_job_sheet($data) {
		$q = $this->dbx->get_where('save_jobsheet',array('kode_ref'=>$data['kode_ref']));
		if($q->num_rows() > 0 ) {
			$kode_ref= $data['kode_ref'];
			unset($data['kode_ref']);
			$this->dbx->where('kode_ref',$kode_ref);
			$this->dbx->update('save_jobsheet',$data);
		} else {
			$this->dbx->insert('save_jobsheet',$data);
		}
	}

	function get_income_job_sheet($kode_ref){
	 	$q1 = $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$kode_ref))->result();
		$q2 = $this->dbx->get_where('inf_income_from_coloader',array('kode_ref'=>$kode_ref))->result();
		$q = array_merge($q1,$q2);
		return $q;
	}
	
	function get_job_sheet_tmp($kode_ref,$type){
		return $this->dbx->get_where("job_shit".$this->session->userdata("fullname"),array("kode_ref"=>$kode_ref,"type"=>$type,'region_id'=>$this->session->userdata("region_id")))->result();
	}
	
	function get_income_global($kode_ref){
	 	$this->dbx->select('*');
	 	$this->dbx->from('x_glbincome');
	 	$this->dbx->where('job_no',$kode_ref);
		return $this->dbx->get()->result();
	}

	function get_cost_job_sheet($table,$kode_ref){
	 	$this->dbx->select('*');
	 	$this->dbx->from($table);
	 	if($table=='x_glbcost') {
	 		$job = substr($kode_ref,0,15);
	 		$this->dbx->where('job_no',$job);
	 	} else {
	 		$this->dbx->where('kode_ref',$kode_ref);
	 	}
		return $this->dbx->get()->result();
	}

	function get_income_pdf($kode_ref){
	 	$this->dbx->select('*');
	 	$this->dbx->from('inf_income_selling_price');
		$this->dbx->where('kode_ref',$kode_ref);
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return false;	
		}	
	}
	
	function get_job_sheet($job){
	 	$query = $this->dbx->get_where('v_sp_gabung',array('kode_ref' => $job));
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;
		}		
	}
	
	function get_job_form($job){
	 	$query = $this->dbx->get_where('a_job_form',array('kode_ref' => $job,'region_id'=>$this->session->userdata("region_id")));
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			$this->dbx->insert('a_job_form', array('kode_ref'=>$job,'region_id'=>$this->session->userdata("region_id")));
			$query = $this->dbx->get_where('a_job_form',array('kode_ref' => $job,'region_id'=>$this->session->userdata("region_id")));
			return $query->row_array();
		}		
	}

	function manage_data_form($id, $item, $kode_ref, $mode, $region){
		// 1. Eksekusi update riwayat form (UI)
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where('region_id',$region);
		$this->dbx->update(
			'a_job_form', 
			array(
				$item.'_'.$id =>'TRUE',
				'tgl_'.$item.'_'.$id =>date('d-m-Y'), 
				'memo'=> $this->session->userdata('fullname').'<br> at '. date('d-m-Y').' '. date('H:i:s'),
			)
		);
		
		// 2. SELALU eksekusi update status lock_job ke database asli
		// (Tanpa kondisi affected_rows dan dimatikan validasi region agar tidak desinkronisasi)
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->update('trnstuffingplandetail',array('lock_job'=>$mode));
		
		return '1';	
	}
	
	function update_data_form($kode_ref,$kurs,$date){
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->update(
			'a_job_form', 
			array(
				'kurs'=>$kurs,
				'tgl_cetak' => $date, 
			)
		);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}

	function get_detail_agent($agent) {
		$this->dbx->like('payable_to',$agent);
		return $this->dbx->get('inf_cost_tbl_satu');
	}
}