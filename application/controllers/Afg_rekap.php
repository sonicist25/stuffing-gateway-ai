<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller: Afg_rekap
 * Endpoint untuk trigger recalculation profit/jobsheet.
 * Dipanggil dari form_income (on close / debounced), dan/atau dari controller saving income/cost.
 */
class Afg_rekap extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_rekap_sales_jobsheet', 'm_jobsheet');
    }

    public function recalc_profit()
    {
        // pakai session login; jika butuh auth tambahan, tambahkan cek di sini.
        $kode_ref = $this->input->post('kode_ref', true);
        $region_id = $this->session->userdata('region_id');

        // data tambahan (opsional) untuk isi kolom non-finance
        $meta = [
            'kurs'            => $this->input->post('kurs', true),
            'spd'             => $this->input->post('spd', true), // sp_detail_id (optional) untuk ttl_debit_note

            'shipper_name'    => $this->input->post('shipper_name', true),
            'sales'           => $this->input->post('sales', true),
            'destination'     => $this->input->post('destination', true),
            'volume'          => $this->input->post('volume', true),
            'weight'          => $this->input->post('weight', true),
            'jumlah_container'=> $this->input->post('jumlah_container', true),
            'jenis_container' => $this->input->post('jenis_container', true),
            'sales_id'        => $this->input->post('sales_id', true),
            'cost_coloader'   => $this->input->post('cost_coloader', true),
        ];

        $res = $this->m_jobsheet->recalc_and_save_jobsheet($kode_ref, $region_id, $meta);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($res));
    }

  /**
   * Recalc jobsheet untuk semua kode_ref dalam 1 consol/prefix (dipakai untuk Global Cost/Income).
   * JS kirim: ref_no (prefix/job no), optional kurs.
   */
  public function recalc_profit_prefix()
  {
    $ref_no = $this->input->post('ref_no', true);
    $kurs   = $this->input->post('kurs', true);

    $region_id = $this->session->userdata('region_id');

    if (empty($ref_no)) {
      echo json_encode(array(
        'status' => false,
        'message' => 'ref_no is required'
      ));
      return;
    }

    $meta = array();
    if ($kurs !== null && $kurs !== '') $meta['kurs'] = $kurs;

    $result = $this->m_jobsheet->recalc_and_save_jobsheet_prefix($ref_no, $region_id, $meta);

    echo json_encode($result);
  }

}
