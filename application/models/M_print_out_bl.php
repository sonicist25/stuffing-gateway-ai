<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class M_print_out_bl extends CI_Model { 
   
   
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

	function get_caya_caya($spd) 
	{
		$q = $this->dbx->get_where('tbl_detail', array('detail_id' => $spd));
		$row = $q->row();

		if ($row->cy_cy == 'TRUE') {
			return 'CY/CY';
		}
		if ($row->cfs_cfs == 'TRUE') {
			return 'CFS/CFS';
		}
		if ($row->diff_status_1 != '' && $row->diff_status_2 != '') {
			return $row->diff_status_1 . '/' . $row->diff_status_2;
		}
		if ($row->diff_status_1 != '') {
			return $row->diff_status_1;
		}
		if ($row->diff_status_2 != '') {
			return $row->diff_status_2;
		}
	}


	function get_last_container($spd)
	{
		return $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd))->row()->last_count;
	}

	function get_data_calendar($spd)
	{
		return $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd))->row()->backup_date;
	}
	function get_data_payable($spd)
	{
		return $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd))->row()->payable;
	}


	function get_connecting($spd,$vessel)
	{	
		$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
		$data = new stdClass();
		if($q->num_rows() > 0 ) {
			if($q->row()->last_vessel != '') {
				$data->vessel = strtoupper($q->row()->last_vessel);
			}
			else 
			{
				$data->vessel = strtoupper($vessel);
			}
			if($q->row()->last_snd_vessel != '') {
				$data->conn_vessel = strtoupper($q->row()->last_snd_vessel);
			}
			else 
			{
				$data->conn_vessel = $q->row()->snd_vessel? strtoupper($q->row()->snd_vessel):"";
			}
		}
		return $data;
	}

 	function get_fcl($spd) 
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd));
		if($q->num_rows() > 0 ) {
			$code = $q->row()->code;
			$reg = $this->session->userdata('region_id');	
			return $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code, 'region_id' => $reg))->row()->shipment_type_multi;
		} else {
			return '';
		}
	}
	
	function get_as_agent($spd)
	{
		$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd));
		if($q->num_rows() > 0 ) {
			return $q->row()->as_agent;	
		} else {
			return '';
		}
	}
	
	function get_count_double($sp_id) 
	{
		return $this->dbx->get_where('count_double',array('id_sp'=>$sp_id));
	}
	
	function get_kode_ref($spd) 
	{
		$data = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd))->row();
		return $data->kode_ref;
	}

	function get_data_code($id) 
	{
		$query = $this->dbx->get_where('x_form_bl', array('id_bl'=>$id));
		if($query->num_rows() > 0 ) {
			return $query->row()->value;
		} else {
			return 1;
		}
	}

	function get_data_trucking($id) 
	{	$reg = $this->session->userdata('region_id');
		$this->dbx->where('trnstuffingplan.region_id', $reg);
		$this->dbx->select('
			trnstuffingplan.sp_id as sp_id,
			trnstuffingplan.port_loading as port_loading,
			trnstuffingplan.vessel as vessel,
			trnstuffingplan.voyage as voyage,
			trnstuffingplan.destination as destination,
			trnstuffingplan.cont_seal_no as cont_seal_no,
			trnstuffingplan.type_cont_size as type_cont_size,
			trnstuffingplan.mother_bl as mother_bl,
			trnstuffingplandetail.kode_ref as kode_ref,
			trnstuffingplandetail.agent_code as agent_code,
			trnstuffingplandetail.c_o as c_o,
			trnstuffingplandetail.shipping_mark as shipping_mark,
			trnstuffingplandetail.no_pack_qty as no_pack_qty,
			trnstuffingplandetail.no_pack_unit as no_pack_unit,
			trnstuffingplandetail.express_release as express_release,
			trnstuffingplandetail.shipped_on_board as shipped_on_board,
			trnstuffingplandetail.desc_goods as desc_goods,
			trnstuffingplandetail.weight as weight,
			trnstuffingplandetail.net_weight_tidak_dicetak as net_weight_tidak_dicetak,
			trnstuffingplandetail.wh_meas as wh_meas,
			trnstuffingplandetail.bl_meas as bl_meas,
			trnstuffingplandetail.ship_meas as ship_meas,
			trnstuffingplandetail.freight as freight,
			trnstuffingplandetail.freights_charges as freights_charges,
			trnstuffingplandetail.cetak_jml_doc_origi as cetak_jml_doc_origi,
			trnstuffingplandetail.shipper,
			trnstuffingplandetail.isN,
			trnstuffingplandetail.marking_nos,
			trnstuffingplandetail.note as note,
			trnstuffingplandetail.notify as notify,
			trnstuffingplandetail.notify_add as notify_add,
			trnstuffingplandetail.consignee as consignee,
			trnstuffingplandetail.consignee_add as consignee_add,
			trnstuffingplandetail.shipper_print as shipper_print,
			trnstuffingplandetail.hbl as hbl,
			trnstuffingplandetail.netto as netto,
			trnstuffingplandetail.booking_code as booking_code,
			trnstuffingplandetail.piece as piece,
			trnstuffingplandetail.rate_class as rate_class,
			trnstuffingplandetail.item_number as item_number,
			trnstuffingplandetail.rate_per_charges as rate_per_charges,
			trnstuffingplandetail.chgs_weight as chgs_weight,
			trnstuffingplandetail.total as total,
			trnstuffingplandetail.shipper_certification as shipper_certification,
			tbl_detail.departure as departure,
			tbl_detail.route_to as route_to,
			tbl_detail.destination as destination,
			tbl_detail.handling_information as handling_information,
			tbl_detail.acc_information_detail as acc_information_detail,
			tbl_detail.currency_declaration as currency_declaration,
			tbl_detail.chcg as chcg,
			tbl_detail.value_for_carriage as value_for_carriage,
			tbl_detail.value_for_custom as value_for_custom,
			tbl_detail.amount_of_insurance as amount_of_insurance,
			tbl_detail.wt_val as wt_val,
			tbl_detail.wt_prep_col as wt_prep_col,
			tbl_detail.other as other,
			tbl_detail.other_prep_col as other_prep_col,
			tbl_detail.feeder_vessel_etd as to_1,
			tbl_detail.snd_vessel_etd as to_2,
			tbl_detail.snd_vessel as by_2,
		');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->join('tbl_detail','tbl_detail.detail_id=trnstuffingplandetail.sp_detail_id','left');
		$query = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id' => $id));
		if($query->num_rows() > 0 ) {
			return $query->row_array();	
		}
	}

	function get_data_sp_by($code)
	{  $reg = $this->session->userdata('region_id');
	 	$query = $this->dbx->get_where('trnstuffingplan',array('kode_ref' => $code, 'region_id' => $reg));
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;	
		}		
	}

	function getConnTable($sp_id)
	{
		$q = $this->dbx->get_where('trnexportconecting',array('exp_id'=>$sp_id));
		if($q->num_rows() > 0) {
			return $q->row_array();
			} else {
			return"0";
		}
	}

	function getAgentByCode($code)	
	{
		$region = $this->session->userdata('region_id');
		if($region===NULL){
		$region = 'Jakarta';
		}
		$q = $this->dbx->get_where('refagentcompany',array('agent_company_code'=>$code,'region_id' =>$region));
		if($q->num_rows() > 0) {
			return $q->row_array();
			} else {
			return"0";
		}
	}
	
}