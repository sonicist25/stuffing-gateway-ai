br
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * M_rekap_sales_jobsheet
 * - Recalc/save summary jobsheet setiap kali income/cost berubah (tidak tergantung print PDF).
 *
 * Catatan:
 * - Fokus hitungan hanya: qty, unit_price, price, currency (sesuai request).
 * - Untuk kolom lain (shipper_name, sales, destination, volume, weight, dll) pakai data dari POST,
 *   atau boleh diisi via lookup jika mau.
 */
class M_rekap_sales_jobsheet extends CI_Model
{
    /** @var CI_DB_query_builder */
    protected $dbw;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbw = $this->load->database('master', TRUE);

        } else {
            $this->dbw = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    /** =========================
     * Helpers
     * ========================= */

    private function _decExpr($col)
    {
        // SQL expression: parse angka dari kolom varchar/decimal -> DECIMAL
        // buang comma & spasi; NULL/'' => 0
        // NOTE: tidak menghapus '.' agar decimal tetap.
        return "COALESCE(CAST(REPLACE(REPLACE(NULLIF($col,''), ',', ''), ' ', '') AS DECIMAL(18,4)), 0)";
    }

  
  /**
   * Recalc jobsheet untuk semua kode_ref dalam 1 consol/prefix.
   * $ref_no boleh kode_ref lengkap atau prefix/job_no.
   */
  public function recalc_and_save_jobsheet_prefix($ref_no, $region_id, $meta = array())
  {
    $prefix = $this->_prefixFromKodeRef($ref_no);

    // Ambil semua kode_ref yang satu consol berdasarkan prefix + region_id
    $this->dbw->distinct();
    $this->dbw->select('kode_ref');
    $this->dbw->like('kode_ref', $prefix, 'after');
    $this->dbw->where('region_id', $region_id);
    $q = $this->dbw->get('trnstuffingplandetail');

    $kodeRefs = array();
    if ($q && $q->num_rows() > 0) {
      foreach ($q->result() as $r) {
        if (!empty($r->kode_ref)) $kodeRefs[] = $r->kode_ref;
      }
    }

    // Fallback: kalau tidak ketemu di trnstuffingplandetail, cari dari inf_income
    if (empty($kodeRefs)) {
      $this->dbw->distinct();
      $this->dbw->select('kode_ref');
      $this->dbw->like('kode_ref', $prefix, 'after');
      $this->dbw->where('region_id', $region_id);
      $q2 = $this->dbw->get('inf_income');
      if ($q2 && $q2->num_rows() > 0) {
        foreach ($q2->result() as $r) {
          if (!empty($r->kode_ref)) $kodeRefs[] = $r->kode_ref;
        }
      }
    }

    $kodeRefs = array_values(array_unique($kodeRefs));

    $updated = 0;
    $errors  = array();
    foreach ($kodeRefs as $kode_ref) {
      $r = $this->recalc_and_save_jobsheet($kode_ref, $region_id, $meta);
      if (!empty($r['status'])) {
        $updated++;
      } else {
        $errors[] = array('kode_ref' => $kode_ref, 'message' => $r['message'] ?? 'failed');
      }
    }

    return array(
      'status'   => true,
      'message'  => 'OK',
      'prefix'   => $prefix,
      'count'    => count($kodeRefs),
      'updated'  => $updated,
      'errors'   => $errors
    );
  }


  /**
   * Buang suffix huruf di akhir kode_ref (mis. ...A, ...B).
   * Jika tidak ada suffix huruf, return as-is.
   */
  private function _jobNoFromKodeRef($kode_ref)
  {
    $kode_ref = (string)$kode_ref;
    if ($kode_ref === '') return '';
    return preg_replace('/[A-Za-z]+$/', '', $kode_ref);
  }

  /**
   * Prefix consol untuk group (dipakai di trnstuffingplandetail LIKE).
   * Default: ambil 15 karakter pertama dari job_no (kode_ref tanpa suffix huruf).
   */
  private function _prefixFromKodeRef($kode_ref)
  {
    $job_no = $this->_jobNoFromKodeRef($kode_ref);
    if ($job_no === '') return '';
    return substr($job_no, 0, 15);
  }

  private function _sumTableByCurrency($table, $whereSql, $binds, $currencyCol = 'currency', $qtyCol = 'qty', $unitCol = 'unit_price', $priceCol = 'price')
    {
        $cur = "UPPER(TRIM($currencyCol))";

        $qty  = $this->_decExpr($qtyCol);
        $unit = $this->_decExpr($unitCol);
        $price= $this->_decExpr($priceCol);

        // IMPORTANT: pakai "!= 0" (bukan >0) supaya nilai negatif tetap ikut (misal discount/refund)
        $amount = "CASE WHEN ($price) != 0 THEN ($price) ELSE ($qty) * ($unit) END";

        $sql = "
            SELECT
              SUM(CASE WHEN $cur = 'USD' THEN amt ELSE 0 END) AS usd,
              SUM(CASE WHEN $cur = 'USD' THEN 0 ELSE amt END) AS idr
            FROM (
              SELECT $currencyCol AS currency, $amount AS amt
              FROM $table
              WHERE $whereSql
            ) t
        ";

        $row = $this->dbw->query($sql, $binds)->row_array();
        return [
            'usd' => isset($row['usd']) ? (float)$row['usd'] : 0.0,
            'idr' => isset($row['idr']) ? (float)$row['idr'] : 0.0,
        ];
    }

    private function _safeFloat($v)
    {
        if ($v === null) return 0.0;
        if (is_numeric($v)) return (float)$v;
        $s = trim((string)$v);
        if ($s === '') return 0.0;

        // buang ribuan umum: spasi & comma
        $s = str_replace([' ', ','], '', $s);
        return (float)$s;
    }

    /** =========================
     * FIX get_qty_gbl_cost (sesuai request)
     * ========================= */

    public function get_bl_meas($kode_ref, $region_id)
    {
        // NOTE: gunakan dbr/replica jika ada; ini memakai dbw saja.
        $this->dbw->select('bl_meas');
        $q = $this->dbw->get_where('trnstuffingplandetail', [
            'kode_ref'  => $kode_ref,
            'region_id' => $region_id
        ], 1);

        $bl_meas = 0.0;
        if ($q->num_rows() > 0) {
            $bl_meas = $this->_safeFloat($q->row()->bl_meas);
        }
        return [$bl_meas, 'bl_meas'];
    }

    public function get_qty_gbl_cost($kode_ref, $region_id)
    {
        // Return: [qty_job, qty_total] angka FLOAT (bukan format string)
        $bl = $this->get_bl_meas($kode_ref, $region_id);
        $bl_meas = (float)$bl[0];

        $prefix = substr($kode_ref, 0, 15);

        $this->dbw->select_sum($bl[1]);
        $this->dbw->where('region_id', $region_id);
        // gunakan prefix (seperti versi lama: like('kode_ref', substr(...)))
        $this->dbw->like('kode_ref', $prefix, 'after');
        $sum_row = $this->dbw->get('trnstuffingplandetail')->row();
        $sum_meas = $sum_row ? $this->_safeFloat($sum_row->{$bl[1]}) : 0.0;

        if ($sum_meas == 0) {
            return [1.0, 1.0];
        }
        return [$bl_meas, $sum_meas];
    }

    /** =========================
     * MAIN: Recalc + Upsert save_jobsheet
     * ========================= */

    public function recalc_and_save_jobsheet($kode_ref, $region_id, array $meta = [])
    {
        $kode_ref = trim((string)$kode_ref);
        if ($kode_ref === '') {
            return ['status' => false, 'message' => 'kode_ref is required'];
        }

        // kurs: ambil dari POST jika ada, kalau tidak ambil dari inf_income (header)
        $kurs = isset($meta['kurs']) ? $this->_safeFloat($meta['kurs']) : null;
        if ($kurs === null || $kurs == 0.0) {
            $rowK = $this->dbw->select('kurs')->get_where('inf_income', ['kode_ref' => $kode_ref], 1)->row();
            $kurs = $rowK ? $this->_safeFloat($rowK->kurs) : 0.0;
        }
        $kursConv = ($kurs > 0) ? $kurs : 1.0;

        // --- LOCAL INCOME (3 tabel) ---
        $inc1 = $this->_sumTableByCurrency(
            'inf_income_selling_price',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $inc2 = $this->_sumTableByCurrency(
            'inf_income_from_agent',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $inc3 = $this->_sumTableByCurrency(
            'inf_income_from_coloader',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $income_usd_local = $inc1['usd'] + $inc2['usd'] + $inc3['usd'];
        $income_idr_local = $inc1['idr'] + $inc2['idr'] + $inc3['idr'];

        // --- LOCAL COST (3 tabel) ---
        $c0 = $this->_sumTableByCurrency(
            'inf_cost_tbl_satu',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $c1 = $this->_sumTableByCurrency(
            'inf_cost_refund_discount',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $c2 = $this->_sumTableByCurrency(
            'inf_cost_refund_sc',
            'kode_ref = ? AND region_id = ?',
            [$kode_ref, $region_id]
        );
        $cost_usd_local = $c0['usd'] + $c1['usd'] + $c2['usd'];
        $cost_idr_local = $c0['idr'] + $c1['idr'] + $c2['idr'];

        // --- GLOBAL SHARE FACTOR ---
        [$qty_job, $qty_total] = $this->get_qty_gbl_cost($kode_ref, $region_id);
        $ratio = ($qty_total != 0) ? ((float)$qty_job / (float)$qty_total) : 1.0;

        // job_no global biasanya tanpa suffix huruf -> pakai substr 0..15 agar sama dengan function lama
        $job_no = substr($kode_ref, 0, 15);

        // --- GLOBAL COST (x_glbcost) ---
        $gCost = $this->_sumTableByCurrency(
            'x_glbcost',
            'job_no = ? AND region_id = ?',
            [$job_no, $region_id],
            'currency', 'qty', 'unit_price', 'price'
        );
        $global_cost_usd = $gCost['usd'] * $ratio;
        $global_cost_idr = $gCost['idr'] * $ratio;

        // --- GLOBAL INCOME (x_glbincome) ---
        $gInc = $this->_sumTableByCurrency(
            'x_glbincome',
            'job_no = ? AND region_id = ?',
            [$job_no, $region_id],
            'currency', 'qty', 'unit_price', 'price'
        );
        $global_income_usd = $gInc['usd'] * $ratio;
        $global_income_idr = $gInc['idr'] * $ratio;

        // --- INV_AGENT (opsional, USD) ---
        // Jika ingin sama seperti print_jobsheet: ttl_debit_note($spd)
        $inv_agent_usd = 0.0;
        if (!empty($meta['spd'])) {
            // model ini ada di project kamu; jika tidak ada, set 0
            $this->load->model('afg_m_stuffing_plan');
            if (method_exists($this->afg_m_stuffing_plan, 'ttl_debit_note')) {
                $inv_agent_usd = (float)$this->afg_m_stuffing_plan->ttl_debit_note($meta['spd']);
            }
        }

        // =========================
        // FINAL FORMULA (fixed)
        // =========================
        $income = ($income_idr_local + $global_income_idr) + (($income_usd_local + $global_income_usd ) * $kursConv);
        $cost   = ($cost_idr_local   + $global_cost_idr)  + (($cost_usd_local   + $global_cost_usd) * $kursConv);

        $profit_idr = ($income_idr_local + $global_income_idr) - ($cost_idr_local + $global_cost_idr);
        $profit_usd = ($income_usd_local + $global_income_usd) - ($cost_usd_local + $global_cost_usd) ;

        $profit = $profit_idr + ($profit_usd * $kursConv);

        // pembulatan sesuai kolom DECIMAL(11,2)
        $round2 = function($x){ return round((float)$x, 2); };

        // =========================
        // UPSERT save_jobsheet
        // =========================
        $payload = [
            'kode_ref'        => $kode_ref,
            'profit'          => $round2($profit),
            'income'          => $round2($income),
            'cost'            => $round2($cost),

            'profit_usd'      => $round2($profit_usd),
            'profit_rp'       => $round2($profit_idr),

            'income_usd'      => $round2($income_usd_local),
            'income_rp'       => $round2($income_idr_local),

            'cost_usd'        => $round2($cost_usd_local),
            'cost_rp'         => $round2($cost_idr_local),

            'global_cost'     => $round2($global_cost_idr),
            'global_cost_usd' => $round2($global_cost_usd),

            'inv_agent'       => $round2($inv_agent_usd),

            // region wajib (biar konsisten filter)
            'region_id'       => $region_id,
        ];

        // Meta fields: hanya update kalau dikirim dan tidak kosong (supaya recalc tidak mengosongkan kolom non-finance)
        $metaMap = [
            'cost_coloader'    => 'cost_coloader',
            'shipper_name'     => 'shipper_name',
            'sales'            => 'sales',
            'destination'      => 'destination',
            'volume'           => 'volume',
            'weight'           => 'weight',
            'jumlah_container' => 'jumlah_container',
            'jenis_container'  => 'jenis_container',
            'sales_id'         => 'sales_id',
        ];
        foreach ($metaMap as $k => $dbField) {
            if (array_key_exists($k, $meta) && $meta[$k] !== null && $meta[$k] !== '') {
                $payload[$dbField] = ($k === 'sales_id') ? (int)$meta[$k] : $meta[$k];
                if ($k === 'cost_coloader') $payload[$dbField] = $round2($meta[$k]);
            }
        }

        $exists = $this->dbw->select('id')->get_where('save_jobsheet', ['kode_ref' => $kode_ref], 1)->row();
        if ($exists) {
            $this->dbw->where('id', $exists->id)->update('save_jobsheet', $payload);
        } else {
            // insert butuh semua kolom NOT NULL sesuai schema; set default kosong/0 untuk yang belum ada
            $defaults = [
                'shipper_name'     => '',
                'sales'            => '',
                'destination'      => '',
                'volume'           => '',
                'weight'           => '',
                'jumlah_container' => '',
                'jenis_container'  => '',
                'sales_id'         => null,
                'cost_coloader'    => 0.0,
            ];
            $this->dbw->insert('save_jobsheet', array_merge($defaults, $payload));
        }

        return [
            'status' => true,
            'message'=> 'OK',
            'meta'   => [
                'kurs' => $kurs,
                'kurs_conv' => $kursConv,
                'ratio' => $ratio,
                'qty_job' => $qty_job,
                'qty_total' => $qty_total,
            ],
            'totals' => [
                'income_rp' => $payload['income_rp'],
                'income_usd'=> $payload['income_usd'],
                'cost_rp'   => $payload['cost_rp'],
                'cost_usd'  => $payload['cost_usd'],
                'global_cost' => $payload['global_cost'],
                'global_cost_usd' => $payload['global_cost_usd'],
                'inv_agent' => $payload['inv_agent'],
                'income'    => $payload['income'],
                'cost'      => $payload['cost'],
                'profit_rp' => $payload['profit_rp'],
                'profit_usd'=> $payload['profit_usd'],
                'profit'    => $payload['profit'],
            ]
        ];
    }
}
