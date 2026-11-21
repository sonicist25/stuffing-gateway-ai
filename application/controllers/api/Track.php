<?php
use chriskacerguis\RestServer\RestController;
 
class Track extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api'));
    }
     function datetime_en($datetime)
        {
         if (!empty($datetime)) {
          list($date, $time) = explode("T", $datetime);
          $result = $this->date_en($date) . " " . $this->time_en($time);
          return $result;
         }else{
            return false; 
         }
        }

     function month_name($month)
        {
          $m_name = array("01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Aug", "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
          return $m_name[$month];
        }

    function date_en($date)
        {
          $date_en = '';
          if (!empty($date)) {
            $tgl = substr($date, 8, 2);
            $bln = substr($date, 5, 2);
            $thn = substr($date, 0, 4);
            $date_en = $this->month_name($bln) . " $tgl".", $thn";
          }
          return $date_en;
        }

    function time_en($date)
        {
          $time_en = '';
          if (!empty($date)) {
            list($h, $m, $s) = explode(":", $date);
            $time_en = $h . ":" . $m;
          }
          return $time_en;
        }
	
	function index_get()
	{
		$bill_of_lading_bookmark_id = $this->m_api->get_id_tracking($this->get('si_number'),$this->get('shipper'))->row();
		//print_r($this->dbx->last_query());
		if(!empty($bill_of_lading_bookmark_id)){
		$tracking_id = $bill_of_lading_bookmark_id->tracking_id;
		}else{
		$tracking_id = "1";	
		}
		$client = new \GuzzleHttp\Client();
		//print_r($tracking_id);
try{
$response = $client->request('GET', 'https://api.portcast.io/api/v2/eta/tracking/bill-of-lading-bookmarks'.'/'.$tracking_id, [
  'headers' => [
    'Accept' => 'application/json',
    'x-api-key' => 'PC25B0ADAAA1412B4615BAC49345E576F7',
  ],
]);
	$data['hasil_lokal'] = $this->m_api->get_x_tracking_export($this->get('si_number'));
	$data['do_number']=$this->m_api->get_do_number($this->get('si_number'))->row('do_number');
	$data['hasil_lokal_import'] = $this->m_api->get_x_tracking_import($this->get('si_number'));
	$data['hasil'] = json_decode($response->getBody());
	$items = [];
	$header = [];
        if (!empty($data['hasil'])) {
          foreach ($data['hasil'] as $i => $r) {
			  $item['status'] = $r->bill_of_lading_bookmark->status;
			  if ( $item['status'] != "NO_DATA_RECEIVED_FROM_CARRIER" && $item['status'] != "INIT"){
				    //print_r($r->bill_of_lading_bookmark->status);
					$item['scac'] = $r->bill_of_lading_bookmark->carrier_no;
					$item['container no'] = $r->bill_of_lading_bookmark->cntr_no;
					$item['last update'] = $this->datetime_en($r->bill_of_lading_bookmark->updated);
					$item['delay'] ="";
					$item['arrived'] ="";
					if (!empty($r->bill_of_lading->pod_actual_arrival_lt_from_ais)||!empty($r->bill_of_lading->pod_actual_arrival_lt)){
					$item['arrived']= 'Arrived';
					}else{
					$item['arrived']= 'On Journey';	
					}
					if (!empty($r->bill_of_lading->pod_scheduled_arrival_lt_first_seen)){
					$datetime1 = new DateTime($r->bill_of_lading->pod_scheduled_arrival_lt_first_seen);
					$datetime2 = new DateTime($r->bill_of_lading->pod_predicted_arrival_lt ?? '');

					// Subtract datetime2 from datetime1
					$interval = $datetime1->diff($datetime2);
					if ( $interval->format('%d') == 0){
					$item['delay'] = 'On time';
					}else{
					$item['delay'] = $interval->format('%d days delay');
					}
					}else{
					$item['delay'] ="On Transit";	
					}
					array_push($header, [$item]);
					if(!empty($r->sailing_info_tracking)){
					foreach ($r->sailing_info_tracking as $r3) {
						if(is_null($r3->sailing_info->pol_actual_departure_lt)) {$departure = isset($r3->sailing_info->pol_predicted_departure_lt)? $this->datetime_en($r3->sailing_info->pol_predicted_departure_lt)." (Predicted)" : $this->datetime_en($r3->sailing_info->pol_scheduled_departure_lt)."(Scheduled)" ;} else {$departure = $this->datetime_en($r3->sailing_info->pol_actual_departure_lt);}	
						if(is_null($r3->sailing_info->pod_actual_arrival_lt)) {$arrival = isset($r3->sailing_info->pod_predicted_departure_lt) ? $this->datetime_en($r3->sailing_info->pod_predicted_arrival_lt)." (Predicted)" : $this->datetime_en($r3->sailing_info->pod_scheduled_arrival_lt)." (Scheduled)";} else { $arrival=$this->datetime_en($r3->sailing_info->pod_actual_arrival_lt);} 
						$details['port of loading']= $r3->sailing_info->pol_name;
						$details['time of departure']= $departure;
						if(is_null($r3->sailing_info->pod_name)) {$details['port of discharge']= $r3->sailing_info->pod;}else{$details['port of discharge']= $r3->sailing_info->pod_name;}
						$details['time of discharge']= $arrival;
						$details['vessel']=  $r3->sailing_info->vessel_name." ".$r3->sailing_info->voyage_no;
						$details['container']=  $item['container no'];
						array_push($items, $details);
					} 
				}
		$events = [];
		$steppers = [];
		$evenlocal = [];
		
		if ($data['hasil_lokal']->num_rows() > 0) {
                    foreach ($data['hasil_lokal']->result() as $row =>$r4)
                        {
                        $departur_transit_city = json_decode($r4->dft,true);
                    foreach ($departur_transit_city as $key => $val) {
                        if(!empty($departur_transit_city['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_transit_city['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
						if(!empty($departur_transit_city['Container No'])){
                        $container_no= $departur_transit_city['Container No'];
                        }else{
                        $container_no= $headArray['container no'];
                        }
                    }
                    $arrival_city = json_decode($r4->atd,true);
                    foreach ($arrival_city as $key => $val) {
                        if(!empty($arrival_city['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_city['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
                               }
                            }
				 $json = json_decode($r4->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r4->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
  
        if(!empty($departur_transit_city) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_transit_time)):
				  $tempArray['port of loading'] =  $r4->connecting;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  $tempArray['port of discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
				  $tempArray['container'] =  $container_no;
				  array_push($items, $tempArray);
				   endif;
            endif; 
        endif; 
     
                    }
            }
		if (!empty($data['hasil'])) {
            $arr = array();
            foreach ($data['hasil'] as $i => $r) {
             if(!empty($r->container_event_list)){
              foreach ($r->container_event_list as $i => $r2) {
                  if(!isset($arr[$r2->port_name]))
                  {
                      $arr[$r2->port_name] = array();
                  }

                  $arr[$r2->port_name][] = $r2;
              }
            }

            $no = 1;
            foreach ($arr as $i => $r) {
                 if (count($arr)>2){
                  if($no==1){
                    $stepper['location']= $i ;
                    $stepper['location type'] = $r[0]->location_type_name;
                     if (isset($r[0]->event_type_name)){
                    $stepper['event name']=$r[0]->event_type_name;
                     }
                     if (isset($r[0]->event_time)){
                    $stepper['event time']= $this->datetime_en($r[0]->event_time);
                    if (isset($r[2]->event_type_name)){
                    $stepper['next event name']= $r[2]->event_type_name;
                    $stepper['next event time']= is_null($r[2]->event_time)? $this->datetime_en($r[2]->event_time_estimated).'*' :$this->datetime_en($r[2]->event_time);}
                     }
                  }
                  else{
                   $stepper['location'] =$i ;
                   $stepper['location type'] = $r[0]->location_type_name;
                   $stepper['event name']= $r[0]->event_type_name;
                     if (isset($r[0]->event_time)){
                    $stepper['event time']= $this->datetime_en($r[0]->event_time);
                    /* $stepper['next event name'] = $r[1]->event_type_name;
                    $stepper['next event time']=is_null($r[1]->event_time)? $this->datetime_en($r[1]->event_time_estimated).'*' : $this->datetime_en($r[1]->event_time); */}
                  }
                 }else{
                    $stepper['location']= $i;
                    $stepper['location type'] = $r[0]->location_type_name;
                    $stepper['event name']= $r[0]->event_type_name;
                    $stepper['event time'] = is_null($r[0]->event_time)?$this->datetime_en($r[0]->event_time_estimated).'*' : $this->datetime_en($r[0]->event_time);
                 }
              $no++;
			  array_push($steppers, $stepper);
             }   
            }
          }
		    if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
					$departur_trans = json_decode($r3->dft,true);
                    foreach ($departur_trans as $key => $val) {
                        if(!empty($departur_trans['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_trans['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
					}
					$arrival = json_decode($r3->atd,true);
                    foreach ($arrival as $key => $val) {
                        if(!empty($arrival['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
                               }
                    }
					$json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
            if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $stepperlocal["location"]= $r3->connecting; 
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif; 
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
                  $stepperlocal["location"]= $r3->destination; 
                  $stepperlocal["location type"]= "Port of destination";
				  $stepperlocal["event name"]= "Arrival"; 
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif; 
             
            }
          }
		if (!empty($data['hasil'])) {  
		foreach ($data['hasil'] as $i => $r5) {
			if(!empty($r5->container_event_list)){
                foreach ($r5->container_event_list as $i => $r6) {
                 $eventlist['event name'] =$r6->event_raw;
                 $eventlist['port name'] =$r6->port_name;
                 $eventlist['location name'] =$r6->location_type_name;
                 $eventlist['event time'] =is_null($r6->event_time)? $this->datetime_en($r6->event_time_estimated)."*" : $this->datetime_en($r6->event_time);
				 array_push($events, $eventlist);
				}
			}
			 if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
                    $json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
            if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $evenlocal["event name"]= "Departure from transipment port"; 
                  $evenlocal["port name"]= $r3->connecting; 
                  $evenlocal["location name"]= "Port of discharge";
                  $evenlocal["event time"]= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))."*";
				  array_push($events, $evenlocal);
			endif; 
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
				  $evenlocal["event name"]= "Arrival at final port of destination"; 
                  $evenlocal["port name"]= $r3->destination; 
                  $evenlocal["location name"]= "Port of destination";
                  $evenlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). "*" ;
				  array_push($events, $evenlocal);
			endif; 
            if($pickup_do): 
				$evenlocal["event name"]= "Pickup DO"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($pickup_do));
				array_push($events, $evenlocal);
            endif;
           if($cargo_delivery):       
                $evenlocal["event name"]= "Release Cargo"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($cargo_delivery));
				array_push($events, $evenlocal);
           endif;
                        }
                   }
               if($data['hasil_lokal_import']->num_rows() > 0){
                    foreach ($data['hasil_lokal_import']->result() as $row)
                        {
							 if(!empty($row->pickup_do)){
                             $date = DateTime::createFromFormat('d/m/Y', $row->pickup_do);
                             $cargo_pickup_import= $date->format('Y-m-d');
                             }else{
                             $cargo_pickup_import="";
                             }
                              if(!empty($row->pickup_cargo)){
                             $date1 = DateTime::createFromFormat('d/m/Y', $row->pickup_cargo);
                             $cargo_release_import= $date1->format('Y-m-d');
                             }else{
                             $cargo_release_import="";
                             }
							if(!empty($row->pickup_do)){
							$evenlocal["event name"]= "Pickup DO"; 
							$evenlocal["port name"]= 'TG. PRIOK, JAKARTA'; 
							$evenlocal["location name"]= "Port of destination";
							$evenlocal["event time"]=date('M j, Y H:i:s',strtotime($cargo_pickup_import));
							array_push($events,$evenlocal);
							}
							if(!empty($row->pickup_cargo)){
							$evenlocal["event name"]= "Release Cargo"; 
							$evenlocal["port name"]= 'TG. PRIOK, JAKARTA'; 
							$evenlocal["location name"]= "Port of destination";
							$evenlocal["event time"]= date('M j, Y H:i:s',strtotime($cargo_release_import));
							array_push($events,$evenlocal);
                            }	
								}
							}
						}
					}
                }else{
			$data['hasil_lokal'] = $this->m_api->get_x_tracking_export($this->get('si_number'));
			$data['hasil_lokal_import'] = $this->m_api->get_x_tracking_import($this->get('si_number'));
			$items = [];
			$header = [];
			$steppers = [];
			$events = [];
	       if ($data['hasil_lokal']->num_rows() > 0):
		   foreach ($data['hasil_lokal']->result() as $row =>$r1)
                        {
				  $headArray['hbl'] =  $r1->hbl_no;
				  $headArray['scac'] =  substr($r1->mbl_no,0,4);
				  $headArray['container no'] =  $r1->container_no;
                  $headArray['status']= "SUCCESS";
                  $headArray['last update'] = $r1->updated_at != "0000-00-00 00:00:00" ? $r1->updated_at : date("Y-m-d H:i:s");
				  $headArray['delay'] ="On Time";
				  $headArray['arrived'] =""; 
				  $arrival_city = json_decode($r1->atd,true);
				  $date = DateTime::createFromFormat('d/m/Y', $arrival_city['Actual Time of Arrival (ATA)']);
				  if($date){
                  $arrival_des_time= $date->format('Y-M-d H:i:s');
				  }else{
				  $arrival_des_time= "";
				  }
					if( date('Ymd',strtotime($arrival_des_time)) <= date('Ymd')){
					$headArray['arrived']= 'Arrived';
					}else{
					$headArray['arrived']= 'On Journey';	
					}			  
				  array_push($header, [$headArray]);
						}
            endif; 
			
		if ($data['hasil_lokal']->num_rows() > 0) {
                    foreach ($data['hasil_lokal']->result() as $row =>$r4)
                        {
					 $departur_pol = json_decode($r4->dpol,true);
                    foreach ($departur_pol as $key => $val) {
                        if(!empty($departur_pol['Actual Time of Departure (ATD)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_pol['Actual Time of Departure (ATD)']);
                        $departur_from_pol= $date->format('Y-M-d H:i:s');
						 if(!empty($departur_pol['Vessel Name'])){
                        $feeder_vessel=$departur_pol['Vessel Name'];
                        }
                        }else{
                        $departur_from_pol= "";
                        }
                    }
					$arrival_trans = json_decode($r4->atp,true);
                    foreach ($arrival_trans  as $key => $val) {
                        if(!empty($arrival_trans ['Arrival at Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_trans['Arrival at Transhipment Port']);
                        $arrival_trans_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $arrival_trans_time= "";
                        }
                    }
                        $departur_transit_city = json_decode($r4->dft,true);
                    foreach ($departur_transit_city as $key => $val) {
                        if(!empty($departur_transit_city['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_transit_city['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
                    }
                    $arrival_city = json_decode($r4->atd,true);
                    foreach ($arrival_city as $key => $val) {
                        if(!empty($arrival_city['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_city['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
						$vessel="";
                               }
                            }
				 $json = json_decode($r4->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r4->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
		if(!empty($r4->connecting) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  $tempArray['port of discharge'] =  $r4->connecting;
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] =  $headArray['container no'];
				  array_push($items, $tempArray);
				   endif;
            endif; 
			if($r4->destination == $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  if(!empty($tempArray['port of discharge'])){$tempArray['port of discharge'] =  $r4->connecting;}else{$tempArray['port of discharge'] =  $r4->connecting;}
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] =  $headArray['container no'];
				  array_push($items, $tempArray);
				   endif;
            endif;	
        else:
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] =  $headArray['container no'];
				  array_push($items, $tempArray);
				   endif;
         endif;
        
  
        if(!empty($r4->connecting) && $r4->destination != $r4->connecting):
				  $tempArray['port of loading'] =  $r4->connecting;
				  if(!empty($departur_transit_time)):
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
				  else:
				  $tempArray['time of departure']="To Be Announced";
				  endif;
				  if(!empty($arrival_des_time)):
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  else:
				  $tempArray['time of discharge']="To Be Announced";
				  endif;
				  $tempArray['port of discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
				  $tempArray['container'] =  $headArray['container no'];
				  array_push($items, $tempArray);
            endif; 
                    }
		
		    if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
					 $departur_pol = json_decode($r3->dpol,true);
                    foreach ($departur_pol as $key => $val) {
                        if(!empty($departur_pol['Actual Time of Departure (ATD)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_pol['Actual Time of Departure (ATD)']);
                        $departur_from_pol= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_from_pol= "";
                        }
                    }
					$arrival_trans = json_decode($r3->atp,true);
                    foreach ($arrival_trans  as $key => $val) {
                        if(!empty($arrival_trans ['Arrival at Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_trans['Arrival at Transhipment Port']);
                        $arrival_trans_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $arrival_trans_time= "";
                        }
                    }
					$departur_trans = json_decode($r3->dft,true);
                    foreach ($departur_trans as $key => $val) {
                        if(!empty($departur_trans['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_trans['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
					}
					$arrival = json_decode($r3->atd,true);
                    foreach ($arrival as $key => $val) {
                        if(!empty($arrival['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
                               }
                    }
					$json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $stepperlocal["location"]= $r3->region_id; 
                  $stepperlocal["location type"]= "Port of loading";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_from_pol)) : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $stepperlocal["location"]=  $r3->connecting;
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Arrival";
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $stepperlocal["location"]= $r3->connecting; 
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif;  
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
                  $stepperlocal["location"]= $r3->destination; 
                  $stepperlocal["location type"]= "Port of destination";
				  $stepperlocal["event name"]= "Arrival"; 
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif; 
             
            }
          }
			 if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
                    $json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $evenlocal["event name"]= "Departure from origin port"; 
                  $evenlocal["port name"]= $r3->region_id; 
                  $evenlocal["location name"]= "Port of loading";
                  $evenlocal["event time"]= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($$departur_from_pol))."*";
				  array_push($events, $evenlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $evenlocal["event name"]= "Arrival at transipment port"; 
                  $evenlocal["port name"]= $r3->connecting;
                  $evenlocal["location name"]= "Port of discharge";
                  $evenlocal["event time"]= date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) : date('M j, Y H:i',strtotime($departur_from_pol))."*";
				  array_push($events, $evenlocal);
			endif;
			
            if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $evenlocal["event name"]= "Departure from transipment port"; 
                  $evenlocal["port name"]= $r3->connecting; 
                  $evenlocal["location name"]= "Port of discharge";
                  $evenlocal["event time"]= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))."*";
				  array_push($events, $evenlocal);
			endif; 
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
				  $evenlocal["event name"]= "Arrival at final port of destination"; 
                  $evenlocal["port name"]= $r3->destination; 
                  $evenlocal["location name"]= "Port of destination";
                  $evenlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). "*" ;
				  array_push($events, $evenlocal);
			endif; 
            if($pickup_do): 
				$evenlocal["event name"]= "Pickup DO"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($pickup_do));
				array_push($events, $evenlocal);
            endif;
           if($cargo_delivery):       
                $evenlocal["event name"]= "Release Cargo"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($cargo_delivery));
				array_push($events, $evenlocal);
           endif;
							}
						}
					}
				}
			
			}
        }else{
			$items=[];
			$events=[];
        }
	
	$body =[
	"header" => $header,
	"routing"=>$items,
	"stepper"=>$steppers,
	"event list"=>$events,
	"do_number"=>$data['do_number']
	];
	header('Content-Type: application/json');
	$this->response($body);
		
	}
	catch(Exception $ex){
	$data['hasil_lokal'] = $this->m_api->get_x_tracking_export($this->get('si_number'));
	$data['hasil_lokal_import'] = $this->m_api->get_x_tracking_import($this->get('si_number'));
	$items = [];
	$header = [];
	$steppers = [];
	$events = [];
	$evenlocal = [];
	       if ($data['hasil_lokal']->num_rows() > 0):
		   foreach ($data['hasil_lokal']->result() as $row =>$r1)
                        {
				  $headArray['hbl'] =  $r1->hbl_no;
				  $headArray['scac'] =  substr($r1->mbl_no,0,4);
				  $headArray['container no'] =  $r1->container_no;
                  $headArray['status']= "SUCCESS";
                  $headArray['last update'] = $r1->updated_at ;
				  $headArray['delay'] ="On Time";
				  $headArray['arrived'] =""; 
				  $arrival_city = json_decode($r1->atd,true);
				  $date = DateTime::createFromFormat('d/m/Y', $arrival_city['Actual Time of Arrival (ATA)']);
                  $arrival_des_time= $date->format('Y-M-d H:i:s');
					if( date('Ymd',strtotime($arrival_des_time)) <= date('Ymd')){
					$headArray['arrived']= 'Arrived';
					}else{
					$headArray['arrived']= 'On Journey';	
					} 
				  array_push($header, [$headArray]);
						}
            endif; 
			
		if ($data['hasil_lokal']->num_rows() > 0) {
                    foreach ($data['hasil_lokal']->result() as $row =>$r4)
                        {
					 $departur_pol = json_decode($r4->dpol,true);
                    foreach ($departur_pol as $key => $val) {
                        if(!empty($departur_pol['Actual Time of Departure (ATD)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_pol['Actual Time of Departure (ATD)']);
                        $departur_from_pol= $date->format('Y-M-d H:i:s');
						 if(!empty($departur_pol['Vessel Name'])){
                        $feeder_vessel=$departur_pol['Vessel Name'];
                        }
                        }else{
                        $departur_from_pol= "";
                        }
                    }
					$arrival_trans = json_decode($r4->atp,true);
                    foreach ($arrival_trans  as $key => $val) {
                        if(!empty($arrival_trans ['Arrival at Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_trans['Arrival at Transhipment Port']);
                        $arrival_trans_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $arrival_trans_time= "";
                        }
                    }
                        $departur_transit_city = json_decode($r4->dft,true);
                    foreach ($departur_transit_city as $key => $val) {
                        if(!empty($departur_transit_city['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_transit_city['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
						if(!empty($departur_transit_city['Container No'])){
                        $container_no= $departur_transit_city['Container No'];
                        }else{
                        $container_no= $headArray['container no'];
                        }
                    }
                    $arrival_city = json_decode($r4->atd,true);
                    foreach ($arrival_city as $key => $val) {
                        if(!empty($arrival_city['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_city['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
                               }
                            }
				 $json = json_decode($r4->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r4->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
		if(!empty($r4->connecting) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  $tempArray['port of discharge'] =  $r4->connecting;
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] = $container_no;
				  array_push($items, $tempArray);
				   endif;
            endif; 
			if($r4->destination == $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  if(!empty($tempArray['port of discharge'])){$tempArray['port of discharge'] =  $r4->connecting;}else{$tempArray['port of discharge'] =  '';}
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] = $headArray['container no'];
				  array_push($items, $tempArray);
				   endif;
            endif;	
        else:
				  if(!empty($departur_from_pol)):
				  $tempArray['port of loading'] =  $r4->region_id;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
				  $tempArray['vessel'] =  $feeder_vessel;
				  $tempArray['container'] =  $headArray['container no'];
				  array_push($items, $tempArray);
				   endif;
         endif;
        
  
        if(!empty($departur_transit_city) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_transit_time)):
				  $tempArray['port of loading'] =  $r4->connecting;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  $tempArray['port of discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
				  $tempArray['container'] = $container_no;
				  array_push($items, $tempArray);
				   endif;
            endif; 
			 if($r4->destination == $r4->connecting ):
				  if(!empty($departur_transit_time)):
				  $tempArray['port of loading'] =  $r4->connecting;
                  $tempArray['time of departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
                  $tempArray['time of discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  $tempArray['port of discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
				  $tempArray['container'] = $headArray['container no']; 
				  array_push($items, $tempArray);
				   endif;
            endif;
        endif; 
     
                    }
		
		    if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
					 $departur_pol = json_decode($r3->dpol,true);
                    foreach ($departur_pol as $key => $val) {
                        if(!empty($departur_pol['Actual Time of Departure (ATD)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_pol['Actual Time of Departure (ATD)']);
                        $departur_from_pol= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_from_pol= "";
                        }
                    }
					$arrival_trans = json_decode($r3->atp,true);
                    foreach ($arrival_trans  as $key => $val) {
                        if(!empty($arrival_trans ['Arrival at Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival_trans['Arrival at Transhipment Port']);
                        $arrival_trans_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $arrival_trans_time= "";
                        }
                    }
					$departur_trans = json_decode($r3->dft,true);
                    foreach ($departur_trans as $key => $val) {
                        if(!empty($departur_trans['Departure from Transhipment Port'])){
                        $date = DateTime::createFromFormat('d/m/Y', $departur_trans['Departure from Transhipment Port']);
                        $departur_transit_time= $date->format('Y-M-d H:i:s');
                        }else{
                        $departur_transit_time= "";
                        }
					}
					$arrival = json_decode($r3->atd,true);
                    foreach ($arrival as $key => $val) {
                        if(!empty($arrival['Actual Time of Arrival (ATA)'])){
                        $date = DateTime::createFromFormat('d/m/Y', $arrival['Actual Time of Arrival (ATA)']);
                        $arrival_des_time= $date->format('Y-M-d H:i:s');
                        if(!empty($arrival_city['Vessel/Voyage'])){
                        $vessel=$arrival_city['Vessel/Voyage'];
                        }
                        }else{
                        $arrival_des_time= "";
                               }
                    }
					$json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $stepperlocal["location"]= $r3->region_id; 
                  $stepperlocal["location type"]= "Port of loading";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_from_pol)) : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $stepperlocal["location"]=  $r3->connecting;
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Arrival";
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $stepperlocal["location"]= $r3->connecting; 
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif;  
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
                  $stepperlocal["location"]= $r3->destination; 
                  $stepperlocal["location type"]= "Port of destination";
				  $stepperlocal["event name"]= "Arrival"; 
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) : "";
				  array_push($steppers, $stepperlocal);
			endif; 
             
            }
          }
			 if(!empty($data['hasil_lokal'])){
                    foreach ($data['hasil_lokal']->result() as $row =>$r3)
                        {
                    $json = json_decode($r3->pickup_do,true);
                    foreach ($json as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $json['Pickup DO']);
                        $pickup_do= $date->format('Y-M-d H:i:s');
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = DateTime::createFromFormat('d/m/Y', $val);
                        $cargo_delivery= $date->format('Y-M-d H:i:s');
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $evenlocal["event name"]= "Departure from origin port"; 
                  $evenlocal["port name"]= $r3->region_id; 
                  $evenlocal["location name"]= "Port of loading";
                  $evenlocal["event time"]= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($$departur_from_pol))."*";
				  array_push($events, $evenlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $evenlocal["event name"]= "Arrival at transipment port"; 
                  $evenlocal["port name"]= $r3->connecting;
                  $evenlocal["location name"]= "Port of discharge";
                  $evenlocal["event time"]= date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) : date('M j, Y H:i',strtotime($$departur_from_pol))."*";
				  array_push($events, $evenlocal);
			endif;
			
            if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $evenlocal["event name"]= "Departure from transipment port"; 
                  $evenlocal["port name"]= $r3->connecting; 
                  $evenlocal["location name"]= "Port of discharge";
                  $evenlocal["event time"]= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))."*";
				  array_push($events, $evenlocal);
			endif; 
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
				  $evenlocal["event name"]= "Arrival at final port of destination"; 
                  $evenlocal["port name"]= $r3->destination; 
                  $evenlocal["location name"]= "Port of destination";
                  $evenlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). "*" ;
				  array_push($events, $evenlocal);
			endif; 
            if($pickup_do): 
				$evenlocal["event name"]= "Pickup DO"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($pickup_do));
				array_push($events, $evenlocal);
            endif;
           if($cargo_delivery):       
                $evenlocal["event name"]= "Release Cargo"; 
                $evenlocal["port name"]= $r3->destination; 
                $evenlocal["location name"]= "Port of destination";
                $evenlocal["event time"]=date('M j, Y H:i:s',strtotime($cargo_delivery));
				array_push($events, $evenlocal);
           endif;
                        }
                   }
              }else{
				  if($data['hasil_lokal_import']->num_rows() > 0){
                    foreach ($data['hasil_lokal_import']->result() as $row)
                        {
							 if(!empty($row->pickup_do)){
                             $date = DateTime::createFromFormat('d/m/Y', $row->pickup_do);
                             $cargo_pickup_import= $date->format('Y-m-d');
                             }else{
                             $cargo_pickup_import="";
                             }
                              if(!empty($row->pickup_cargo)){
                             $date1 = DateTime::createFromFormat('d/m/Y', $row->pickup_cargo);
                             $cargo_release_import= $date1->format('Y-m-d');
                             }else{
                             $cargo_release_import="";
                             }
							if(!empty($row->pickup_do)){
							$evenlocal["event name"]= "Pickup DO"; 
							$evenlocal["port name"]= $row->destination; 
							$evenlocal["location name"]= "Port of destination";
							$evenlocal["event time"]=$row->pickup_do;
							}
							if(!empty($row->pickup_cargo)){
							$evenlocal["event name"]= "Release Cargo"; 
							$evenlocal["port name"]= $row->destination; 
							$evenlocal["location name"]= "Port of destination";
							$evenlocal["event time"]=$row->pickup_cargo;
                            }	
							array_push($events,$evenlocal);
								}
							}
			$events[]=$evenlocal;
            }
	
	$body =[
	"header" => $header,
	"routing"=>$items,
	"stepper"=>$steppers,
	"event list"=>$events
	];
	header('Content-Type: application/json');
	$this->response($body);
		
		}
	}
}
?>