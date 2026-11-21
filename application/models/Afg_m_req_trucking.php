<?php
class Afg_m_req_trucking extends CI_Model
{	
		private $second_db;
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

   function find($code) 
   {
		$region = $this->session->userdata('region_id');
		$data = null;
		$filter = array('no_job'=>$code);
		$headerTruck = [];

		$queryexp = $this->dbx->get_where('header_track_truck', array('kode_ref' => $code));
		if($queryexp !== FALSE && $queryexp->num_rows() > 0) {
			$headerTruck = $queryexp->result()[0];
		}

		$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.stuffing_date as eta',
				'trnstuffingplan.shipment_type_multi as shipment_type_multi',
				'trnstuffingplandetail.kode_ref as no_job',
				'trnstuffingplandetail.shipper as id_shipper',
				'trnstuffingplandetail.shipment_request_id as id_request',
				'trnstuffingplan.agen_penagihan as id_agent',
				'trnstuffingplan.mother_bl as mbl_no',
				'trnstuffingplan.transhipment_port as connecting',
				'trnstuffingplandetail.hb_l as hbl_no',
				'trnstuffingplandetail.destination as destination',
				'CONCAT(trnstuffingplan.vessel, " ", trnstuffingplan.voyage) as feeder_name',
				'trnstuffingplan.etd_jkt as etd',
				'trnstuffingplandetail.shipper_print as shipper',
				'trnstuffingplandetail.consignee_add as consignee',
				'trnstuffingplandetail.consignee as shipper_import',
				'trnstuffingplan.cont_seal_no as container_no',
				'trnstuffingplandetail.sp_detail_id as sp_detail_id',	
				'trnstuffingplandetail.no_of_pack as packaging',	
				'trnstuffingplan.prepared as prepared',
				'trnstuffingplandetail.no_pack_qty as qty',
				'trnstuffingplan.warehouse',
				'trnstuffingplandetail.shipping_mark',
				'trnstuffingplandetail.bl_meas',
			)
		);
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where('trnstuffingplan.region_id',$region);
		$this->dbx->where('trnstuffingplandetail.kode_ref', $code);
		$this->dbx->order_by('trnstuffingplandetail.num','asc');
		$stuffingPlan = $this->dbx->get('trnstuffingplandetail')->result()[0];


		return array('plan' => $stuffingPlan, 'header' => $headerTruck);
	 }
	 
	 function insert_or_update_header_truck($data){
			$region = $this->session->userdata('region_id');
			$booking = $this->find($data['no_job'])['plan'];
			
			$this->dbx->select('*');
			$this->dbx->from('refwarehouse');
			$this->dbx->where('warehouse_code', $booking->warehouse);
			$this->dbx->where('region_id', $region);
			$query = $this->dbx->get();
			$wh = $query->row();
			
			
			$potongCode = substr($data['no_job'],4,3);
			if ($potongCode == '300' || $potongCode == '301' || $potongCode == '302' || $potongCode == '400' || $potongCode == '402' || $potongCode =='600'){
				$modeTrans = "IMPORT";
			}else{
				$modeTrans = "EXPORT";
			}
			
			$trackingData = array
			(
				'kode_ref' => $data['no_job'],
				'pickup_date'=>$data['pickup_date'],
				'type_trans'=>$booking->shipment_type_multi,
				'id_shipper'=>$booking->id_shipper,
				'nama_shipper'=>$booking->shipper,
				'package'=>$booking->packaging,
				'quantity'=> $booking->qty,
				'pic_document'=> $this->session->userdata('fullname'),
				'mode_transaction'=>$modeTrans,
				'alamat_pickup' => $data['addrPickup'],
				'warehouse'=> $data['addrDrop'],
				'origin_position'=> '-6.1023604,106.9846541',
				'middle_position_1'=> $data['posPickup'],
				'middle_position_2'=> $data['posDrop'],
				'remark'=> $data['remark'],
				'status'=> 'Initiated',
				'destination'=>$booking->destination,
				'dimention'=>$data['dimention'],
			);

			if($data['header_id'] != ''){
				$trackingData['updated_at'] = date("Y-m-d H:i:s");

				$this->dbx->where("id", $data['header_id']);
				$this->dbx->update('header_track_truck',$trackingData);
			}else{
				$trackingData['created_at'] = date("Y-m-d H:i:s");
				$trackingData['updated_at'] = date("Y-m-d H:i:s");

				$this->dbx->insert('header_track_truck',$trackingData);
			}
			
			return array('status'=>$this->dbx->affected_rows());
	 }
   
   	function add_export($data){
		$q = $this->dbx->get_where('header_track_truk',array('kode_ref'=>$data['kode_ref']));
		if($q !== FALSE && $q->num_rows() > 0) {
			$this->dbx->where('kode_ref',$data['kode_ref']);
			$this->dbx->update('header_track_truk',$data);
		} else {
			$this->dbx->insert('header_track_truk',$data);
			//print_r($data);
			die;
		}
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
   
  }
  ?>