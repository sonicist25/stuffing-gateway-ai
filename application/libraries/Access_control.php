<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Access_control
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function is_super_admin()
    {
        return (string)$this->CI->session->userdata('user_level') === '0'
            || (string)$this->CI->session->userdata('level_id') === '0'
            || (string)$this->CI->session->userdata('user_id') === '0';
    }

    public function user_id()
    {
        return $this->CI->session->userdata('user_id');
    }

    /**
     * Rule access baru hanya berlaku untuk region Surabaya.
     * Selain Surabaya, sementara semua akses dilewatkan agar tidak mengganggu cabang lain.
     */
    public function is_surabaya_region()
    {
        $region = trim((string)$this->CI->session->userdata('region_id'));

        return strtoupper($region) === 'SURABAYA';
    }

    /**
     * Kalau rule baru belum diberlakukan untuk region ini,
     * maka return true agar memakai behaviour lama / tidak dibatasi.
     */
    private function should_bypass_new_rule()
    {
        return !$this->is_surabaya_region();
    }

    private function normalize_action($action)
    {
        $action = strtolower(trim((string)$action));

        $allowed = ['view', 'create', 'edit', 'delete'];

        return in_array($action, $allowed, true) ? $action : 'view';
    }

    private function action_column($action)
    {
        $action = $this->normalize_action($action);

        switch ($action) {
            case 'create':
                return 'can_create';

            case 'edit':
                return 'can_edit';

            case 'delete':
                return 'can_delete';

            case 'view':
            default:
                return 'can_view';
        }
    }

    public function can_menu_action($menu_key, $action = 'view')
    {
        /**
         * FILTER UTAMA:
         * Selain Surabaya, jangan berlakukan rule baru dulu.
         */
        if ($this->should_bypass_new_rule()) {
            return true;
        }

        if ($this->is_super_admin()) {
            return true;
        }

        $user_id = $this->user_id();

        if (empty($user_id) || empty($menu_key)) {
            return false;
        }

        $column = $this->action_column($action);

        return $this->CI->db
            ->where('user_id', $user_id)
            ->where('menu_key', $menu_key)
            ->where($column, 1)
            ->count_all_results('mst_user_menu_access') > 0;
    }

    public function can_menu($menu_key)
    {
        return $this->can_menu_action($menu_key, 'view');
    }

    public function can_create_menu($menu_key)
    {
        return $this->can_menu_action($menu_key, 'create');
    }

    public function can_edit_menu($menu_key)
    {
        return $this->can_menu_action($menu_key, 'edit');
    }

    public function can_delete_menu($menu_key)
    {
        return $this->can_menu_action($menu_key, 'delete');
    }

    public function can_feature($feature_key, $action = 'view')
    {
        /**
         * FILTER UTAMA:
         * Selain Surabaya, jangan berlakukan rule baru dulu.
         */
        if ($this->should_bypass_new_rule()) {
            return true;
        }

        if ($this->is_super_admin()) {
            return true;
        }

        $user_id = $this->user_id();

        if (empty($user_id) || empty($feature_key)) {
            return false;
        }

        $column = $this->action_column($action);

        return $this->CI->db
            ->where('user_id', $user_id)
            ->where('feature_key', $feature_key)
            ->where($column, 1)
            ->count_all_results('mst_user_feature_access') > 0;
    }

    public function can_create_feature($feature_key)
    {
        return $this->can_feature($feature_key, 'create');
    }

    public function can_edit_feature($feature_key)
    {
        return $this->can_feature($feature_key, 'edit');
    }

    public function can_delete_feature($feature_key)
    {
        return $this->can_feature($feature_key, 'delete');
    }

    public function require_menu($menu_key, $action = 'view')
    {
        if (!$this->can_menu_action($menu_key, $action)) {
            $this->deny();
        }

        return true;
    }

    public function require_feature($feature_key, $action = 'view', $ajax = false)
		{
			if ($this->can_feature($feature_key, $action)) {
				return true;
			}

			$labelAction = [
				'view'   => 'melihat',
				'create' => 'menambah',
				'edit'   => 'mengubah',
				'delete' => 'menghapus',
			];

			$act = isset($labelAction[$action]) ? $labelAction[$action] : $action;

			$message = 'Anda tidak memiliki akses untuk ' . $act . ' data ini. Silakan hubungi administrator.';

			if ($ajax) {
				$this->deny_json($message, $feature_key, $action);
			} else {
				$this->deny($message);
			}

			return false;
	}

    public function deny($message = 'Access denied')
	{
		$this->CI->session->set_flashdata('restrict', $message);
		redirect(base_url('afg_c_stuffing_plan'), 'refresh');
		exit;
	}

    public function deny_json($message = 'Anda tidak memiliki akses untuk melakukan aksi ini.', $feature_key = null, $action = null)
	{
		$payload = [
			'status'  => false,
			'code'    => 'ACCESS_DENIED',
			'message' => $message,
		];

		if ($feature_key !== null) {
			$payload['feature'] = $feature_key;
		}

		if ($action !== null) {
			$payload['action'] = $action;
		}

		$this->CI->output
			->set_status_header(403)
			->set_content_type('application/json')
			->set_output(json_encode($payload));

		exit;
	}
	public function get_user_job_codes($user_id = null)
{
    if ($user_id === null) {
        $user_id = $this->user_id();
    }

    if (empty($user_id)) {
        return [];
    }

    $rows = $this->CI->db
        ->select('job_code')
        ->where('user_id', $user_id)
        ->order_by('job_code', 'asc')
        ->get('mst_user_job_access')
        ->result_array();

    $codes = [];

    foreach ($rows as $row) {
        $code = trim((string)$row['job_code']);
        if ($code !== '') {
            $codes[] = $code;
        }
    }

    return $codes;
}

/**
 * TRUE kalau user punya pembatasan job code.
 * Kalau false, berarti default all job code allowed.
 */
public function has_job_code_restriction($user_id = null)
{
    if ($user_id === null) {
        $user_id = $this->user_id();
    }

    if (empty($user_id)) {
        return false;
    }

    return $this->CI->db
        ->where('user_id', $user_id)
        ->count_all_results('mst_user_job_access') > 0;
}
}