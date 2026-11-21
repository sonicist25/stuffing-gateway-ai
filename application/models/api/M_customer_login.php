<?php
#[\AllowDynamicProperties]
class M_customer_login extends CI_Model {

    public function __construct()
	{
		parent::__construct();

		$this->db = $this->load->database('replica', TRUE);
	}
    
    public function get_shipper_detail($id_shipper) {
        return $this->db->select('shipper_name, shipper_address, cp_name, cp_email, cp_phone, payment')
                        ->from('mstshipper')
                        ->where('id_shipper', $id_shipper)
                        ->where('isActive', 'Yes')
                        ->get()->row_array();
    }

    // Total shipment
    public function get_total_shipment($shipper_id) {
        $this->db->select('p.type_of_trans, COUNT(d.sp_detail_id) as total');
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner');
        $this->db->where('d.shipper', $shipper_id);
        $this->db->group_by('p.type_of_trans');
        return $this->db->get()->result_array();
    }
    // Open invoices
    public function get_open_invoices($id_shipper) {
        $this->db->select('SUM(f.grand_total) as total_grand_total, COUNT(*) as total_invoices');
        $this->db->from('mstshipper s');
        $this->db->join('x_invoice i', 'i.shipper_name = s.shipper_name', 'inner');
        $this->db->join('x_finance f', 'f.number = i.number', 'inner');
        $this->db->where('s.id_shipper', $id_shipper);
        $this->db->where('f.status !=', 'paid');
        $result = $this->db->get()->row();
        return $result ? [
            'total_grand_total' => $result->total_grand_total,
            'total_invoices' => $result->total_invoices
        ] : [
            'total_grand_total' => 0,
            'total_invoices' => 0
        ];
    }

    // Latest invoices
    public function get_latest_invoices($id_shipper, $limit = 8) {
        $this->db->select('f.date, f.number, d.destination, f.grand_total, f.status, f.tgl_bayar');
        $this->db->from('trnstuffingplandetail d');
        $this->db->join('x_finance f', 'f.job_no = d.kode_ref', 'inner');
        $this->db->where('d.shipper', $id_shipper);
        $this->db->order_by('f.date', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }


  // Chart volume LCL per destinasi per bulan (nested array)
	public function get_volume_chart($shipper_id) {
		$result = $this->db->select("
				d.destination,
				p.type_of_trans,
				DATE_FORMAT(STR_TO_DATE(p.tanggal_job, '%Y%m%d'), '%Y-%m') as bulan,
				SUM(CAST(d.bl_meas AS DECIMAL(15,2))) as total_volume
			")
			->from('trnstuffingplandetail d')
			->join('trnstuffingplan p', 'd.sp_id = p.sp_id', 'inner')
			->where('d.shipper', $shipper_id)
			->group_start()
				->where('p.type_of_trans', 'LCL')
				->or_where('p.type_of_trans', 'SF IMPORT LCL')
			->group_end()
			->group_by(['bulan','d.destination','p.type_of_trans'])
			->order_by('bulan', 'ASC')
			->order_by('d.destination', 'ASC')
			->get()
			->result_array();

		// Transform hasil flat ke nested array per bulan
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


    // Last 10 bookings
     public function get_last_bookings($shipper_id, $limit = 8) {
        return $this->db->select('
                            d.no_from_shipper,
                            sp.etd_jkt,
                            sp.eta,
                            d.feeder_vessel,
                            spd.destination,
                            spd.bl_meas,
                            spd.hb_l,
                            sp.status_gocomet
                        ')
                        ->from('tbl_detail d')
                        ->join('trnstuffingplandetail spd', 'd.detail_id = spd.sp_detail_id', 'inner')
                        ->join('trnstuffingplan sp', 'spd.sp_id = sp.sp_id', 'inner')
                        ->where('spd.shipper', $shipper_id)
                        ->order_by('d.id', 'DESC')   // bisa diganti order_by sp.etd_jkt kalau lebih relevan
                        ->limit($limit)
                        ->get()
                        ->result_array();
    }
    
    public function get_sum_price_per_month($id_shipper)
    {
        $this->db->select("
            SUBSTRING(xf.job_no, 8, 4) AS bulan_ke,
            SUM(xfi.price) AS total_price
        ");
        $this->db->from("x_finance xf");
        $this->db->join("x_finance_item xfi", "xf.id = xfi.number", "inner");
        $this->db->join("trnstuffingplandetail tspd", "xf.job_no = tspd.kode_ref", "inner");
        $this->db->where("tspd.shipper", $id_shipper);
        $this->db->group_by("bulan_ke");

        $query = $this->db->get();
        return $query->result_array();
    }


    /**
     * Top 10 destination berdasarkan nilai tagihan per bulan untuk Firmenich.
     *
     * Detail shipment dideduplicate per kode_ref sebelum join ke x_finance,
     * sehingga grand_total tidak berlipat ketika satu job memiliki lebih dari
     * satu row pada trnstuffingplandetail.
     */
    public function get_firmenich_top_destination_billing(
        array $shipperIds,
        $dateFrom,
        $dateToExclusive,
        $limit = 10
    ) {
        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if (!$shipperIds) {
            return [];
        }

        $limit = max(1, min((int) $limit, 50));
        $placeholders = implode(',', array_fill(0, count($shipperIds), '?'));

        // x_finance.date adalah VARCHAR dengan format YYYYMMDD, contoh 20260106.
        // Endpoint menerima Y-m-d, jadi ubah dulu menjadi Ymd agar filter bulan match.
        $dateFromKey = preg_replace('/[^0-9]/', '', (string) $dateFrom);
        $dateToExclusiveKey = preg_replace('/[^0-9]/', '', (string) $dateToExclusive);

        if (strlen($dateFromKey) !== 8 || strlen($dateToExclusiveKey) !== 8) {
            return [];
        }

        $sql = "
            SELECT
                d.destination,
                ROUND(SUM(COALESCE(f.grand_total, 0)), 2) AS total_billing,
                COUNT(DISTINCT f.number) AS total_invoice
            FROM x_finance f
            INNER JOIN (
                SELECT
                    TRIM(kode_ref) AS kode_ref,
                    MAX(UPPER(TRIM(destination))) AS destination
                FROM trnstuffingplandetail
                WHERE shipper IN ({$placeholders})
                  AND kode_ref IS NOT NULL
                  AND TRIM(kode_ref) <> ''
                  AND destination IS NOT NULL
                  AND TRIM(destination) <> ''
                GROUP BY TRIM(kode_ref)
            ) d
                ON d.kode_ref = TRIM(f.job_no)
            WHERE f.jenis = 'INVOICE'
              AND f.date >= ?
              AND f.date < ?
            GROUP BY d.destination
            ORDER BY total_billing DESC, d.destination ASC
            LIMIT {$limit}
        ";

        $binds = array_merge(
            $shipperIds,
            [$dateFromKey, $dateToExclusiveKey]
        );

        $rows = $this->db->query($sql, $binds)->result_array();

        foreach ($rows as &$row) {
            $row['total_billing'] = (float) ($row['total_billing'] ?? 0);
            $row['total_invoice'] = (int) ($row['total_invoice'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * Top 10 destination berdasarkan JUMLAH SHIPMENT per bulan untuk Firmenich.
     * Satu shipment dihitung dari COUNT(DISTINCT d.kode_ref).
     * CBM tetap dikembalikan sebagai informasi tambahan, bukan dasar ranking.
     * Bulan mengikuti tanggal job pada trnstuffingplan.tanggal_job (YYYYMMDD).
     */
    public function get_firmenich_top_destination_cbm(
        array $shipperIds,
        $dateFrom,
        $dateToExclusive,
        $limit = 10
    ) {
        $shipperIds = $this->normalize_shipper_ids($shipperIds);
        if (!$shipperIds) {
            return [];
        }

        $limit = max(1, min((int) $limit, 50));
        $placeholders = implode(',', array_fill(0, count($shipperIds), '?'));

        $sql = "
            SELECT
                UPPER(TRIM(d.destination)) AS destination,
                ROUND(
                    SUM(
                        CAST(
                            NULLIF(TRIM(d.bl_meas), '')
                            AS DECIMAL(18,3)
                        )
                    ),
                    3
                ) AS total_cbm,
                COUNT(DISTINCT d.kode_ref) AS total_job
            FROM trnstuffingplandetail d
            INNER JOIN trnstuffingplan p
                ON p.sp_id = d.sp_id
            WHERE d.shipper IN ({$placeholders})
              AND d.destination IS NOT NULL
              AND TRIM(d.destination) <> ''
              AND STR_TO_DATE(p.tanggal_job, '%Y%m%d') >= ?
              AND STR_TO_DATE(p.tanggal_job, '%Y%m%d') < ?
            GROUP BY UPPER(TRIM(d.destination))
            ORDER BY total_job DESC, total_cbm DESC, destination ASC
            LIMIT {$limit}
        ";

        $binds = array_merge(
            $shipperIds,
            [(string) $dateFrom, (string) $dateToExclusive]
        );

        $rows = $this->db->query($sql, $binds)->result_array();

        foreach ($rows as &$row) {
            $row['total_cbm'] = (float) ($row['total_cbm'] ?? 0);
            $row['total_job'] = (int) ($row['total_job'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    private function normalize_shipper_ids(array $shipperIds)
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $shipperIds),
            static function ($id) {
                return $id > 0;
            }
        )));
    }

}