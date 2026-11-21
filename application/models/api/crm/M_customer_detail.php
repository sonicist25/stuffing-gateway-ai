<?php
class M_customer_detail extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database('replica', TRUE);
    }

    private function apply_sales_filter($sales_id, $alias = 'd')
    {
        if ($sales_id !== null && $sales_id !== '') {
            $this->db->where($alias . '.sales', $sales_id);
        }
    }

    public function get_shipper_detail($id_shipper) {
        return $this->db->select('shipper_name, shipper_address, cp_name, cp_email, cp_phone, payment')
                        ->from('mstshipper')
                        ->where('id_shipper', $id_shipper)
                        ->get()
                        ->row_array();
    }

    public function get_total_shipment($shipper_id, $sales_id = null) {
        $this->db->select('p.type_of_trans, COUNT(d.sp_detail_id) as total');
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner');
        $this->db->where('d.shipper', $shipper_id);

        $this->apply_sales_filter($sales_id, 'd');

        $this->db->group_by('p.type_of_trans');

        return $this->db->get()->result_array();
    }

    public function get_open_invoices($id_shipper, $sales_id = null) {
        $this->db->select('SUM(f.grand_total) as total_grand_total, COUNT(*) as total_invoices');
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner');
        $this->db->join('x_finance f', 'f.job_no = d.kode_ref', 'inner');
        $this->db->where('d.shipper', $id_shipper);
        $this->db->where('f.status !=', 'paid');

        $this->apply_sales_filter($sales_id, 'd');

        $result = $this->db->get()->row();

        return $result ? [
            'total_grand_total' => $result->total_grand_total ?: 0,
            'total_invoices'    => $result->total_invoices ?: 0
        ] : [
            'total_grand_total' => 0,
            'total_invoices'    => 0
        ];
    }

    public function get_latest_invoices($id_shipper, $limit = 8, $sales_id = null) {
        $this->db->select('f.date, f.job_no, f.number, d.destination, f.grand_total, f.status, f.tgl_bayar');
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner');
        $this->db->join('x_finance f', 'f.job_no = d.kode_ref', 'inner');
        $this->db->where('d.shipper', $id_shipper);

        $this->apply_sales_filter($sales_id, 'd');

        $this->db->order_by('f.date', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    public function get_volume_chart($shipper_id, $sales_id = null) {
        $this->db->select("
            d.destination,
            p.type_of_trans,
            DATE_FORMAT(STR_TO_DATE(p.tanggal_job, '%Y%m%d'), '%Y-%m') as bulan,
            SUM(CAST(d.bl_meas AS DECIMAL(15,2))) as total_volume
        ");
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner');
        $this->db->where('d.shipper', $shipper_id);

        $this->apply_sales_filter($sales_id, 'd');

        $this->db->group_start()
            ->where('p.type_of_trans', 'LCL')
            ->or_where('p.type_of_trans', 'SF IMPORT LCL')
        ->group_end();

        $this->db->group_by(['bulan', 'd.destination', 'p.type_of_trans']);
        $this->db->order_by('bulan', 'ASC');
        $this->db->order_by('d.destination', 'ASC');

        $result = $this->db->get()->result_array();

        $nested = [];

        foreach ($result as $row) {
            $bulan = $row['bulan'];

            if (!isset($nested[$bulan])) {
                $nested[$bulan] = [];
            }

            $nested[$bulan][] = [
                'destination'   => $row['destination'],
                'type_of_trans' => $row['type_of_trans'],
                'total_volume'  => $row['total_volume']
            ];
        }

        return $nested;
    }

    public function get_last_bookings($shipper_id, $limit = 8, $sales_id = null) {
        $this->db->select('
            d.no_from_shipper,
            sp.etd_jkt,
            d.feeder_vessel,
            spd.destination,
            spd.bl_meas,
            spd.kode_ref,
            spd.hb_l as hbl,
            sp.status_gocomet as status_tracking
        ');
        $this->db->from('tbl_detail d');
        $this->db->join('trnstuffingplandetail spd', 'd.detail_id = spd.sp_detail_id', 'inner');
        $this->db->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner');
        $this->db->where('spd.shipper', $shipper_id);

        if ($sales_id !== null && $sales_id !== '') {
            $this->db->where('spd.sales', $sales_id);
        }

        $this->db->order_by('d.id', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    public function get_sum_price_per_month($id_shipper, $sales_id = null)
	{
		$this->db->select("
			SUBSTRING(xf.job_no, 8, 4) AS bulan_ke,
			SUM(xfi.price) AS total_price
		");
		$this->db->from("x_finance xf");
		$this->db->join("x_finance_item xfi", "xf.id = xfi.number", "inner");
		$this->db->join("trnstuffingplandetail tspd", "xf.job_no = tspd.kode_ref", "inner");
		$this->db->join("trnstuffingplan p", "tspd.sp_id = p.sp_id", "inner");
		$this->db->where("tspd.shipper", $id_shipper);

		// FIX: alias harus tspd, bukan d
		$this->apply_sales_filter($sales_id, 'tspd');

		$this->db->group_by("bulan_ke");

		return $this->db->get()->result_array();
	}
}