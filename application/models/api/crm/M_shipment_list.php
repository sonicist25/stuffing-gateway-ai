<?php
class M_shipment_list extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database('replica', TRUE);
    }
    // Last 10 bookings
    
    // Ambil booking dari trnstuffingplan_booking
	  public function get_bookings($sales_user_id) {
		return $this->db
			->select([
				'b.booking_code AS no_from_shipper',
				'b.house_bl AS hbl',
				'b.forwarder_name AS customer',
				'bd.volume AS measurement',
				'b.expect_etd AS etd_jkt',
				'b.eta',
				'b.vessel AS feeder_vessel',
				'b.origin_city AS origin',
				'b.destination_city AS destination',
				'"PREPARED" AS status_gocomet'
			])
			->from('trnstuffingplan_booking b')
			->join('trnstuffingplan_booking_detail bd', 'bd.booking_id = b.id', 'left')
			->where('b.sales', $sales_user_id)
			->where('b.status !=', 'booked')
			->order_by('b.expect_etd', 'DESC')
			->get()
			->result_array();
	}



	// Ambil last bookings dari tabel detail
	public function get_last_bookings($sales_user_id) {
		return $this->db->select("
				spd.sp_detail_id,
				spd.kode_ref,
				spd.hb_l AS hbl,
				spd.shipper_print AS customer,
				spd.bl_meas AS measurement,
				sp.etd_jkt,
				sp.eta,
				d.profit,
				CASE 
					WHEN sp.type_of_trans LIKE '%import%' 
					THEN spd.destination 
					ELSE sp.port_loading 
				END AS origin,
				CASE 
					WHEN sp.type_of_trans LIKE '%import%' 
					THEN sp.destination 
					ELSE spd.destination
				END AS destination,
				sp.status_gocomet
			")
			->from('save_jobsheet d')
			->join('trnstuffingplandetail spd', 'd.kode_ref = spd.kode_ref', 'inner')
			->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner')
			->where('spd.sales', $sales_user_id)
			->order_by('sp.etd_jkt', 'DESC') // lebih relevan dibanding d.id
			->get()
			->result_array();
	}

    // Gabungkan hasil keduanya
    public function get_combined_bookings($sales_user_id) {
        $bookings = $this->get_bookings($sales_user_id);
        $last_bookings = $this->get_last_bookings($sales_user_id);

        return [
            'bookings' => $bookings,
            'last_bookings' => $last_bookings
        ];
    }

}