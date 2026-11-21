<?php
#[\AllowDynamicProperties]
class M_finance_proses extends CI_Model
{	
	public $dbx;
	public $dbr;

	public function __construct() {
		parent::__construct();

		$tahun = $this->session->userdata('db');

		if ($tahun == '' || !is_numeric($tahun)) {
			$this->dbx = $this->load->database('master', TRUE);
			// Aktifkan jika memang butuh replica di model ini:
			// $this->dbr = $this->load->database('replica', TRUE); 
		} else {
			$this->dbx = $this->load->database(switch_db($tahun), TRUE);
			// Aktifkan jika memang butuh replica di model ini:
			// $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE); 
		}

		// KUNCI PENTING:
		// Arahkan property default CI ($this->db) ke koneksi dinamis Anda ($this->dbx)
		// agar seluruh query di bawahnya otomatis menggunakan database tahun yang benar.
		$this->db = $this->dbx;
	}

	function manage_data($data)
	{
		$table  = trim((string)($data['table'] ?? ''));
		$oper   = strtolower(trim((string)($data['oper'] ?? '')));
		$region = trim((string)$this->session->userdata('region_id'));

		/* Endpoint ini adalah header Finance Process; jangan izinkan arbitrary table. */
		if ($table !== 'x_finance' || $region === '') {
			return "0";
		}

		if ($oper === 'add') {
			unset($data['oper'], $data['table'], $data['id']);
			$data['status'] = (($data['jenis'] ?? '') === 'CREDIT NOTE') ? '' : 'unpaid';
			$data['region_id'] = $region;
			if ($this->dbx->field_exists('created_by', 'x_finance')) {
				$data['created_by'] = (string)$this->session->userdata('fullname');
			}
			if ($this->dbx->field_exists('created_at', 'x_finance')) {
				$data['created_at'] = date('Y-m-d H:i:s');
			}
			$this->dbx->insert('x_finance', $data);
			return $this->dbx->affected_rows() > 0 ? "1" : "0";
		}

		if ($oper === 'edit') {
			$id = (int)($data['id'] ?? 0);
			if ($id <= 0) return "0";

			$current = $this->db
				->where('id', $id)
				->where('region_id', $region)
				->limit(1)
				->get('x_finance')
				->row_array();

			if (!$current) {
				return "0";
			}

			if (strtolower(trim((string)($current['status'] ?? ''))) === 'paid') {
				$response = $this->paid_revision_required_response($current);
				$response['msg'] = $response['message'];
				return $response;
			}

			unset(
				$data['oper'], $data['table'], $data['id'], $data['status'],
				$data['region_id'], $data['number'], $data['job_no'],
				$data['created_by'], $data['created_at']
			);

			if ($this->dbx->field_exists('updated_by', 'x_finance')) {
				$data['updated_by'] = (string)$this->session->userdata('fullname');
			}
			if ($this->dbx->field_exists('updated_at', 'x_finance')) {
				$data['updated_at'] = date('Y-m-d H:i:s');
			}

			$this->db
				->where('id', $id)
				->where('region_id', $region)
				->where('status !=', 'paid')
				->update('x_finance', $data);

			return $this->dbx->trans_status() !== false ? "1" : "0";
		}

		if ($oper === 'del') {
			$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($data['id'] ?? ''))))));
			if (empty($ids)) return "0";

			$this->dbx->trans_begin();

			foreach ($ids as $id) {
				$finance = $this->db
					->where('id', $id)
					->where('region_id', $region)
					->limit(1)
					->get('x_finance')
					->row_array();

				if (!$finance) {
					$this->dbx->trans_rollback();
					return "0";
				}

				if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
					$this->dbx->trans_rollback();
					return $this->paid_revision_required_response($finance);
				}

				if (!$this->_release_finance_sources($id, (string)($finance['number'] ?? ''), $region)) {
					$this->dbx->trans_rollback();
					return "0";
				}

				if ($this->dbx->table_exists('x_finance_delete')) {
					$this->dbx->insert('x_finance_delete', [
						'kode_ref'       => (string)($finance['job_no'] ?? ''),
						'deleted_by'     => (string)$this->session->userdata('fullname'),
						'tanggal_delete' => date('Y-m-d H:i:s'),
						'region_id'      => $region,
						'number'         => (string)($finance['number'] ?? ''),
					]);
				}

				$this->dbx->where('number', $id)->where('region_id', $region)->delete('x_finance_item');
				$this->dbx->where('number', (string)($finance['number'] ?? ''))->where('region_id', $region)->delete('x_invoice');
				$this->dbx->where('id', $id)->where('region_id', $region)->delete('x_finance');

				if ($this->dbx->trans_status() === false) {
					$this->dbx->trans_rollback();
					return "0";
				}
			}

			$this->dbx->trans_commit();
			return "1";
		}

		return "0";
	}

	function get_agent($code)
	{
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code,'region_id'=> $this->session->userdata('region_id')));
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->agen_penagihan;
		} else {
			return "";
		}
	}
	
	function get_item_by_job($id)
	{
		return $id;
	}

	public function get_active_db()
	{
		return $this->db;
	}

	public function find_finance_for_post($finance_id = 0, $invoice_no = '')
	{
		$region_id = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)$finance_id;
		$invoice_no = trim((string)$invoice_no);

		if ($region_id === '' || ($finance_id <= 0 && $invoice_no === '')) {
			return null;
		}

		$this->dbx->from('x_finance')
			->where('region_id', $region_id);

		if ($finance_id > 0) {
			$this->dbx->where('id', $finance_id);
		}

		if ($invoice_no !== '') {
			$this->dbx->where('number', $invoice_no);
		}

		return $this->dbx->limit(1)->get()->row_array();
	}
	


	/**
	 * CONTROLLED PAID REVISION
	 *
	 * Invoice PAID boleh direvisi, tetapi harus dibuka eksplisit.
	 * Reopen hanya mengubah status legacy PAID -> UNPAID dan mencatat snapshot.
	 * Payment/allocation/GL history tidak dihapus di fungsi ini.
	 */
	private function revision_schema_ready()
	{
		return (
			$this->dbx->table_exists('x_finance_revision_log')
			&& $this->dbx->field_exists('revision_no', 'x_finance')
			&& $this->dbx->field_exists('revision_state', 'x_finance')
			&& $this->dbx->field_exists('revision_reason', 'x_finance')
			&& $this->dbx->field_exists('revision_previous_status', 'x_finance')
			&& $this->dbx->field_exists('revision_opened_at', 'x_finance')
			&& $this->dbx->field_exists('revision_opened_by', 'x_finance')
			&& $this->dbx->field_exists('revision_updated_at', 'x_finance')
		);
	}

	private function revision_snapshot($finance_id, $region)
	{
		$header = $this->db
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->limit(1)
			->get('x_finance')
			->row_array();

		$items = $this->db
			->where('number', (int)$finance_id)
			->where('region_id', $region)
			->order_by('id', 'asc')
			->get('x_finance_item')
			->result_array();

		return [
			'header' => $header ?: [],
			'items'  => $items ?: [],
		];
	}

	private function record_revision_event($finance_id, $region, $action, $reason = '', $status_before = null, $status_after = null, $snapshot = null)
	{
		if (!$this->revision_schema_ready()) {
			return false;
		}

		if ($snapshot === null) {
			$snapshot = $this->revision_snapshot($finance_id, $region);
		}

		$header = (isset($snapshot['header']) && is_array($snapshot['header'])) ? $snapshot['header'] : [];
		$items  = (isset($snapshot['items']) && is_array($snapshot['items'])) ? $snapshot['items'] : [];

		if (empty($header)) {
			return false;
		}

		return (bool)$this->dbx->insert('x_finance_revision_log', [
			'finance_id'      => (int)$finance_id,
			'invoice_no'      => (string)($header['number'] ?? ''),
			'job_no'          => (string)($header['job_no'] ?? ''),
			'region_id'       => (string)$region,
			'revision_no'     => (int)($header['revision_no'] ?? 0),
			'action'          => strtoupper(trim((string)$action)),
			'reason'          => trim((string)$reason),
			'status_before'   => $status_before !== null ? (string)$status_before : (string)($header['status'] ?? ''),
			'status_after'    => $status_after !== null ? (string)$status_after : (string)($header['status'] ?? ''),
			'grand_total'     => isset($header['grand_total']) ? (float)$header['grand_total'] : 0,
			'ppn'             => isset($header['ppn']) ? (float)$header['ppn'] : 0,
			'snapshot_header' => json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'snapshot_items'  => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'created_by'      => (string)$this->session->userdata('fullname'),
			'created_at'      => date('Y-m-d H:i:s'),
		]);
	}

	private function mark_revision_stage($finance_id, $region, $stage)
	{
		if (!$this->revision_schema_ready()) {
			return;
		}

		$header = $this->db
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->limit(1)
			->get('x_finance')
			->row_array();

		if (!$header || (int)($header['revision_no'] ?? 0) <= 0) {
			return;
		}

		$this->db
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->update('x_finance', [
				'revision_state'      => strtoupper(trim((string)$stage)),
				'revision_updated_at' => date('Y-m-d H:i:s'),
			]);

		$this->record_revision_event($finance_id, $region, $stage);
	}


	/**
	 * User yang boleh membatalkan status PAID untuk membuka revisi.
	 * Backend adalah authority utama; hide tombol di view hanya UX.
	 */
	public function can_revise_paid_invoice($user_id = null)
	{
		if ($user_id === null) {
			$user_id = $this->session->userdata('user_id');
		}

		return in_array((int)$user_id, [0, 107], true);
	}

	public function reopen_paid_invoice_for_revision($finance_id, $reason)
	{
		$region     = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)$finance_id;
		$reason     = trim((string)$reason);

		if (!$this->can_revise_paid_invoice()) {
			return [
				'status'            => 0,
				'http'              => 403,
				'message'           => 'PAID_INVOICE_REVISION_ACCESS_DENIED',
				'requires_revision' => 0,
			];
		}

		if ($region === '' || $finance_id <= 0) {
			return ['status'=>0, 'http'=>422, 'message'=>'INVALID_REVISION_CONTEXT'];
		}

		if (mb_strlen($reason) < 5) {
			return ['status'=>0, 'http'=>422, 'message'=>'REVISION_REASON_REQUIRED'];
		}

		if (!$this->revision_schema_ready()) {
			return ['status'=>0, 'http'=>500, 'message'=>'REVISION_SCHEMA_REQUIRED'];
		}

		$this->dbx->trans_begin();

		$finance = $this->dbx->query(
			"SELECT * FROM x_finance WHERE id = ? AND region_id = ? LIMIT 1 FOR UPDATE",
			[$finance_id, $region]
		)->row_array();

		if (!$finance) {
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>404, 'message'=>'INVOICE_NOT_FOUND'];
		}

		$current_status = strtolower(trim((string)($finance['status'] ?? '')));

		if ($current_status !== 'paid') {
			$this->dbx->trans_commit();
			return [
				'status'         => 1,
				'http'           => 200,
				'message'        => 'INVOICE_ALREADY_OPEN_FOR_REVISION',
				'finance_id'     => $finance_id,
				'invoice_no'     => (string)($finance['number'] ?? ''),
				'current_status' => (string)($finance['status'] ?? ''),
				'revision_no'    => (int)($finance['revision_no'] ?? 0),
				'revision_state' => (string)($finance['revision_state'] ?? ''),
			];
		}

		$before        = $this->revision_snapshot($finance_id, $region);
		$next_revision = ((int)($finance['revision_no'] ?? 0)) + 1;
		$now           = date('Y-m-d H:i:s');
		$user          = (string)$this->session->userdata('fullname');

		$update = [
			'status'                   => 'unpaid',
			'revision_no'              => $next_revision,
			'revision_state'           => 'OPEN',
			'revision_reason'          => $reason,
			'revision_previous_status' => (string)($finance['status'] ?? 'paid'),
			'revision_opened_at'       => $now,
			'revision_opened_by'       => $user,
			'revision_updated_at'      => $now,
		];

		if ($this->dbx->field_exists('updated_by', 'x_finance')) {
			$update['updated_by'] = $user;
		}
		if ($this->dbx->field_exists('updated_at', 'x_finance')) {
			$update['updated_at'] = $now;
		}

		$ok = $this->db
			->where('id', $finance_id)
			->where('region_id', $region)
			->where('status', 'paid')
			->update('x_finance', $update);

		if (!$ok || $this->dbx->affected_rows() !== 1) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>409, 'message'=>'FAILED_REOPEN_PAID_INVOICE', 'error'=>$err];
		}

		/* Audit snapshot harus mewakili kondisi sebelum status dibatalkan. */
		$before['header']['revision_no'] = $next_revision;

		if (!$this->record_revision_event(
			$finance_id,
			$region,
			'REOPEN_PAID',
			$reason,
			'paid',
			'unpaid',
			$before
		)) {
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>500, 'message'=>'FAILED_WRITE_REVISION_AUDIT'];
		}

		if ($this->dbx->trans_status() === false) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>500, 'message'=>'REVISION_TRANSACTION_FAILED', 'error'=>$err];
		}

		$this->dbx->trans_commit();

		return [
			'status'          => 1,
			'http'            => 200,
			'message'         => 'PAID_STATUS_CANCELLED_FOR_REVISION',
			'finance_id'      => $finance_id,
			'invoice_no'      => (string)($finance['number'] ?? ''),
			'previous_status' => 'paid',
			'current_status'  => 'unpaid',
			'revision_no'     => $next_revision,
			'revision_state'  => 'OPEN',
		];
	}

	private function paid_revision_required_response($finance)
	{
		if (!$this->can_revise_paid_invoice()) {
			return [
				'status'            => 0,
				'http'              => 403,
				'message'           => 'PAID_INVOICE_REVISION_ACCESS_DENIED',
				'requires_revision' => 0,
				'finance_id'        => (int)($finance['id'] ?? 0),
				'invoice_no'        => (string)($finance['number'] ?? ''),
				'job_no'            => (string)($finance['job_no'] ?? ''),
			];
		}

		return [
			'status'            => 0,
			'http'              => 409,
			'message'           => 'PAID_INVOICE_REQUIRES_REVISION',
			'requires_revision' => 1,
			'finance_id'        => (int)($finance['id'] ?? 0),
			'invoice_no'        => (string)($finance['number'] ?? ''),
			'job_no'            => (string)($finance['job_no'] ?? ''),
		];
	}


	public function post_invoice_debit_note_table_satu($data)
	{
		$region_id = trim((string)$this->session->userdata('region_id'));
		$id        = (int)($data['id'] ?? 0);

		if ($region_id === '' || $id <= 0) {
			return [
				'status'  => 0,
				'http'    => 422,
				'message' => 'INVALID_FINANCE_CONTEXT',
			];
		}

		/*
		 * Jakarta tetap menggunakan flow invoice Jakarta / curl helper.
		 */
		if (strcasecmp($region_id, 'Jakarta') === 0) {
			return [
				'status'  => 0,
				'http'    => 409,
				'message' => 'JAKARTA_MUST_USE_LEGACY_CURL_HELPER',
			];
		}

		$finance = $this->db
			->where('id', $id)
			->where('region_id', $region_id)
			->limit(1)
			->get('x_finance')
			->row_array();

		if (!$finance) {
			return [
				'status'  => 0,
				'http'    => 404,
				'message' => 'FINANCE_HEADER_NOT_FOUND',
			];
		}

		if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
			return $this->paid_revision_required_response($finance);
		}

		/*
		 * Rebuild menggunakan fungsi yang sama dengan tombol Tarik Data,
		 * sehingga direct source mapping dan ownership rule hanya ada satu.
		 */
		$rebuildData = $data;
		$rebuildData['id']      = (int)$finance['id'];
		$rebuildData['number']  = (string)$finance['number'];
		$rebuildData['job_no']  = (string)$finance['job_no'];
		$rebuildData['group']   = (string)$finance['group'];
		$rebuildData['jenis']   = (string)$finance['jenis'];
		$rebuildData['kepada']  = (string)$finance['kepada'];
		$rebuildData['agent']   = (string)($finance['kepada_kode'] ?? '');

		$rebuild = $this->tarik_data_table_satu($rebuildData);

		if (empty($rebuild['status'])) {
			$rebuild['http'] = $rebuild['http'] ?? 422;
			return $rebuild;
		}

		/*
		 * Post ke GL setelah item/header selesai dibuat.
		 */
		$this->load->model('api/finance/ar/M_posting', 'mpost');

		$postRes = $this->mpost->post_ar_to_gl([
			'finance_id'   => (int)$finance['id'],
			'force_repost' => 1,
		]);

		if (empty($postRes['ok'])) {
			return [
				'status'  => 0,
				'http'    => $postRes['http'] ?? 500,
				'message' => $postRes['message'] ?? 'POST_AR_TO_GL_FAILED',
				'data'    => $postRes['data'] ?? null,
			];
		}

		$updatePosted = [
			'fin_proses' => 'POSTED',
		];

		if ($this->dbx->field_exists('ar_posted', 'x_finance')) {
			$updatePosted['ar_posted'] = 1;
		}
		if ($this->dbx->field_exists('ar_posted_at', 'x_finance')) {
			$updatePosted['ar_posted_at'] = date('Y-m-d H:i:s');
		}
		if ($this->dbx->field_exists('ar_posted_by', 'x_finance')) {
			$updatePosted['ar_posted_by'] = $this->session->userdata('fullname');
		}
		if ($this->dbx->field_exists('ar_post_message', 'x_finance')) {
			$updatePosted['ar_post_message'] = 'POST_INVOICE_DN_SUCCESS';
		}
		if ($this->dbx->field_exists('updated_by', 'x_finance')) {
			$updatePosted['updated_by'] = $this->session->userdata('fullname');
		}
		if ($this->dbx->field_exists('updated_at', 'x_finance')) {
			$updatePosted['updated_at'] = date('Y-m-d H:i:s');
		}

		$this->db
			->where('id', (int)$finance['id'])
			->where('region_id', $region_id)
			->update('x_finance', $updatePosted);

		$this->mark_revision_stage((int)$finance['id'], $region_id, 'REPOSTED');

		return [
			'status'  => 1,
			'http'    => 200,
			'message' => 'Invoice / Debit Note posted',
			'data'    => [
				'finance_id'    => (int)$finance['id'],
				'invoice_no'    => (string)$finance['number'],
				'kode_ref'      => (string)$finance['job_no'],
				'jenis'         => (string)$finance['jenis'],
				'kepada'        => (string)$finance['kepada'],
				'group'         => (string)$finance['group'],
				'inserted_item' => $rebuild['inserted'] ?? 0,
				'grand_total'   => $rebuild['grand_total'] ?? 0,
				'post'          => $postRes,
			],
			'result' => $rebuild['result'] ?? [],
		];
	}

private function tag_income_source_rows($rows, $sourceTable)
{
    if (empty($rows)) {
        return [];
    }

    foreach ($rows as $row) {

        $sourceId = isset($row->id)
            ? (int)$row->id
            : 0;

        /*
         * Generic source mapping.
         */
        $row->_income_source_table = $sourceTable;
        $row->_income_source_id = $sourceId > 0
            ? $sourceId
            : null;

        /*
         * Compatibility column khusus selling price.
         */
        $row->_source_income_id = (
            $sourceTable === 'inf_income_selling_price'
            && $sourceId > 0
        )
            ? $sourceId
            : null;
    }

    return $rows;
}


	private function _allow_unowned_or_current($column, $refno)
	{
		$this->db
			->group_start()
				->where($column . ' IS NULL', null, false)
				->or_where('TRIM(' . $column . ") = ''", null, false)
				->or_where($column, $refno)
			->group_end();
	}

	private function _build_table_satu_post_rows($data)
	{
		$jenis     = strtoupper(trim((string)($data['jenis'] ?? '')));
		$kepada    = strtoupper(trim((string)($data['kepada'] ?? '')));
		$job_no    = trim((string)($data['job_no'] ?? ''));
		$group     = trim((string)($data['group'] ?? ''));
		$refno     = trim((string)($data['number'] ?? ''));
		$region_id = trim((string)($data['region_id'] ?? ''));

		if ($job_no === '' || $region_id === '') {
			return [
				'ok'      => false,
				'status'  => 0,
				'message' => 'INVALID_SOURCE_CONTEXT',
				'rows'    => [],
			];
		}

		$q = [];

		/*
		 * IMPORTANT:
		 * Function ini PURE SELECT.
		 * Tidak boleh mengubah ditarik / ship_co / dn_no.
		 * State source hanya diubah SETELAH x_finance_item berhasil dibuat.
		 */
		if ($jenis === 'DEBIT NOTE') {

			$this->db
				->from('inf_income_selling_price')
				->where('prep_coll', 'COLLECT')
				->where('invoice_db', 'DEBIT NOTE')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}

			if ($this->dbx->field_exists('ship_co', 'inf_income_selling_price')) {
				$this->_allow_unowned_or_current('ship_co', $refno);
			}

			$q1 = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'inf_income_selling_price'
			);

			$this->db
				->from('inf_income_from_agent')
				->where('invoice_db', 'DEBIT NOTE')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}

			if ($this->dbx->field_exists('dn_no', 'inf_income_from_agent')) {
				$this->_allow_unowned_or_current('dn_no', $refno);
			}

			$q2 = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'inf_income_from_agent'
			);

			$this->db
				->from('inf_income_from_coloader')
				->where('invoice_db', 'DEBIT NOTE')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}

			if ($this->dbx->field_exists('dn_no', 'inf_income_from_coloader')) {
				$this->_allow_unowned_or_current('dn_no', $refno);
			}

			$q3 = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'inf_income_from_coloader'
			);

			$this->db
				->from('x_glbincome')
				->like('job_no', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('`group` IS NULL', null, false)
						->or_where("TRIM(`group`) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('group', $group);
			}

			if ($this->dbx->field_exists('region_id', 'x_glbincome')) {
				$this->dbx->where('region_id', $region_id);
			}

			$q4 = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'x_glbincome'
			);

			$q = array_merge($q1 ?: [], $q2 ?: [], $q3 ?: [], $q4 ?: []);

		} elseif ($jenis === 'INVOICE' && $kepada === 'COLOADER') {

			$this->db
				->from('inf_income_from_coloader')
				->where('invoice_db', 'INVOICE')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}

			if ($this->dbx->field_exists('dn_no', 'inf_income_from_coloader')) {
				$this->_allow_unowned_or_current('dn_no', $refno);
			}

			$q = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'inf_income_from_coloader'
			);

		} elseif ($jenis === 'INVOICE' && $kepada === 'SHIPCO') {

			$this->db
				->from('x_glbincome')
				->where('currency', 'RP.')
				->like('job_no', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('`group` IS NULL', null, false)
						->or_where("TRIM(`group`) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('group', $group);
			}

			if ($this->dbx->field_exists('region_id', 'x_glbincome')) {
				$this->dbx->where('region_id', $region_id);
			}

			$q = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'x_glbincome'
			);

		} elseif ($jenis === 'INVOICE') {

			$this->db
				->from('inf_income_selling_price')
				->where('prep_coll', 'PREPAID')
				->where('invoice_db', 'INVOICE')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}

			if ($this->dbx->field_exists('ship_co', 'inf_income_selling_price')) {
				$this->_allow_unowned_or_current('ship_co', $refno);
			}

			$q = $this->tag_income_source_rows(
				$this->dbx->get()->result(),
				'inf_income_selling_price'
			);

		} else {

			/*
			 * Legacy cost fallback.
			 * Tidak diberi income_source mapping karena bukan tabel income.
			 */
			$this->db
				->from('inf_cost_tbl_satu')
				->where('prep_coll', 'COLLECT')
				->where('region_id', $region_id)
				->like('kode_ref', $job_no, 'after');


			if ($group === '') {
				$this->db
					->group_start()
						->where('g IS NULL', null, false)
						->or_where("TRIM(g) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('g', $group);
			}
	
			$q1 = $this->dbx->get()->result();

			$this->db
				->from('x_glbcost')
				->where('prep_coll', 'COLLECT')
				->like('job_no', $job_no, 'after');

			if ($group === '') {
				$this->db
					->group_start()
						->where('`group` IS NULL', null, false)
						->or_where("TRIM(`group`) = ''", null, false)
					->group_end();
			} else {
				$this->dbx->where('group', $group);
			}

			if ($this->dbx->field_exists('region_id', 'x_glbcost')) {
				$this->dbx->where('region_id', $region_id);
			}

			$q2 = $this->dbx->get()->result();

			$q = array_merge($q1 ?: [], $q2 ?: []);
		}

		return [
			'ok'   => true,
			'rows' => $q ?: [],
		];
	}
	

	private function _mapped_source_ids_in_other_finance($table, array $ids, $finance_id, $region)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$finance_id = (int)$finance_id;
		$region = trim((string)$region);

		if (
			empty($ids)
			|| $finance_id <= 0
			|| $region === ''
			|| !$this->dbx->table_exists('x_finance_item')
		) {
			return [];
		}

		$blocked = [];

		if (
			$this->dbx->field_exists('income_source_table', 'x_finance_item')
			&& $this->dbx->field_exists('income_source_id', 'x_finance_item')
		) {
			$rows = $this->db
				->select('income_source_id')
				->where('region_id', $region)
				->where('number !=', $finance_id)
				->where('income_source_table', $table)
				->where_in('income_source_id', $ids)
				->get('x_finance_item')
				->result_array();

			foreach ($rows as $r) {
				$id = (int)($r['income_source_id'] ?? 0);
				if ($id > 0) $blocked[] = $id;
			}
		}

		/*
		 * Compatibility mapping lama khusus selling price.
		 */
		if (
			$table === 'inf_income_selling_price'
			&& $this->dbx->field_exists('source_income_id', 'x_finance_item')
		) {
			$rows = $this->db
				->select('source_income_id')
				->where('region_id', $region)
				->where('number !=', $finance_id)
				->where_in('source_income_id', $ids)
				->get('x_finance_item')
				->result_array();

			foreach ($rows as $r) {
				$id = (int)($r['source_income_id'] ?? 0);
				if ($id > 0) $blocked[] = $id;
			}
		}

		return array_values(array_unique($blocked));
	}


	private function _lock_tagged_sources(array $rows, $region)
	{
		$region = trim((string)$region);

		if ($region === '') {
			return false;
		}

		$allowed = [
			'inf_income_selling_price',
			'inf_income_from_agent',
			'inf_income_from_coloader',
			'x_glbincome',
		];

		$byTable = [];

		foreach ($rows as $row) {
			$table = isset($row->_income_source_table)
				? trim((string)$row->_income_source_table)
				: '';
			$id = isset($row->_income_source_id)
				? (int)$row->_income_source_id
				: 0;

			if (in_array($table, $allowed, true) && $id > 0) {
				$byTable[$table][] = $id;
			}
		}

		foreach ($byTable as $table => $ids) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			if (empty($ids)) continue;

			$idList = implode(',', $ids);

			$sql = "SELECT id FROM `{$table}` WHERE region_id = ? AND id IN ({$idList}) FOR UPDATE";
			$locked = $this->dbx->query($sql, [$region])->result_array();

			$lockedIds = array_map('intval', array_column($locked, 'id'));
			sort($lockedIds);
			sort($ids);

			if ($lockedIds !== $ids) {
				return false;
			}
		}

		return true;
	}


	private function _validate_tagged_sources_not_mapped_elsewhere(array $rows, $finance_id, $region)
	{
		$byTable = [];

		foreach ($rows as $row) {
			$table = isset($row->_income_source_table)
				? trim((string)$row->_income_source_table)
				: '';
			$id = isset($row->_income_source_id)
				? (int)$row->_income_source_id
				: 0;

			if ($table !== '' && $id > 0) {
				$byTable[$table][] = $id;
			}
		}

		$conflicts = [];

		foreach ($byTable as $table => $ids) {
			$blocked = $this->_mapped_source_ids_in_other_finance(
				$table,
				$ids,
				$finance_id,
				$region
			);

			foreach ($blocked as $id) {
				$conflicts[] = [
					'table' => $table,
					'id'    => (int)$id,
				];
			}
		}

		return $conflicts;
	}


	private function _source_release_by_ids($table, array $ids, $refno, $region, $finance_id = 0)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$refno = trim((string)$refno);
		$region = trim((string)$region);
		$finance_id = (int)$finance_id;

		if (empty($ids) || !$this->dbx->table_exists($table)) {
			return true;
		}

		$allowed = [
			'inf_income_selling_price',
			'inf_income_from_agent',
			'inf_income_from_coloader',
			'x_glbincome',
		];

		if (!in_array($table, $allowed, true)) {
			return false;
		}

		/*
		 * Kalau source juga dipetakan ke finance lain, jangan reset source itu.
		 * Ini terutama penting untuk x_glbincome yang tidak punya ship_co/dn_no.
		 */
		if ($finance_id > 0) {
			$blocked = $this->_mapped_source_ids_in_other_finance(
				$table,
				$ids,
				$finance_id,
				$region
			);

			if (!empty($blocked)) {
				$ids = array_values(array_diff($ids, $blocked));
			}
		}

		if (empty($ids)) {
			return true;
		}

		$update = [];
		$refCol = '';

		if ($this->dbx->field_exists('ditarik', $table)) {
			$update['ditarik'] = 0;
		}

		if ($table === 'inf_income_selling_price') {
			$refCol = 'ship_co';

			if ($this->dbx->field_exists('ship_co', $table)) {
				$update['ship_co'] = '';
			}
			if ($this->dbx->field_exists('billing_status', $table)) {
				$update['billing_status'] = 'UNBILLED';
			}
			if ($this->dbx->field_exists('billed_at', $table)) {
				$update['billed_at'] = null;
			}
			if ($this->dbx->field_exists('billed_by', $table)) {
				$update['billed_by'] = null;
			}
		} elseif (
			$table === 'inf_income_from_agent'
			|| $table === 'inf_income_from_coloader'
		) {
			$refCol = 'dn_no';

			if ($this->dbx->field_exists('dn_no', $table)) {
				$update['dn_no'] = '';
			}
			if ($this->dbx->field_exists('billing_status', $table)) {
				$update['billing_status'] = 'UNBILLED';
			}
			if ($this->dbx->field_exists('billed_at', $table)) {
				$update['billed_at'] = null;
			}
			if ($this->dbx->field_exists('billed_by', $table)) {
				$update['billed_by'] = null;
			}
		}

		if (empty($update)) {
			return true;
		}

		$this->dbx->where_in('id', $ids);

		if ($this->dbx->field_exists('region_id', $table)) {
			$this->dbx->where('region_id', $region);
		}

		/*
		 * Source yang sudah berpindah ownership ke invoice lain tidak boleh
		 * di-clear walaupun mapping x_finance_item lama masih tertinggal.
		 */
		if ($refCol !== '' && $this->dbx->field_exists($refCol, $table)) {
			$this->db
				->group_start()
					->where($refCol, $refno)
					->or_where($refCol, '')
					->or_where($refCol . ' IS NULL', null, false)
				->group_end();
		}

		$this->dbx->update($table, $update);

		return $this->dbx->trans_status() !== false;
	}

	private function _release_finance_sources($finance_id, $refno, $region)
	{
		$finance_id = (int)$finance_id;
		$refno      = trim((string)$refno);
		$region     = trim((string)$region);

		/*
		 * Direct mapping dulu, terutama untuk x_glbincome yang tidak punya
		 * nomor invoice ownership seperti ship_co/dn_no.
		 */
		if (
			$this->dbx->field_exists('income_source_table', 'x_finance_item')
			&& $this->dbx->field_exists('income_source_id', 'x_finance_item')
		) {
			$mapped = $this->db
				->select('income_source_table,income_source_id')
				->where('number', $finance_id)
				->where('region_id', $region)
				->where('id_tarik !=', 0)
				->where('income_source_table IS NOT NULL', null, false)
				->where('income_source_id IS NOT NULL', null, false)
				->get('x_finance_item')
				->result_array();

			$byTable = [];
			foreach ($mapped as $m) {
				$table = trim((string)($m['income_source_table'] ?? ''));
				$id    = (int)($m['income_source_id'] ?? 0);
				if ($table !== '' && $id > 0) {
					$byTable[$table][] = $id;
				}
			}

			foreach ($byTable as $table => $ids) {
				if (!$this->_source_release_by_ids($table, $ids, $refno, $region, $finance_id)) {
					return false;
				}
			}
		}

		/*
		 * Legacy ownership fallback. Tidak ada LIKE job/group.
		 */
		if ($this->dbx->table_exists('inf_income_selling_price')) {
			$update = ['ditarik' => 0, 'ship_co' => ''];

			if ($this->dbx->field_exists('billing_status', 'inf_income_selling_price')) {
				$update['billing_status'] = 'UNBILLED';
			}
			if ($this->dbx->field_exists('billed_at', 'inf_income_selling_price')) {
				$update['billed_at'] = null;
			}
			if ($this->dbx->field_exists('billed_by', 'inf_income_selling_price')) {
				$update['billed_by'] = null;
			}

			$this->db
				->where('region_id', $region)
				->where('ship_co', $refno)
				->update('inf_income_selling_price', $update);
		}

		foreach (['inf_income_from_agent', 'inf_income_from_coloader'] as $table) {
			if (
				$this->dbx->table_exists($table)
				&& $this->dbx->field_exists('dn_no', $table)
			) {
				$update = ['dn_no' => ''];
				if ($this->dbx->field_exists('ditarik', $table)) {
					$update['ditarik'] = 0;
				}
				if ($this->dbx->field_exists('billing_status', $table)) {
					$update['billing_status'] = 'UNBILLED';
				}
				if ($this->dbx->field_exists('billed_at', $table)) {
					$update['billed_at'] = null;
				}
				if ($this->dbx->field_exists('billed_by', $table)) {
					$update['billed_by'] = null;
				}

				$this->db
					->where('region_id', $region)
					->where('dn_no', $refno)
					->update($table, $update);
			}
		}

		return $this->dbx->trans_status() !== false;
	}

	private function _mark_tagged_sources_billed(array $rows, $refno, $region)
	{
		$refno = trim((string)$refno);
		$region = trim((string)$region);

		$byTable = [];
		foreach ($rows as $row) {
			$table = isset($row->_income_source_table)
				? trim((string)$row->_income_source_table)
				: '';
			$id = isset($row->_income_source_id)
				? (int)$row->_income_source_id
				: 0;

			if ($table !== '' && $id > 0) {
				$byTable[$table][] = $id;
			}
		}

		foreach ($byTable as $table => $ids) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			if (empty($ids) || !$this->dbx->table_exists($table)) {
				continue;
			}

			$update = [];

			if ($this->dbx->field_exists('ditarik', $table)) {
				$update['ditarik'] = 1;
			}

			if ($table === 'inf_income_selling_price') {
				if ($this->dbx->field_exists('ship_co', $table)) {
					$update['ship_co'] = $refno;
				}
				if ($this->dbx->field_exists('billing_status', $table)) {
					$update['billing_status'] = 'BILLED';
				}
				if ($this->dbx->field_exists('billed_at', $table)) {
					$update['billed_at'] = date('Y-m-d H:i:s');
				}
				if ($this->dbx->field_exists('billed_by', $table)) {
					$update['billed_by'] = (string)$this->session->userdata('fullname');
				}
			} elseif (
				$table === 'inf_income_from_agent'
				|| $table === 'inf_income_from_coloader'
			) {
				if ($this->dbx->field_exists('dn_no', $table)) {
					$update['dn_no'] = $refno;
				}
			}

			if (empty($update)) {
				continue;
			}

			$this->dbx->where_in('id', $ids);
			if ($this->dbx->field_exists('region_id', $table)) {
				$this->dbx->where('region_id', $region);
			}
			$this->dbx->update($table, $update);
		}
	}

	function tarik_data_table_satu($data)
	{
		$region     = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)($data['id'] ?? 0);

		if ($region === '' || $finance_id <= 0) {
			return [
				'status'  => 0,
				'message' => 'INVALID_FINANCE_CONTEXT',
			];
		}

		/*
		 * Header DB adalah source of truth.
		 */
		$finance = $this->db
			->where('id', $finance_id)
			->where('region_id', $region)
			->limit(1)
			->get('x_finance')
			->row_array();

		if (!$finance) {
			return [
				'status'  => 0,
				'message' => 'FINANCE_NOT_FOUND',
			];
		}

		if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
			return $this->paid_revision_required_response($finance);
		}

		$refno  = trim((string)($finance['number'] ?? ''));
		$job_no = trim((string)($finance['job_no'] ?? ''));
		$group  = trim((string)($finance['group'] ?? ''));
		$jenis  = strtoupper(trim((string)($finance['jenis'] ?? ($data['jenis'] ?? ''))));
		$kepada = strtoupper(trim((string)($finance['kepada'] ?? ($data['kepada'] ?? ''))));
		$agent  = trim((string)($finance['kepada_kode'] ?? ($data['agent'] ?? '')));

		if ($refno === '' || $job_no === '' || $jenis === '') {
			return [
				'status'  => 0,
				'message' => 'INVALID_FINANCE_HEADER',
			];
		}

		$this->dbx->trans_begin();

		/*
		 * Pilih source SEBELUM release; builder mengizinkan row kosong
		 * atau yang memang dimiliki invoice ini.
		 */
		$sourceRows = $this->_build_table_satu_post_rows([
			'id'          => $finance_id,
			'job_no'      => $job_no,
			'agent'       => $agent,
			'group'       => $group,
			'jenis'       => $jenis,
			'kepada'      => $kepada,
			'number'      => $refno,
			'kurs'        => $data['kurs'] ?? ($finance['kurs_job'] ?? 1),
			'destination' => $data['destination'] ?? '',
			'region_id'   => $region,
		]);

		if (empty($sourceRows['ok'])) {
			$this->dbx->trans_rollback();
			return $sourceRows;
		}

		$q = $sourceRows['rows'] ?? [];

		if (empty($q)) {
			$this->dbx->trans_rollback();
			return [
				'status'  => 0,
				'message' => 'NO_CURRENT_INCOME_FOUND',
				'finance_id' => $finance_id,
				'invoice_no' => $refno,
			];
		}

		/*
		 * Lock exact source rows di transaction ini agar dua proses Tarik Data
		 * tidak bisa mengklaim source yang sama secara bersamaan.
		 */
		if (!$this->_lock_tagged_sources($q, $region)) {
			$this->dbx->trans_rollback();
			return [
				'status'  => 0,
				'http'    => 409,
				'message' => 'SOURCE_LOCK_FAILED_OR_SOURCE_CHANGED',
			];
		}

		/*
		 * Jangan pernah menarik source yang direct-mapping-nya masih aktif
		 * di finance lain, walaupun ref legacy (ship_co/dn_no) kosong.
		 */
		$mappingConflicts = $this->_validate_tagged_sources_not_mapped_elsewhere(
			$q,
			$finance_id,
			$region
		);

		if (!empty($mappingConflicts)) {
			$this->dbx->trans_rollback();
			return [
				'status'    => 0,
				'http'      => 409,
				'message'   => 'SOURCE_ALREADY_MAPPED_TO_OTHER_FINANCE',
				'conflicts' => $mappingConflicts,
			];
		}

		$hasSourceIncomeId = $this->dbx->field_exists(
			'source_income_id',
			'x_finance_item'
		);
		$hasIncomeSourceTable = $this->dbx->field_exists(
			'income_source_table',
			'x_finance_item'
		);
		$hasIncomeSourceId = $this->dbx->field_exists(
			'income_source_id',
			'x_finance_item'
		);

		$items = [];

		foreach ($q as $row) {
			$item = trim((string)($row->item_service ?? ''));
			if ($item === '') {
				$item = 'XXX';
			}

			$kode_ref = !empty($row->kode_ref)
				? trim((string)$row->kode_ref)
				: trim((string)($row->job_no ?? $job_no));

			if ($kode_ref === '') {
				$kode_ref = $job_no;
			}

			$qty        = (float)($row->qty ?? 0);
			$unit_price = (float)($row->unit_price ?? 0);
			$currency   = trim((string)($row->currency ?? 'RP.'));

			$price = isset($row->price) && $row->price !== null && $row->price !== ''
				? (float)$row->price
				: ($unit_price * $qty);

			if (strcasecmp($currency, 'USD') !== 0) {
				$price = round($price);
			}

			if ($price == 0 && $qty == 0 && $unit_price == 0) {
				continue;
			}

			$itemData = [
				'kepada'     => $agent,
				'jenis'      => $jenis,
				'kode_ref'   => $job_no,
				'bl_no'      => $this->get_bl_num($kode_ref),
				'number'     => $finance_id,
				'code'       => $this->fungsi->get_code_service($item),
				'item'       => $item,
				'qty'        => $qty,
				'currency'   => $currency,
				'price'      => $price,
				'unit_price' => $unit_price,
				'remarks'    => '',
				'pre_col'    => $row->prep_coll ?? '',
				'id_tarik'   => 1,
				'region_id'  => $region,
				'anak_job'   => $kode_ref,
			];

			if ($hasSourceIncomeId) {
				$itemData['source_income_id'] = (
					isset($row->_source_income_id)
					&& (int)$row->_source_income_id > 0
				) ? (int)$row->_source_income_id : null;
			}

			if ($hasIncomeSourceTable) {
				$itemData['income_source_table'] = !empty($row->_income_source_table)
					? trim((string)$row->_income_source_table)
					: null;
			}

			if ($hasIncomeSourceId) {
				$itemData['income_source_id'] = (
					isset($row->_income_source_id)
					&& (int)$row->_income_source_id > 0
				) ? (int)$row->_income_source_id : null;
			}

			$items[] = $itemData;
		}

		if (empty($items)) {
			$this->dbx->trans_rollback();
			return [
				'status'  => 0,
				'message' => 'NO_VALID_FINANCE_ITEM',
			];
		}

		/*
		 * Lepas source lama HANYA milik finance ini.
		 * Direct mapping digunakan untuk source tanpa ref invoice.
		 */
		if (!$this->_release_finance_sources($finance_id, $refno, $region)) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();

			return [
				'status'   => 0,
				'message'  => 'FAILED_RELEASE_OLD_FINANCE_SOURCES',
				'db_error' => $err,
			];
		}

		/*
		 * Hapus auto item lama saja; item manual id_tarik=0 dipertahankan.
		 */
		$this->db
			->where('number', $finance_id)
			->where('region_id', $region)
			->where('id_tarik !=', 0)
			->delete('x_finance_item');

		foreach ($items as $itemData) {
			$this->dbx->insert('x_finance_item', $itemData);

			if ($this->dbx->trans_status() === false) {
				$err = $this->dbx->error();
				$this->dbx->trans_rollback();

				return [
					'status'   => 0,
					'message'  => 'FAILED_INSERT_FINANCE_ITEM',
					'db_error' => $err,
					'row'      => $itemData,
				];
			}
		}

		/*
		 * PPN generated row: tidak mempunyai direct source mapping.
		 * Pertahankan perilaku lama bila ppn dikirim dari frontend.
		 */
		$ppnInput = isset($data['ppn']) ? trim((string)$data['ppn']) : '';
		if ($ppnInput !== '' && (float)$ppnInput != 0) {
			$baseTotal = 0;
			foreach ($items as $it) {
				$baseTotal += (float)$it['price'];
			}

			$qtyPpn   = 0.11;
			$pricePpn = round($baseTotal * $qtyPpn);

			$taxData = [
				'kepada'     => $agent,
				'jenis'      => $jenis,
				'kode_ref'   => $job_no,
				'bl_no'      => $this->get_bl_num($job_no),
				'number'     => $finance_id,
				'code'       => 'TAX 11',
				'item'       => 'PPN',
				'qty'        => $qtyPpn,
				'currency'   => 'RP.',
				'price'      => $pricePpn,
				'unit_price' => $baseTotal,
				'remarks'    => '',
				'pre_col'    => 'PREPAID',
				'id_tarik'   => 1,
				'region_id'  => $region,
				'anak_job'   => $job_no,
			];

			if ($hasSourceIncomeId) {
				$taxData['source_income_id'] = null;
			}
			if ($hasIncomeSourceTable) {
				$taxData['income_source_table'] = null;
			}
			if ($hasIncomeSourceId) {
				$taxData['income_source_id'] = null;
			}

			$this->dbx->insert('x_finance_item', $taxData);
		}

		/*
		 * Baru setelah detail berhasil dibuat, mark EXACT source IDs.
		 */
		$this->_mark_tagged_sources_billed($q, $refno, $region);

		$sumRow = $this->db
			->select_sum('price', 'total')
			->where('number', $finance_id)
			->where('region_id', $region)
			->get('x_finance_item')
			->row_array();

		$grandTotal = isset($sumRow['total']) ? (float)$sumRow['total'] : 0;

		$headerUpdate = [
			'grand_total' => $grandTotal,
		];

		if ($this->dbx->field_exists('kurs_job', 'x_finance')) {
			$headerUpdate['kurs_job'] = (float)($data['kurs'] ?? ($finance['kurs_job'] ?? 1));
		}
		if ($this->dbx->field_exists('updated_by', 'x_finance')) {
			$headerUpdate['updated_by'] = (string)$this->session->userdata('fullname');
		}
		if ($this->dbx->field_exists('updated_at', 'x_finance')) {
			$headerUpdate['updated_at'] = date('Y-m-d H:i:s');
		}

		$this->db
			->where('id', $finance_id)
			->where('region_id', $region)
			->update('x_finance', $headerUpdate);

		if ($this->dbx->trans_status() === false) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();

			return [
				'status'   => 0,
				'message'  => 'FINANCE_REBUILD_TRANSACTION_FAILED',
				'db_error' => $err,
			];
		}

		$this->dbx->trans_commit();

		$this->mark_revision_stage($finance_id, $region, 'REBUILT');

		return [
			'status'      => 1,
			'message'     => 'FINANCE_ITEMS_REBUILT',
			'finance_id'  => $finance_id,
			'invoice_no'  => $refno,
			'job_no'      => $job_no,
			'group'       => $group,
			'inserted'    => count($items),
			'grand_total' => $grandTotal,
			'result'      => $q,
		];
	}
			
	
	function get_bl_num($job_no)
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$job_no));
		//print_r($this->dbx->last_query());
		//die();
		if($q->num_rows()>0) 
		{
			return $q->row()->hb_l;
			//return $spd;
		}
		else 
		{
			return "";
		}
	}
	
	function get_table_satu($ref_no){
		$q = $this->dbx->query("select * from x_finance_proses_item where ref_no = '$ref_no' order by id desc");
		return $q;
	}
	
	private function is_tax_finance_item(array $item)
{
    $code = strtoupper(trim((string)($item['code'] ?? '')));
    $name = strtoupper(trim((string)($item['item'] ?? '')));

    return (
        strpos($code, 'TAX') === 0
        || strpos($name, 'VAT') === 0
        || strpos($name, 'PPN') === 0
        || strpos($name, 'TAX') === 0
    );
}


/**
 * Cari source legacy berdasarkan ownership invoice + exact line.
 *
 * PENTING:
 * - tidak pakai LIKE kode_ref
 * - wajib region sama
 * - wajib masih dimiliki invoice yang sedang diedit
 * - kalau ketemu > 1 source => ABORT
 */
private function find_legacy_source_for_finance_item(
    array $item,
    $invoice_no,
    $region
) {
    $invoice_no = trim((string)$invoice_no);
    $region     = trim((string)$region);

    $job = trim((string)(
        !empty($item['anak_job'])
            ? $item['anak_job']
            : ($item['kode_ref'] ?? '')
    ));

    $itemName  = strtoupper(trim((string)($item['item'] ?? '')));
    $currency  = strtoupper(trim((string)($item['currency'] ?? '')));
    $code      = strtoupper(trim((string)($item['code'] ?? '')));

    $qty       = (float)($item['qty'] ?? 0);
    $unitPrice = (float)($item['unit_price'] ?? 0);
    $price     = (float)($item['price'] ?? 0);

    /*
     * Hanya tabel yang punya ownership invoice yang jelas.
     */
    $sources = [
        [
            'table'   => 'inf_income_selling_price',
            'ref_col' => 'ship_co',
        ],
        [
            'table'   => 'inf_income_from_agent',
            'ref_col' => 'dn_no',
        ],
        [
            'table'   => 'inf_income_from_coloader',
            'ref_col' => 'dn_no',
        ],
    ];

    $matches = [];

    foreach ($sources as $src) {

        $table  = $src['table'];
        $refCol = $src['ref_col'];

        if (!$this->dbx->table_exists($table)) {
            continue;
        }

        if (
            !$this->dbx->field_exists('id', $table)
            || !$this->dbx->field_exists('region_id', $table)
            || !$this->dbx->field_exists($refCol, $table)
        ) {
            continue;
        }

        $this->db
            ->select('id')
            ->from($table)
            ->where('region_id', $region)
            ->where($refCol, $invoice_no);

        /*
         * Exact job. JANGAN LIKE.
         */
        if (
            $job !== ''
            && $this->dbx->field_exists('kode_ref', $table)
        ) {
            $this->dbx->where('kode_ref', $job);
        }

        /*
         * Exact item name.
         */
        if (
            $itemName !== ''
            && $this->dbx->field_exists('item_service', $table)
        ) {
            $this->dbx->where(
                'UPPER(TRIM(item_service)) = ' .
                $this->dbx->escape($itemName),
                null,
                false
            );
        }

        /*
         * Service code memperkuat matching kalau tersedia.
         */
        if (
            $code !== ''
            && $this->dbx->field_exists('code_service', $table)
        ) {
            $this->dbx->where(
                'UPPER(TRIM(code_service)) = ' .
                $this->dbx->escape($code),
                null,
                false
            );
        }

        /*
         * Currency.
         */
        if (
            $currency !== ''
            && $this->dbx->field_exists('currency', $table)
        ) {
            $this->dbx->where(
                'UPPER(TRIM(currency)) = ' .
                $this->dbx->escape($currency),
                null,
                false
            );
        }

        /*
         * Numeric matching dengan tolerance.
         */
        if ($this->dbx->field_exists('qty', $table)) {
            $this->dbx->where(
                'ABS(CAST(COALESCE(qty,0) AS DECIMAL(18,4)) - ' .
                $this->dbx->escape($qty) .
                ') <= 0.0001',
                null,
                false
            );
        }

        if ($this->dbx->field_exists('unit_price', $table)) {
            $this->dbx->where(
                'ABS(CAST(COALESCE(unit_price,0) AS DECIMAL(18,2)) - ' .
                $this->dbx->escape($unitPrice) .
                ') <= 0.01',
                null,
                false
            );
        }

        /*
         * Invoice price historically kadang rounded.
         */
        if ($this->dbx->field_exists('price', $table)) {
            $this->dbx->where(
                'ABS(ROUND(CAST(COALESCE(price,0) AS DECIMAL(18,2)),0) - ' .
                $this->dbx->escape(round($price)) .
                ') <= 1',
                null,
                false
            );
        }

        /*
         * Kita cuma perlu tahu apakah 0 / 1 / >1.
         */
        $rows = $this->db
            ->limit(2)
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $matches[] = [
                'table'   => $table,
                'id'      => (int)$row['id'],
                'ref_col' => $refCol,
                'legacy'  => true,
            ];
        }
    }

    if (count($matches) === 0) {
        return [
            'ok'    => false,
            'error' => 'SOURCE_LINK_NOT_FOUND',
        ];
    }

    if (count($matches) > 1) {
        return [
            'ok'      => false,
            'error'   => 'AMBIGUOUS_SOURCE_LINK',
            'matches' => $matches,
        ];
    }

    return [
        'ok'     => true,
        'source' => $matches[0],
    ];
}


/**
 * Release SATU source invoice item.
 */
private function release_finance_item_source(
    array $item,
    array $finance,
    $region
) {
    $region     = trim((string)$region);
    $invoiceNo  = trim((string)($finance['number'] ?? ''));

    if ($invoiceNo === '' || $region === '') {
        return [
            'ok'    => false,
            'error' => 'INVALID_SOURCE_RELEASE_CONTEXT',
        ];
    }

    $sourceTable = '';
    $sourceId    = 0;
    $refCol      = '';

    /*
     * ============================================================
     * 1. DIRECT MAPPING BARU
     * ============================================================
     */
    if (
        !empty($item['income_source_table'])
        && !empty($item['income_source_id'])
    ) {
        $sourceTable = trim((string)$item['income_source_table']);
        $sourceId    = (int)$item['income_source_id'];
    }

    /*
     * source_income_id historically khusus selling price.
     */
    if (
        $sourceId <= 0
        && !empty($item['source_income_id'])
    ) {
        $sourceTable = 'inf_income_selling_price';
        $sourceId    = (int)$item['source_income_id'];
    }

    /*
     * Whitelist source table.
     */
    $allowed = [
        'inf_income_selling_price',
        'inf_income_from_agent',
        'inf_income_from_coloader',
        'x_glbincome',
    ];

    if (
        $sourceId > 0
        && !in_array($sourceTable, $allowed, true)
    ) {
        return [
            'ok'           => false,
            'error'        => 'INVALID_INCOME_SOURCE_TABLE',
            'source_table' => $sourceTable,
        ];
    }

    /*
     * ============================================================
     * 2. FALLBACK LEGACY
     * ============================================================
     *
     * Mapping lama banyak masih NULL.
     */
    if ($sourceId <= 0) {

        $legacy = $this->find_legacy_source_for_finance_item(
            $item,
            $invoiceNo,
            $region
        );

        if (empty($legacy['ok'])) {
            return $legacy;
        }

        $sourceTable = $legacy['source']['table'];
        $sourceId    = (int)$legacy['source']['id'];
        $refCol      = $legacy['source']['ref_col'];
    }

    if (!$this->dbx->table_exists($sourceTable)) {
        return [
            'ok'           => false,
            'error'        => 'SOURCE_TABLE_NOT_FOUND',
            'source_table' => $sourceTable,
        ];
    }

    /*
     * Tentukan ownership field.
     */
    if ($refCol === '') {
        if ($sourceTable === 'inf_income_selling_price') {
            $refCol = 'ship_co';
        } elseif (
            $sourceTable === 'inf_income_from_agent'
            || $sourceTable === 'inf_income_from_coloader'
        ) {
            $refCol = 'dn_no';
        }
    }

    /*
     * ============================================================
     * 3. LOCK / VALIDATE CURRENT SOURCE
     * ============================================================
     */
    $source = $this->db
        ->where('id', $sourceId)
        ->where('region_id', $region)
        ->limit(1)
        ->get($sourceTable)
        ->row_array();

    if (!$source) {
        return [
            'ok'           => false,
            'error'        => 'SOURCE_ROW_NOT_FOUND',
            'source_table' => $sourceTable,
            'source_id'    => $sourceId,
        ];
    }

    /*
     * Paid authority ada di parent x_finance yang sudah dicek caller.
     * Marker payment pada source historis bisa stale dan tidak boleh
     * memblokir release source unpaid.
     */

    /*
     * Kalau ownership sekarang menunjuk invoice lain,
     * JANGAN sentuh.
     */
    if (
        $refCol !== ''
        && $this->dbx->field_exists($refCol, $sourceTable)
    ) {
        $currentInvoice = trim((string)($source[$refCol] ?? ''));

        if (
            $currentInvoice !== ''
            && $currentInvoice !== $invoiceNo
        ) {
            return [
                'ok'              => false,
                'error'           => 'SOURCE_OWNED_BY_OTHER_INVOICE',
                'source_table'    => $sourceTable,
                'source_id'       => $sourceId,
                'current_invoice' => $currentInvoice,
                'delete_invoice'  => $invoiceNo,
            ];
        }
    }

    /*
     * ============================================================
     * 4. BUILD RELEASE DATA
     * ============================================================
     */
    $update = [];

    if ($this->dbx->field_exists('ditarik', $sourceTable)) {
        $update['ditarik'] = 0;
    }

    if (
        $refCol !== ''
        && $this->dbx->field_exists($refCol, $sourceTable)
    ) {
        $update[$refCol] = '';
    }

    if ($this->dbx->field_exists('billing_status', $sourceTable)) {
        $update['billing_status'] = 'UNBILLED';
    }

    if ($this->dbx->field_exists('billed_at', $sourceTable)) {
        $update['billed_at'] = null;
    }

    if ($this->dbx->field_exists('billed_by', $sourceTable)) {
        $update['billed_by'] = null;
    }

    if ($this->dbx->field_exists('updated_by', $sourceTable)) {
        $update['updated_by'] =
            (string)$this->session->userdata('fullname');
    }

    if ($this->dbx->field_exists('updated_at', $sourceTable)) {
        $update['updated_at'] = date('Y-m-d H:i:s');
    }

    if (empty($update)) {
        return [
            'ok'           => false,
            'error'        => 'SOURCE_HAS_NO_RELEASE_COLUMNS',
            'source_table' => $sourceTable,
            'source_id'    => $sourceId,
        ];
    }

    /*
     * ============================================================
     * 5. UPDATE BY PRIMARY KEY
     * ============================================================
     */
    $this->db
        ->where('id', $sourceId)
        ->where('region_id', $region);

    /*
     * Concurrency guard:
     * source masih boleh kosong atau milik invoice ini,
     * tetapi tidak boleh tiba-tiba berpindah ke invoice lain.
     */
    if (
        $refCol !== ''
        && $this->dbx->field_exists($refCol, $sourceTable)
    ) {
        $this->db
            ->group_start()
                ->where($refCol, $invoiceNo)
                ->or_where($refCol, '')
                ->or_where($refCol . ' IS NULL', null, false)
            ->group_end();
    }

    $this->dbx->update($sourceTable, $update);

    if ($this->dbx->trans_status() === false) {
        return [
            'ok'           => false,
            'error'        => 'SOURCE_RELEASE_DB_ERROR',
            'source_table' => $sourceTable,
            'source_id'    => $sourceId,
            'db_error'     => $this->dbx->error(),
        ];
    }

    return [
        'ok'           => true,
        'released'     => true,
        'source_table' => $sourceTable,
        'source_id'    => $sourceId,
    ];
}
	
	
	function manage_table_satu($data)
{
    $oper   = isset($data['oper'])
        ? strtolower(trim((string)$data['oper']))
        : '';

    $region = trim((string)$this->session->userdata('region_id'));

    /*
     * Function ini hanya menangani delete.
     */
    if ($oper !== 'del') {
        return [
            'status'  => 0,
            'message' => 'UNSUPPORTED_OPERATION',
        ];
    }

    if ($region === '') {
        return [
            'status'  => 0,
            'message' => 'REGION_REQUIRED',
        ];
    }

    /*
     * Bisa menerima:
     * 123
     * atau
     * 123,124,125
     */
    $idsRaw = explode(',', (string)($data['id'] ?? ''));

    $ids = [];

    foreach ($idsRaw as $id) {
        $id = (int)$id;

        if ($id > 0) {
            $ids[] = $id;
        }
    }

    $ids = array_values(array_unique($ids));

    if (empty($ids)) {
        return [
            'status'  => 0,
            'message' => 'NO_FINANCE_ITEM_ID',
        ];
    }

    $this->dbx->trans_begin();

    $deleted      = [];
    $released     = [];
    $manualItems  = [];
    $taxItems     = [];
    $financeIds   = [];

    foreach ($ids as $itemId) {

        /*
         * ========================================================
         * 1. AMBIL ITEM + KUNCI REGION
         * ========================================================
         */
        $item = $this->db
            ->where('id', $itemId)
            ->where('region_id', $region)
            ->limit(1)
            ->get('x_finance_item')
            ->row_array();

        if (!$item) {
            $this->dbx->trans_rollback();

            return [
                'status'  => 0,
                'message' => 'FINANCE_ITEM_NOT_FOUND',
                'item_id' => $itemId,
            ];
        }

        $financeId = (int)($item['number'] ?? 0);

        if ($financeId <= 0) {
            $this->dbx->trans_rollback();

            return [
                'status'  => 0,
                'message' => 'INVALID_FINANCE_ID',
                'item_id' => $itemId,
            ];
        }

        /*
         * ========================================================
         * 2. AMBIL HEADER
         * ========================================================
         */
        $finance = $this->db
            ->where('id', $financeId)
            ->where('region_id', $region)
            ->limit(1)
            ->get('x_finance')
            ->row_array();

        if (!$finance) {
            $this->dbx->trans_rollback();

            return [
                'status'     => 0,
                'message'    => 'FINANCE_HEADER_NOT_FOUND',
                'item_id'    => $itemId,
                'finance_id' => $financeId,
            ];
        }

        /*
         * ========================================================
         * 3. PAID INVOICE IMMUTABLE
         * ========================================================
         */
        if (
            strtolower(
                trim((string)($finance['status'] ?? ''))
            ) === 'paid'
        ) {
            $this->dbx->trans_rollback();

            return [
                'status'     => 0,
                'http'       => 409,
                'message'    => 'PAID_INVOICE_REQUIRES_REVISION',
                'requires_revision' => 1,
                'item_id'    => $itemId,
                'finance_id' => $financeId,
                'invoice_no' => $finance['number'] ?? '',
            ];
        }

        /*
         * ========================================================
         * 4. RELEASE SOURCE
         * ========================================================
         *
         * VAT/TAX tidak mempunyai source income langsung.
         *
         * id_tarik=0 dianggap item manual, jadi jangan release
         * operational income.
         */
        if ($this->is_tax_finance_item($item)) {

            $taxItems[] = $itemId;

        } elseif ((int)($item['id_tarik'] ?? 0) === 0) {

            $manualItems[] = $itemId;

        } else {

            $release = $this->release_finance_item_source(
                $item,
                $finance,
                $region
            );

            if (empty($release['ok'])) {
                $this->dbx->trans_rollback();

                return [
                    'status'     => 0,
                    'http'       => 409,
                    'message'    => $release['error']
                        ?? 'SOURCE_RELEASE_FAILED',
                    'item_id'    => $itemId,
                    'finance_id' => $financeId,
                    'invoice_no' => $finance['number'] ?? '',
                    'detail'     => $release,
                ];
            }

            if (!empty($release['released'])) {
                $released[] = [
                    'item_id'      => $itemId,
                    'source_table' => $release['source_table'],
                    'source_id'    => $release['source_id'],
                ];
            }
        }

        /*
         * ========================================================
         * 5. DELETE HANYA x_finance_item YANG DIMINTA
         * ========================================================
         */
        $this->db
            ->where('id', $itemId)
            ->where('number', $financeId)
            ->where('region_id', $region)
            ->delete('x_finance_item');

        if ($this->dbx->trans_status() === false) {
            $err = $this->dbx->error();
            $this->dbx->trans_rollback();

            return [
                'status'   => 0,
                'message'  => 'DELETE_FINANCE_ITEM_FAILED',
                'item_id'  => $itemId,
                'db_error' => $err,
            ];
        }

        $deleted[] = $itemId;
        $financeIds[$financeId] = true;
    }

    /*
     * ============================================================
     * 6. RECALCULATE GRAND TOTAL
     * ============================================================
     *
     * Dilakukan per finance header setelah seluruh requested item
     * selesai dihapus.
     */
    foreach (array_keys($financeIds) as $financeId) {

        $sumRow = $this->db
            ->select_sum('price', 'total')
            ->where('number', $financeId)
            ->where('region_id', $region)
            ->get('x_finance_item')
            ->row_array();

        $grandTotal = isset($sumRow['total'])
            ? (float)$sumRow['total']
            : 0;

        $updateFinance = [
            'grand_total' => $grandTotal,
        ];

        if ($this->dbx->field_exists('updated_by', 'x_finance')) {
            $updateFinance['updated_by'] =
                (string)$this->session->userdata('fullname');
        }

        if ($this->dbx->field_exists('updated_at', 'x_finance')) {
            $updateFinance['updated_at'] =
                date('Y-m-d H:i:s');
        }

        $this->db
            ->where('id', $financeId)
            ->where('region_id', $region)
            ->update('x_finance', $updateFinance);
    }

    /*
     * ============================================================
     * 7. FINAL TRANSACTION CHECK
     * ============================================================
     */
    if ($this->dbx->trans_status() === false) {

        $err = $this->dbx->error();

        $this->dbx->trans_rollback();

        return [
            'status'   => 0,
            'message'  => 'DELETE_FINANCE_ITEM_TRANSACTION_FAILED',
            'db_error' => $err,
        ];
    }

    $this->dbx->trans_commit();

    return [
        'status'       => 1,
        'message'      => 'FINANCE_ITEM_DELETED',
        'deleted'      => $deleted,
        'released'     => $released,
        'manual_items' => $manualItems,
        'tax_items'    => $taxItems,
    ];
}

	
	function get_table_dua($data,$limit){
		
		$this->dbx->select('kode_ref,agen_penagihan,sp_id,trk_data',false);
		
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      	$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains

        	);

      	foreach ($ops as $key=>$value){
 				if($searchOper==$key) {
            	$ops = $value;
          	}
     		} 

       	if($searchOper == 'eq' ) $searchString = $searchString;
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		$this->dbx->order_by($data['sidx'], $data['sord']);
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		return $this->dbx->get_where('trnstuffingplan',array(
			'kode_ref !='=>'',
			'region_id'=>$this->session->userdata('region_id'),
		))->result();
	}
	
	function get_blue($kr){
		$q = $this->dbx->get_where('x_finance',array('job_no'=>$kr,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function tampil_data($data){
		$table = $data['table'];
		unset($data['table']);
		if ($table == 'x_finance') {
			$this->dbx->where('x_finance.region_id',$this->session->userdata('region_id'));
		}
		if ($table == 'x_finance_item') {
			// Urutkan agar item PPN, VAT, atau TAX dipindah ke paling bawah (nilai 1), selain itu di atas (nilai 0)
			$this->dbx->order_by("(CASE WHEN item LIKE '%PPN%' OR item LIKE '%VAT%' OR item LIKE '%TAX%' OR code LIKE '%TAX%' THEN 1 ELSE 0 END)", "ASC", FALSE);
			// Setelah dipisah atas-bawah, baru diurutkan berdasarkan bl_no
			$this->dbx->order_by('bl_no','asc');
		}
		return $this->dbx->get_where($table,$data);
	}
	
	function get_content($data){
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$data["kode_ref"],'region_id'=>$this->session->userdata("region_id")))->row();
		return $q;
	}

	function get_opt_agent($agent){
		$this->dbx->select ('agent,g,prep_coll',FALSE);
		return $this->dbx->get_where('inf_income_selling_price',array('agent'=>$agent,'prep_coll'=>'COLLECT','ditarik'=>'0'));
	}
	
	function get_opt_agent_cost($agent){
		$this->dbx->select ('g,prep_coll',FALSE);
		return $this->dbx->get_where('inf_cost_tbl_satu',array('prep_coll'=>'COLLECT'));
	}
	
	function getListAgent(){
		$this->dbx->select ('agent_company_id,agent_company_code',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('refagentcompany');
	}
	
	function getListShippco(){
		$this->dbx->select ('shipping_company_id,shipping_company_code',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('refshippingcompany');
	}
	
	function getListWarehouse(){
		$this->dbx->select ('warehouse_id,warehouse_code ',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('refwarehouse');
	}
	
	function getListTrucking(){
		$this->dbx->select ('trucking_company_id,trucking_company_code ',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('reftruckingcompany');
	}
	
	function getListShipper(){
		$this->dbx->select ('shipper_id,shipper_name',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('mstshipper');
	}
	function getListConsignee(){
		$this->dbx->select ('consignee_notify_code,consignee_notify_name',FALSE);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get('refconsigneenotify');
	}
	
	function tampil_data_tabel_empat($post){
		$region = $this->session->userdata('region_id');
		$this->dbx->order_by($post['sidx'],$post['sord']);
		return $this->dbx->get_where('x_finance',array('fin_proses'=>'1','region_id'=>$region));
	}

	function lock_job($data){
		$query = $this->dbx->query("update trnstuffingplan set trk_data = 1 where kode_ref='$data'");
		return $query;
	}

		function get_nama_alamat($data)
	{
		// terima 'kepada' maupun 'cari' (nama parameter di view lama)
		$kepada  = (isset($data['kepada']) && $data['kepada'] !== '')
					? strtoupper(trim($data['kepada']))
					: (isset($data['cari']) ? strtoupper(trim($data['cari'])) : '');
		$code    = isset($data['code'])   ? trim($data['code'])   : '';
		$number  = isset($data['number']) ? trim($data['number']) : '';
		$confirm = isset($data['confirm']) && $data['confirm'] == '1';

		$region  = $this->session->userdata('region_id');

		// tabel, kolom kode, kolom nama, kolom alamat
		$map = array(
			'AGENT'     => array('refagentcompany',     'agent_company_code',     'agent_company_name',     'agent_company_address'),
			'SHIPCO'    => array('refshippingcompany',  'shipping_company_code',  'shipping_company_name',  'shipping_company_address'),
			'COLOADER'  => array('refshippingcompany',  'shipping_company_code',  'shipping_company_name',  'shipping_company_address'),
			'WAREHOUSE' => array('refwarehouse',        'warehouse_code',         'warehouse_name',         'warehouse_address'),
			'TRUCKING'  => array('reftruckingcompany',  'trucking_company_code',  'trucking_company_name',  'trucking_company_address'),
			'CONSIGNEE' => array('refconsigneenotify',  'consignee_notify_code',  'consignee_notify_name',  'consignee_notify_address'),
			'SHIPPER'   => array('mstshipper',          'shipper_id',             'shipper_name',           'shipper_address'),
		);

		if (!isset($map[$kepada]) || $code === '') {
			return array('status' => 0, 'nama' => '', 'alamat' => '', 'saved' => 0);
		}

		list($tbl, $col_code, $col_name, $col_addr) = $map[$kepada];

		$where = array($col_code => $code);

		// hanya filter region kalau tabelnya punya kolom region_id
		$fields = $this->dbx->list_fields($tbl);
		if (in_array('region_id', $fields)) {
			$where['region_id'] = $region;
		}

		$q = $this->dbx->get_where($tbl, $where, 1);

		if ($q->num_rows() == 0) {
			return array('status' => 0, 'nama' => '', 'alamat' => '', 'saved' => 0);
		}

		$row    = $q->row();
		$nama   = isset($row->{$col_name}) ? $row->{$col_name} : '';
		$alamat = isset($row->{$col_addr}) ? $row->{$col_addr} : '';
		$saved  = 0;

		// Menyimpan HANYA bila dikonfirmasi eksplisit dari frontend
		if ($confirm && $number !== '') {
			$this->dbx->where('number', $number);
			$this->dbx->where('region_id', $region);
			$this->dbx->update('x_finance', array(
				'kepada'      => $kepada,
				'kepada_kode' => $code,
				'updated_by'  => $this->session->userdata('fullname'),
				'updated_at'  => date('Y-m-d H:i:s'),
			));
			$saved = $this->dbx->affected_rows();
		}

		return array('status' => 1, 'nama' => $nama, 'alamat' => $alamat, 'saved' => $saved);
	}


}
?>