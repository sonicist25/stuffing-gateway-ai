<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class ScheduleDirectImport_model extends CI_Model
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

  public function ScheduleDirectImportByCities($month, $year) {

    $this->dbx->select('*, REPLACE(REPLACE(REPLACE(LOWER(city_id), ", ", "-"), ",", "-"), " ", "-") AS url_friendly');
    $this->dbx->from('tbl_schedules_direct_import as a');
    $this->dbx->where("a.isDeleted", 0);
    $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
    $query = $this->dbx->get();
	//echo $this->dbx->last_query();
	//die();
    $return = array();
    foreach ($query->result() as $city) {
        $return[$city->city_id] = $city;
        $return[$city->city_id]->schedule_direct_listing = $this->ScheduleDirectListingImport($city->city_id, $month, $year );
    } return $return;
}

public function ScheduleDirectListingImport($city_id, $month, $year) {
  $this->dbx->select("*, LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(city_id, ' ', '-'), ',', '-'), '(', ''), ')', ''), '--', '-')) AS url_friendly");
  $this->dbx->from('tbl_schedules_direct_import as a');
  $this->dbx->where("a.isDeleted", 0);
  $this->dbx->where('substr(etd, 6,2) =', $month);
  $this->dbx->where('substr(etd, 1,4) =', $year);
  $this->dbx->where('a.city_id', $city_id);
  $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
  $query = $this->dbx->get();
  //print_r($this->dbx->last_query());
  //die();
  $result = $query->result();
  return $result;
}


    function ScheduleDirectListingImport_byCity($month, $year)
    {
        $this->dbx->select('*, REPLACE(REPLACE(REPLACE(LOWER(city_id), ", ", "-"), ",", "-"), " ", "-") AS url_friendly');
        $this->dbx->from('tbl_schedules_direct_import as a');
        $this->dbx->where("a.isDeleted", 0);
        $this->dbx->where('substr(etd, 6,2) =', $month);
        $this->dbx->where('substr(etd, 1,4) =', $year);
        $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
        $this->dbx->group_by('a.city_id');
        $query = $this->dbx->get();
		//print_r($this->dbx->last_query());
	    //die();
        $result = $query->result();
        return $result;
    }


  function getCities()
    {
        $this->dbx->select('id, name');
        $this->dbx->from('tableName');
        $query = $this->dbx->get();
        return $query->result();
    }

    function getVessels()
    {
        $this->dbx->select('id, vessel');
        $this->dbx->from('tbl_vessels');
        $query = $this->dbx->get();
        return $query->result();
    }

    function getCityById($id)
    {
        $this->dbx->select('*');
        $this->dbx->from('mstcities');
        $this->dbx->where('id_city', $id);
        $query = $this->dbx->get();
        return $query->result();
    }


    function createScheduleDirectImport($scheduleDirectInfo)
    {
        $this->dbx->trans_start();
        $this->dbx->insert('tbl_schedules_direct_import', $scheduleDirectInfo);
        $insert_id = $this->dbx->insert_id();
        $this->dbx->trans_complete();
        return $insert_id;
    }

    function updateScheduleDirectImport($scheduleDirectInfo, $id)
    {
        $this->dbx->where('id', $id);
        $this->dbx->update('tbl_schedules_direct_import', $scheduleDirectInfo);
        return TRUE;
    }


    function deleteScheduleDirectImport($id, $scheduleDirectInfo)
    {
        $this->dbx->where('id', $id);
		    $this->dbx->delete('tbl_schedules_direct_import');
        return $this->dbx->affected_rows();
    }

}
