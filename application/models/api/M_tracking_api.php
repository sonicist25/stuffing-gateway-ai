<?php
#[\AllowDynamicProperties]  
Class M_tracking_api extends CI_Model
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

    public function get_tracking_warehouse($hbl)
   {
	  $this->dbx->select(array(
	  'trnstuffingplandetail.hb_l as hb_l',
	  'trnstuffingplandetail.booking_code as booking_code',
	  'trnstuffingplandetail.received_status as received_status',
	  'trnstuffingplandetail.received_date as received_date',
	  'trnstuffingplandetail.stuffing_status as stuffing_status',
	  'trnstuffingplandetail.stuffing_date as stuffing_date',
	  'trnstuffingplandetail.gate_status as gate_status',
	  'trnstuffingplandetail.gate_date as gate_date',
	  ));
	  $query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.hb_l'=>$hbl));
	  //print_r($this->dbx->last_query());
	  //die();
      if ($query->num_rows() > 0) {
         foreach ($query->result() as $row) {
            $tempArray = array();
            $tempArray['received_status'] = $row->received_status;
            $tempArray['received_date'] = $row->received_date;
			$tempArray['stuffing_status'] = $row->stuffing_status;
            $tempArray['stuffing_date'] = $row->stuffing_date;
			$tempArray['gate_status'] = $row->gate_status;
            $tempArray['gate_date'] = $row->gate_date;
            $response[] = $tempArray;
         }
	  }
      return $response;
   }

}