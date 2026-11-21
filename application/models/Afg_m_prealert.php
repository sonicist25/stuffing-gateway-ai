<?php
class Afg_m_prealert extends CI_Model
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

	function get_data_spd($kode_ref)
	{
		$this->dbx->select(array(
			'sp_detail_id as spd',
			'trnstuffingplandetail.kode_ref as kdr',
			'trnstuffingplandetail.num as num',
			'trnstuffingplandetail.consignee as consignee',
			'trnstuffingplandetail.shipper_print as shipper_print',
			'trnstuffingplandetail.shipper_import as shipper_import',
			'trnstuffingplandetail.notify_add as notify_add',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplandetail.agent_code as agent_code',
			'mstadmin.fullname as sales_name',
			'trnstuffingplandetail.hb_l as hb_l',
			'trnstuffingplandetail.desc_print as desc_print',
			'trnstuffingplandetail.bl_meas as bl_meas',
			'trnstuffingplandetail.weight as weight',
			'trnstuffingplan.destination as destination',
			'trnstuffingplan.carrier as carrier',
			'trnstuffingplan.warehouse as warehouse',
			'trnstuffingplan.type_cont_qty as type_cont_qty',
			'trnstuffingplan.type_cont_size as type_cont_size',
			'trnstuffingplan.cont_seal_no as cont_seal_no',
			'trnstuffingplan.kurs as kurs',
			'trnstuffingplan.etd_jkt as etd',
			'trnstuffingplan.eta as eta',
			'trnstuffingplan.vessel as vessel',
			'trnstuffingplan.voyage as voyage',
			'trnstuffingplan.region_id as region_id',
		));
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->join('mstadmin','mstadmin.user_id=trnstuffingplandetail.sales','left');
		$this->dbx->where('trnstuffingplan.kode_ref',$kode_ref);
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
		$this->dbx->order_by('trnstuffingplandetail.num','asc');
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query->result_array();
		} else {
			return null;	
		}
	}
}
