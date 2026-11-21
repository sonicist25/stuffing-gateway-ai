<?php
#[\AllowDynamicProperties] 
class afg_m_manifest extends CI_Model
{
	var $table='refcontainer';

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }
	
	function get_data($data)
	{
		$region = $this->session->userdata('region_id');
		$data["region_id"]=$region;
		$q = $this->dbx->get_where('trnstuffingplan',$data)->row();
		$q_detail = $this->dbx->get_where('trnstuffingplandetail',array('code'=>$data["kode_ref"]))->result();
		return array("header"=>$q,"detail"=>$q_detail);
	}
	
	function create_data($id) 
	{
		$this->dbx->select('
        COUNT(trnstuffingplan.vessel) AS jumlah,
        trnstuffingplan.kode_ref AS kode_ref,
        trnstuffingplandetail.sp_id AS sp_id,            
        trnstuffingplandetail.num AS num,
        trnstuffingplan.vessel AS ves,
        trnstuffingplan.tanggal_job AS tanggal_job,
        trnstuffingplan.voyage AS voyage,
        trnstuffingplan.carrier AS carrier,
        trnstuffingplan.stuffing_date AS stuffing_date,
        trnstuffingplan.closing_date_time AS closing_date_time,
        trnstuffingplan.etd_jkt AS etd_jkt,
        tbl_detail.feeder_vessel_eta_date AS feeder_vessel_eta_date,
    
        city_origin.city_id AS city_id,    
        trnstuffingplan.port_loading AS port_loading,
        city_origin.country_id AS country_id,
        city_origin.id AS origin_id,
        city_origin.country_id AS country,
	
        trnstuffingplandetail.destination AS destination,
        city_dest.city_id AS city_id2,
        city_dest.country_id AS country_id2,
        city_dest.country_id AS country2, 

        refagentcompany.agent_company_name AS agent_company_name,
        refagentcompany.agent_company_address AS agent_company_address,
        ref.agent_company_name AS agent_company_name2,
        ref.agent_company_address AS agent_company_address2,            
        trnstuffingplan.cont_seal_no AS Container,            
        trnstuffingplan.type_cont_size AS type_cont_size,
        trnstuffingplandetail.hb_l AS hb_l,
        trnstuffingplandetail.shipper_print AS shipper_print,
        mstshipper.shipper_address AS shipper_address,
        mstshipper.shipper_name AS shipper_name,
        mstshipper.cp_phone AS cp_phone,
        trnstuffingplandetail.note AS note,
        trnstuffingplandetail.consignee AS consignee,
        trnstuffingplandetail.consignee_add AS consignee_add,    
        trnstuffingplandetail.notify_add AS notify_add,
        trnstuffingplandetail.express_release AS express_release,
        trnstuffingplandetail.cetak_jml_doc_origi AS cetak_jml_doc_origi,
        trnstuffingplan.shipment_type_multi AS shipment_type_multi,
        trnstuffingplandetail.freight AS freight,
        trnstuffingplandetail.weight AS weight,
        trnstuffingplandetail.bl_meas AS bl_meas,
        trnstuffingplandetail.no_pack_qty AS qty,
        trnstuffingplandetail.no_pack_unit AS unit,
        tbl_detail.cy_cy AS cy_cy,
        tbl_detail.cfs_cfs AS cfs_cfs,
        tbl_detail.diff_status_1 AS diff_status_1,
        trnstuffingplandetail.marking_nos AS marking_nos,
        trnstuffingplandetail.no_of_pack AS no_of_pack,
        trnstuffingplandetail.desc_goods AS desc_goods,
        tbl_detail.port_of_receive AS port_of_receive,
        tbl_detail.port_of_loading AS port_of_loading,
        tbl_detail.port_of_discharge AS port_of_discharge,
        tbl_detail.port_of_delivery AS port_of_delivery,
        tbl_detail.payable AS payable,
        trnstuffingplandetail.agent_code AS agent_code,
        trnstuffingplan.region_id AS region_id,
        x_finance_item.code AS code,
        x_finance_item.item AS item,
        x_finance_item.price AS price,
        x_finance_item.pre_col AS prep_col,
        x_finance_item.bl_no AS bl_no
    ');

    $this->dbx->from('trnstuffingplandetail');
    $this->dbx->join('trnstuffingplan', 'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id', 'inner');
    
    // Join ke tableName untuk Port of Loading
    $this->dbx->join('tableName as city_origin', 'city_origin.name = trnstuffingplan.port_loading', 'left');
    
    // Join ke tableName untuk Destination
    $this->dbx->join('tableName as city_dest', 'city_dest.name = trnstuffingplandetail.destination', 'left');
    
    $this->dbx->join('refagentcompany', 'refagentcompany.agent_company_code = trnstuffingplan.agen_penagihan', 'left');
    $this->dbx->join('refagentcompany as ref', 'ref.agent_company_code = trnstuffingplandetail.agent_code', 'left');       
    $this->dbx->join('tbl_detail', 'tbl_detail.detail_id = trnstuffingplandetail.sp_detail_id', 'inner');
    $this->dbx->join('mstshipper', 'mstshipper.shipper_name = tbl_detail.shipper_name', 'left');
    $this->dbx->join('x_finance_item', 'x_finance_item.bl_no = trnstuffingplandetail.hb_l', 'left');

    $this->dbx->where('trnstuffingplan.sp_id', $id);
    
    $q = $this->dbx->get();
    return $q->result_array();
}

	function cek_num($a){
		if($a=='1'){
			$num = "A";
		}elseif($a=='2'){
			$num = "B";
		}elseif($a=='3'){
			$num = "C";
		}elseif($a=='4'){
			$num = "D";
		}elseif($a=='5'){
			$num = "E";
		}elseif($a=='6'){
			$num = "F";
		}elseif($a=='7'){
			$num = "G";
		}elseif($a=='8'){
			$num = "H";
		}elseif($a=='9'){
			$num = "I";
		}elseif($a=='10'){
			$num = "J";
		}elseif($a=='11'){
			$num = "K";
		}elseif($a=='12'){
			$num = "L";
		}elseif($a=='13'){
			$num = "M";
		}elseif($a=='14'){
			$num = "N";
		}elseif($a=='15'){
			$num = "O";
		}elseif($a=='16'){
			$num = "P";
		}elseif($a=='17'){
			$num = "Q";
		}elseif($a=='18'){
			$num = "R";
		}elseif($a=='19'){
			$num = "S";
		}elseif($a=='20'){
			$num = "T";
		}elseif($a=='21'){
			$num = "U";
		}elseif($a=='22'){
			$num = "V";
		}elseif($a=='23'){
			$num = "W";
		}elseif($a=='24'){
			$num = "X";
		}elseif($a=='25'){
			$num = "Y";
		}elseif($a=='26'){
			$num = "Z";
		}
		return $num;
	}
	
	
}




























