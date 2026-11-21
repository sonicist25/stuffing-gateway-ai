<?php
#[\AllowDynamicProperties]
class M_instant_booking extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $tahun = '2026';
        if ($tahun == '2026' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    public function create_quotation($data, $data_detail)
    {
        // Cek apakah data sudah ada (Update) atau baru (Insert)
        $this->dbx->where('booking_code', $data['booking_code']);
        $q = $this->dbx->get('trnstuffingplan_booking');
		$regional = $this->dbx->get_where('mstshipper', array('id_shipper' => $data['shipper_id']))->row()->region_id;
		$periode = "26";
		$q0 = $this->dbx->get_where('refregion', array('region_name' => $regional))->row()->region_id;
		$q3 = $this->dbx->get_where('tableName', array('name' => $data['destination_city']))->row()->code;
		$portTrans = (!empty($data['trans_city']) && $data['trans_city'] !== "null") ? $data['trans_city'] : '';

        $this->dbx->trans_start(); // Mulai Transaksi

        if ($q->num_rows() > 0) {
            // --- UPDATE ---
            $this->dbx->where('booking_code', $data['booking_code']);
            $masukan = $this->dbx->update('trnstuffingplan_booking', $data);

            $this->dbx->where('booking_id', $data['booking_code']); // Note: Field detail pakai booking_id
            $insert2 = $this->dbx->update('trnstuffingplan_booking_detail', $data_detail);
        } else {
            // --- INSERT ---
            $masukan = $this->dbx->insert('trnstuffingplan_booking', $data);
			    // 2. Ambil ID hasil insert (auto-increment)
			$idbooking = $this->dbx->insert_id();
			// 1) Siapkan prefix sesuai destinasi (hanya jika $portTrans kosong)
			$destination = strtoupper(trim($data['destination_city'] ?? ''));
			$prefix = null;

			if (empty($portTrans) || $portTrans === 'LOS ANGELES' || $portTrans === 'NEW YORK') {
				if ($destination === 'LOS ANGELES' || $destination === 'HOUSTON' || $destination === 'SEATTLE' || $destination === 'OAKLAND') {
					$prefix = 'LAX'.$periode; // hasil: IDULA50001..IDULA59999
				} elseif ($destination === 'NEW YORK' || $destination === 'CHICAGO' || $destination === 'ATLANTA' || $destination === 'MIAMI' || $destination === 'KANSAS CITY'|| $destination === 'BALTIMORE') {
					$prefix = 'NYK'.$periode; // hasil: IDUNY60001..IDUNY69999
				}
			}


			// 2) Bangun $bl
			if ($prefix !== null) {

				// (Opsional tapi disarankan) gunakan transaksi + FOR UPDATE agar anti race-condition
				$this->dbx->trans_start();

				// Ambil suffix terbesar (4 digit terakhir) untuk prefix tsb
				// Catatan: REGEXP memastikan format tepat: PREFIX + 4 digit
				$sql = "
					SELECT MAX(CAST(RIGHT(house_bl, 5) AS UNSIGNED)) AS max_suffix
					FROM trnstuffingplan_booking
					WHERE house_bl REGEXP '^{$prefix}[0-9]{4}$'
					FOR UPDATE
				";
				$row = $this->dbx->query($sql)->row();
				$next = (int)($row->max_suffix ?? 0) + 1;

				if ($next > 9999) {
					// guard kalau range penuh
					throw new Exception('Range nomor untuk prefix ' . $prefix . ' sudah habis (0001..9999).');
				}

				// Bentuk nomor BL: PREFIX + 4 digit (leading zero)
				$bl = $prefix . str_pad($idbooking, 5, '0', STR_PAD_LEFT);

				// ... lakukan INSERT/UPDATE yang menggunakan $bl di sini jika perlu ...

				$this->dbx->trans_complete();

			} else {
				// fallback ke format lama kamu
				$q5 = str_pad($idbooking, 5, '0', STR_PAD_LEFT);
				$bl = substr($regional, 0, 1) . $q3 . "-" . $q0 . $periode . $q5;
			}


			// 4. Update record dengan house_bl
			$this->dbx->where('id', $idbooking);
			$this->dbx->update('trnstuffingplan_booking', array('house_bl' => $bl));
            $insert2 = $this->dbx->insert('trnstuffingplan_booking_detail', $data_detail);
        }

        $this->dbx->trans_complete(); // Selesai Transaksi

        if ($this->dbx->trans_status() === FALSE) {
            return $this->failed_response();
        } else {
            $response = array();
            $response['status'] = 201;
            $response['error'] = 0; // Ubah ke 0 integer biar konsisten dengan cek di controller
            $response['message'] = 'Booking was successfully created';
			$response['booking_id'] = $idbooking;   // tambahkan ID booking
			$response['house_bl']  = $bl;           // tambahkan nomor house_bl
            return $response;
        }
    }

    function get_shipper_detail($data)
    {
        $q2 = $this->dbx->get_where('mstshipper', array('shipper_name' => $data))->row();
        if (isset($q2)) {
            return array(
                "shipper" => $q2->id_shipper,
                "shipper_print" => $q2->shipper_name,
                "sales" => $q2->sales,
                "region_id" => $q2->region_id,
                "shipper_address" => $q2->shipper_address,
                "cp_email" => $q2->cp_email // DITAMBAHKAN untuk keperluan email
            );
        } else {
            return 'Username does not exist';
        }
    }

    public function failed_response()
    {
        $response['status'] = 400;
        $response['error'] = 1;
        $response['message'] = 'Gagal membuat shipping intruction';
        return $response;
    }

    public function failed_shipper()
    {
        $response = 'Username does not exist';
        return $response;
    }
}
?>