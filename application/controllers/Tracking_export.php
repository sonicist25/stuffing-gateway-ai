<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tracking_export extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->fungsi->restrict();
        $this->load->database();
        $this->load->helper(['form','url']);
        $this->load->library('excel');   // PHPExcel wrapper
        $this->load->model('afg_m_tracking');
    }

    /**
     * STEP 1: UPLOAD & PREVIEW (TANPA INSERT DB)
     * return: status, message, data (array row preview)
     */
    private function excel_date_to_php($excel_date)
    {
        if (!is_numeric($excel_date)) return $excel_date;
        // Excel base date 1900-01-01
        $unix = ($excel_date - 25569) * 86400;
        return gmdate("d/m/Y", $unix);
    }
    public function preview_import()
    {
        if (empty($_FILES['file_excel']['name'])) {
            return $this->_json_error('File Excel tidak ditemukan.');
        }

        $format_type = $this->input->post('format_type');  // 'HK' atau 'JOB'

        // ---- Upload ----
        $config['upload_path']   = APPPATH.'third_party/file/tracking/';
        $config['allowed_types'] = '*';
        $config['max_size']      = 20480;
        $config['file_name']     = 'preview_'.time();

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_excel')) {
            return $this->_json_error($this->upload->display_errors('', ''));
        }

        $file = $this->upload->data('full_path');

        // ---- Baca Excel ----
        try {
            // tentukan reader sesuai format file
            $reader = PHPExcel_IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);   // ini yang benar

            $excel = $reader->load($file);
        } catch (Exception $e) {
            @unlink($file);
            return $this->_json_error('Gagal membaca Excel: '.$e->getMessage());
        }



        $sheet      = $excel->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $data_preview = [];

        /**
         * FORMAT HK (template 1):
         *  Col A: HBL
         *  Col B: CONNECTING VESSEL
         *  Col C: ATD T/S PORT
         *  Col D: ATA DESTINATION
         *  Col E: NO CONTAINER
         *  Col F: COLLECT DO
         *  Col G: COLLECT CARGO
         *
         * FORMAT JOB (template lain):
         *  Col A: HBL
         *  Col B: COLLECT DO
         *  Col C: COLLECT CARGO
         */

        for ($row = 3; $row <= $highestRow; $row++)
        {
            if ($format_type == 'HK') {

                $hbl             = trim($sheet->getCellByColumnAndRow(0,$row)->getValue());
                if ($hbl == '') continue;       // Wajib

                $connecting_xls  = $sheet->getCellByColumnAndRow(2,$row)->getValue();
                $connecting_xls  = $connecting_xls !== null ? trim($connecting_xls) : '';

                // Ambil raw value dari Excel
                $raw_atd          = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                $raw_ata          = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                $raw_collect_do   = $sheet->getCellByColumnAndRow(6, $row)->getValue();
                $raw_collect_cargo= $sheet->getCellByColumnAndRow(7, $row)->getValue();
                
                $cont_xls        = $sheet->getCellByColumnAndRow(5,$row)->getValue();
                $cont_xls        = $cont_xls !== null ? trim($cont_xls) : '';
                // Konversi ke PHP date (jika ada value)
                $atd_ts_port          = $raw_atd !== null ? $this->excel_date_to_php($raw_atd) : null;
                $ata_destination          = $raw_ata !== null ? $this->excel_date_to_php($raw_ata) : null;
                $collect_do   = $raw_collect_do !== null ? $this->excel_date_to_php($raw_collect_do) : null;
                $collect_cargo= $raw_collect_cargo !== null ? $this->excel_date_to_php($raw_collect_cargo) : null;
            } else {

                $cellValue = $sheet->getCellByColumnAndRow(0, $row)->getValue(); 
                $hbl = $cellValue !== null ? trim($cellValue) : '';
                if ($hbl == '') continue;
                $raw_collect_do   = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                $raw_collect_cargo= $sheet->getCellByColumnAndRow(4, $row)->getValue();
                $collect_do   = $raw_collect_do !== null ? $this->excel_date_to_php($raw_collect_do) : null;
                $collect_cargo= $raw_collect_cargo !== null ? $this->excel_date_to_php($raw_collect_cargo) : null;

                // kolom lain tidak ada di Excel format ini
                $connecting_xls  = null;
                $atd_ts_port     = null;
                $ata_destination = null;
                $cont_xls        = null;
            }

            // ---- AMBIL DATA DARI DATABASE BERDASARKAN HBL ----
            $db = $this->afg_m_tracking->get_detail_by_hbl($hbl);

            // kalau tidak ketemu di DB, biarkan null supaya kelihatan di preview
            if (!$db) {
                $db = [
                    'no_job'           => null,
                    'id_shipper'       => null,
                    'id_request'       => null,
                    'packaging'        => null,
                    'id_agent'         => null,
                    'mbl_no'           => null,
                    'hbl_no'           => $hbl,
                    'feeder_name'      => null,
                    'etd'              => null,
                    'atd'              => null,
                    'eta'              => null,
                    'ata'              => null,
                    'shipper'          => null,
                    'connecting'       => null,
                    'consol_destination'=> null,
                    'consignee'        => null,
                    'destination'      => null,
                    'container_no'     => null,
                    'sp_detail_id'     => null,
                    'region_id'        => null,
                ];
            }

            // ---- Gabungkan ke satu row preview ----
            $row_values = [
                // DATA DB (18 field)
                'no_job'            => $db['no_job'],
                'id_shipper'        => $db['id_shipper'],
                'id_request'        => $db['id_request'],
                'packaging'         => $db['packaging'],
                'id_agent'          => $db['id_agent'],
                'mbl_no'            => $db['mbl_no'],
                'hbl_no'            => $db['hbl_no'],
                'feeder_name'       => $db['feeder_name'],
                'etd'               => $db['etd'],
                'atd'               => $db['atd'],
                'eta'               => $db['eta'],
                'ata'               => $db['ata'],
                'shipper'           => $db['shipper'],
                'connecting_db'     => $db['connecting'],
                'consol_destination'=> $db['consol_destination'],
                'consignee'         => $db['consignee'],
                'destination'       => $db['destination'],
                'container_no_db'   => $db['container_no'],
                'sp_detail_id'      => $db['sp_detail_id'],
                'region_id'         => $db['region_id'],

                // DATA EXCEL (warna beda)
                'connecting_excel'  => $connecting_xls,
                'container_no_excel'=> $cont_xls,
                'atd_ts_port'       => $atd_ts_port,
                'ata_destination'   => $ata_destination,
                'collect_do'        => $collect_do,
                'collect_cargo'     => $collect_cargo,
            ];

            // penanda sumber untuk pewarnaan cell
            $source_map = [];

            foreach ($row_values as $key => $val) {
                if (in_array($key, [
                    'connecting_excel','atd_ts_port','ata_destination',
                    'container_no_excel','collect_do','collect_cargo'
                ])) {
                    $source_map[$key] = 'excel';
                } else {
                    $source_map[$key] = 'db';
                }
            }

            // simpan source di field khusus
            $row_values['__source'] = $source_map;

            $data_preview[] = $row_values;
        }

        @unlink($file);

        if (empty($data_preview)) {
            return $this->_json_error('Tidak ada data valid yang terbaca.');
        }

        return $this->_json_ok('Preview berhasil. Silakan cek dan klik Insert ke Database.', [
            'data' => $data_preview
        ]);
    }

    /**
     * STEP 2: INSERT KE DATABASE (DARI DATA PREVIEW)
     */
    public function insert_import()
    {
        $format_type = $this->input->post('format_type');
        $json = $this->input->post('data');
        if (!$json) {
            return $this->_json_error('Data kosong.');
        }

        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            return $this->_json_error('Format data tidak valid.');
        }

        $region_session = $this->session->userdata('region_id');
        $user           = $this->session->userdata('username');
        $now            = date('Y-m-d H:i:s');
        $count          = 0;

        foreach ($rows as $r) {

            if (!isset($r['hbl_no']) || $r['hbl_no'] == '') {
                continue;
            }
        
            // ambil value DB + Excel
            $connecting_db   = $r['connecting_db']     ?? null;
            $connecting_xls  = $r['connecting_excel']  ?? null;
            $cont_db         = $r['container_no_db']   ?? null;
            $cont_xls        = $r['container_no_excel']?? null;
            $collect_do      = $r['collect_do']        ?? null;
            $collect_cargo   = $r['collect_cargo']     ?? null;
            $atd_ts_port     = $r['atd_ts_port']       ?? null;
            $ata_destination = $r['ata_destination']   ?? null;

            // final value (Excel override DB)
            $connecting_final = $connecting_xls !== '' ? $connecting_xls : $connecting_db;
            $container_final  = $cont_xls       !== '' ? $cont_xls       : $cont_db;


            // pakai region dari session jika ada, kalau tidak pakai yang dari DB
            $region_id_final = $region_session ?: ($r['region_id'] ?? null);

            // DATA UNTUK TABEL x_tracking_export
            $data_insert = [
                'format_type'        => $format_type,   
                'no_job'             => $r['no_job']          ?? null,
                'id_shipper'         => $r['id_shipper']      ?? null,
                'id_request'         => $r['id_request']      ?? null,
                'packaging'          => $r['packaging']       ?? null,
                'id_agent'           => $r['id_agent']        ?? null,
                'mbl_no'             => $r['mbl_no']          ?? null,
                'hbl_no'             => $r['hbl_no'],
                'feeder_name'        => $r['feeder_name']     ?? null,
                'etd'                => $r['etd']             ?? null,
                'eta'                => $r['eta']             ?? null,
                'ata'                => $r['ata'] ?? null, 
                'shipper'            => $r['shipper']         ?? null,
                'connecting_db'       => $connecting_db,
                'connecting_excel'       => $connecting_xls,
                'consol_destination' => $r['consol_destination'] ?? null,
                'consignee'          => $r['consignee']       ?? null,
                'destination'        => $r['destination']     ?? null,
                'container_no'       => $r['container_no_db'], 
                'container_trans'    => $cont_xls, 
                'atd_ts_port'        => $r['atd_ts_port'], 
                'ata_destination'        => $r['ata_destination'], 
                'remarks'            => null,
                'sp_detail_id'       => $r['sp_detail_id']    ?? null,
                'region_id'          => $region_id_final,
                'pickup_cargo'       => null,
                'atd'                => $r['atd']    ?? null,
                'collect_do'          => $collect_do,
                'collect_cargo'     => $collect_cargo,
                'prepared'           => 0,
                'created_by'         => $user,
                'created_at'         => $now,
                'updated_by'         => $user,
                'updated_at'         => $now
            ];

            // UP SERT
            $this->afg_m_tracking->upsert_tracking_export($data_insert);
            $count++;
        }

        if ($count > 0) {
            return $this->_json_ok("Berhasil insert/ update $count baris ke x_tracking_export.");
        } else {
            return $this->_json_error("Tidak ada baris yang diinsert.");
        }
    }

    /* Helper JSON */
    private function _json_ok($msg, $extra = [])
    {
        $out = array_merge([
            'status'  => 'ok',
            'message' => $msg
        ], $extra);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($out));
    }

    private function _json_error($msg, $extra = [])
    {
        $out = array_merge([
            'status'  => 'error',
            'message' => $msg
        ], $extra);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($out));
    }
}
