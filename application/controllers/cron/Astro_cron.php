<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Astro_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load helper yang baru saja dibuat
        $this->load->helper('astro');
        $this->load->helper('tracking_route');
        $this->load->model('api/M_api');
    }

    public function run_sync() {
        // Tarik 10 data teratas
        $limit = 10;
        $data_kargo = $this->M_api->get_target_cron_astro($limit);
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

            // Eksekusi Helper Scraping Web ASTRO
            $api_respon = get_astro_tracking($hbl);
            
            if ($api_respon['status'] === true && $api_respon['data']['is_found'] === true) {
                $dt = $api_respon['data'];

                // =======================================================
                // SKENARIO 1: KHUSUS DESTINASI SINGAPORE (Direct)
                // =======================================================
                if ($direct_sin) {
                    tracking_agent_eta($update_data, $row, $dt['eta_sin'] ?? '', 'ASTRO_ETA_SIN',
                        trim(($dt['cfs_vessel'] ?? '') . ' ' . ($dt['cfs_voyage'] ?? '')));
                    // Preserve existing direct-only delivery behavior, reject invalid/future dates.
                    $clearance = tracking_agent_date($dt['clearance_date'] ?? '', true);
                    if ($clearance) {
                        $update_data['cargo_delivery'] = json_encode(['Cargo delivered' => $clearance->format('d/m/Y')]);
                    }
                }
                if ($transit) {
                    $cont = trim((string)($dt['2nd_cont'] ?? ''));
                    $seal = trim((string)($dt['2nd_seal'] ?? ''));
                    if ($cont !== '' && $seal !== '') $cont .= ' / ' . $seal;
                    tracking_agent_etd($update_data, $row, $dt['2nd_etd'] ?? '', $cont);
                    tracking_agent_eta($update_data, $row, $dt['2nd_eta'] ?? '', 'ASTRO_2ND_ETA',
                        trim(($dt['2nd_vessel'] ?? '') . ' ' . ($dt['2nd_voyage'] ?? '')));
                    if (!empty($dt['2nd_mbl'])) $update_data['2nd_mbl_no'] = trim($dt['2nd_mbl']);
                }

                $log_api_status['Tracking'] = 'Sukses Dapat Data';
            } else {
                $log_api_status['Tracking'] = 'Gagal/Kosong';
            }

            // =======================================================
            // EKSEKUSI UPDATE DATABASE
            // =======================================================
            
            // Selalu update kolom waktu agar tidak terjadi loop berulang hari ini
            $update_data['updated_at'] = date('Y-m-d H:i:s');
			$update_data['updated_by'] = "CRON ASTRO";

            $this->db->where('id_tracking', (int)$row['id_tracking']);
            $this->db->where('hbl_no', $hbl);
            foreach (['destination','consol_destination','connecting','ata','atd','dft','ata_by','lt_pod_eta_at','lt_pod_eta_source','ctl_actual_pod_confirmed_at','ctl_actual_second_departure_at'] as $field) {
                $this->db->where($field, $row[$field] ?? null);
            }
            $this->db->update('x_tracking_export', $update_data);
            
            // Log status DB (Jika array > 1, artinya ada data asli yang diupdate selain updated_at)
            $db_status = count($update_data) > 1 ? 'Sukses Update Data & Waktu' : 'Hanya Update Waktu Pengecekan';

            // Masukkan ke array log untuk output Postman / Cron
            $response_log[] = [
                'hbl'           => $hbl,
                'kategori'      => ($destination !== 'SINGAPORE') ? 'NON-SIN' : 'SIN',
                'status_api'    => $log_api_status,
                'status_db'     => $db_status,
                'data_diupdate' => $update_data
            ];
        }

        // Output JSON untuk monitoring di Postman atau terminal Linux
        header('Content-Type: application/json');
        echo json_encode([
            'status'        => true,
            'total_hit_api' => count($data_kargo),
            'log_detail'    => $response_log
        ], JSON_PRETTY_PRINT);
    }
}