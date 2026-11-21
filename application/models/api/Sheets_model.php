<?php
#[\AllowDynamicProperties]
class Sheets_model extends CI_Model {

    /** @var CI_DB_query_builder */
    public $dbw;   // master/write
    /** @var CI_DB_query_builder */
    public $dbr;   // replica/read

    public $db2;
    public $second_db;

    public function __construct() {
        parent::__construct();

        $tahun = '2026';

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbw       = $this->load->database('master', TRUE);
            $this->db2       = $this->load->database('second', TRUE);
            $this->second_db = $this->load->database('reference', TRUE);
            $this->dbr       = $this->load->database('replica', TRUE);
        } else {
            $this->dbw       = $this->load->database(switch_db($tahun), TRUE);
            $this->db2       = $this->load->database('second', TRUE);
            $this->second_db = $this->load->database('reference', TRUE);
            $this->dbr       = $this->load->database('replica', TRUE);
        }
    }

  /** =========================
   *  READ dari replica (API GET)
   *  ========================= */
public function fetch_tracking($region, $limit, $warehouse = 'ALL') {
  $eta_sql       = $this->date_any_to_ddmmyyyy_sql('eta');
  $stripping_sql = $this->date_any_to_ddmmyyyy_sql('stripping_date');
  $do_sql        = $this->date_any_to_ddmmyyyy_sql('pickup_do');
  $cargo_sql     = $this->date_any_to_ddmmyyyy_sql('pickup_cargo');

  $sql = "
    SELECT
      no_job             AS NO_JOB,
      {$eta_sql}         AS ETA,
      hbl_no             AS HBL,
      consignee          AS CONSIGNEE,
      destination        AS POL,
      container_no       AS CONTAINER,
      {$stripping_sql}   AS STRIPPING,
      {$do_sql}          AS RELEASE_DO,
      {$cargo_sql}       AS RELEASE_CARGO,
      warehouse          AS GUDANG
    FROM x_tracking
    WHERE region_id = ?
  ";

  $bind = [$region];

  if ($warehouse !== 'ALL') {
    $sql .= " AND warehouse = ? ";
    $bind[] = $warehouse; // 'PRJP' atau 'AIRIN'
  }

  $sql .= " ORDER BY id_tracking DESC LIMIT ? ";
  $bind[] = (int)$limit;

  $q = $this->dbr->query($sql, $bind);
  return array_map('array_values', $q->result_array());
}


/** Terima variasi: dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, yyyy/mm/dd, yyyymmdd, DATE/DATETIME; balas 'dd/mm/yyyy' atau '' */
private function fmt_ddmmyyyy($v) {
  if ($v === null) return '';
  $s = is_string($v) ? trim($v) : '';
  // yyyymmdd
  if (preg_match('/^\d{8}$/', $s)) {
    $y = substr($s,0,4); $m=substr($s,4,2); $d=substr($s,6,2);
    return $this->valid_date($y,$m,$d) ? sprintf('%02d/%02d/%04d',$d,$m,$y) : '';
  }
  // dd/mm/yyyy | dd-mm-yyyy
  if (preg_match('#^(\d{2})[/-](\d{2})[/-](\d{4})$#', $s, $m)) {
    return $this->valid_date($m[3],$m[2],$m[1]) ? sprintf('%02d/%02d/%04d',$m[1],$m[2],$m[3]) : '';
  }
  // yyyy-mm-dd | yyyy/mm/dd
  if (preg_match('#^(\d{4})[/-](\d{2})[/-](\d{2})$#', $s, $m)) {
    return $this->valid_date($m[1],$m[2],$m[3]) ? sprintf('%02d/%02d/%04d',$m[3],$m[2],$m[1]) : '';
  }
  // native Date/Datetime string (biarkan DateTime yang urus)
  try {
    $dt = new DateTime($s);
    return $dt ? $dt->format('d/m/Y') : '';
  } catch (Exception $e) {
    return '';
  }
}

private function valid_date($Y,$m,$d) {
  return checkdate((int)$m,(int)$d,(int)$Y);
}


  /**
   * Bangun ekspresi SQL yang:
   * - Mendeteksi berbagai pola tanggal (varchar atau date)
   * - Mengubah ke DATE
   * - Me-return string dd/mm/yyyy (atau '' jika tidak valid)
   */
  private function date_any_to_ddmmyyyy_sql($col) {
    // Catatan:
    // - Tangani NULL, '' dan '0000-00-00'
    // - Pola yang didukung:
    //   dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, yyyy/mm/dd, yyyymmdd, dan native DATE
    return "
      CASE
        WHEN {$col} IS NULL OR {$col} = '' OR {$col} = '0000-00-00' THEN ''
        WHEN {$col} REGEXP '^[0-9]{8}$'
          THEN DATE_FORMAT(STR_TO_DATE({$col}, '%Y%m%d'), '%d/%m/%Y')
        WHEN {$col} REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
          THEN DATE_FORMAT(STR_TO_DATE({$col}, '%d/%m/%Y'), '%d/%m/%Y')
        WHEN {$col} REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}$'
          THEN DATE_FORMAT(STR_TO_DATE({$col}, '%d-%m-%Y'), '%d/%m/%Y')
        WHEN {$col} REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
          THEN DATE_FORMAT(STR_TO_DATE({$col}, '%Y-%m-%d'), '%d/%m/%Y')
        WHEN {$col} REGEXP '^[0-9]{4}/[0-9]{2}/[0-9]{2}$'
          THEN DATE_FORMAT(STR_TO_DATE({$col}, '%Y/%m/%d'), '%d/%m/%Y')
        ELSE
          -- fallback: jika kolom bertipe DATE/DATETIME, DATE_FORMAT akan bekerja
          DATE_FORMAT(CAST({$col} AS DATE), '%d/%m/%Y')
      END
    ";
  }

  /** =========================
   *  WRITE ke master (API POST)
   *  Menerima dd/mm/yyyy, dd-mm-yyyy, yyyymmdd, yyyy-mm-dd, yyyy/mm/dd
   *  ========================= */
public function apply_updates($idemKey, array $updates) {
  // ... (bagian awal tetap)
  $allowed = ['stripping_date','pickup_do','pickup_cargo'];
  $updated = 0; $skipped = 0; $skipped_reasons = [];

  foreach ($updates as $u) {
    $field = isset($u['field']) ? strtolower(trim($u['field'])) : '';
    $hbl   = isset($u['hbl_no']) ? trim($u['hbl_no']) : '';
    $hasClear = array_key_exists('clear', $u) && $u['clear'];   // <--- NEW
    $valRaw = isset($u['value']) ? $u['value'] : null;

    if ($hbl === '' || !in_array($field, $allowed, true)) {
      $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'invalid_field_or_empty']; 
      continue;
    }

    // Ambil nilai saat ini
    $q = $this->dbw->query("SELECT {$field} AS cur FROM x_tracking WHERE TRIM(hbl_no)=TRIM(?) LIMIT 1", [$hbl]);
    if ($q->num_rows() === 0) {
      $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'not_found']; 
      continue;
    }
    $cur = $q->row()->cur; // bisa NULL/DATE/VARCHAR

    // === CASE CLEAR (hapus tanggal) ===
    if ($hasClear || $valRaw === '' || $valRaw === null) {
      // kalau sudah NULL, anggap same_value
      if ($cur === null || $cur === '0000-00-00' || $cur === '') {
        $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'same_value_null']; 
        continue;
      }
      $this->dbw->query("UPDATE x_tracking SET {$field} = NULL WHERE TRIM(hbl_no)=TRIM(?)", [$hbl]);
      if ($this->dbw->affected_rows() > 0) $updated++;
      else { $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'not_changed']; }
      continue;
    }

    // === CASE SET TANGGAL (ada value) ===
    $val = trim((string)$valRaw);
    $ymd = $this->normalize_to_ymd($val); // fungsi normalisasi yang sudah ada
    if (!$ymd) {
      $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'bad_date_format','value'=>$val]; 
      continue;
    }

    // bandingkan dengan nilai lama (normalisasi lama juga)
    $curYmd = $this->normalize_to_ymd((string)$cur);
    if ($curYmd === $ymd) {
      $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'same_value','value'=>$val]; 
      continue;
    }

    $this->dbw->query("UPDATE x_tracking SET {$field} = ? WHERE TRIM(hbl_no)=TRIM(?)", [$ymd, $hbl]);
    if ($this->dbw->affected_rows() > 0) $updated++;
    else { $skipped++; $skipped_reasons[] = ['hbl'=>$hbl,'field'=>$field,'reason'=>'not_changed']; }
  }

  $this->dbw->trans_complete();
  return [
    'ok' => $this->dbw->trans_status(),
    'updated_rows' => $updated,
    'skipped' => $skipped,
    'skipped_sample' => array_slice($skipped_reasons, 0, 5)
  ];
}

/** normalisasi berbagai format tanggal → 'Y-m-d' */
private function normalize_to_ymd($s) {
  $s = trim($s);
  if ($s === '') return null;
  if (preg_match('/^\d{8}$/', $s)) { // yyyymmdd
    $y=substr($s,0,4); $m=substr($s,4,2); $d=substr($s,6,2);
    return checkdate($m,$d,$y) ? sprintf('%04d-%02d-%02d',$y,$m,$d) : null;
  }
  if (preg_match('#^(\d{2})[/-](\d{2})[/-](\d{4})$#', $s, $m)) { // dd/mm/yyyy | dd-mm-yyyy
    return checkdate($m[2],$m[1],$m[3]) ? sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]) : null;
  }
  if (preg_match('#^(\d{4})[/-](\d{2})[/-](\d{2})$#', $s, $m)) { // yyyy-mm-dd | yyyy/mm/dd
    return checkdate($m[2],$m[3],$m[1]) ? sprintf('%04d-%02d-%02d',$m[1],$m[2],$m[3]) : null;
  }
  // fallback
  $fmts = ['d/m/Y','d-m-Y','Ymd','Y-m-d','Y/m/d'];
  foreach ($fmts as $fmt) {
    $dt = DateTime::createFromFormat('!'.$fmt, $s);
    if ($dt && $dt->format($fmt) === $s) return $dt->format('Y-m-d');
  }
  return null;
}

  private function checkdate_or_null($Y,$m,$d) {
    if (checkdate((int)$m,(int)$d,(int)$Y)) {
      return sprintf('%04d-%02d-%02d', $Y, $m, $d);
    }
    return null;
  }
}
