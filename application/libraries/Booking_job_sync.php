<?php
/** Shared transaction and booking/job invariants. Always use the caller's write DB. */
class Booking_job_sync
{
    private $db;
    public $stage = '';
    public $dbError = '';
    public $dbCode = 0;
    private $active = false;
    private $debug;
    private $strict;

    public function __construct($db) { $this->db = $db; }

    public function begin()
    {
        $this->debug = $this->db->db_debug;
        $this->strict = $this->db->trans_strict;
        $this->db->db_debug = false;
        // CI3 retains failed transaction status in strict mode. trans_complete()
        // in non-strict mode resets it, so the next booking in a batch can run.
        $this->db->trans_strict(false);
        try {
            $this->check($this->db->trans_begin(), 'BEGIN');
            $this->active = true;
        } catch (Throwable $e) {
            $this->finish();
            throw $e;
        }
    }

    public function check($result, $stage)
    {
        $this->stage = $stage;
        if ($result === false) {
            $err = $this->db->error();
            $this->dbError = (string)($err['message'] ?? '');
            $this->dbCode = (int)($err['code'] ?? 0);
            log_message('error', '[BOOKING_JOB][' . $stage . '] db=' . $this->db->database
                . ' code=' . $this->dbCode . ' error=' . $this->dbError);
            throw new RuntimeException($this->dbError ?: 'Query gagal: ' . $stage);
        }
        return $result;
    }

    public function commit()
    {
        if ($this->db->trans_status() === false) {
            throw new RuntimeException('Transaction database gagal.');
        }
        $this->check($this->db->trans_commit(), 'COMMIT');
        $this->active = false;
    }

    public function finish()
    {
        if ($this->active) {
            // If CI marked a query failed, let trans_complete rollback and reset
            // the sticky failure flag. Otherwise rollback business-rule errors.
            if ($this->db->trans_status() === false) {
                $this->db->trans_complete();
            } else {
                $this->db->trans_rollback();
            }
            $this->active = false;
        }
        $this->db->db_debug = $this->debug;
        $this->db->trans_strict($this->strict);
    }

    // Refuse writes if rollback cannot be guaranteed for the participating tables.
    public function requireInnoDB(array $tables)
    {
        $rows = $this->check($this->db->select('TABLE_NAME, ENGINE')
            ->where('TABLE_SCHEMA',$this->db->database)->where_in('TABLE_NAME',$tables)
            ->get('information_schema.TABLES'), 'CHECK_TABLE_ENGINES')->result_array();
        $engines = [];
        foreach ($rows as $r) $engines[strtolower($r['TABLE_NAME'])] = strtoupper((string)$r['ENGINE']);
        foreach ($tables as $table) {
            if (($engines[strtolower($table)] ?? '') !== 'INNODB') throw new DomainException('Tabel '.$table.' tidak ditemukan atau bukan InnoDB; transaction tidak aman.');
        }
    }

    public function lockPlan($id, $region)
    {
        $rows = $this->check($this->db->query(
            'SELECT * FROM trnstuffingplan WHERE sp_id = ? AND region_id = ? FOR UPDATE',
            [$id, $region]), 'LOCK_PLAN')->result_array();
        if (count($rows) !== 1) throw new DomainException('Stuffing plan tidak ditemukan atau ambigu pada region aktif.');
        return $rows[0];
    }

    public function lockBooking($bc)
    {
        $rows = $this->check($this->db->query(
            'SELECT * FROM trnstuffingplan_booking WHERE booking_code = ? FOR UPDATE',
            [$bc]), 'LOCK_BOOKING')->result_array();
        if (count($rows) !== 1) throw new DomainException('Master booking tidak ditemukan atau ambigu: ' . $bc);
        return $rows[0];
    }

    public function details($bc)
    {
        return $this->check($this->db->query(
            'SELECT sp_detail_id, sp_id, code, kode_ref, num, region_id, booking_code FROM trnstuffingplandetail WHERE booking_code = ? FOR UPDATE',
            [$bc]), 'LOCK_BOOKING_DETAILS')->result_array();
    }

    // Call only with booking lock held. Never choose an arbitrary job for an ambiguous BC.
    public function sync($bc, array $extra = [], $allowEmpty = false)
    {
        $rows = $this->details($bc);
        if (!$rows && !$allowEmpty) throw new DomainException('Detail booking tidak ditemukan: ' . $bc);
        $job = '';
        if ($rows) {
            $job = trim((string)$rows[0]['kode_ref']);
            if ($job === '') throw new DomainException('Kode ref detail kosong: ' . $bc);
            foreach ($rows as $r) {
                if ($r['kode_ref'] !== $rows[0]['kode_ref'] || (string)$r['sp_id'] !== (string)$rows[0]['sp_id'] || $r['region_id'] !== $rows[0]['region_id']) {
                    throw new DomainException('Booking terhubung ke beberapa job/plan: ' . $bc . '. Audit manual diperlukan.');
                }
            }
        }
        $extra['status'] = $rows ? 'booked' : 'finalize';
        $extra['job_no'] = $job;
        $this->check($this->db->where('booking_code', $bc)->update('trnstuffingplan_booking', $extra), 'SYNC_BOOKING');
        $v = $this->check($this->db->query('SELECT status, job_no FROM trnstuffingplan_booking WHERE booking_code = ? FOR UPDATE', [$bc]), 'VERIFY_BOOKING')->row_array();
        if (!$v || $v['status'] !== $extra['status'] || $v['job_no'] !== $job) {
            throw new RuntimeException('Verifikasi status/job_no booking gagal: ' . $bc);
        }
        return $job;
    }

    public function planSchedule(array $plan)
    {
        // COLOAD / flexible (0/null) retains original booking schedule.
        return (int)($plan['schedule_id'] ?? 0) > 0 ? ['schedule_id'=>(int)$plan['schedule_id']] : [];
    }

    public function freeSuffix(array $plan, $region, $requested = '', $excludeId = 0)
    {
        $rows = $this->check($this->db->query(
            'SELECT sp_detail_id, num, kode_ref FROM trnstuffingplandetail WHERE region_id = ? AND (sp_id = ? OR code = ?) FOR UPDATE',
            [$region, $plan['sp_id'], $plan['kode_ref']]), 'LOCK_JOB_SLOTS')->result_array();
        $used = []; $refs = [];
        foreach ($rows as $r) {
            if ((string)$r['sp_detail_id'] === (string)$excludeId) continue;
            $used[strtoupper(trim((string)$r['num']))] = true;
            $refs[strtoupper(trim((string)$r['kode_ref']))] = true;
        }
        $candidates = [];
        if (preg_match('/^(?:[A-Z]|Z[A-Z]|Z[0-9]{1,5})$/D', $requested)) $candidates[] = $requested;
        for ($i=0; $i<26; $i++) $candidates[] = chr(65+$i);
        for ($i=0; $i<26; $i++) $candidates[] = 'Z'.chr(65+$i);
        for ($i=0; $i<1000; $i++) $candidates[] = 'Z'.$i;
        foreach ($candidates as $c) {
            $ref = $plan['kode_ref'].$c;
            if (strlen($ref) <= 18 && !isset($used[$c]) && !isset($refs[strtoupper($ref)])) return $c;
        }
        throw new DomainException('Tidak ada suffix job yang tersedia.');
    }

    public function move($spd, $target, $region, $user, $oldRef = '', $requested = '', $expectedTargetCode = '')
    {
        $this->begin();
        try {
            $this->requireInnoDB(['trnstuffingplan','trnstuffingplan_booking','trnstuffingplandetail','tbl_detail','x_tracking_export']);
            // Read identity first, then lock plans in a consistent order and revalidate.
            $src = $this->check($this->db->get_where('trnstuffingplandetail', ['sp_detail_id'=>$spd,'region_id'=>$region]), 'LOAD_SOURCE')->row_array();
            if (!$src || ($oldRef !== '' && $src['kode_ref'] !== $oldRef)) throw new DomainException('Detail asal tidak ditemukan atau job sudah berubah.');
            $ids = array_unique([(int)$src['sp_id'], (int)$target]); sort($ids, SORT_NUMERIC);
            $plans = [];
            foreach ($ids as $id) $plans[$id] = $this->lockPlan($id, $region);
            $plan = $plans[(int)$target];
            if (trim((string)$plan['kode_ref']) === '' || ($expectedTargetCode !== '' && $plan['kode_ref'] !== $expectedTargetCode)) throw new DomainException('Master job tujuan berubah. Muat ulang job.');
            $bc = trim((string)($src['booking_code'] ?? ''));
            if ($bc !== '') $this->lockBooking($bc);
            $curr = $this->check($this->db->query('SELECT * FROM trnstuffingplandetail WHERE sp_detail_id = ? AND region_id = ? FOR UPDATE', [$spd,$region]), 'LOCK_SOURCE')->row_array();
            if (!$curr || (string)$curr['sp_id'] !== (string)$src['sp_id'] || $curr['kode_ref'] !== $src['kode_ref'] || $curr['booking_code'] !== $src['booking_code']) throw new DomainException('Detail berubah; muat ulang job.');
            if ($bc !== '' && count($this->details($bc)) !== 1) throw new DomainException('Booking mempunyai beberapa detail. Pindah manual tidak aman: '.$bc);
            // Same master plan is not necessarily the same child job.
            // A requested suffix change (e.g. M -> E) must follow the full move path.
            $samePlan = (int)$src['sp_id'] === (int)$target;
            $requested = strtoupper(trim((string)$requested));
            if ($requested !== '' && !preg_match('/^(?:[A-Z]|Z[A-Z]|Z[0-9]{1,5})$/D', $requested)) {
                throw new DomainException('Suffix job tujuan tidak valid.');
            }
            $sameChild = $requested === '' || (
                $requested === strtoupper(trim((string)$curr['num']))
                && $curr['kode_ref'] === $plan['kode_ref'].$requested
            );
            if ($samePlan && $sameChild) {
                if ($bc !== '') $this->sync($bc, $this->planSchedule($plan));
                $this->commit();
                return ['status'=>1,'message'=>'Booking sudah di job tujuan.','kode_ref_awal'=>$src['kode_ref'],'kode_ref_akhir'=>$src['kode_ref'],'booking_code'=>$bc,'job_no'=>$src['kode_ref']];
            }
            $sameRef = $this->check($this->db->query('SELECT sp_detail_id FROM trnstuffingplandetail WHERE kode_ref = ? AND region_id = ? FOR UPDATE',[$src['kode_ref'],$region]), 'CHECK_SOURCE_REF')->num_rows();
            if (trim((string)$src['kode_ref']) === '' || $sameRef !== 1) throw new DomainException('Nomor anak job asal kosong atau duplikat; audit sebelum moving.');
            // Exclude only the moving detail from the occupied slots.
            $num = $this->freeSuffix($plan, $region, $requested, $spd);
            // For an explicit renumber within the same master plan, never
            // silently pick another suffix if the requested slot is occupied.
            if ($samePlan && $requested !== '' && $num !== $requested) {
                throw new DomainException('Suffix job tujuan '.$requested.' sudah digunakan atau tidak tersedia. Muat ulang job dan pilih suffix lain.');
            }
            $ref = $plan['kode_ref'].$num;
            $this->check($this->db->where('sp_detail_id',$spd)->where('region_id',$region)->update('trnstuffingplandetail', [
                'sp_id'=>$target,'code'=>$plan['kode_ref'],'num'=>$num,'kode_ref'=>$ref,
                'edited_by'=>$user,'edited_at'=>date('Y-m-d H:i:s')]), 'MOVE_DETAIL');
            if ($this->db->affected_rows() !== 1) throw new RuntimeException('Detail gagal dipindahkan.');
            $this->check($this->db->where('detail_id',$spd)->update('tbl_detail',['sp_id'=>$target]), 'MOVE_TBL_DETAIL');
            // Existing deployments may cascade no_job via FK; this also handles non-cascade.
            $this->check($this->db->where('no_job',$src['kode_ref'])->where('region_id',$region)->update('x_tracking_export',['no_job'=>$ref]), 'MOVE_TRACKING_EXPORT');
            if ($bc !== '') $this->sync($bc, $this->planSchedule($plan));
            $this->commit();
            return ['status'=>1,'message'=>'Move job berhasil.','kode_ref_awal'=>$src['kode_ref'],'kode_ref_akhir'=>$ref,'booking_code'=>$bc,'job_no'=>$ref];
        } finally { $this->finish(); }
    }
}
