<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Benkel_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load helper yang baru saja dibuat
        $this->load->helper('benkel');
        $this->load->helper('tracking_route');
        $this->load->model('api/M_api');
    }

    public function run_sync() {
		
		// Tarik 10 data teratas
		$limit = (int) $this->input->get('limit');
		if ($limit <= 0) {
			$limit = 10;
		}
		if ($limit > 100) {
			$limit = 100; // supaya aman, jangan kebanyakan hit API
		}

		$data_kargo = $this->M_api->get_target_cron_benkel($limit);
		$response_log = [];

		foreach ($data_kargo as $row) {
			$hbl = trim($row['hbl_no'] ?? '');
			$destination = tracking_route_port($row['destination'] ?? '');
            $route_kind = tracking_route_kind($row);
            $direct_sin = $route_kind === 'DIRECT' && $destination === 'SINGAPORE';
            $transit = $route_kind === 'TRANSIT' && tracking_route_port($row['consol_destination'] ?? '') === 'SINGAPORE';
            if (!$direct_sin && !$transit) {
                $response_log[] = ['hbl'=>$hbl,'status_api'=>'SKIPPED_UNSUPPORTED_ROUTE'];
                continue;
            }
			
			$update_data = [];
			$log_api_status = [];

			// Hit API Benkel (Fr8labs)
			$api_respon = get_benkel_tracking($hbl);
			
			if ($api_respon['status'] === true && !empty($api_respon['data'])) {
				$import_leg = null;
				$export_leg = null;
				
				// Pisahkan data Import (Kapal 1) dan Export (Kapal 2)
				foreach ($api_respon['data'] as $leg) {
					if (strcasecmp($leg['Direction'], 'Import') === 0) {
						$import_leg = $leg;
					} elseif (strcasecmp($leg['Direction'], 'Export') === 0) {
						$export_leg = $leg;
					}
				}

				// =======================================================
				// 1. DATA TRANSHIPMENT (KHUSUS NON-SINGAPORE)
				// =======================================================
                if ($transit && !empty($export_leg)) {
                    tracking_agent_etd($update_data, $row, $export_leg['ETD'] ?? '', $export_leg['CNTR/SEAL/SIZE'] ?? '');
                    tracking_agent_eta($update_data, $row, $export_leg['ETA'] ?? '', 'BENKEL_EXPORT_ETA',
                        $export_leg['Vessel / Voyage'] ?? '');
                    if (!empty($export_leg['MBL No'])) $update_data['2nd_mbl_no'] = $export_leg['MBL No'];
                }
                if ($direct_sin && !empty($import_leg)) {
                    tracking_agent_eta($update_data, $row, $import_leg['ETA'] ?? '', 'BENKEL_IMPORT_ETA',
                        $import_leg['Vessel / Voyage'] ?? '');
                }

                // No import-leg release fallback for transit. Preserve the existing
                // release-to-delivery convention on the selected leg only.
                $final_leg = $transit ? $export_leg : $import_leg;
                $cargo_date = tracking_agent_date($final_leg['Cargo Released Date'] ?? '', true);
                $do_date = tracking_agent_date($final_leg['DO Release Date'] ?? '', true);
                if ($cargo_date) $update_data['cargo_delivery'] = json_encode(['Cargo delivered' => $cargo_date->format('d/m/Y')]);
                if ($do_date) $update_data['pickup_do'] = json_encode(['Pickup DO' => $do_date->format('d/m/Y')]);

				$log_api_status['Tracking'] = 'Sukses Dapat Data';
			} else {
				$log_api_status['Tracking'] = 'Gagal/Kosong';
			}

			// =======================================================
			// 3. EKSEKUSI UPDATE DATABASE
			// =======================================================
			// Selalu update waktu pengecekan agar tidak diloop hari ini
			$update_data['updated_at'] = date('Y-m-d H:i:s');
			$update_data['updated_by'] = "CRON BENKEL";

			$this->db->where('id_tracking', (int)$row['id_tracking']);
            $this->db->where('hbl_no', $hbl);
            foreach (['destination','consol_destination','connecting','ata','atd','dft','ata_by','lt_pod_eta_at','lt_pod_eta_source','ctl_actual_pod_confirmed_at','ctl_actual_second_departure_at'] as $field) {
                $this->db->where($field, $row[$field] ?? null);
            }
			$this->db->update('x_tracking_export', $update_data);
			
			$db_status = count($update_data) > 1 ? 'Sukses Update Data & Waktu' : 'Hanya Update Waktu Pengecekan';

			$response_log[] = [
				'hbl' => $hbl,
				'kategori' => ($destination !== 'SINGAPORE') ? 'NON-SIN' : 'SIN',
				'status_api' => $log_api_status,
				'status_db' => $db_status,
				'data_diupdate' => $update_data
			];
		}

		// Output untuk pengujian Postman
		header('Content-Type: application/json');
		echo json_encode([
			'status' => true,
			'total_hit_api' => count($data_kargo),
			'log_detail' => $response_log
		], JSON_PRETTY_PRINT);
	}
}