<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class ScheduleVia_model extends CI_Model
{
    public $dbx;

    public function __construct()
    {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    public function ScheduleViaByCities($month, $year)
    {
        $this->dbx->select("a.*, LOWER(TRIM(BOTH '-' FROM REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.eta_city_id, ', ', '-'), ',', '-'), ' ', '-'), '(', ''), ')', ''), '--', '-'))) AS url_friendly", false);
        $this->dbx->from('tbl_schedules_via as a');
        $this->dbx->where('a.isDeleted', 0);
        $this->dbx->where('a.region_id', $this->session->userdata('region_id'));
        $this->dbx->where('substr(a.etd_jkt, 6, 2) =', $month);
        $this->dbx->where('substr(a.etd_jkt, 1, 4) =', $year);
        $this->dbx->group_by('a.eta_city_id');
        $this->dbx->order_by('a.eta_city_id', 'ASC');

        $query = $this->dbx->get();

        $return = array();
        foreach ($query->result() as $city) {
            $return[$city->eta_city_id] = $city;
            $return[$city->eta_city_id]->schedule_via_listing = $this->ScheduleViaListing($city->eta_city_id, $month, $year);
        }

        return $return;
    }

    public function ScheduleViaListing($city_id, $month, $year)
    {
        $this->dbx->select("a.*, LOWER(TRIM(BOTH '-' FROM REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.eta_city_id, ', ', '-'), ',', '-'), ' ', '-'), '(', ''), ')', ''), '--', '-'))) AS url_friendly", false);
        $this->dbx->from('tbl_schedules_via as a');
        $this->dbx->where('a.isDeleted', 0);
        $this->dbx->where('substr(a.etd_jkt, 6, 2) =', $month);
        $this->dbx->where('substr(a.etd_jkt, 1, 4) =', $year);
        $this->dbx->where('a.eta_city_id', $city_id);
        $this->dbx->where('a.region_id', $this->session->userdata('region_id'));
        $this->dbx->order_by('a.etd_jkt', 'ASC');
        $this->dbx->order_by('a.vessel', 'ASC');

        return $this->dbx->get()->result();
    }

    public function ScheduleViaListing_byCity($month, $year)
    {
        $this->dbx->select("a.eta_city_id, LOWER(TRIM(BOTH '-' FROM REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.eta_city_id, ', ', '-'), ',', '-'), ' ', '-'), '(', ''), ')', ''), '--', '-'))) AS url_friendly", false);
        $this->dbx->from('tbl_schedules_via as a');
        $this->dbx->where('a.isDeleted', 0);
        $this->dbx->where('substr(a.etd_jkt, 6, 2) =', $month);
        $this->dbx->where('substr(a.etd_jkt, 1, 4) =', $year);
        $this->dbx->where('a.region_id', $this->session->userdata('region_id'));
        $this->dbx->group_by('a.eta_city_id');
        $this->dbx->order_by('a.eta_city_id', 'ASC');

        return $this->dbx->get()->result();
    }

    /**
     * Backward compatible endpoint for legacy editable select.
     */
    public function getCities()
    {
        $this->dbx->select('id_city AS id, UPPER(TRIM(name)) AS name', false);
        $this->dbx->from('mstcities');
        $this->dbx->where('name IS NOT NULL', null, false);
        $this->dbx->where("TRIM(name) !=", '');
        $this->dbx->order_by('name', 'ASC');
        $this->dbx->limit(200);

        return $this->dbx->get()->result();
    }

    public function searchDestinationOptions($keyword = '', $limit = 30)
    {
        $keyword = strtoupper(trim((string) $keyword));
        $limit   = ((int) $limit > 0) ? (int) $limit : 30;

        $this->dbx->select("UPPER(TRIM(name)) AS id, UPPER(TRIM(name)) AS text", false);
        $this->dbx->from('tableName');
        $this->dbx->where('name IS NOT NULL', null, false);
        $this->dbx->where("TRIM(name) !=", '');

        if ($keyword !== '') {
            $this->dbx->group_start();
            $this->dbx->like('UPPER(name)', $keyword);
            if ($this->dbx->field_exists('code', 'tableName')) {
                $this->dbx->or_like('UPPER(code)', $keyword);
            }
            $this->dbx->group_end();
        }

        $this->dbx->group_by('UPPER(TRIM(name))');
        $this->dbx->order_by('name', 'ASC');
        $this->dbx->limit($limit);

        return $this->dbx->get()->result();
    }

    public function searchLinerOptions($keyword = '', $limit = 30)
    {
        $keyword = strtoupper(trim((string) $keyword));
        $limit   = ((int) $limit > 0) ? (int) $limit : 30;

        $this->dbx->select("UPPER(TRIM(code)) AS id, UPPER(CONCAT(TRIM(code), IF(scac IS NULL OR TRIM(scac) = '', '', CONCAT(' - ', TRIM(scac))))) AS text", false);
        $this->dbx->from('ref_scac_lcl');
        $this->dbx->where('code IS NOT NULL', null, false);
        $this->dbx->where("TRIM(code) !=", '');

        if ($keyword !== '') {
            $this->dbx->group_start();
            $this->dbx->like('UPPER(code)', $keyword);
            $this->dbx->or_like('UPPER(scac)', $keyword);
            $this->dbx->group_end();
        }

        $this->dbx->group_by('UPPER(TRIM(code)), UPPER(TRIM(scac))');
        $this->dbx->order_by('code', 'ASC');
        $this->dbx->limit($limit);

        return $this->dbx->get()->result();
    }

    public function getScacByCarrierCode($carrierCode)
    {
        $carrierCode = strtoupper(trim((string) $carrierCode));

        if ($carrierCode === '') {
            return '';
        }

        $this->dbx->select('scac');
        $this->dbx->from('ref_scac_lcl');
        $this->dbx->where('UPPER(TRIM(code)) = ' . $this->dbx->escape($carrierCode), null, false);
        $this->dbx->limit(1);

        $row = $this->dbx->get()->row();

        return $row ? (string) $row->scac : '';
    }

    public function getCityById($id)
    {
        $this->dbx->select('*');
        $this->dbx->from('mstcities');
        $this->dbx->where('id_city', $id);
        return $this->dbx->get()->result();
    }

    public function createScheduleVia($scheduleViaInfo)
    {
        $this->dbx->trans_start();
        $this->dbx->insert('tbl_schedules_via', $scheduleViaInfo);
        $insert_id = $this->dbx->insert_id();
        $this->dbx->trans_complete();
        return $insert_id;
    }

    public function createScheduleViaSafe($payload)
    {
        $payload = $this->_filterExistingColumns('tbl_schedules_via', $payload);
        $this->dbx->insert('tbl_schedules_via', $payload);
        return $this->dbx->insert_id();
    }

    public function getScheduleViaById($id)
    {
        $this->dbx->where('id', (int) $id);
        return $this->dbx->get('tbl_schedules_via')->row();
    }

    public function updateScheduleVia($scheduleViaInfo, $id)
    {
        $this->dbx->where('id', (int) $id);
        $this->dbx->update('tbl_schedules_via', $scheduleViaInfo);
        return true;
    }

    public function updateScheduleViaSafe($payload, $id)
    {
        $payload = $this->_filterExistingColumns('tbl_schedules_via', $payload);
        if (empty($payload)) {
            return false;
        }

        $this->dbx->where('id', (int) $id);
        $this->dbx->update('tbl_schedules_via', $payload);
        return true;
    }

    public function deleteScheduleVia($id, $scheduleViaInfo = array())
    {
        $payload = array_merge(array(
            'isDeleted' => 1,
        ), is_array($scheduleViaInfo) ? $scheduleViaInfo : array());

        $payload = $this->_filterExistingColumns('tbl_schedules_via', $payload);

        $this->dbx->where('id', (int) $id);
        $this->dbx->update('tbl_schedules_via', $payload);
        return $this->dbx->affected_rows();
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
		if ($this->dbx->field_exists('route_type', 'trnstuffingplan_booking')) {
            $this->dbx->where('route_type', 'VIA');
        }
		$this->dbx->where('trnstuffingplan_booking.schedule_id', (int)$scheduleId);
		$this->dbx->order_by('trnstuffingplan_booking.id', 'ASC');
		return $this->dbx->get()->result();
	}

    public function countBookingsByScheduleId($scheduleId)
    {
        $this->dbx->from('trnstuffingplan_booking');
        $this->dbx->where('schedule_id', (int) $scheduleId);
        if ($this->dbx->field_exists('route_type', 'trnstuffingplan_booking')) {
            $this->dbx->where('route_type', 'VIA');
        }
        return (int) $this->dbx->count_all_results();
    }

    private function _filterExistingColumns($table, $payload)
    {
        $out = array();
        foreach ((array) $payload as $col => $val) {
            if ($this->dbx->field_exists($col, $table)) {
                $out[$col] = $val;
            }
        }
        return $out;
    }
}
