<?php
#[\AllowDynamicProperties]  
Class M_api extends CI_Model
{
	 public function __construct() {
        parent::__construct();

        $tahun ='2026';

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
			$this->db2 = $this->load->database('second', TRUE);
			$second_db = $this->load->database('reference', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
			$this->db2 = $this->load->database('second', TRUE);
			$second_db = $this->load->database('reference', TRUE);
        }
    }
	
   public function create_quotation($data,$data_detail)
   {
      //$this->dbx->where('booking_code',$data['booking_code']);
	  //print_r($data);
	  //die();
	  $this->dbx->trans_start();
	  $insert = $this->dbx->insert('trnstuffingplan_booking', $data);
	  //print_r($this->dbx->last_query());
	  //die();
	  //$this->dbx->where('booking_id',$data['booking_code']);
      $insert2 = $this->dbx->insert('trnstuffingplan_booking_detail', $data_detail);
	  $this->dbx->trans_complete();
      if ($this->dbx->trans_status() === TRUE) {
         $response = array();
		 $response['status']=201;
         $response['error'] = false;
         $response['message'] = 'Successfully added quotation';
         return $response;
      } else {
             $response = array();
		 $response['status']=201;
         $response['error'] = false;
         $response['message'] = 'Successfully added quotation';
         return $response;
        }
      return false;
   }
 
   public function read_product($id=null,$code=null)
   {
      if($id === null){
	  $query = $this->dbx->get('trnstuffingplan_booking');
	  }else{
	  $query = $this->dbx->get_where('trnstuffingplan_booking',['booking_code'=>$code]);
	  //print_r($this->dbx->last_query());
	 // die();
	  $query_detail = $this->dbx->get_where('trnstuffingplan_booking_detail',['booking_id'=>$code]);
	  //print_r($this->dbx->last_query());
	  //die();
	  $query_finance = $this->dbx->get_where('x_finance_booking_detail',['booking_id'=>$id])->result_array();
	  $finances = [];
	  //print_r($this->dbx->last_query());
	  //die();
	  }
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved product data';
         foreach ($query->result() as $row) {
			$books = array();
            $tempArray = array();
            $tempArray['type_of_booking'] = $row->type_of_booking;
            $tempArray['booking_code'] = $row->booking_code;
            $tempArray['mode'] = $row->mode;
            $tempArray['movement_type'] = $row->movement_type;
            $tempArray['incoterm'] = $row->incoterm;
            $tempArray['origin_country'] = $row->origin_country;
            $tempArray['origin_city'] = $row->origin_city;
            $tempArray['destination_country'] = $row->destination_country;
            $tempArray['destination_city'] = $row->destination_city;
            $tempArray['trans_city'] = $row->trans_city;
            $tempArray['insurance'] = $row->insurance;
            $tempArray['trucking'] = $row->trucking;
            $tempArray['freight'] = $row->freight;
            $tempArray['peb'] = $row->peb;
            $tempArray['clearance'] = $row->clearance;
            $tempArray['cargo_type'] = $row->cargo_type;
            $tempArray['shipper_id'] = $row->shipper_id;
            $tempArray['consignee_id'] = $row->consignee_id;
            $tempArray['notify_id'] = $row->notify_id;
            $tempArray['number_of_bl'] = $row->number_of_bl;
            $tempArray['status'] = $row->status;
            $tempArray['total'] = $row->total;
            $tempArray['pickup_datetime'] = $row->pickup_datetime;
            $tempArray['expect_etd'] = $row->expect_etd;
            $tempArray['closing_date'] = $row->closing_date;
            $tempArray['vessel'] = $row->vessel;
            $tempArray['voyage'] = $row->voyage;
            $tempArray['eta'] = $row->eta;
            $tempArray['pic_document'] = $row->pic_document;
            $tempArray['warehouse'] = $row->warehouse;
            $tempArray['remark'] = $row->remark;
            $tempArray['valid_at'] = $row->valid_at;
            $tempArray['closing_date'] = $row->closing_date;
            $tempArray['file_msds'] = $row->file_msds;
            $tempArray['file_invoice'] = $row->file_invoice;
            $tempArray['file_packing_list'] = $row->file_packing_list;
            $tempArray['file_bl'] = $row->file_bl;
            $tempArray['file_co'] = $row->file_co;
            $tempArray['file_other'] = $row->file_other;
            $tempArray['pickup_location'] = $row->pickup_location;
            $tempArray['pickup_destination'] = $row->drop_location;
            //$response['data'][] = $tempArray;
			array_push($books,$tempArray);	
         }
		if ($query_detail->num_rows() > 0) { 
		foreach ($query_detail->result() as $res) {
		$detail = array();
		$details = array();
		$detail['shipper_name'] = $res->shipper_name;
		$detail['shipper_address'] = $res->shipper_address;
		$detail['consignee'] = $res->consignee;
		$detail['notify'] = $res->notify;
		$detail['package_type'] = $res->package_type;
		$detail['quantity'] = $res->quantity;
		$detail['dimension'] = $res->dimension;
		$detail['volume'] = $res->volume;
		$detail['weight'] = $res->weight;
		$detail['netto'] = $res->netto;
		$detail['container_type'] = $res->container_type;
		$detail['shipping_mark'] = $res->shipping_mark;
		$detail['marking'] = $res->marking;
		$detail['description_print'] = $res->description_print;
		$detail['description_of_goods'] = $res->description_of_goods;
		$detail['created_at'] = $res->created_at;
		array_push($details,$detail);
			}
		}else{
		$details = array();
		}
		foreach ($query_finance as $fin) {
		$finance['of'] = $fin['of_emkl'];
		$finance['item'] = $fin['item'];
		$finance['quantity'] = $fin['qty'];
		$finance['currency'] = $fin['currency'];
		$finance['unit_price'] = $fin['unit_price'];
		$finance['price'] = $fin['price'];
		$finance['term'] = $fin['kondisi'];
		$finance['created_at'] = $fin['created_at'];
		array_push($finances,$finance);
		}
		$body = [
		
			"books" => $books,
			"details" => $details,
			"finances" => $finances
		];
		$response['data'][] = $body;
      return $response;
      } 
      return false;
   }
 
   public function update_quotation($data,$data_detail)
   {
      if($data['booking_code'] == ''){
		return $this->empty_response();
	 }else{
      $where = array(
       "booking_code"=>$data['booking_code']);
	  $this->dbx->where($where);
	  $update = $this->dbx->update('trnstuffingplan_booking', $data);
	  // print_r($this->dbx->last_query());
	  // die();
	  $this->dbx->where(array("booking_id"=>$data['booking_code']));
	  $update1 = $this->dbx->update('trnstuffingplan_booking_detail', $data_detail);
	  
      if ($update && $update1) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully changed quotation';
         return $response;
	  }
      return false;
	 }
   }
 
   public function delete_quotation($id)
   {
		$this->dbx->where('booking_code', $id);
        $delete = $this->dbx->delete('trnstuffingplan_booking');
		$this->dbx->where('booking_id', $id);
        $delete2 = $this->dbx->delete('trnstuffingplan_booking_detail');
		if ( $this->dbx->affected_rows() > 0 ) {
         $response = array();
		 $response['status']=400;
         $response['error'] = false;
         $response['message'] = 'Successfully deleted product data';
         return $response;
       } else {
           return $this->failed_response();
        }
   }
   
    public function empty_response(){
    $response['status']=502;
    $response['error']=true;
    $response['message']='Kode Booking tidak boleh kosong';
    return $response;
	}
	public function failed_response(){
    $response['status']=400;
    $response['error']=true;
    $response['message']='Unsuccessful';
    return $response;
	}
	public function not_found_response(){
    $response['status']=404;
    $response['error']=true;
    $response['message']='Data tidak ditemukan';
    return $response;
	}
	public function conflict(){
    $response['status']=409;
    $response['error']=true;
    $response['message']='Data already exist';
    return $response;
	}
public function read_awb($id=null)
   {
      if($id === null){
	  $this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
	  $this->dbx->join("trnstuffingplan","trnstuffingplan.sp_id=trnstuffingplandetail.sp_id","left");
	  $query = $this->dbx->get('trnstuffingplandetail');
	  }else{
	  $this->dbx->select(
	 'tbl_detail.shipper_name as shipper_name,
	  tbl_detail.shipper_address as shipper_address,
	  tbl_detail.consignee as consignee,
	  tbl_detail.consignee_add as consignee_add,
	  tbl_detail.departure as departure,
	  tbl_detail.route_to as route_to,
	  tbl_detail.feeder_vessel as by_first_carrier,
	  tbl_detail.feeder_vessel_etd as to_1,
	  tbl_detail.feeder_vessel_eta as by_1,
	  tbl_detail.snd_vessel_etd as to_2,
	  tbl_detail.snd_vessel_eta as by_2,
	  tbl_detail.destination as destination,
	  tbl_detail.feeder_vessel_etd_date as flight_date_1,
	  tbl_detail.feeder_vessel_eta_date as flight_date_2,
	  tbl_detail.handling_information as handling_information,
	  tbl_detail.sci as sci,
	  tbl_detail.charge_weight_prep as charge_weight_prep,
	  tbl_detail.charge_weight_col as charge_weight_col,
	  tbl_detail.valuation_charge_prep as valuation_charge_prep,
	  tbl_detail.valuation_charge_col as valuation_charge_col,
	  tbl_detail.tax_pre as tax_pre,
	  tbl_detail.tax_col as tax_col,
	  tbl_detail.other_charge_due_agent_pre as other_charge_due_agent_pre,
	  tbl_detail.other_charge_due_agent_col as other_charge_due_agent_col,
	  tbl_detail.other_charge_due_carrier_pre as other_charge_due_carrier_pre,
	  tbl_detail.other_charge_due_carrier_col as other_charge_due_carrier_col,
	  tbl_detail.total_prep as total_prep,
	  tbl_detail.total_col as total_col,
	  tbl_detail.currency_conve_rates as currency_conve_rates,
	  tbl_detail.cc_charges_destination as cc_charges_destination,
	  tbl_detail.charges_at_des as charges_at_des,
	  tbl_detail.total_collect as total_collect,
	  tbl_detail.acc_information_detail as acc_information_detail,
	  tbl_detail.currency_declaration as currency_declaration,
	  tbl_detail.chcg as chcg,
	  tbl_detail.value_for_carriage as value_for_carriage,
	  tbl_detail.value_for_custom as value_for_custom,
	  tbl_detail.amount_of_insurance as amount_of_insurance,
	  tbl_detail.wt_val as wt_val,
	  tbl_detail.other_prep_col as other_prep_col,
	  tbl_detail.other_charges_detail as other_charges_detail,
	  tbl_detail.other_charges_amount as other_charges_amount,
	  tbl_detail.other_charges_entitlement as other_charges_entitlement,
	  tbl_detail.shipper_certification as shipper_certification,
	  tbl_detail.shipper_signatures as shipper_signatures,
	  tbl_detail.carrier_execution as carrier_execution,
	  tbl_detail.date as date,
	  tbl_detail.place as place,
	  tbl_detail.signature as signature,
	  tbl_detail.booking_code as booking_code,
	  tbl_detail.region_id as region_id,
	  trnstuffingplandetail.piece as piece,
	  trnstuffingplandetail.weight as gross_weight,
	  trnstuffingplandetail.rate_class as rate_class,
	  trnstuffingplandetail.item_number as item_number,
	  trnstuffingplandetail.chgs_weight as charges_weight,
	  trnstuffingplandetail.rate_per_charges as rate_per_charges,
	  trnstuffingplandetail.total as total,
	  trnstuffingplandetail.marking_nos as marking_nos,
	  trnstuffingplandetail.desc_goods as nature_and_quantity,
	  trnstuffingplandetail.lenght_1 as lenght_1,
	  trnstuffingplandetail.width_1 as width_1,
	  trnstuffingplandetail.height_1 as height_1,
	  trnstuffingplandetail.pieces_1 as pieces_1,
	  trnstuffingplandetail.weight_1 as weight_1,
	  trnstuffingplandetail.lenght_2 as lenght_2,
	  trnstuffingplandetail.width_2 as width_2,
	  trnstuffingplandetail.height_2 as height_2,
	  trnstuffingplandetail.pieces_2 as pieces_2,
	  trnstuffingplandetail.weight_2 as weight_2,
	  trnstuffingplandetail.lenght_3 as lenght_3,
	  trnstuffingplandetail.width_3 as width_3,
	  trnstuffingplandetail.height_3 as height_3,
	  trnstuffingplandetail.pieces_3 as pieces_3,
	  trnstuffingplandetail.weight_3 as weight_3,
	  trnstuffingplandetail.lenght_4 as lenght_4,
	  trnstuffingplandetail.width_4 as width_4,
	  trnstuffingplandetail.height_4 as height_4,
	  trnstuffingplandetail.pieces_4 as pieces_4,
	  trnstuffingplandetail.weight_4 as weight_4,
	  trnstuffingplandetail.hb_l as hawb_num,
	  trnstuffingplandetail.kode_ref as kode_ref,
	  trnstuffingplan.mother_bl as awb_num,
	  ');
	  $this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
	  $this->dbx->join("trnstuffingplan","trnstuffingplan.sp_id=trnstuffingplandetail.sp_id","left");
	  $query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.booking_code'=>$id));
	  //print_r($this->dbx->last_query());
	  //die();
	  }
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved product data';
         foreach ($query->result() as $row) {
            $tempArray = array();
            $tempArray['kode_ref'] = $row->kode_ref;
            $tempArray['booking_code'] = $row->booking_code;
            $tempArray['shipper_name'] = $row->shipper_name;
            $tempArray['shipper_add'] = $row->shipper_address;
            $tempArray['consignee'] = $row->consignee;
            $tempArray['consignee_add'] = $row->consignee_add;
            $tempArray['departure'] = $row->departure;
            $tempArray['route_to'] = $row->route_to;
            $tempArray['by_first_carrier'] = $row->by_first_carrier;
            $tempArray['to_1'] = $row->to_1;
            $tempArray['by_1'] = $row->by_1;
			$tempArray['to_2'] = $row->to_2;
            $tempArray['by_2'] = $row->by_2;
            $tempArray['destination'] = $row->destination;
            $tempArray['flight_date_1'] = $row->flight_date_1;
            $tempArray['flight_date_2'] = $row->flight_date_2;
            $tempArray['handling_information'] = $row->handling_information;
            $tempArray['sci'] = $row->sci;
            $tempArray['charge_weight_prep'] = $row->charge_weight_prep;
            $tempArray['charge_weight_col'] = $row->charge_weight_col;
            $tempArray['valuation_charge_prep'] = $row->valuation_charge_prep;
            $tempArray['valuation_charge_col'] = $row->valuation_charge_col;
            $tempArray['tax_pre'] = $row->tax_pre;
            $tempArray['tax_col'] = $row->tax_col;
            $tempArray['other_charge_due_agent_pre'] = $row->other_charge_due_agent_pre;
            $tempArray['other_charge_due_agent_col'] = $row->other_charge_due_agent_col;
            $tempArray['other_charge_due_carrier_pre'] = $row->other_charge_due_carrier_pre;
            $tempArray['other_charge_due_carrier_col'] = $row->other_charge_due_carrier_col;
            $tempArray['total_prep'] = $row->total_prep;
            $tempArray['total_col'] = $row->total_col;
            $tempArray['currency_conve_rates'] = $row->currency_conve_rates;
            $tempArray['cc_charges_destination'] = $row->cc_charges_destination;
            $tempArray['charges_at_des'] = $row->charges_at_des;
            $tempArray['total_collect'] = $row->total_collect;
            $tempArray['awb_num'] = $row->awb_num;
            $tempArray['hawb_num'] = $row->hawb_num;
            $tempArray['acc_information_detail'] = $row->acc_information_detail;
            $tempArray['currency_declaration'] = $row->currency_declaration;
            $tempArray['chcg'] = $row->chcg;
            $tempArray['value_for_carriage'] = $row->value_for_carriage;
            $tempArray['value_for_custom'] = $row->value_for_custom;
            $tempArray['amount_of_insurance'] = $row->amount_of_insurance;
            $tempArray['wt_val'] = $row->wt_val;
            $tempArray['other_prep_col'] = $row->other_prep_col;
            $tempArray['other_charges_detail'] = $row->other_charges_detail;
            $tempArray['other_charges_amount'] = $row->other_charges_amount;
            $tempArray['other_charges_entitlement'] = $row->other_charges_entitlement;
            $tempArray['shipper_certification'] = $row->shipper_certification;
            $tempArray['shipper_signatures'] = $row->shipper_signatures;
            $tempArray['carrier_execution'] = $row->carrier_execution;
            $tempArray['date'] = $row->date;
            $tempArray['place'] = $row->place;
            $tempArray['signature'] = $row->signature;
            $tempArray['booking_code'] = $row->booking_code;
            $tempArray['piece'] = $row->piece;
            $tempArray['gross_weight'] = $row->gross_weight;
            $tempArray['rate_class'] = $row->rate_class;
            $tempArray['item_number'] = $row->item_number;
            $tempArray['charges_weight'] = $row->charges_weight;
            $tempArray['rate_per_charges'] = $row->rate_per_charges;
            $tempArray['total'] = $row->total;
            $tempArray['marking_nos'] = $row->marking_nos;
            $tempArray['nature_and_quantity'] = $row->nature_and_quantity;
            $tempArray['lenght_1'] = $row->lenght_1;
            $tempArray['width_1'] = $row->width_1;
            $tempArray['height_1'] = $row->height_1;
            $tempArray['pieces_1'] = $row->pieces_1;
            $tempArray['weight_1'] = $row->weight_1;
			$tempArray['lenght_2'] = $row->lenght_2;
            $tempArray['width_2'] = $row->width_2;
            $tempArray['height_2'] = $row->height_2;
            $tempArray['pieces_2'] = $row->pieces_2;
            $tempArray['weight_2'] = $row->weight_2;
			$tempArray['lenght_3'] = $row->lenght_3;
            $tempArray['width_3'] = $row->width_3;
            $tempArray['height_3'] = $row->height_3;
            $tempArray['pieces_3'] = $row->pieces_3;
            $tempArray['weight_3'] = $row->weight_3;
			$tempArray['lenght_4'] = $row->lenght_4;
            $tempArray['width_4'] = $row->width_4;
            $tempArray['height_4'] = $row->height_4;
            $tempArray['pieces_4'] = $row->pieces_4;
            $tempArray['weight_4'] = $row->weight_4;
            $response['data'][] = $tempArray;
         }
         return $response;
      } else {
           return $this->not_found_response();
      }
      return false;
   }
      public function create_booking_airfreight($data,$data_detail)
   {
      $this->dbx->where('booking_code',$data['booking_code']);
	  $insert = $this->dbx->update('trnstuffingplan_booking', $data);
	  //print_r($this->dbx->last_query());
	  //die();
	  $this->dbx->where('booking_id',$data['booking_code']);
      $insert2 = $this->dbx->update('trnstuffingplan_booking_detail', $data_detail);
	  //print_r($this->dbx->last_query());
	  //die();
      if ($insert2 && $insert) {
         $response = array();
		 $response['status']=201;
         $response['error'] = false;
         $response['message'] = 'Successfully added shipping intruction';
         return $response;
      } else {
           return $this->failed_response();
        }
      return false;
   }
	public function update_awb($data,$data_detail,$booking_code)
   {
      if($booking_code == ''){
		return $this->empty_response();
	 }else{
      $where = array("booking_code"=>$booking_code);
	  $this->dbx->where($where);
	  $update = $this->dbx->update('tbl_detail', $data);
	  //print_r($this->dbx->last_query());
	  //die();
	  $this->dbx->where($where);
	  $update1 = $this->dbx->update('trnstuffingplandetail', $data_detail);
	  
      if ($update && $update1) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully changed document';
         return $response;
	  }
      return false;
	 }
   }
public function read_bl($id=null)
   {
      if($id == null){
	  $this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
	  $this->dbx->join("trnstuffingplan","trnstuffingplan.sp_id=trnstuffingplandetail.sp_id","left");
	  $query = $this->dbx->get('trnstuffingplandetail');
	  }else{
	  $this->dbx->select(
	  'trnstuffingplandetail.kode_ref as kode_ref,
	  trnstuffingplandetail.booking_code as booking_code,
	  trnstuffingplan.kode_ref as code_ref,
	  trnstuffingplan.sp_id as sp_id,
	  trnstuffingplan.etd_jkt as etd,
	  trnstuffingplan.mother_bl as mother_bl,
	  trnstuffingplan.vessel as vessel,
	  trnstuffingplan.voyage as voyage,
	  trnstuffingplan.cont_seal_no as cont_seal_no,
	  trnstuffingplandetail.sp_detail_id as sp_detail_id,
	  trnstuffingplandetail.hb_l as hb_l,
	  trnstuffingplandetail.shipper_print as shipper_print,
	  trnstuffingplandetail.note as note,
	  trnstuffingplandetail.consignee_add as consignee_add,
	  trnstuffingplandetail.notify_add as notify_add,
	  trnstuffingplandetail.agent_code as agent_code,
	  trnstuffingplandetail.destination as destination,
	  trnstuffingplandetail.marking_nos as marking_nos,
	  trnstuffingplandetail.desc_goods as desc_goods,
	  trnstuffingplandetail.shipped_on_board as shipped_on_board,
	  trnstuffingplandetail.no_pack_qty as no_pack_qty,
	  trnstuffingplandetail.no_pack_unit as no_pack_unit,
	  trnstuffingplandetail.weight as weight,
	  trnstuffingplandetail.netto as netto,
	  trnstuffingplandetail.bl_meas as bl_meas,
	  trnstuffingplandetail.freight as freight,
	  trnstuffingplandetail.express_release as express_release,
	  tbl_detail.as_agent as as_agent,
	  tbl_detail.backup_date as back_date
	  ');
	  $this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
	  $this->dbx->join("trnstuffingplan","trnstuffingplan.sp_id=trnstuffingplandetail.sp_id","left");
	  $query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.booking_code'=>$id));
	  //print_r($this->dbx->last_query());
	  //die();
	  }
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved product data';
         foreach ($query->result() as $row) {
			$connecting = $this->m_print_out_bl->get_connecting($row->sp_detail_id,$row->vessel.' '.$row->voyage);
			if(isset($connecting)) {
					$vessel = $connecting->vessel;
					$voyage = strtoupper($connecting->conn_vessel);
				}
			$port_of = $this->m_bill_of_ladding->get_port_of($row->sp_detail_id);
			$agent = $this->m_print_out_bl->getAgentByCode($row->agent_code);
			$agent_print = (isset($agent['agent_company_name']))? $agent['agent_company_name']. htmlentities("\n").$agent['agent_company_address'] : "";
			if($row->express_release== TRUE)
			{$number_of_bl = 'EXPRESS RELEASE';}
			else if($row->cetak_jml_doc_origi== TRUE)
			{$number_of_bl = '3(THREE) ORIGINAL';}
			$last_container = $this->m_print_out_bl->get_last_container($row->sp_detail_id);
			$count = $this->m_print_out_bl->get_count_double($row->sp_id);
				if($count->num_rows() > 0 ) {
					foreach($count->result() as $row1 ) {
					$container[] = strtoupper($row1->count_no).' / '. strtoupper($row1->seal_no).' / '. strtoupper($row1->count_size);
					}
				}
		  		else if ($last_container != '')
		  		{  
		  		$container = $last_container;
				}
				else 
				{
				$container = $row->cont_seal_no;
				}
            $tempArray = array();
            $tempArray['kode_ref'] = $row->kode_ref;
            $tempArray['hbl'] = $row->hb_l;
            $tempArray['mbl'] = $row->mother_bl;
            $tempArray['shipper_name'] = $row->shipper_print;
            $tempArray['shipper'] = $row->note;
            $tempArray['consignee'] = $row->consignee_add;
            $tempArray['notify'] = $row->notify_add;
            $tempArray['agent'] = $agent_print;
            $tempArray['destination'] = $row->destination;
            $tempArray['port_of_receipt'] = $port_of->port_of_receive;
            $tempArray['port_of_loading'] = $port_of->port_of_loading;
            $tempArray['port_of_delivery'] = $port_of->port_of_discharge;
            $tempArray['port_of_discharge'] = $port_of->port_of_delivery;
            $tempArray['vessel'] = $vessel;
            $tempArray['voyage'] = $voyage;
            $tempArray['marking'] = $row->marking_nos;
            $tempArray['desc_of_goods'] = $row->desc_goods;
            $tempArray['number_of_bl'] = $number_of_bl;
            $tempArray['container'] = $container;
            $tempArray['shipped_on_board'] = $row->shipped_on_board;
			$tempArray['qty'] = $row->no_pack_qty;
			$tempArray['packaging'] = $row->no_pack_unit;
            $tempArray['gross_weight'] = $row->weight;
            $tempArray['nett_weight'] = $row->netto;
            $tempArray['volume'] = $row->bl_meas;
            $tempArray['freight_detail'] = 'FREIGHT';
            $tempArray['charges'] = $row->freight;
            $tempArray['as_agent'] = $row->as_agent;
            $tempArray['etd'] = empty($row->back_date)? $row->etd : $row->back_date;
            $response['data'][] = $tempArray;
         }
         return $response;
      } else {
           return $this->not_found_response();
      }
      return false;
   }
    public function create_shipping_intruction($data,$data_detail)
   {
      $this->dbx->where('booking_code',$data['booking_code']);
	  $insert = $this->dbx->update('trnstuffingplan_booking', $data);
	  $this->dbx->where('booking_id',$data['booking_code']);
      $insert2 = $this->dbx->update('trnstuffingplan_booking_detail', $data_detail);
	  //print_r($this->dbx->last_query());
	  //die();
      if ($insert2) {
         $response = array();
		 $response['status']=201;
         $response['error'] = false;
         $response['message'] = 'Successfully added shipping intruction';
         return $response;
      } else {
           return $this->failed_response();
        }
      return false;
   }
   public function update_bl($data,$data_detail,$booking_code)
   {
      if($booking_code == ''){
		return $this->empty_response();
	 }else{
      $where = array("booking_code"=>$booking_code);
	  $this->dbx->where($where);
	  $update = $this->dbx->update('trnstuffingplandetail', $data);
	  //print_r($this->dbx->last_query());
	  //die();
	  $this->dbx->where($where);
	  $update1 = $this->dbx->update('tbl_detail', $data_detail);
	  
      if ($update && $update1) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully changed document';
         return $response;
	  }
      return false;
	 }
   }
   
   function get_shipper_detail($data)
   {
	   $q2 = $this->dbx->get_where('mstshipper',array('shipper_name'=>$data))->row();
		return array("shipper"=>$q2->id_shipper,"shipper_print"=>$q2->shipper_name,"sales"=>$q2->sales,"region_id"=>$q2->region_id);
   }
   
   public function get_stuffing_plan($code,$key)
   {
      if($code === null){
	  $this->dbx->select (array(
	  'trnstuffingplan.kode_ref as kode_ref',
	  'trnstuffingplan.stuffing_date as stuffing_date',
	  'trnstuffingplan.cont_seal_no as container_number',
	  'trnstuffingplan.destination as destination',
	  'trnstuffingplan.warehouse as warehouse',
	  'trnstuffingplan.air_line as status')); 
	  $this->dbx->group_start();
	  $this->dbx->like('kode_ref', '-100', 'after'); 
	  //$this->dbx->or_like('kode_ref', 'GCL-102', 'after');
	  $this->dbx->group_end();
	  	if($key === null){
			 $query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta'));
		}else if($key =='unloaded'){
			 $query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line'=>$key));
		}else if($key =='loaded'){
			 $query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line'=>$key));
		}else if($key =='today'){
			$date = date('Ymd');
			$query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','stuffing_date'=>$date));
		}
		//print_r($query_list->result());
	   if (!empty($query_list->result())) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved list stuffing plan';
         foreach ($query_list->result() as $row) {
            $tempArray = array();
            $tempArray['job_no'] = $row->kode_ref;
            $tempArray['stuffing_date'] = $row->stuffing_date;
            $tempArray['container_number'] = $row->container_number;
            $tempArray['destination'] = $row->destination;
            $tempArray['status'] = $row->status;
			$response['data'][] = $tempArray;
		      }
       return $response;
	   } else {
           return $this->not_found_response();
		}
	  }else{
		$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.sp_detail_id as sp_detail_id',
				'trnstuffingplandetail.shipment_request_id as shipment_request_id',
				'trnstuffingplandetail.num as num',
				'trnstuffingplandetail.shipper as shipper',
				'trnstuffingplandetail.wg_ctn as wg_ctn',
				'trnstuffingplandetail.note as note',
				'trnstuffingplandetail.consignee as consignee',
				'trnstuffingplandetail.consignee_add as consignee_add',
				'trnstuffingplandetail.notify as notify',
				'trnstuffingplandetail.notify_add as notify_add',
				'trnstuffingplandetail.no_pack_qty as no_pack_qty',
				'trnstuffingplandetail.no_pack_unit as no_pack_unit',
				'trnstuffingplandetail.no_of_pack as no_of_pack',
				'trnstuffingplandetail.desc_goods as desc_goods',
				'trnstuffingplandetail.weight as weight',
				'trnstuffingplandetail.ship_meas as ship_meas',
				'trnstuffingplandetail.wh_meas as wh_meas',
				'trnstuffingplandetail.chgs_weight as chgs_weight',
				'trnstuffingplandetail.cost_meas as cost_meas',
				'trnstuffingplandetail.wh_agent as wh_agent',
				'trnstuffingplandetail.bl_meas as bl_meas',
				'trnstuffingplandetail.destination as destination',
				'trnstuffingplandetail.doc as doc',
				'trnstuffingplandetail.hb_l as hb_l',
				'trnstuffingplandetail.agent_code as agent_code',
				'trnstuffingplandetail.freights_charges as freights_charges',
				'trnstuffingplandetail.freight as freight',
				'trnstuffingplandetail.c_o as c_o',
				'trnstuffingplandetail.marking_nos as marking_nos',
				'trnstuffingplandetail.shipping_mark as shipping_mark',
				'trnstuffingplandetail.isN as isN',
				'trnstuffingplandetail.isOptCost as isOptCost',
				'trnstuffingplandetail.status_selling as status_selling',
				'trnstuffingplandetail.isSpotRate as isSpotRate',
				'trnstuffingplandetail.express_release as express_release',
				'trnstuffingplandetail.shipped_on_board as shipped_on_board',
				'trnstuffingplandetail.net_weight_tidak_dicetak as net_weight_tidak_dicetak',
				'trnstuffingplandetail.cetak_jml_doc_origi as cetak_jml_doc_origi',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.isLock as isLock',
				'trnstuffingplandetail.code as code',
				'trnstuffingplandetail.lock_job as lock_job',
				'trnstuffingplandetail.shipp_co as shipp_co',
				'trnstuffingplandetail.shipper_print as shipper_print',
				'trnstuffingplandetail.desc_print as desc_print',
				'trnstuffingplandetail.no_of_pack as no_of_pack',
				'trnstuffingplandetail.sales as sales',
				
				'trnstuffingplandetail.shipper_import as shipper_import',
				'trnstuffingplandetail.shipper_import_code as shipper_import_code',
				'trnstuffingplandetail.shipper_import_add as shipper_import_add',
				'trnstuffingplandetail.bl_img as status_cargo',
				
				'trnstuffingplandetail.hbl as hbl',
				'trnstuffingplandetail.netto as netto',
				'trnstuffingplandetail.booking_code as booking_number',
				
			)
		);
		
		$this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->order_by('trnstuffingplandetail.num','asc');
		$query =$this->dbx->get_where('trnstuffingplandetail',array('code'=>$code));
		$this->dbx->join('x_instruksi_stuffing','x_instruksi_stuffing.id_sp =trnstuffingplan.sp_id','left');
		$query_head = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code));
		
      if ($query->result() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved stuffing plan';
		 	foreach ($query_head->result() as $row) {
			$headArray = array();
            $headArray['job_no'] = $row->kode_ref;
            $headArray['stuffing_date'] = $row->stuffing_date;
            $headArray[	'etd'] = $row->	etd_jkt;
            $headArray[	'container_size'] = $row->type_cont_size;
            $headArray[	'container_number'] = $row->cont_seal_no;
            $headArray[	'closing_date'] = $row->closing_date_time;
            $headArray[	'destination'] = $row->destination;
            $headArray[	'vessel'] = $row->vessel. " ".$row->voyage;
            $headArray[	'agent'] = $row->agen_penagihan;
            $headArray[	'instruksi_stuffing'] = $row->instruksi_stuffing;
            $response['header'][] = $headArray;
			}
         foreach ($query->result() as $row) {
            $tempArray = array();
            $tempArray['num'] = $row->kode_ref.$row->num;
            $tempArray['shipper'] = $row->shipper_print;
            $tempArray['packages'] = $row->no_of_pack;
            $tempArray['quantity'] = $row->no_pack_qty;
            $tempArray['description'] = $row->desc_print;
            $tempArray['gross_weight'] = $row->weight;
            $tempArray['nett_weight'] = $row->netto;
            $tempArray['shipper_meas'] = $row->ship_meas;
            $tempArray['warehouse_meas'] = $row->wh_meas;
            $tempArray['charged_meas'] = $row->bl_meas;
            $tempArray['destination'] = $row->destination;
            $tempArray['peb'] = $row->doc='y'?'Gateway' : 'Shipper';
            $tempArray['marking'] = $row->shipping_mark;
            $tempArray['booking_no'] = $row->booking_number;
			$tempArray['status_cargo'] = $row->status_cargo;
			$response['data'][] = $tempArray;
		    }
	
          return $response;
        } else {
           return $this->not_found_response();
		}
	  }
      return false;
   }
   public function filter_stuffing_plan($code=null)
   {
      if($code === null){
	  $this->dbx->select (array(
	  'trnstuffingplan.kode_ref as kode_ref',
	  'trnstuffingplan.stuffing_date as stuffing_date',
	  'trnstuffingplan.cont_seal_no as container_number',
	  'trnstuffingplan.destination as destination',
	  'trnstuffingplan.warehouse as warehouse',
	  'trnstuffingplan.air_line as status'));
	  $this->dbx->group_start();
	   $this->dbx->like('kode_ref', '-100', 'after'); 
	  //$this->dbx->or_like('kode_ref', 'GCL-102', 'after');
	  $this->dbx->group_end();
	  $query_list = $this->dbx->get_where('trnstuffingplan',array('air_line'=>'unloaded','region_id'=>'Jakarta','warehouse'=>'GCLMARUNDA'));
	   if ($query_list->result() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved list stuffing plan';
         $response['total_unloaded'] = $query_list->num_rows();;
         foreach ($query_list->result() as $row) {
            $tempArray = array();
            $tempArray['job_no'] = $row->kode_ref;
            $tempArray['stuffing_date'] = $row->stuffing_date;
            $tempArray['container_number'] = $row->container_number;
            $tempArray['destination'] = $row->destination;
            $tempArray['status'] = $row->status;
			$response['data'][] = $tempArray;
		      }
       return $response;
	   }
	  }else{
		$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.sp_detail_id as sp_detail_id',
				'trnstuffingplandetail.shipment_request_id as shipment_request_id',
				'trnstuffingplandetail.num as num',
				'trnstuffingplandetail.shipper as shipper',
				'trnstuffingplandetail.wg_ctn as wg_ctn',
				'trnstuffingplandetail.note as note',
				'trnstuffingplandetail.consignee as consignee',
				'trnstuffingplandetail.consignee_add as consignee_add',
				'trnstuffingplandetail.notify as notify',
				'trnstuffingplandetail.notify_add as notify_add',
				'trnstuffingplandetail.no_pack_qty as no_pack_qty',
				'trnstuffingplandetail.no_pack_unit as no_pack_unit',
				'trnstuffingplandetail.no_of_pack as no_of_pack',
				'trnstuffingplandetail.desc_goods as desc_goods',
				'trnstuffingplandetail.weight as weight',
				'trnstuffingplandetail.ship_meas as ship_meas',
				'trnstuffingplandetail.wh_meas as wh_meas',
				'trnstuffingplandetail.chgs_weight as chgs_weight',
				'trnstuffingplandetail.cost_meas as cost_meas',
				'trnstuffingplandetail.wh_agent as wh_agent',
				'trnstuffingplandetail.bl_meas as bl_meas',
				'trnstuffingplandetail.destination as destination',
				'trnstuffingplandetail.doc as doc',
				'trnstuffingplandetail.hb_l as hb_l',
				'trnstuffingplandetail.agent_code as agent_code',
				'trnstuffingplandetail.freights_charges as freights_charges',
				'trnstuffingplandetail.freight as freight',
				'trnstuffingplandetail.c_o as c_o',
				'trnstuffingplandetail.marking_nos as marking_nos',
				'trnstuffingplandetail.shipping_mark as shipping_mark',
				'trnstuffingplandetail.isN as isN',
				'trnstuffingplandetail.isOptCost as isOptCost',
				'trnstuffingplandetail.status_selling as status_selling',
				'trnstuffingplandetail.isSpotRate as isSpotRate',
				'trnstuffingplandetail.express_release as express_release',
				'trnstuffingplandetail.shipped_on_board as shipped_on_board',
				'trnstuffingplandetail.net_weight_tidak_dicetak as net_weight_tidak_dicetak',
				'trnstuffingplandetail.cetak_jml_doc_origi as cetak_jml_doc_origi',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.isLock as isLock',
				'trnstuffingplandetail.code as code',
				'trnstuffingplandetail.lock_job as lock_job',
				'trnstuffingplandetail.shipp_co as shipp_co',
				'trnstuffingplandetail.shipper_print as shipper_print',
				'trnstuffingplandetail.desc_print as desc_print',
				'trnstuffingplandetail.no_of_pack as no_of_pack',
				'trnstuffingplandetail.sales as sales',
				
				'trnstuffingplandetail.shipper_import as shipper_import',
				'trnstuffingplandetail.shipper_import_code as shipper_import_code',
				'trnstuffingplandetail.shipper_import_add as shipper_import_add',
				'trnstuffingplandetail.bl_img as status_cargo',
				
				'trnstuffingplandetail.hbl as hbl',
				'trnstuffingplandetail.netto as netto',
				'tbl_detail.no_from_shipper as booking_number',
				
			)
		);
		$this->dbx->join('tbl_detail','tbl_detail.detail_id =trnstuffingplandetail.sp_detail_id','left');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->order_by('trnstuffingplandetail.num','asc');
		$query =$this->dbx->get_where('trnstuffingplandetail',array('code'=>$code));
	  
      if ($query->result() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved stuffing plan';
         foreach ($query->result() as $row) {
            $tempArray = array();
            $tempArray['job_no'] = $row->kode_ref;
            $tempArray['num'] = $row->kode_ref.$row->num;
            $tempArray['shipper'] = $row->shipper_print;
            $tempArray['packages'] = $row->no_of_pack;
            $tempArray['description'] = $row->desc_print;
            $tempArray['gross_weight'] = $row->weight;
            $tempArray['nett_weight'] = $row->netto;
            $tempArray['shipper_meas'] = $row->ship_meas;
            $tempArray['warehouse_meas'] = $row->wh_meas;
            $tempArray['charged_meas'] = $row->bl_meas;
            $tempArray['destination'] = $row->destination;
            $tempArray['peb'] = $row->doc='y'?'Gateway' : 'Shipper';
            $tempArray['marking'] = $row->shipping_mark;
            $tempArray['booking_no'] = $row->booking_number;
            $tempArray['status_cargo'] = $row->status_cargo;
			$response['data'][] = $tempArray;
		    }
          return $response;
        } else {
           return $this->not_found_response();
      }
	  }
      return false;
   }
   
public function get_stuffing_plan_data()
   {
	  $this->dbx->select (array(
	  'trnstuffingplan.kode_ref as kode_ref',
	  'trnstuffingplan.stuffing_date as stuffing_date',
	  'trnstuffingplan.cont_seal_no as container_number',
	  'trnstuffingplan.destination as destination',
	  'trnstuffingplan.warehouse as warehouse',
	  'trnstuffingplan.air_line as status'));
	  $query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta'));
	  $query_loaded = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line'=>'loaded'));
	  $query_unloaded = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line !='=>'loaded'));
	   if ($query_list->result() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved list stuffing plan';
		 $response['total'] = $query_list->num_rows();
		 $response['loaded'] = $query_loaded->num_rows();
		 $response['unloaded'] = $query_unloaded->num_rows();
	   }
		$this->dbx->select('destination');
		$this->dbx->distinct();
		$query = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta'));
		if ($query->result() > 0) {
         foreach ($query->result() as $row) {
			$this->dbx->where('destination',$row->destination);
			$this->dbx->where('warehouse','GCLMARUNDA');
			$this->dbx->like('type_cont_size',20);
			$this->dbx->from("trnstuffingplan");
			$teus20 =  $this->dbx->count_all_results();
			$this->dbx->where('destination',$row->destination);
			$this->dbx->where('warehouse','GCLMARUNDA');
			$this->dbx->like('type_cont_size',4);
			$this->dbx->from("trnstuffingplan");
			$teus40 =  2*($this->dbx->count_all_results());
            $tempArraya = array();
            $tempArraya[$row->destination] = $teus20+$teus40;
			$response['destination'][] = $tempArraya;
		      }
		}
       return $response;
	   }
   
	   
  public function update_stuffing_plan($data,$code)
   {
      if($code == ''){
		return $this->empty_response();
	 }else{
      $where = array("kode_ref"=>$code);
	  $this->dbx->where($where);
	  $update = $this->dbx->update('trnstuffingplan', $data);
	  //print_r($this->dbx->last_query());
	  //die();
      if ($update) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully update stuffing plan';
         return $response;
	  }
      return false;
	 }
   }
   
public function update_x_finance($params)
	{
if (is_array($params) || is_object($params))
	{ 
    foreach($params as $row){
		$cleaned_inv_no = explode('-', $row['invoice_no'])[0];
		$data = array('status'=>$row['status'],'tgl_bayar'=>$row['tanggal_bayar'],'overdue'=>$row['overdue']);
		$this->dbx->where('number',$cleaned_inv_no);
		$this->dbx->update('x_finance', $data);
	}
	//print_r($this->dbx->last_query());
	//die();
	}
    if ($this->dbx->affected_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully update invoice status';
         return $response;
	 }else{
		return $this->failed_response();
	 }
   }
   
public function update_cost($params)
{
    if (!is_array($params) || empty($params)) {
        return $this->empty_response();
    }

    // Memulai transaksi di kedua database
    $this->dbx->trans_begin();
    $this->db2->trans_begin();

    try {
        $anyUpdated = false;

        foreach ($params as $row) {
            // 1. Ambil dan bersihkan data awal
            $ref  = isset($row['reference_number']) ? trim((string)$row['reference_number']) : null;
            $item = isset($row['item_service']) ? trim((string)$row['item_service']) : null;

            // 2. Filter: Skip jika data tidak valid atau bernilai "None"
            if (empty($ref) || empty($item) || strtolower($ref) === 'none') {
                continue;
            }

            $data = array(
                'status'        => $row['status'] ?? null,
                'tanggal_bayar' => $row['tanggal_bayar'] ?? null,
                'journal_no'    => $row['journal_no'] ?? null,
            );

            // Daftar tabel dan kolom kunci untuk diproses
            $tables = array(
                'inf_cost_tbl_satu'         => 'kode_ref',
                'inf_cost_refund_sc'        => 'kode_ref',
                'inf_cost_refund_discount'  => 'kode_ref',
                'x_glbcost'                 => 'job_no'
            );

            foreach ($tables as $table => $keyColumn) {
                $isMatch = $this->_update_first_match(
                    $table,
                    array($keyColumn => $ref, 'item_service' => $item),
                    $data
                );
                
                if ($isMatch) {
                    $anyUpdated = true;
                }
            }
        }

        // Cek status transaksi di kedua DB
        if ($this->dbx->trans_status() === FALSE || $this->db2->trans_status() === FALSE) {
            $this->dbx->trans_rollback();
            $this->db2->trans_rollback();
            return $this->failed_response();
        } else {
            // Commit semua perubahan jika sukses
            $this->dbx->trans_commit();
            $this->db2->trans_commit();
            
            if ($anyUpdated) {
                return array('error' => false, 'message' => 'Successfully updated payable status');
            } else {
                return array('error' => true, 'message' => 'No matching data found to update');
            }
        }

    } catch (Exception $e) {
        // Rollback jika terjadi error sistem yang tak terduga
        $this->dbx->trans_rollback();
        $this->db2->trans_rollback();
        log_message('error', 'Update Cost Error: ' . $e->getMessage());
        return $this->failed_response();
    }
}

/**
 * Helper: Update di dbx, jika tidak ada row, coba di db2.
 */
private function _update_first_match($table, $where, $data)
{
    $updated = false;

    // Coba Update di DBX
    $this->dbx->where($where)->update($table, $data);
    if ($this->dbx->affected_rows() > 0) {
        $updated = true;
    }

    // Coba Update di DB2
    $this->db2->where($where)->update($table, $data);
    if ($this->db2->affected_rows() > 0) {
        $updated = true;
    }

    return $updated;
}

   
public function update_arrived_cfs($params)
{
    if ($params['booking_code'] == '') {
        return $this->empty_response();
    }

    // Inisialisasi variabel agar tidak undefined
    $update = false;
    $update1 = false;
    $update2 = false;

    $this->dbx->trans_start();

    if ($params['status'] == 'Received In Warehouse') {
        $this->dbx->where('booking_code', $params['booking_code']);
        $update1 = $this->dbx->update('trnstuffingplandetail', [
            'received_status' => $params['status'],
            'received_date'   => date("Y-m-d H:i:s")
        ]);

        $this->dbx->where('booking_code', $params['booking_code']);
        $update = $this->dbx->update('trnstuffingplan_booking', [
            'warehouse_status' => $params['status'],
            'received_date'    => date("Y-m-d H:i:s"),
            'received_by'      => $params['confirm_by']
        ]);

        $this->dbx->where('booking_code', $params['booking_code']);
        $update2 = $this->dbx->update('x_tracking_export', [
            'received_date' => date("Y-m-d H:i:s"),
            'received_by'   => $params['confirm_by']
        ]);
    } elseif ($params['status'] == 'Stuffing') {
        $this->dbx->where('booking_code', $params['booking_code']);
        $update1 = $this->dbx->update('trnstuffingplandetail', [
            'stuffing_status' => $params['status'],
            'stuffing_date'   => date("Y-m-d H:i:s")
        ]);
    } elseif ($params['status'] == 'Deliver To Destination') {
        $this->dbx->where('booking_code', $params['booking_code']);
        $update1 = $this->dbx->update('trnstuffingplandetail', [
            'gate_status' => $params['status'],
            'gate_date'   => date("Y-m-d H:i:s")
        ]);
    }

    $this->dbx->trans_complete();

    if ($update || $update1 || $update2) {
        return ['error' => false, 'message' => 'Successfully update cargo status'];
    } else {
        return ['error' => true, 'message' => 'Status tidak berubah'];
    }
}

   
     public function update_stuffing_plan_detail($data,$code)
   {
      if($code == ''){
		return $this->empty_response();
	 }else{
	  $update = ("UPDATE trnstuffingplandetail join tbl_detail on trnstuffingplandetail.sp_detail_id=tbl_detail.detail_id SET `trnstuffingplandetail`.`bl_img` = '$data' WHERE `tbl_detail`.`no_from_shipper` = '$code'");
	  $this->dbx->query($update);
	  $afftectedRows = $this->dbx->affected_rows();
	  }
      if ($afftectedRows > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully update stuffing plan status';
         return $response;
	  }else{
		 $response = array();
         $response['error'] = true;
         $response['message'] = 'Status tidak berubah';
         return $response;
      }
	 }
   
   public function get_id_tracking($si,$shipper)
   {   
   if($si == ''){
		return $this->empty_response();
	 }else{
		$pos = strpos($si, '-GCL26');
		//print_r($pos);
		if ($pos === 4 || $pos === true) {
		$this->db2->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplan.hide as tracking_id',
				'trnstuffingplandetail.sp_id as detail_sp_id',
				'trnstuffingplandetail.shipper_print as shipper_print',
			));
	 $this->db2->join('trnstuffingplandetail', 'trnstuffingplandetail.sp_id=trnstuffingplan.sp_id', 'left');
	 if($shipper=='PT. ABC JAKARTA'){
		$query =$this->db2->get_where('trnstuffingplan',array('trnstuffingplandetail.hb_l'=>$si));
	 }else{
	   $query =$this->db2->get_where('trnstuffingplan',array('trnstuffingplandetail.hb_l'=>$si));
	 }
	 if ($query) {
	 return $query;
	 }else{
		return 'Kosong';
      }
      return false;
	 } else{
		 	$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplan.hide as tracking_id',
				'trnstuffingplandetail.sp_id as detail_sp_id',
				'trnstuffingplandetail.shipper_print as shipper_print',
			));
	 $this->dbx->join('trnstuffingplandetail', 'trnstuffingplandetail.sp_id=trnstuffingplan.sp_id', 'left');
	 if($shipper=='PT. ABC JAKARTA'){
		$query =$this->dbx->order_by('sp_id', 'ASC')->get_where('trnstuffingplan',array('trnstuffingplandetail.hb_l'=>$si),1);
	 }else{
	   $query =$this->dbx->get_where('trnstuffingplan',array('trnstuffingplandetail.hb_l'=>$si),1);
	 }
	 if ($query) {
	 return $query;
	 }else{
		return 'Kosong';
      }
      return false;
	 }
	}
		 
	}
	
public function get_gocomet($si)
{
    if ($si === '') {
        return $this->empty_response();
    }

    // 1. DAFTAR DATABASE YANG AKAN DICEK (Urutan: dbx dulu, baru db2)
    $databases = ['dbx', 'db2'];

    foreach ($databases as $db_name) {
        $db_obj = $this->{$db_name}; // Mengambil object dbx atau db2

        // --- Langkah A: Cek berdasarkan kolom hb_l ---
        $this->prepare_query_gocomet($db_obj);
        $query = $db_obj->get_where('trnstuffingplan', ['trnstuffingplandetail.hb_l' => $si], 1);

        if ($query && $query->num_rows() > 0) {
            return $query;
        }
    }

    // Jika di kedua database tidak ditemukan
    return $query; // meskipun kosong
}

/**
 * Helper function untuk menyiapkan select dan join agar tidak nulis ulang
 */
private function prepare_query_gocomet($db_obj)
{
    $db_obj->select([
        'trnstuffingplan.kode_ref as kode_ref',
        'trnstuffingplan.sp_id as sp_id',
        'trnstuffingplan.mother_bl as mother_bl',
        'trnstuffingplan.id_gocomet as tracking_id',
        'trnstuffingplan.etd_jkt as etd_jkt',
        'trnstuffingplan.atd as atd',
        'trnstuffingplan.eta as eta',
        'trnstuffingplan.ata as ata',
        'trnstuffingplandetail.sp_id as detail_sp_id',
    ]);
    $db_obj->join('trnstuffingplandetail', 'trnstuffingplandetail.sp_id = trnstuffingplan.sp_id', 'left');
    $db_obj->order_by('trnstuffingplan.sp_id', 'ASC');
}
		 
	
public function get_do_number($si)
   {
   if($si == ''){
		return $this->empty_response();
	 }else{
	$this->dbx->select(
			array(
				'trnstuffingplandetail.hb_l as hbl',
				'trnstuffingplandetail.shipment_request_id as do_number',
			));
	 $query =$this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.hb_l'=>$si));
	 if ($query) {
	 return $query;
	 }else{
		return '';
      }
      return false;
	 }
 }
 
public function get_consol_destination($si)
{
    if ($si === '') {
        return $this->empty_response();
    }

    $databases = ['dbx', 'db2'];

    foreach ($databases as $db_name) {
        $db_obj = $this->{$db_name};

        // 1. SIAPKAN QUERY (Select & Join)
        $this->prepare_query_consol($db_obj, $db_name);

        // 2. LOGIKA PRIORITAS JAKARTA
        $db_obj->order_by("CASE WHEN trnstuffingplan.region_id = 'Jakarta' THEN 0 ELSE 1 END", "ASC");

        // 3. COBA CARI DI KOLOM hb_l DULU (Langkah A)
        // Kita gunakan group_start untuk memastikan logic WHERE tidak berantakan jika nanti ditambah kondisi lain
        $db_obj->where('trnstuffingplandetail.hb_l', $si);
        $query = $db_obj->get('trnstuffingplan', 1);

        if ($query && $query->num_rows() > 0) {
            // Jika ketemu, FUNGSI BERHENTI DI SINI dan mengembalikan hasil
            return $query;
        }

        // 4. JIKA LANGKAH A TIDAK KETEMU, BARU LANJUT KE KOLOM doc (Langkah B)
        // Kita harus siapkan ulang select & join karena get() sebelumnya sudah membersihkan builder
        $this->prepare_query_consol($db_obj, $db_name);
        $db_obj->order_by("CASE WHEN trnstuffingplan.region_id = 'Jakarta' THEN 0 ELSE 1 END", "ASC");
        $db_obj->where('trnstuffingplandetail.doc', $si);
        $query = $db_obj->get('trnstuffingplan', 1);

        if ($query && $query->num_rows() > 0) {
            // Jika ketemu di langkah B, fungsi berhenti di sini
            return $query;
        }
        
        // Jika di dbx (loop pertama) tidak ketemu sama sekali di A maupun B, 
        // maka loop akan berlanjut ke database berikutnya (db2).
    }

    return 'Kosong';
}

/**
 * Helper function untuk menyiapkan query Consol Destination
 */
private function prepare_query_consol($db_obj, $db_type)
{
    $select = [
        'trnstuffingplan.kode_ref as kode_ref',
        'trnstuffingplan.sp_id as sp_id',
        'trnstuffingplan.destination as consol_destination',
        'trnstuffingplan.stuffing_date as stuffing_date',
        'trnstuffingplan.etd_jkt',
        'trnstuffingplan.atd',
        'trnstuffingplan.eta',
        'trnstuffingplan.ata',
        'trnstuffingplan.type_of_trans as type_of_trans',
        'trnstuffingplan.scac as scac',
        'trnstuffingplan.carrier as carrier',
        'trnstuffingplan.vessel as vessel',
        'trnstuffingplan.voyage as voyage',
        'trnstuffingplan.port_loading as pol',
        'trnstuffingplan.mother_bl as mbl',
        'trnstuffingplan.transhipment_port as transhipment_port',
        'trnstuffingplan.type_cont_size as type_cont_size',
        'trnstuffingplandetail.sp_id as detail_sp_id',
        'trnstuffingplandetail.destination as destination',
        'trnstuffingplandetail.hb_l as hbl',
        'trnstuffingplandetail.bl_meas as volume',
        'trnstuffingplandetail.region_id as region_id',
    ];

    // Tambahkan kolom khusus dbx jika sedang menggunakan dbx
    if ($db_type === 'dbx') {
        $select[] = 'trnstuffingplan.closing_date_time as closing_date_time';
        $select[] = 'trnstuffingplan.closing_time as closing_time';
    }

    $db_obj->select($select);
    $db_obj->join('trnstuffingplandetail', 'trnstuffingplandetail.sp_id = trnstuffingplan.sp_id', 'left');
    // PASTIKAN CASE INI ADALAH ORDER_BY PERTAMA
    $db_obj->order_by("CASE WHEN trnstuffingplandetail.region_id = 'Jakarta' THEN 0 ELSE 1 END", "ASC");
    
    // sp_id diletakkan setelahnya sebagai cadangan urutan
    $db_obj->order_by('trnstuffingplan.sp_id', 'ASC');
}
public function get_x_tracking_export($si)
   {
	 $useDb2 = (preg_match('/-(GCL25|SBY25|SMG25|CKR25|BDG25|MKS25|BLW25)/', $si) === 1);
	 if ($useDb2) {
	  $query = $this->db2->get_where('x_tracking_export',['hbl_no'=>$si]);
	 }else{
	  $query = $this->dbx->get_where('x_tracking_export',['hbl_no'=>$si]); 
	 }
	  //print_r($this->dbx->last_query());
	  //die();
      return $query;
   }
public function get_x_tracking_gudang($si)
{
    $pos = strpos($si, '-GCL25');
    $db = ($pos === false) ? $this->dbx : $this->db2;

    $db->select('
		tb.booking_code,
        tsp.received_status, 
        tsp.received_date, 
        tsp.stuffing_status, 
        tsp.stuffing_date, 
        tsp.gate_status, 
        tsp.gate_date,
        tsp.region_id,
        COALESCE(tb.created_at, tb.updated_at) AS booking_time
    ');
    $db->from('trnstuffingplandetail tsp');
    $db->join('trnstuffingplan_booking tb', 'tsp.hb_l = tb.house_bl', 'left');
    $db->where('tsp.hb_l', $si);
    //$db->where('tsp.region_id', 'Jakarta');

    $query = $db->get();
    return $query;
}
public function get_x_tracking_import($si)
{
    if ($si === '') {
        // Jika ingin tetap mengembalikan CI_DB_result (bukan string), bisa kembalikan query yang pasti kosong:
        // return $this->dbx->get_where('x_tracking', ['hbl_no' => '__never_match__']);
        return $this->empty_response(); // pakai ini jika memang sudah dipakai di project-mu
    }

    // 1) Coba berdasarkan hbl_no (tanpa limit)
    $query = $this->dbx->get_where('x_tracking', ['hbl_no' => $si]);

    // 2) Jika kosong, fallback ke hbl_agent (TANPA limit)
    if ($query && $query->num_rows() === 0) {
        $query = $this->dbx->get_where('x_tracking', ['hbl_agent' => $si]);
    }

    // 3) Selalu kembalikan objek CI_DB_result (walau 0 row)
    return $query;
}


   
 public function get_bl_number($id=null,$shipper=null)
   {
      if($id == null){
	  $this->dbx->select(array(
	  'trnstuffingplandetail.hb_l as hb_l',
	  'trnstuffingplandetail.booking_code as booking_code',
	  ));
	  $query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.shipper_print'=>$shipper,'trnstuffingplandetail.booking_code!=' => NULL));
	  //print_r($this->dbx->last_query());
	  //die();
	  }else{
	   $this->dbx->select(array(
	  'trnstuffingplandetail.hb_l as hb_l',
	  'trnstuffingplandetail.booking_code as booking_code',
	  ));
	  $query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.booking_code'=>$id));
	  //print_r($this->dbx->last_query());
	  //die();
	  }
      if ($query->num_rows() > 0) {
         foreach ($query->result() as $row) {
            $tempArray = array();
            $tempArray['hbl'] = $row->hb_l;
            $tempArray['quot_no'] = $row->booking_code;
            $response[] = $tempArray;
         }
	  }
      return $response;
   }

public function cek_customer($params){
	foreach($params as $row){
	$q = $second_db->get_where('mstshipper',array('account'=>$row['full_name']));	
	}
	return $q->num_rows();
}
   
public function insert_customer($params)
	{
if (is_array($params) || is_object($params))
	{ 
    foreach($params as $row){
		$sales_array = $this->fungsi->get_id_by_username_array($row['sales_person']);
		$sales = implode (",",$sales_array);  
		$data = array('shipper_id'=>$row['customer_code'],
					'shipper_name'=>$row['full_name'],
					'shipper_address'=>$row['address'],
					'cp_name'=>$row['pic_name'],
					'cp_phone'=>$row['pic_phone'],
					'cp_email'=>$row['pic_email'],
					'sales'=>$sales,
					'region_id'=>'Jakarta',
					);
		$this->dbx->trans_start();
		$this->dbx->insert('mstshipper', $data);
		if($row['tax_id'] != ""){
		$npwp = array('tax_id'=>$row['tax_id'],
					'account_name'=>$row['tax_name'],
					'account_address'=>$row['tax_address'],
					);
		$this->dbx->insert('ref_account', $npwp);
		}
		$this->dbx->trans_complete();
	}
	//print_r($this->dbx->last_query());
	//die();
	}
	//print_r($this->dbx->last_query());
	//die();
    if ($this->dbx->trans_status() === FALSE)  {
		return $this->failed_response();
	 }else{
		 $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully insert new customer';
         return $response;
	 }
   }
   
public function update_customer($params)
	{
if (is_array($params) || is_object($params))
	{ 
    foreach($params as $row){
		$sales_array = $this->fungsi->get_id_by_username_array($row['sales_person']);
		$sales = implode (",",$sales_array);
		$data = array('shipper_id'=>$row['customer_code'],
					'shipper_name'=>$row['full_name'],
					'shipper_address'=>$row['address'],
					'cp_name'=>$row['pic_name'],
					'cp_phone'=>$row['pic_phone'],
					'cp_email'=>$row['pic_email'],
					'sales'=>$sales,
					'region_id'=>'Jakarta',
					);
		$npwp = array('tax_id'=>$row['tax_id'],
					'account_name'=>$row['tax_name'],
					'account_address'=>$row['tax_address'],
					);
		$this->dbx->trans_start();
        $this->dbx->where('shipper_name',$row['full_name']);	
		$this->dbx->update('mstshipper', $data);
		if($row['tax_id'] != ""){	
		$this->dbx->replace('ref_account', $npwp);
		}
		$this->dbx->trans_complete();
	}
	//print_r($this->dbx->last_query());
	//die();
	}
    if ($this->dbx->trans_status() === FALSE) {
		 return $this->failed_response();
	 }else{
		 $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully update customer';
         return $response;
	 }
   }
   
 public function delete_customer($params)
	{
if (is_array($params) || is_object($params))
	{ 
    foreach($params as $row){
		//$sales = $this->fungsi->get_id_by_username($row['sales_person']);
        $this->dbx->where('shipper_name',$row['full_name']);	
        //$this->dbx->where('sales',$sales);	
		$this->dbx->delete('mstshipper');
	}
	//print_r($this->dbx->last_query());
	//die();
	}
    if ($this->dbx->affected_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully delete customer';
         return $response;
	 }else{
		return $this->failed_response();
	 }
   }
   
public function hitung_stuffing_plan()
   {
	  $this->dbx->select (array(
	  'trnstuffingplan.kode_ref as kode_ref',
	  'trnstuffingplan.stuffing_date as stuffing_date',
	  'trnstuffingplan.cont_seal_no as container_number',
	  'trnstuffingplan.destination as destination',
	  'trnstuffingplan.warehouse as warehouse',
	  'trnstuffingplan.air_line as status')); 
	$this->dbx->like('kode_ref', '-100', 'after'); 
	$query_list = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta'));
	$this->dbx->like('kode_ref', '-100', 'after');
	$query_list_unloaded = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line'=>'unloaded'));
	$this->dbx->like('kode_ref', '-100', 'after'); 
	$query_list_loaded = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','air_line !='=>'unloaded'));
	$this->dbx->like('kode_ref', '-100', 'after'); 
	$query_list_today = $this->dbx->get_where('trnstuffingplan',array('warehouse'=>'GCLMARUNDA','region_id'=>'Jakarta','stuffing_date'=> date('Ymd')));
	   if (!empty($query_list->result())) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved list stuffing plan';
       
            $tempArray = array();
            $tempArray['total'] = $query_list->num_rows();
            $tempArray['loaded'] = $query_list_loaded->num_rows();
            $tempArray['unloaded'] = $query_list_unloaded->num_rows();
            $tempArray['today'] = $query_list_today->num_rows();
			$response['data'] = $tempArray;
       return $response;
	   } else {
           return $this->not_found_response();
		}
   }
   
/**
 * Date expression for legacy first-leg ETA or JSON milestone values.
 * Only internal, fixed SQL expressions may be passed here.
 */
private function ctl_target_date_expr($expr)
{
    $value = "TRIM(CAST(($expr) AS CHAR))";
    return "(CASE
        WHEN $value REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}( [0-9]{2}:[0-9]{2}:[0-9]{2})?$'
            THEN STR_TO_DATE(LEFT($value, 10), '%d/%m/%Y')
        WHEN $value REGEXP '^[0-9]{8}$'
            THEN STR_TO_DATE($value, '%Y%m%d')
        WHEN $value REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})?$'
            THEN STR_TO_DATE(LEFT($value, 10), '%Y-%m-%d')
        ELSE NULL END)";
}

/**
 * Common selector for CTL branches. Direct HKG stops on final delivery;
 * transshipment stops on structured final POD actual, not legacy ETA/ATA
 * JSON or pickup at the hub. SG endpoint currently supports second leg only.
 */
private function ctl_target_rows($hub, array $agents, $limit, $region_id, $transit_days, $include_direct, $job_filter, $direct_days = 30)
{
    $region_id = trim((string) $region_id);
    $transit_days = max(1, min(365, (int) $transit_days));
    $direct_days = max(1, min(365, (int) $direct_days));
    $hub_sql = $this->db->escape($hub);
    $destination = 'UPPER(TRIM(x.destination))';
    $eta = $this->ctl_target_date_expr('x.eta');
    $delivery_raw = "JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(x.cargo_delivery), x.cargo_delivery, '{}'), '$.\"Cargo delivered\"'))";
    $delivery = $this->ctl_target_date_expr($delivery_raw);
    $missing_final_actual = "(x.lt_actual_pod_arrival_at IS NULL
        OR CAST(x.lt_actual_pod_arrival_at AS CHAR) IN ('0000-00-00', '0000-00-00 00:00:00')
        OR x.lt_actual_pod_arrival_at > NOW()
        OR x.ctl_actual_pod_confirmed_at IS NULL
        OR x.ctl_actual_pod_confirmed_at <> x.lt_actual_pod_arrival_at)";

    $this->db->select('x.id_tracking, x.hbl_no, x.no_job, x.region_id,
        x.id_agent, x.destination, x.consol_destination, x.connecting, x.cbm, x.eta,
        x.dft, x.atd, x.cargo_delivery, x.lt_actual_pod_arrival_at,
        x.lt_pod_eta_at, x.updated_at');
    $this->db->from('x_tracking_export x');
    $this->db->where("UPPER(TRIM(x.consol_destination)) = $hub_sql", null, false);
    $this->db->where('UPPER(TRIM(x.id_agent)) IN (' . implode(', ', array_map([$this->db, 'escape'], $agents)) . ')', null, false);
    $this->db->where("TRIM(COALESCE(x.hbl_no, '')) <> ''", null, false);
    $this->db->where("UPPER(TRIM(COALESCE(x.destination, ''))) NOT IN ('', 'NULL')", null, false);
    if ($job_filter) {
        $this->db->like('x.no_job', '-100');
    }
    if ($region_id !== '') {
        $this->db->where('x.region_id', $region_id);
    }
    $this->db->where($eta . ($hub === 'HONG KONG' ? ' < CURDATE()' : ' <= CURDATE()'), null, false);

    $transit = "($destination <> $hub_sql AND (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END) <> (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) END) AND $eta >= DATE_SUB(CURDATE(), INTERVAL $transit_days DAY)
        AND $missing_final_actual)";
    if ($include_direct) {
        $direct = "($destination = $hub_sql AND $eta >= DATE_SUB(CURDATE(), INTERVAL $direct_days DAY)
            AND ($delivery IS NULL OR $delivery < '1000-01-01' OR $delivery > CURDATE()))";
        $this->db->where("($direct OR $transit)", null, false);
    } else {
        $this->db->where($transit, null, false);
    }

    // Store attempt independently of actual data changes. Failed API calls also count.
    $this->db->where("(x.ctl_last_poll_attempt_at IS NULL OR x.ctl_last_poll_attempt_at < CURDATE())", null, false);
    $this->db->order_by('x.ctl_last_poll_attempt_at', 'ASC');
    $this->db->order_by($eta, 'ASC', false);
    $this->db->order_by('x.id_tracking', 'ASC');
    if ($limit !== null) {
        $this->db->limit(max(1, min(100, (int) $limit)));
    }
    return $this->db->get()->result_array();
}

/** Claim one CTL attempt per shipment per database calendar day. */
public function claim_charterlink_poll($id)
{
    if ((int)$id <= 0) return false;
    $ok = $this->db->set('ctl_last_poll_attempt_at', 'NOW()', false)
        ->set('updated_at', 'updated_at', false)
        ->where('id_tracking', (int)$id)
        ->where('(ctl_last_poll_attempt_at IS NULL OR ctl_last_poll_attempt_at < CURDATE())', null, false)
        ->update('x_tracking_export');
    return $ok && $this->db->affected_rows() === 1;
}

public function get_target_cron_charterlink($limit = 10, $region_id = null, $transit_days = 90, $direct_days = 30)
{
    return $this->ctl_target_rows('HONG KONG',
        ['CHAHKG', 'CTL - HKG', 'CTL HONGKO'],
        $limit, $region_id, $transit_days, true, true, $direct_days);
}

public function get_target_cron_benkel($limit = null)
{
    $this->db->select('x.id_tracking, x.hbl_no, x.destination, x.consol_destination, x.connecting, x.cbm, x.atd, x.dft, x.id_agent, x.ata, x.ata_by, x.lt_pod_eta_at, x.lt_pod_eta_source, x.ctl_actual_pod_confirmed_at, x.ctl_actual_second_departure_at');
    $this->db->from('x_tracking_export x');

    $this->db->where('x.consol_destination', 'SINGAPORE');
    $this->db->where("((CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END) NOT IN ('', 'NULL', 'N/A', 'NA', 'UNKNOWN', '0', 'NONE','TBA','TBC') AND ((CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END) = 'SINGAPORE' OR (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) END) <> (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END)))", null, false);
    $this->db->like('x.no_job', '-100');
    $this->db->where_in('x.id_agent', ['BENKEL', 'BENKEL INT', 'BKL']);

    $this->db->group_start();
        $this->db->where('DATE(x.updated_at) < CURDATE()', null, false);
        $this->db->or_where('x.updated_at', '0000-00-00 00:00:00');
        $this->db->or_where('x.updated_at IS NULL', null, false);
    $this->db->group_end();

    /*
     * ETA bisa beda format.
     * Pakai COALESCE supaya dd/mm/yyyy dan yyyy-mm-dd sama-sama bisa kebaca.
     */
    $etaExpr = "COALESCE(
        STR_TO_DATE(x.eta, '%d/%m/%Y'),
        STR_TO_DATE(x.eta, '%Y-%m-%d'),
        STR_TO_DATE(x.eta, '%Y-%m-%d %H:%i:%s')
    )";

    $this->db->where("$etaExpr < CURDATE()", null, false);
    $this->db->where("STR_TO_DATE(x.eta, '%d/%m/%Y') >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)", NULL, FALSE);

    $this->db->group_start();
        $this->db->where('x.cargo_delivery IS NULL', null, false);
        $this->db->or_where("JSON_EXTRACT(x.cargo_delivery, '$.\"Cargo delivered\"') = 'null'", null, false);
        $this->db->or_where("JSON_EXTRACT(x.cargo_delivery, '$.\"Cargo delivered\"') IS NULL", null, false);
    $this->db->group_end();

    $this->db->group_start();

        // NON-SINGAPORE transhipment via Singapore.
        // Sebelumnya hanya Jakarta, sehingga Surabaya BENKEL INT tidak ikut.
        $this->db->group_start();
            $this->db->where('x.destination !=', 'SINGAPORE');
            $this->db->where_in('x.region_id', [
				'Jakarta',
				'Surabaya',
				'Semarang'
			]);
        $this->db->group_end();

        // SINGAPORE direct.
        $this->db->or_group_start();
            $this->db->where('x.destination', 'SINGAPORE');
        $this->db->group_end();

    $this->db->group_end();

    $this->db->order_by('x.id_tracking', 'ASC');

    if ($limit !== null) {
        $this->db->limit((int)$limit);
    }

    return $this->db->get()->result_array();
}
public function get_target_cron_astro($limit = null) {
    $this->db->select('x.id_tracking, x.hbl_no, x.destination, x.consol_destination, x.connecting, x.cbm, x.atd, x.dft, x.id_agent, x.ata, x.ata_by, x.lt_pod_eta_at, x.lt_pod_eta_source, x.ctl_actual_pod_confirmed_at, x.ctl_actual_second_departure_at');
    $this->db->from('x_tracking_export x');
    
    // ==============================================================
    // 1. FILTER UTAMA UMUM (Khusus SINGAPORE & JOB GCL)
    // ==============================================================
    $this->db->where('x.consol_destination', 'SINGAPORE');
    $this->db->where("((CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END) NOT IN ('', 'NULL', 'N/A', 'NA', 'UNKNOWN', '0', 'NONE','TBA','TBC') AND ((CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END) = 'SINGAPORE' OR (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.connecting, '')), ' ', ''), '-', ''), '_', '')) END) <> (CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('SGSIN','SIN','SINGAPORE') THEN 'SINGAPORE' WHEN UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) IN ('HKHKG','HKG','HONGKONG') THEN 'HONGKONG' ELSE UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(x.destination, '')), ' ', ''), '-', ''), '_', '')) END)))", null, false);
    $this->db->like('x.no_job', '-100');
	$this->db->where_in('x.id_agent', ['ASTSIN', 'ASTRO', 'AST', 'ASTRO PACI']);
	

    // ==============================================================
    // 2. BATASAN 1X SEHARI: Jangan hit berulang di hari yang sama
    // ==============================================================
    $this->db->group_start();
        $this->db->where('DATE(x.updated_at) < CURDATE()', NULL, FALSE);
        $this->db->or_where('x.updated_at', '0000-00-00 00:00:00');
        $this->db->or_where('x.updated_at IS NULL', NULL, FALSE);
    $this->db->group_end();

    // ==============================================================
    // 3. BATASAN ETA: Sudah lewat hari H, maksimal 1 Bulan ke belakang
    // ==============================================================
    $this->db->where("STR_TO_DATE(x.eta, '%d/%m/%Y') < CURDATE()", NULL, FALSE);
    $this->db->where("STR_TO_DATE(x.eta, '%d/%m/%Y') >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)", NULL, FALSE);

    // ==============================================================
    // 4. FILTER SKENARIO BERHENTI (TAMAT) BERDASARKAN DESTINATION
    // ==============================================================
    $this->db->group_start(); 
    
        // SKENARIO A: SINGAPORE (Berhenti jika Cargo Delivery terisi)
        $this->db->group_start();
            $this->db->where('x.destination', 'SINGAPORE');
            $this->db->group_start();
                $this->db->where('x.cargo_delivery IS NULL', NULL, FALSE);
                $this->db->or_where("JSON_EXTRACT(x.cargo_delivery, '$.\"Cargo delivered\"') = 'null'", NULL, FALSE);
            $this->db->group_end();
        $this->db->group_end();

        $this->db->or_group_start();
        // SKENARIO B: NON-SINGAPORE (Berhenti jika ATA / ETA POD sudah didapat)
            $this->db->where('x.destination !=', 'SINGAPORE');
            $this->db->where('x.region_id', 'Jakarta'); 
            
            // Kolom atd menyimpan JSON: "Actual Time of Arrival (ATA)"
            $this->db->group_start();
                $this->db->where('x.atd IS NULL', NULL, FALSE);
                $this->db->or_where("JSON_EXTRACT(x.atd, '$.\"Actual Time of Arrival (ATA)\"') = 'null'", NULL, FALSE);
                $this->db->or_where_in('x.lt_pod_eta_source', ['ASTRO_ETA_SIN','ASTRO_2ND_ETA']);
            $this->db->group_end();
        $this->db->group_end();

    $this->db->group_end();
    // ==============================================================

    if ($limit !== null) {
        $this->db->limit($limit);
    }

    return $this->db->get()->result_array();
}

public function get_target_cron_charterlink_singapore($limit = 10, $region_id = null, $transit_days = 90)
{
    // Tracking SG returns second-leg data, not direct-SG import delivery.
    return $this->ctl_target_rows('SINGAPORE',
        ['CTLSIN', 'CTL - SIN', 'CTL SIN'],
        max(1, (int) $limit), $region_id, $transit_days, false, false);
}
}