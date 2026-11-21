<?php
use chriskacerguis\RestServer\RestController;
 
class Track_local extends RestController{
     
    public function __construct(){
        parent::__construct();
        $this->load->model(array('api/m_api'));
        $this->dbx = $this->m_api->dbx;
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
                  $headArray['status']= "SUCCESS";
                  $headArray['last_update'] = $r1->updated_at ;
				  array_push($header, $headArray);
						}
            endif; 
			
		if ($data['hasil_lokal']->num_rows() > 0) {
			 $feeder_vessel='';
			 $vessel='';
                    foreach ($data['hasil_lokal']->result() as $row =>$r4)
                        {
					 $departur_pol = json_decode($r4->dpol,true);
                    foreach ($departur_pol as $key => $val) {
                        if(!empty($departur_pol['Actual Time of Departure (ATD)'])){
                        $departur_from_pol= $departur_pol['Actual Time of Departure (ATD)'];
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
                        $arrival_trans_time= $arrival_trans['Arrival at Transhipment Port'];
                        }else{
                        $arrival_trans_time= "";
                        }
                    }
                        $departur_transit_city = json_decode($r4->dft,true);
                    foreach ($departur_transit_city as $key => $val) {
                        if(!empty($departur_transit_city['Departure from Transhipment Port'])){
                        $departur_transit_time= $departur_transit_city['Departure from Transhipment Port'];
                        }else{
                        $departur_transit_time= "";
                        }
                    }
                    $arrival_city = json_decode($r4->atd,true);
                    foreach ($arrival_city as $key => $val) {
                        if(!empty($arrival_city['Actual Time of Arrival (ATA)'])){
                        $arrival_des_time= $arrival_city['Actual Time of Arrival (ATA)'];
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
                        $pickup_do= $json['Pickup DO'];
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r4->cargo_delivery,true);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $cargo_delivery= $val;
                        }else{
                        $cargo_delivery= "";
                        }
                    }
		if(!empty($r4->connecting) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port_of_loading'] =  $r4->region_id;
                  $tempArray['time_of_departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time_of_discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  $tempArray['port_of_discharge'] =  $r4->connecting;
				  $tempArray['vessel'] =  $feeder_vessel;
				  array_push($items, $tempArray);
				   endif;
            endif; 
			if($r4->destination == $r4->connecting ):
				  if(!empty($departur_from_pol)):
				  $tempArray['port_of_loading'] =  $r4->region_id;
                  $tempArray['time_of_departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
                  $tempArray['time_of_discharge'] = date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_trans_time)) :  date('M j, Y H:i',strtotime($arrival_trans_time)). " (Predicted)" ;
				  $tempArray['port_of_discharge'] =  $r4->connecting;
				  $tempArray['vessel'] =  $feeder_vessel;
				  array_push($items, $tempArray);
				   endif;
            endif;	
        else:
				  if(!empty($departur_from_pol)):
				  $tempArray['port_of_loading'] =  $r4->region_id;
                  $tempArray['time_of_departure']= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_from_pol)) : date('M j, Y H:i',strtotime($departur_from_pol))." (Predicted)";
				  $tempArray['vessel'] =  $feeder_vessel;
				  array_push($items, $tempArray);
				   endif;
         endif;
        
  
        if(!empty($departur_transit_city) ):
            if($r4->destination != $r4->connecting ):
				  if(!empty($departur_transit_time)):
				  $tempArray['port_of_loading'] =  $r4->connecting;
                  $tempArray['time_of_departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
                  $tempArray['time_of_discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  $tempArray['port_of_discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
				  array_push($items, $tempArray);
				   endif;
            endif; 
			 if($r4->destination == $r4->connecting ):
				  if(!empty($departur_transit_time)):
				  $tempArray['port_of_loading'] =  $r4->connecting;
                  $tempArray['time_of_departure']= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ?   date('M j, Y H:i',strtotime($departur_transit_time)) : date('M j, Y H:i',strtotime($departur_transit_time))." (Predicted)";
                  $tempArray['time_of_discharge'] = date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? date('M j, Y H:i',strtotime($arrival_des_time)) :  date('M j, Y H:i',strtotime($arrival_des_time)). " (Predicted)" ;
				  $tempArray['port_of_discharge'] =  $r4->destination;
				  $tempArray['vessel'] =  $vessel;
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
					foreach ($arrival_trans as $key => $val) {
						if (!empty($val)) {
							$date = DateTime::createFromFormat('d/m/Y', $val);
							if ($date !== false) {
								$arrival_trans_time = $date->format('Y-m-d H:i:s');
							} else {
								$arrival_trans_time = "";
							}
						} else {
							$arrival_trans_time = "";
						}

						// contoh: simpan hasil ke array baru
						$arrival_trans[$key] = $arrival_trans_time;
					}

					$departur_trans = json_decode($r3->dft,true);
                    foreach ($departur_trans as $key => $val) {
                        if(!empty($departur_trans['Departure from Transhipment Port'])){
                        $departur_transit_time= $departur_trans['Departure from Transhipment Port'];
                        }else{
                        $departur_transit_time= "";
                        }
					}
					$arrival = json_decode($r3->atd,true);
                    foreach ($arrival as $key => $val) {
                        if(!empty($arrival['Actual Time of Arrival (ATA)'])){
                        $arrival_des_time= $arrival['Actual Time of Arrival (ATA)'];
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
                        $pickup_do= $json['Pickup DO'];
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $cargo_delivery= $val;
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $stepperlocal["location"]= $r3->region_id; 
                  $stepperlocal["location type"]= "Port of loading";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? $departur_from_pol : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $stepperlocal["location"]=  $r3->connecting;
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Arrival";
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? $arrival_trans_time : "";
				  array_push($steppers, $stepperlocal);
			endif;
			if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $stepperlocal["location"]= $r3->connecting; 
                  $stepperlocal["location type"]= "Port of discharge";
                  $stepperlocal["event name"]= "Departure";
                  $stepperlocal["event time"]=date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? $departur_transit_time : "";
				  array_push($steppers, $stepperlocal);
			endif;  
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
                  $stepperlocal["location"]= $r3->destination; 
                  $stepperlocal["location type"]= "Port of destination";
				  $stepperlocal["event name"]= "Arrival"; 
                  $stepperlocal["event time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? $arrival_des_time : "";
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
                        $pickup_do= $json['Pickup DO'];
                        }else{
                        $pickup_do= "";
                        }
                    }
                    $json1 = json_decode($r3->cargo_delivery);
                    foreach ($json1 as $key => $val) {
                        if(!empty($val)){
                        $date = $val;
                        }else{
                        $cargo_delivery= "";
                        }
                    }
			if(!empty($departur_pol['Actual Time of Departure (ATD)'])):
                  $evenlocal["event_name"]= "Departure from origin port"; 
                  $evenlocal["port_name"]= $r3->region_id; 
                  $evenlocal["location_name"]= "Port of loading";
                  $evenlocal["event_time"]= date('Ymd',strtotime($departur_from_pol)) < date('Ymd') ? $departur_from_pol : $$departur_from_pol."*";
				  array_push($events, $evenlocal);
			endif;
			if(!empty($arrival_trans ['Arrival at Transhipment Port'])):
                  $evenlocal["event_name"]= "Arrival at transipment port"; 
                  $evenlocal["port_name"]= $r3->connecting;
                  $evenlocal["location_name"]= "Port of discharge";
                  $evenlocal["event_time"]= date('Ymd',strtotime($arrival_trans_time)) < date('Ymd') ? $arrival_trans_time : $departur_from_pol."*";
				  array_push($events, $evenlocal);
			endif;
			
            if(!empty($departur_trans['Departure from Transhipment Port'])):
                  $evenlocal["event_name"]= "Departure from transipment port"; 
                  $evenlocal["port_name"]= $r3->connecting; 
                  $evenlocal["location_name"]= "Port of discharge";
                  $evenlocal["event_time"]= date('Ymd',strtotime($departur_transit_time)) < date('Ymd') ? $departur_transit_time : $departur_transit_time."*";
				  array_push($events, $evenlocal);
			endif; 
            if(!empty($arrival['Actual Time of Arrival (ATA)'])): 
				  $evenlocal["event_name"]= "Arrival at final port of destination"; 
                  $evenlocal["port_name"]= $r3->destination; 
                  $evenlocal["location_name"]= "Port of destination";
                  $evenlocal["event_time"]=date('Ymd',strtotime($arrival_des_time)) < date('Ymd') ? $arrival_des_time :  $arrival_des_time. "*" ;
				  array_push($events, $evenlocal);
			endif; 
            if($pickup_do): 
				$evenlocal["event_name"]= "Pickup DO"; 
                $evenlocal["port_name"]= $r3->destination; 
                $evenlocal["location_name"]= "Port of destination";
                $evenlocal["event_time"]=$pickup_do;
				array_push($events, $evenlocal);
            endif;
           if($cargo_delivery):       
                $evenlocal["event_name"]= "Release Cargo"; 
                $evenlocal["port_name"]= $r3->destination; 
                $evenlocal["location_name"]= "Port of destination";
                $evenlocal["event_time"]=$cargo_delivery;
				array_push($events, $evenlocal);
           endif;
                        }
                   }
              }else{
				  if($data['hasil_lokal_import']->num_rows() > 0){
                    // Ambil query hasil
				$q = $data['hasil_lokal_import'] ?? null;
				$count = ($q && method_exists($q, 'num_rows')) ? (int) $q->num_rows() : 0;

				if ($q && $count > 0) {
					foreach ($q->result() as $row) {

						// ===== STRIPPING DATE (format sumber: Y-m-d H:i:s) =====
						if (!empty($row->stripping_date)
							&& $row->stripping_date !== '0000-00-00 00:00:00'
							&& $row->stripping_date !== '0000-00-00') {

							$dt = DateTime::createFromFormat('d/m/Y', $row->stripping_date);
							if ($dt) {
								$cargo_stripping_import = $dt->format('d/m/Y');

								$event = [
									"job_number"    =>  $row->no_job,
									"party"    		=>  $row->party,
									"event name"    => "Stripping Cargo",
									"port name"     => trim(($row->warehouse ?? "")) . " (CFS)",
									"location name" => "Unloading Cargo",
									"event time"    => $cargo_stripping_import. " ".trim(($row->warehouse ?? "")) . " (CFS)",
								];
								$events[] = $event;
								$table_import[] = $event;
							}
						}

						// ===== PICKUP DO (format sumber: d/m/Y) =====
						if (!empty($row->pickup_do)) {
							$dtdo = DateTime::createFromFormat('d/m/Y', $row->pickup_do);
							if ($dtdo) {
								//print_r($data['hasil_lokal_import']);
								$cargo_pickup_import = $dtdo->format('d/m/Y');

								$event = [
									"job_number"    =>  $row->no_job,
									"party"    		=>  $row->party,
									"event name"    => "Pickup DO",
									"port name"     => $row->port_of_destination ?? $row->region_id,
									"location name" => "Documentation",
									"event time"    => $cargo_pickup_import,
								];
								$events[] = $event;
								$table_import[] = $event;
							}
						}

						// ===== RELEASE CARGO (format sumber: d/m/Y) =====
						if (!empty($row->pickup_cargo)) {
							$dt1 = DateTime::createFromFormat('d/m/Y', $row->pickup_cargo);
							if ($dt1) {
								$cargo_release_import = $dt1->format('d/m/Y');

								$event = [
									"job_number"    =>  $row->no_job,
									"party"    		=>  $row->party,
									"event name"    => "Release Cargo",
									"port name"     => $row->port_of_destination ?? $row->region_id,
									"location name" => "Final Delivery",
									"event time"    => $cargo_release_import,
								];
								$events[] = $event;
								$table_import[] = $event;
								}
							}
						}
					}
				  }
			//$events[]=$evenlocal;
            }
	
	$body =[
	"header" => $header,
	"routing"=>$items,
	"stepper"=>$steppers,
	"event_list"=>$events
	];
	header('Content-Type: application/json');
	$this->response($body);
		
		}
}
?>