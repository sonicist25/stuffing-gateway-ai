<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class ScheduleDirect_model extends CI_Model
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
	
	public function searchDestinationOptions($keyword = '', $limit = 30)
	{
		$keyword = strtoupper(trim((string) $keyword));
		$limit   = (int) $limit > 0 ? (int) $limit : 30;

		$this->dbx->select("
			UPPER(TRIM(name)) AS id,
			UPPER(TRIM(name)) AS text
		", false);

		$this->dbx->from('tableName'); // sesuaikan

		$this->dbx->where('code IS NOT NULL', null, false);
		$this->dbx->where("TRIM(code) !=", "");

		if ($keyword !== '') {
			$this->dbx->group_start();
			$this->dbx->like('UPPER(code)', $keyword);
			$this->dbx->or_like('UPPER(name)', $keyword);
			$this->dbx->group_end();
		}

		$this->dbx->order_by('name', 'ASC');
		$this->dbx->limit($limit);

		return $this->dbx->get()->result();
	}
	
	
	public function searchLinerOptions($keyword = '', $limit = 30)
	{
		$keyword = strtoupper(trim((string) $keyword));
		$limit   = (int) $limit > 0 ? (int) $limit : 30;

		$this->dbx->select("
			UPPER(TRIM(code)) AS id,
			UPPER(TRIM(name)) AS text
		", false);

		// GANTI ini sesuai master liner Bapak
		$this->dbx->from('ref_scac_lcl');

		$this->dbx->where('code IS NOT NULL', null, false);
		$this->dbx->where("TRIM(code) !=", "");

		if ($keyword !== '') {
			$this->dbx->group_start();
			$this->dbx->like('UPPER(code)', $keyword);
			$this->dbx->or_like('UPPER(name)', $keyword);
			$this->dbx->group_end();
		}

		$this->dbx->group_by('UPPER(TRIM(code)), UPPER(TRIM(name))');
		$this->dbx->order_by('name', 'ASC');
		$this->dbx->limit($limit);

		return $this->dbx->get()->result();
	}
	
	public function destinationExists($value)
	{
		$value = strtoupper(trim((string) $value));

		$this->dbx->from('tableName');
		$this->dbx->where('UPPER(TRIM(name)) =', $value);

		return $this->dbx->count_all_results() > 0;
	}

	public function linerExists($code)
	{
		$code = strtoupper(trim((string) $code));

		$this->dbx->from('ref_scac_lcl');
		$this->dbx->where('UPPER(TRIM(code)) =', $code);

		return $this->dbx->count_all_results() > 0;
	}

  public function ScheduleDirectByCities($month, $year) {

    $this->dbx->select("a.*, 
    LOWER(
        TRIM(BOTH '-' FROM REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(a.city_id, ', ', '-'),
                        ',', '-'),
                    ' ', '-'),
                '(', ''),
            ')', ''),
        '--', '-')
    )) AS url_friendly");
    $this->dbx->from('tbl_schedules_direct as a');
    $this->dbx->where("a.isDeleted", 0);
    $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
    $query = $this->dbx->get();
	//echo $this->dbx->last_query();
	//die();
    $return = array();
    foreach ($query->result() as $city) {
        $return[$city->city_id] = $city;
        $return[$city->city_id]->schedule_direct_listing = $this->ScheduleDirectListing($city->city_id, $month, $year );
    } return $return;
}

public function ScheduleDirectListing($city_id, $month, $year) {
  $this->dbx->select("a.*, 
    LOWER(
        TRIM(BOTH '-' FROM REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(a.city_id, ', ', '-'),
                        ',', '-'),
                    ' ', '-'),
                '(', ''),
            ')', ''),
        '--', '-')
    )) AS url_friendly");
  $this->dbx->from('tbl_schedules_direct as a');
  $this->dbx->where("a.isDeleted", 0);
  $this->dbx->where('substr(etd_jkt, 6,2) =', $month);
  $this->dbx->where('substr(etd_jkt, 1,4) =', $year);
  $this->dbx->where('a.city_id', $city_id);
  $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
  $query = $this->dbx->get();
  $result = $query->result();
  return $result;
}


    function ScheduleDirectListing_byCity($month, $year)
    {
        $this->dbx->select('city_id');
        $this->dbx->from('tbl_schedules_direct as a');
        $this->dbx->where("a.isDeleted", 0);
        $this->dbx->where('substr(etd_jkt, 6,2) =', $month);
        $this->dbx->where('substr(etd_jkt, 1,4) =', $year);
        $this->dbx->where("a.region_id", $this->session->userdata('region_id'));
        $this->dbx->group_by('a.city_id');
        $query = $this->dbx->get();
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


    function createScheduleDirect($scheduleDirectInfo)
    {
        $this->dbx->trans_start();
        $this->dbx->insert('tbl_schedules_direct', $scheduleDirectInfo);
        $insert_id = $this->dbx->insert_id();
        $this->dbx->trans_complete();
        return $insert_id;
    }

   function updateScheduleDirect($scheduleDirectInfo, $id)
    {
        // 1. Update tabel tbl_schedules_direct
        $this->dbx->where('id', $id);
        $this->dbx->update('tbl_schedules_direct', $scheduleDirectInfo);
        return TRUE;
    }
    public function getScheduleDirectById($id)
    {
        $this->dbx->where('id', $id);
        $query = $this->dbx->get('tbl_schedules_direct');
        return $query->row(); // mengembalikan 1 record sebagai object
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        


    function deleteScheduleDirect($id, $scheduleDirectInfo)
    {
        $this->dbx->where('id', $id);
		    $this->dbx->delete('tbl_schedules_direct');
        return $this->dbx->affected_rows();
    }
	
public function getScacByCarrierCode($carrierCode)
{
    $carrierCode = strtoupper(trim((string) $carrierCode));

    if ($carrierCode === '') {
        return '';
    }

    $this->dbx->select('scac');
    $this->dbx->from('ref_scac_lcl');
    $this->dbx->where(
        'UPPER(TRIM(code)) = ' . $this->dbx->escape($carrierCode),
        null,
        false
    );
    $this->dbx->limit(1);

    $row = $this->dbx->get()->row();

    return $row ? (string) $row->scac : '';
}
	
public function createScheduleDirectSafe($payload)
{
    $payload = $this->_filterExistingColumns('tbl_schedules_direct', $payload);
    $this->dbx->insert('tbl_schedules_direct', $payload);
    return $this->dbx->insert_id();
}

public function updateScheduleDirectSafe($payload, $id)
{
    $payload = $this->_filterExistingColumns('tbl_schedules_direct', $payload);
    if (empty($payload)) {
        return false;
    }
    $this->dbx->where('id', $id);
    $this->dbx->update('tbl_schedules_direct', $payload);
    return true;
}

public function getBookingsByScheduleId($scheduleId)
{
    $this->dbx->select('trnstuffingplan_booking.*, trnstuffingplan_booking_detail.*');
    $this->dbx->from('trnstuffingplan_booking');
    $this->dbx->join(
        'trnstuffingplan_booking_detail',
        'trnstuffingplan_booking_detail.booking_id = trnstuffingplan_booking.booking_code',
        'left'
    );
    $this->dbx->where('trnstuffingplan_booking.schedule_id', (int)$scheduleId);
    $this->dbx->order_by('trnstuffingplan_booking.id', 'ASC');
    return $this->dbx->get()->result();
}


public function countBookingsByScheduleId($scheduleId)
{
    $this->dbx->from('trnstuffingplan_booking');
    $this->dbx->where('schedule_id', (int)$scheduleId);
    return (int) $this->dbx->count_all_results();
}

private function _filterExistingColumns($table, $payload)
{
    $out = array();
    foreach ($payload as $col => $val) {
        if ($this->dbx->field_exists($col, $table)) {
            $out[$col] = $val;
        }
    }
    return $out;
}


}
