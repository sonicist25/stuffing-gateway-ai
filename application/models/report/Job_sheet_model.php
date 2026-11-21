<?php
class Job_sheet_model extends CI_Model
{
	protected $second_db;
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
	
	function get_proses($data){
		$region_id=$this->session->userdata('region_id');
		$user_id=$this->session->userdata('user_id');
		
		$this->dbx->where('user_id',$user_id);
		$this->dbx->delete('last_query');
		
		$this->dbx->select(array(
			'trnstuffingplan.sp_id as sp_id',
			'trnstuffingplan.kode_ref as sp_kode_ref',
			'trnstuffingplan.tanggal_job as tanggal_job',
			'trnstuffingplan.mother_bl as mbl',
			'trnstuffingplan.type_cont_qty as cont_qty',
			'trnstuffingplan.type_cont_size as cont_size',
			'trnstuffingplan.port_loading as port_loading',
			'trnstuffingplan.destination as sp_destination',
			'trnstuffingplan.carrier as carrier',
			'trnstuffingplan.cont_seal_no as csn',
			'trnstuffingplandetail.sp_detail_id as spd_id',
			'trnstuffingplandetail.kode_ref as spd_kode_ref',
			'trnstuffingplandetail.freight as freight',
			'trnstuffingplandetail.shipper_print as shipper_print',
			'trnstuffingplandetail.destination as spd_destination',
			'trnstuffingplandetail.shipper_import as shipper_import',
			'trnstuffingplandetail.hb_l as hbl',
			'trnstuffingplandetail.bl_meas as meas',
			
		),FALSE);
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$bulan = isset($data['bulan']) ? trim($data['bulan']) : '';
		$kode_ref = isset($data['kode_ref']) ? trim($data['kode_ref']) : '';

		// Bulan optional: kosong = semua periode.
		if ($bulan !== '') {
			$this->dbx->like('trnstuffingplan.tanggal_job', $bulan, 'after');
		}

		// Keyword job mengikuti kode master pada trnstuffingplan.kode_ref.
		// Contoh: GCL-100..., GCL-701..., UPG-902..., dst.
		if ($kode_ref !== '') {
			$job_code = substr($kode_ref, 0, 3);
			$this->dbx->where(
				'SUBSTRING(trnstuffingplan.kode_ref, 5, 3) = ' . $this->dbx->escape($job_code),
				NULL,
				FALSE
			);
		}

		$this->dbx->where('trnstuffingplan.region_id',$region_id);
		$this->dbx->order_by('trnstuffingplan.kode_ref', 'asc');
		$query = $this->dbx->get('trnstuffingplandetail');
		//print_r($this->dbx->last_query());
		//die();
		if($query->num_rows() > 0 ) {
			$last_query = $this->dbx->last_query();
			$this->dbx->insert('last_query',array('user_id'=>$user_id,'query'=>$last_query));
			return array('status'=>'1','bulan'=>$bulan);
		} else {
			return array('status'=>'0','bulan'=>$bulan);	
		}
	}
	
	function get_query(){
		$data = $this->dbx->get_where('last_query',array('user_id'=>$this->session->userdata('user_id')));	
		if($data->num_rows() > 0 ) {
			//print_r('OK');
			//die();
			$sql = $data->row()->query;
			$query = $this->dbx->query($sql);
			return $query;
		} else {
			return false;	
		}
	}
	
	function get_schedule($id){
		$data = $this->dbx->get_where('trnexport',array('sp_id'=>$id));	
		if($data->num_rows() > 0 ) {
			return $data->row()->export_id;
			} else {
			return "";	
		}
	}

	function get_schedule_detail($id){
		$data = $this->dbx->get_where('trnstuffingplan',array('sp_id'=>$id));	
		if($data->num_rows() > 0 ) {
			$vesel = $data->row()->vessel;
			$voyage = $data->row()->voyage;
			$etd_jkt = $data->row()->etd_jkt;
			$atd_jkt = $data->row()->atd;
			$eta_des = $data->row()->eta;
			$ata_des = $data->row()->ata;
			return $vesel.' '.$voyage.'<br>ETD : '.$this->jin_date_str($etd_jkt).'- ETA : '.$this->jin_date_str($eta_des).'<br>ATD : '.$this->jin_date_str($atd_jkt).'- ATA : '.$this->jin_date_str($ata_des);
			} else {
			return "";	
		}
	}

	function jin_date_str($date){
		$exp1 = substr ($date,0,4);
		$exp2 = substr ($date,4,2);
		$exp3 = substr ($date,6,2);
		return $exp3.'/'.$exp2.'/'.$exp1;
	}

	function get_eta($id){
		$data = $this->dbx->get_where('trnstuffingplan',array('sp_id'=>$id));	
		if($data->num_rows() > 0 ) {
			$des = $data->row()->destination;
			$eta = $data->row()->etd_jkt;
			return $des.' '.$this->jin_date_str($eta);
			} else {
			return "";	
		}
	}

	function get_volume($kode_ref){
		$data = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$kode_ref));	
		if($data->num_rows() > 0 ) {
			$meas = $data->row()->bl_meas;
			return $meas;
			} else {
			return "";	
		}
	}
	function get_party($kode_ref){
		$data = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$kode_ref));	
		if($data->num_rows() > 0 ) {
			$party = $data->row()->no_of_pack;
			return $party;
			} else {
			return "";	
		}
	}

}


