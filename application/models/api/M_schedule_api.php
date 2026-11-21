<?php
#[\AllowDynamicProperties]    
Class M_schedule_api extends CI_Model
{
    public function __construct() {
        parent::__construct();

        $tahun ='Y';

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);;
        }
    }
	public function not_found_response(){
    $response['status']=404;
    $response['error']=true;
    $response['message']='Data tidak ditemukan';
    return $response;
	}
	
	public function get_schedule_import($now,$month = null)
   {
	  
	  $query = $this->dbx->get_where('tbl_schedules_direct_import',['eta_jkt >=' => $now]);
	  //print_r($query->num_rows());
	  //die();
	  $query_via = $this->dbx->get_where('tbl_schedules_via_import',['eta_jkt >=' => $now]);
	  //print_r($this->dbx->last_query());
	 // die();
	  $books = array();
      $tempArray = array();
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved schedules import';
         foreach ($query->result() as $row) {
            $tempArray['vessel'] = $row->vessel;
            $tempArray['voyage'] = $row->voy;
            $tempArray['closing_date'] = $row->cut_off;
            $tempArray['etd'] = $row->etd;
            $tempArray['eta'] = $row->eta_jkt;
            $tempArray['origin_id'] = $row->city_id;
            $tempArray['origin_name'] = $row->city_id;
            $tempArray['region_id'] = $row->region_id;
            //$response['data'][] = $tempArray;
			array_push($books,$tempArray);	
         }
	  }
	  $detail = array();
	  $vias = array();
	  if ($query_via->num_rows() > 0) {
		foreach ($query_via->result() as $res) {
		$detail['vessel'] = $res->vessel;
		$detail['voy_vessel'] = $res->voy_vessel;
		$detail['stf_cls'] = $res->cut_off;
		$detail['etd'] = $res->etd;
		$detail['connecting_vessel'] = $res->eta_con;
		$detail['etd_city_con_id'] = $res->etd_city_con_id;
		$detail['etd_city_con_name'] = $this->getCityById($res->etd_city_con_id);
		$detail['eta'] = $res->eta_jkt;
		$detail['origin_id'] = $res->eta_city_id;
		$detail['origin_name'] = $this->getCityById($res->eta_city_id);
		$detail['region_id'] = $res->region_id;
		array_push($vias,$detail);
		}
	  }
	  if ($query_via->num_rows() > 0 || $query->num_rows() ) {
		$body = [
		
			"direct" => $books,
			"via" => $vias,
		];
		$response['data'][] = $body;
		return $response;
      } else {
      return $this->not_found_response();
      }
      return false;
   }
   
   public function search_schedule_import($city,$eta_jkt)
   {
	  $city = $this->getCityByName($city);
	  $query = $this->dbx->get_where('tbl_schedules_direct_import',['eta_jkt'=> $eta_jkt, 'city_id'=>$city,'region_id'=>'Jakarta']);
	 // print_r($this->dbx->last_query());
	  //die();
	  $query_via = $this->dbx->get_where('tbl_schedules_via_import',['eta_jkt'=> $eta_jkt, 'eta_city_id'=>$city,'region_id'=>'Jakarta']);
	  //print_r($this->dbx->last_query());
	  //die();
	  $books = array();
      $tempArray = array();
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved searched schedule';
         foreach ($query->result() as $row) {
			$tempArray['vessel'] = $row->vessel;
            $tempArray['voyage'] = $row->voy;
            $tempArray['closing_date'] = $row->cut_off;
            $tempArray['etd'] = $row->etd;
            $tempArray['eta'] = $row->eta_jkt;
            $tempArray['origin_id'] = $row->city_id;
            $tempArray['origin_name'] = $this->getCityById($row->city_id);
            //$response['data'][] = $tempArray;
			array_push($books,$tempArray);	
         }
	  }
	  $detail = array();
	  $vias = array();
	  if ($query_via->num_rows() > 0) {
		foreach ($query_via->result() as $res) {
		$detail['vessel'] = $res->vessel;
		$detail['voy_vessel'] = $res->voy_vessel;
		$detail['stf_cls'] = $res->cut_off;
		$detail['etd'] = $res->etd;
		$detail['connecting_vessel'] = $res->eta_con;
		$detail['etd_city_con_id'] = $res->etd_city_con_id;
		$detail['etd_city_con_name'] = $this->getCityById($res->etd_city_con_id);
		$detail['eta'] = $res->eta_jkt;
		$detail['origin_id'] = $res->eta_city_id;
		$detail['origin_name'] = $this->getCityById($res->eta_city_id);
		array_push($vias,$detail);
		}
	  }
	 // print_r($query);
	  //die();
	 if($books||$vias)
	 {
		$body = [
		
			"direct" => $books,
			"via" => $vias,
		];
		$response['data'][] = $body;
      return $response;
      } else {
      return $this->not_found_response();
      }
      return false;
   }
	
    public function get_schedule($now,$month = null)
   {
	  $this->dbx->order_by('city_id','asc');
	  $query = $this->dbx->get_where('tbl_schedules_direct',['etd_jkt >=' => $now]);
	  //print_r($this->dbx->last_query());
	  //die();
	  $this->dbx->order_by('etd_jkt','asc');
	  $query_via = $this->dbx->get_where('tbl_schedules_via',['etd_jkt >=' => $now]);
	  $this->dbx->order_by('etd_jkt','asc');
	  //print_r($this->dbx->last_query());
	 // die();
	  $books = array();
      $tempArray = array();
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved schedules export';
         foreach ($query->result() as $row) {
            $tempArray['carrier'] = $row->carrier;
            $tempArray['vessel'] = $row->vessel;
            $tempArray['voyage'] = $row->voy;
            $tempArray['closing_date'] = $row->stf_cls;
            $tempArray['stuffing_date'] = $row->stuffing_date;
            $tempArray['stuffing_location'] = $row->stuffing_location;
            $tempArray['etd'] = $row->etd_jkt;
            $tempArray['eta'] = $row->eta;
            $tempArray['destination_id'] = $row->city_id;
            $tempArray['destination_name'] = $row->city_id;
            $tempArray['origin_name'] = strtoupper($row->region_id);
			array_push($books,$tempArray);	
         }
	  }
	  $detail = array();
	  $vias = array();
	  if ($query_via->num_rows() > 0) {
		foreach ($query_via->result() as $res) {
		$detail['carrier'] = $res->liner_id;
		$detail['vessel'] = $res->vessel;
		$detail['voy_vessel'] = $res->voy_vessel;
		$detail['stf_cls'] = $res->stf_cls;
		$detail['stuffing_date'] = $res->stuffing_date;
		$detail['stuffing_location'] = $res->stuffing_location;
		$detail['etd_jkt'] = $res->etd_jkt;
		$detail['connecting_vessel'] = $res->connecting;
		$detail['voy_con'] = $res->voy_con;
		$detail['etd_con'] = $res->etd_con;
		$detail['etd_city_con_id'] = $res->etd_city_con_id;
		$detail['etd_city_con_name'] = $res->etd_city_con_id;
		$detail['eta'] = $res->eta;
		$detail['destination_id'] = $res->eta_city_id;
		$detail['destination_name'] = $res->eta_city_id;
		$detail['origin_name'] = strtoupper($res->region_id);
		array_push($vias,$detail);
		}
		}
		$body = [
		
			"direct" => $books,
			"via" => $vias,
		];
		$response['data'][] = $body;
      return $response;
   }
   
   public function search_schedule($city,$etd_jkt)
   {
	  if(empty($etd_jkt))
	  {
		$query = $this->dbx->get_where('tbl_schedules_direct',['city_id'=>$city]);
		$query_via = $this->dbx->get_where('tbl_schedules_via',['eta_city_id'=>$city]);
	  }else{
		 $query = $this->dbx->get_where('tbl_schedules_direct',['etd_jkt'=> $etd_jkt, 'city_id'=>$city]); 
		 $query_via = $this->dbx->get_where('tbl_schedules_via',['etd_jkt'=> $etd_jkt, 'eta_city_id'=>$city]);
	  }
	  //print_r($this->dbx->last_query());
	  //die();
	  $books = array();
      $tempArray = array();
      if ($query->num_rows() > 0) {
         $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved searched schedule';
         foreach ($query->result() as $row) {
            $tempArray['vessel'] = $row->vessel;
            $tempArray['voyage'] = $row->voy;
            $tempArray['closing_date'] = $row->stf_cls;
            $tempArray['etd'] = $row->etd_jkt;
            $tempArray['eta'] = $row->eta;
            $tempArray['destination_id'] = $row->city_id;
            $tempArray['destination_name'] = $row->city_id;
			$tempArray['origin_name'] = strtoupper($row->region_id);
            $tempArray['id'] = $row->id;
            $tempArray['warehouse'] = $row->stuffing_location??'GCL WAREHOUSE';
			array_push($books,$tempArray);	
         }
	  }
	  $detail = array();
	  $vias = array();
	  if ($query_via->num_rows() > 0) {
		 $response = array();
         $response['error'] = false;
         $response['message'] = 'Successfully retrieved searched schedule';
		foreach ($query_via->result() as $res) {
		$detail['vessel'] = $res->vessel;
		$detail['voy_vessel'] = $res->voy_vessel;
		$detail['stf_cls'] = $res->stf_cls;
		$detail['etd_jkt'] = $res->etd_jkt;
		$detail['connecting_vessel'] = $res->connecting;
		$detail['voy_con'] = $res->voy_con;
		$detail['etd_con'] = $res->etd_con;
		$detail['etd_city_con_id'] = $res->etd_city_con_id;
		$detail['etd_city_con_name'] = $res->etd_city_con_id;
		$detail['eta'] = $res->eta;
		$detail['destination_id'] = $res->eta_city_id;
		$detail['destination_name'] = $res->eta_city_id;
		$detail['origin_name'] = strtoupper($res->region_id);
		$detail['id'] = $res->id;
		$detail['warehouse'] = $res->stuffing_location??'GCL WAREHOUSE';
		array_push($vias,$detail);
		}
	  }
	  //print_r($this->dbx->last_query());
	  //die();
	 if($books||$vias)
	 {
		$body = [
		
			"direct" => $books,
			"via" => $vias,
		];
		$response['data'][] = $body;
      return $response;
      } else {
      return $this->not_found_response();
      }
      return false;
   }
   
    function getCityById($id)
    {
        $this->dbx->select('city_name');
        $this->dbx->from('mstcities');
        $this->dbx->where('id_city', $id);
        $query = $this->dbx->get();
        return $query->row('city_name');
    }
	 function getCityByName($city)
    {
        $this->dbx->select('id_city');
        $this->dbx->from('mstcities');
        $this->dbx->where('city_name', $city);
        $query = $this->dbx->get();
        return $query->row('id_city');
    }
}
?>