<?php
class M_bill_of_ladding extends CI_Model
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
	
	public function get_form_bl($spd) {
    $q = $this->dbx->get_where('x_form_bl', ['id_bl' => $spd]);
    if ($q->num_rows() > 0) {
        return $q->row()->value;
    } else {
        return 1; // default
    }
	}


	function collect_data($data) {
		$spd = $data["spd"];
		$dst = $data['destination'];
		$trans = $data['transhipment'];
		$kode_ref = $data['kode_ref'];		
		$regional = $this->session->userdata("region_id");	
		$q0= $this->dbx->get_where('refregion',array('region_name'=>$regional))->row()->region_id;
		$q1 = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd))->row();
		//print_r($this->dbx->last_query());
		//die();
		$q2 = $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd))->row();
		//print_r($this->dbx->last_query());
		//die();
		$q3 = $this->afg_m_cities->get_city_id($dst);
		$q6 = $this->afg_m_cities->get_city_id($trans);
		$q4 = substr($kode_ref,7,2);
		$q5 =  substr($kode_ref,4,3);
		if($q5=='200' || $q5=='800'){
		$bl = substr($regional,0,1).$q3."-".$q0.$q4.substr($kode_ref,11,6);
		}else if ($q5 =='101'){
		$bl = substr($regional,0,1).$q3."-".$q0.$q4.substr($kode_ref,11,4);
		}else{
		$bl =$this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd))->row()->hb_l;	
		}
		return array("stuffing"=>$q1,"detail"=>$q2,"hb_l"=>$bl,"destination"=>$q3,"transhipment"=>$q6);
	}

	function collect_data_awb($spd,$kode_ref,$destination)
	{
		$dst = $destination;	
		$regional = $this->session->userdata("region_id");
		$code = substr($kode_ref,0,15);
		$q0= $this->dbx->get_where('refregion',array('region_name'=>$regional))->row()->region_id;
		$q1 = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row();
		//print_r($this->dbx->last_query());
		//die();
		$q2 = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->si_number;
		$q3 = $this->afg_m_cities->get_city_id($dst);
		$q4 = substr($kode_ref,7,2);
		if($q5=='100' || $q5=='200' || $q5=='800'){
		$bl = substr($regional,0,1).$q3."-".$q0.$q4.substr($kode_ref,11,6);
		}else if ($q5 =='101'){
		$bl = substr($regional,0,1).$q3."-".$q0.$q4.substr($kode_ref,11,4);
		}else if ($q5=='102'){
		$bl = substr($regional,0,1).$q3."-".$q0.$q4."C".substr($kode_ref,11,6);
		}else{
		$bl =$this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd))->row()->hb_l;	
		}
		$mawb=$this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code))->row()->mother_bl;
		$shipper_name = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->shipper_name;
		$shipper_add = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->shipper_add;
		$consignee_name = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->consignee_name;
		$consignee_add = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->consignee_add;
		$departure = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->departure;
		$route_to = $this->dbx->get_where('awb',array('kode_ref'=>$kode_ref))->row()->route_to;
		//print_r($this->dbx->last_query());
		//die();
	return array(
		"awb"=>$q1,
		"awb_detail"=>$q2,
		"hb_l"=>$bl,
		"mawb"=>$mawb,
		"shipper_name"=>$shipper_name,
		"shipper_add"=>$shipper_add,
		"consignee_name"=>$consignee_name,
		"consignee_add"=>$consignee_add,
		);
	}
	
	function collect_data_draft($booking_id)
	{
			$this->dbx->select('
			booking.id,
			booking.booking_code,
			booking.type_of_booking,
			booking.movement_type,
			booking.origin_city,
			booking.destination_city,
			booking.trans_city,
			booking.forwarder_name,
			booking.freight,
			booking.shipper_id,
			booking.number_of_bl,
			booking.expect_etd,
			booking.eta,
			booking.vessel,
			booking.voyage,
			booking.pol_bl,
			booking.tp_bl,
			booking.pod_bl,
			booking.house_bl,
			booking.warehouse,
			booking.for_delivery,
			booking.payable,
			booking.shipped_on_board,
			detail.shipper_name,
			detail.note,
			detail.shipper_address,
			detail.consignee,
			detail.notify,
			detail.quantity,
			detail.package_type,
			detail.volume,
			detail.weight,
			detail.netto,
			detail.marking,
			detail.description_of_goods,
			detail.as_agent,
			detail.container_no,
		');
		$this->dbx->from('trnstuffingplan_booking AS booking');
		$this->dbx->join('trnstuffingplan_booking_detail AS detail', 'booking.booking_code = detail.booking_id', 'inner');
		$this->dbx->where('booking.booking_code', $booking_id);
		return $this->dbx->get()->result(); 

	}

	function update_agent($data)
	{
		$code = $data["agent_company_code"];
		unset($data["for_delivery"]);
		unset($data["agent_company_code"]);
		$this->dbx->update("refagentcompany",$data,array("agent_company_code"=>$code));
		return array("status"=>$this->dbx->affected_rows());
	}
	function update_agent_draft($data)
	{
		$this->dbx->update("trnstuffingplan_booking",array(
					"for_delivery"=>$data["for_delivery"],
				),array("booking_code"=>$data['rowid']));
		return array("status"=>$this->dbx->affected_rows());
	}

function submit_moving($data)
{
    require_once APPPATH . 'libraries/Booking_job_sync.php';
    $region = $this->session->userdata('region_id');
    $old = trim((string)($data['kode_ref'] ?? ''));
    $code = trim((string)($data['code'] ?? ''));
    $spd = (int)($data['id'] ?? 0);
    if (!$spd || $old === '' || $code === '' || !$region) return ['status'=>0,'message'=>'Parameter moving tidak valid.'];
    $tx = new Booking_job_sync($this->dbx);
    try {
        $q = $this->dbx->get_where('trnstuffingplan',['kode_ref'=>$code,'region_id'=>$region]);
        if (!$q || $q->num_rows() !== 1) throw new DomainException('Stuffing plan tujuan tidak ditemukan atau kode ref duplikat.');
        return $tx->move($spd,(int)$q->row()->sp_id,$region,$this->session->userdata('fullname'),$old,strtoupper(trim((string)($data['num'] ?? ''))),$code);
    } catch (Throwable $e) {
        log_message('error','[BL_MOVE] '.$old.' stage='.$tx->stage.' error='.$e->getMessage());
        return ['status'=>0,'message'=>$e->getMessage(),'kode_ref_awal'=>$old,'kode_ref_akhir'=>$code];
    }
}


	function move_job($data)
	{
		$this->dbx->order_by('num','asc');
		//$this->dbx->limit(1);
		$this->dbx->select("num,code,sp_detail_id");
		$q = $this->dbx->get_where("trnstuffingplandetail",array(
			"code"=>$data["code"],
		));
		return $q;
	}

	function pindah_job($data)
	{
		$prev = $this->fungsi->get_prev_month(date("Ym"));
		$this->dbx->select("kode_ref");
		$this->dbx->order_by("kode_ref","desc");
		$q = $this->dbx->get_where("trnstuffingplan",array(
			"kode_ref LIKE"=>substr($data["kode_ref"], 0, 7)."%",
			//"closing_date_time LIKE"=>date("Ym")."%",
			//"closing_date_time LIKE"=>$prev."%",
			"region_id"=>$this->session->userdata("region_id"),
		));
		return $q;
	}

	function get_port_of($spd)
	{
		$this->dbx->select('port_of_loading,port_of_discharge,port_of_receive,port_of_delivery');
		return $this->dbx->get_where('tbl_detail',array('detail_id'=>$spd))->row();
	}

	function set_value_img($id,$value) 
	{	
		$query = $this->dbx->get_where('x_form_bl', array('id_bl'=>$id));
		if($query->num_rows() > 0 ) {
			$this->dbx->where('id_bl',$id);
			$this->dbx->update('x_form_bl', array('value'=>$value));	
		} else {
			$this->dbx->insert('x_form_bl', array('id_bl'=>$id,'value'=>$value));	
		}
		return $this->dbx->affected_rows();
	}
	
	function move_discription($data) 
	{
		$this->dbx->select(array('sp_detail_id','desc_goods','desc_attc'));
		$q1 = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$data['sp_detail_id']))->row();
		if($q1 && $data['oper']=='0') {
			$this->dbx->where('sp_detail_id',$data['sp_detail_id']);
			$this->dbx->update('trnstuffingplandetail',array('desc_attc'=>'1'));
		} else {
			$this->dbx->where('sp_detail_id',$data['sp_detail_id']);
			$this->dbx->update('trnstuffingplandetail',array('desc_attc'=>''));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$data['oper']);
	}
	
	function move_marks($data) 
	{
		$this->dbx->select(array('sp_detail_id','c_o','marks_attc'));
		$q1 = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$data['sp_detail_id']))->row();
		if($q1 && $data['oper']=='0') {
			$this->dbx->where('sp_detail_id',$data['sp_detail_id']);
			$this->dbx->update('trnstuffingplandetail',array('marks_attc'=>'1'));
		} else {
			$this->dbx->where('sp_detail_id',$data['sp_detail_id']);
			$this->dbx->update('trnstuffingplandetail',array('marks_attc'=>''));
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$data['oper']);
	}
	
	function check_attc_discription($id)
	{
		$this->dbx->select(array('sp_detail_id','desc_goods'));
		$str = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id))->row()->desc_goods;
		$count = count(explode("\n", $str));
		return $count;
	}
	
	function check_attc_mark($id)
	{
		$this->dbx->select(array('sp_detail_id','marking_nos'));
		$str = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id))->row()->marking_nos;
		$count = count(explode("\n", $str));
		return $count;
	}
	
	function get_sp_spd_by($kode_ref)
	{
		$this->dbx->select('*');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->where('trnstuffingplandetail.kode_ref',$kode_ref);
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query->row_array();
		} else {
			return null;	
		}
	}

	function get_code_port_loading($city)
	{
		$query = $this->dbx->get_where('refportloading',array('portloading_city' => $city));
		if($query->num_rows() > 0 ) {
			return $query->row_array();
		} else {
			return "";	
		}
	}

	function get_data($kode_ref)
	{
		$query = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref' => $kode_ref));
		return $query->row_array();
	}

	function get_bl_data($id)
	{
	//	$query = $this->dbx->get_where('x_bill_form',array('id_form'=>$id));
		$query = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id));
		if($query->num_rows() > 0) {
			return "1";
		} else {
			return "0";
		}
	}
	
	function get_data_agent($id)
	{
		$this->dbx->select('*');
		$this->dbx->from('refagentcompany');
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.agent_code=refagentcompany.agent_company_code');
		$this->dbx->where('trnstuffingplandetail.sp_detail_id',$id);
		return $this->dbx->get();
	}
	
	function update_data_agent($id,$data)
	{
		$this->dbx->where('id_form',$id);
		$this->dbx->update('x_bill_form',$data);
		if($this->dbx->affected_rows() > 0) {
			return "1";
		} else {
			return "0";
		}
	}
	
	function add_bl_form($id)
	{
		$this->dbx->insert('x_bill_form',$id);
		if($this->dbx->affected_rows() > 0) {
			return "1";
		} else {
			return "0";
		}
	}
	
	function update_bl_form($data,$id)
	{
		$this->dbx->where('id_form',$id);
		$this->dbx->update('x_bill_form',$data);
		if($this->dbx->affected_rows() > 0) {
			return "1";
		} else {
			return "0";
		}
	}
	
	function delete_bl_form($id)
	{
		$this->dbx->where('id_form',$id);
		$this->dbx->delete('x_bill_form');
		if($this->dbx->affected_rows() > 0) {
			return "1";
		} else {
			return "0";
		}
	}
	
	function get_form_by($bl_no)
	{
	 	$query = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref' => $bl_no));
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;	
		}		
	}
	
	function get_data_sp_by($code)
	{
	 	$query = $this->dbx->get_where('trnstuffingplan',array('kode_ref' => $code));
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;	
		}		
	}
	
	function get_user_by_id($user_id)
	{
		$query = $this->dbx->get_where('mstshipper',array('shipper_id' => $user_id));	
	 	if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;	
		}		
	}
	
	function get_export_shipper($sp_detail_id)
	{
		$this->dbx->select('shipper');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where('sp_detail_id', $sp_detail_id);
		$query = $this->dbx->get();

		if ($query->num_rows() > 0) {
			return $query->row()->shipper; // string
		}
		return ''; // fallback kalau tidak ada data
	}




	
}