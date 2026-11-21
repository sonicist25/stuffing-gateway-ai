<?php if(!defined('BASEPATH')) exit('No via script access allowed');

class ScheduleViaImport_model extends CI_Model
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
    
	public function ScheduleViaImportByCities() {
       $month = $this->session->userdata('month_via');
       $year = $this->session->userdata('year_via');
       $this->dbx->select('*');
       $this->dbx->from('tbl_schedules_via_import as a');
       $this->dbx->join('mstcities as b', 'a.eta_city_id = b.id_city');
       $this->dbx->where("isDeleted", 0);
       $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
       $query = $this->dbx->get();
       $return = array();
       foreach ($query->result() as $city) {
           $return[$city->id_city] = $city;
           $return[$city->id_city]->schedule_via_listing = $this->ScheduleViaListingImport($city->id_city, $month, $year );
       } return $return;
   }

   public function ScheduleViaListingImport($city_id, $month, $year) {
     $this->dbx->select('*');
     $this->dbx->from('tbl_schedules_via_import as a');
     $this->dbx->join('mstcities as b', 'a.eta_city_id = b.id_city');
     $this->dbx->join('mstcities as c', 'a.etd_city_con_id = c.id_city');
     $this->dbx->where("a.isDeleted", 0);
     $this->dbx->where('substr(eta_jkt, 6,2) =', $month);
     $this->dbx->where('substr(eta_jkt, 1,4) =', $year);
     $this->dbx->where('a.eta_city_id', $city_id);
     $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
     $query = $this->dbx->get();
     $result = $query->result();
     return $result;
   }


       function ScheduleViaListingImport_byCity($month, $year)
       {
           $this->dbx->select('*');
           $this->dbx->from('tbl_schedules_via_import as a');
           $this->dbx->join('mstcities as b', 'a.eta_city_id = b.id_city');
           $this->dbx->where('substr(eta_jkt, 6,2) =', $month);
           $this->dbx->where('substr(eta_jkt, 1,4) =', $year);
           $this->dbx->group_by('a.eta_city_id');
           $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
           $query = $this->dbx->get();
           $result = $query->result();
           return $result;
       }


    function getCities()
    {
        $this->dbx->select('id_city, city_name');
        $this->dbx->from('mstcities');
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


    function createScheduleViaImport($scheduleViaInfo)
    {
        $this->dbx->trans_start();
        $this->dbx->insert('tbl_schedules_via_import', $scheduleViaInfo);
        $insert_id = $this->dbx->insert_id();
        $this->dbx->trans_complete();
        return $insert_id;
    }


    function updateScheduleViaImport($scheduleViaInfo, $id)
    {
        $this->dbx->where('id', $id);
        $this->dbx->update('tbl_schedules_via_import', $scheduleViaInfo);
        return TRUE;
    }


    function deleteScheduleViaImport($id, $scheduleViaInfo)
    {
        $this->dbx->where('id', $id);
		    $this->dbx->delete('tbl_schedules_via_import');
        return $this->dbx->affected_rows();
    }

}
