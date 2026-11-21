<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    protected $erp;

    public function __construct()
    {
        parent::__construct();
        $this->erp = $this->load->database('erp', TRUE);
    }

    public function erp_db()
    {
        return $this->erp;
    }

    protected function auth_context()
    {
        $ci =& get_instance();

        if (method_exists($ci, 'get_auth_context')) {
            $ctx = $ci->get_auth_context();
            return is_array($ctx) ? $ctx : [];
        }

        return [];
    }

    /*
     * Backward compatibility untuk model lama yang masih memanggil auth_payload().
     * Tidak decode JWT kedua kali; payload dibentuk dari context MY_Controller.
     */
    protected function auth_payload()
    {
        $ctx = $this->auth_context();

        if (empty($ctx)) {
            return null;
        }

        return (object)[
            'data' => (object)[
                'user_id'   => $ctx['user_id'] ?? null,
                'username'  => $ctx['username'] ?? null,
                'level'     => $ctx['level'] ?? null,
                'region_id' => $ctx['region_id'] ?? null,
                'year'      => $ctx['year'] ?? null,
            ],
        ];
    }

    protected function auth_region()
    {
        $ctx = $this->auth_context();
        return trim((string)($ctx['region_id'] ?? ''));
    }

    protected function auth_level()
    {
        $ctx = $this->auth_context();
        return $ctx['level'] ?? null;
    }

    protected function auth_year()
    {
        $ctx = $this->auth_context();
        return $ctx['year'] ?? null;
    }

    protected function auth_database()
    {
        $ctx = $this->auth_context();
        return $ctx['database'] ?? null;
    }

    protected function auth_username()
    {
        $ctx = $this->auth_context();
        return $ctx['username'] ?? null;
    }

    protected function auth_user_id()
    {
        $ctx = $this->auth_context();
        return isset($ctx['user_id']) ? (int)$ctx['user_id'] : null;
    }

    /*
     * Level 0 tetap boleh dipakai untuk authorization fitur,
     * tetapi TIDAK PERNAH boleh bypass filter region.
     */
    protected function is_super_admin()
    {
        return (string)$this->auth_level() === '0';
    }

    public function apply_region_filter($column = 'region_id')
    {
        $region = $this->auth_region();

        if ($region !== '') {
            $this->erp->where($column, $region);
        }
    }

    public function apply_region_filter_alias($alias, $column = 'region_id')
    {
        $region = $this->auth_region();

        if ($region !== '') {
            $this->erp->where($alias . '.' . $column, $region);
        }
    }

    public function inject_region(array &$data, $column = 'region_id')
    {
        $region = $this->auth_region();

        /*
         * Request user: auth region override semua nilai payload.
         * Internal API: auth region kosong, jadi region trusted payload dibiarkan.
         */
        if ($region !== '') {
            $data[$column] = $region;
        }
    }

    public function inject_create_audit(array &$data)
    {
        $username = $this->auth_username();

        if (!isset($data['created_by'])) {
            $data['created_by'] = $username;
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
    }

    public function inject_update_audit(array &$data)
    {
        $username = $this->auth_username();

        if (!isset($data['updated_by'])) {
            $data['updated_by'] = $username;
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
    }

    public function apply_company_filter($column = 'region_id')
    {
        $this->apply_region_filter($column);
    }

    public function apply_company_filter_alias($alias, $column = 'region_id')
    {
        $this->apply_region_filter_alias($alias, $column);
    }
}
