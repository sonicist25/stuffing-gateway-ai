<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_cron extends CI_Controller
{
    protected $cfg = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->library('Transvoyant_sync_service');
        $this->load->model('Transvoyant_shipment_sync_model');

        $this->config->load('transvoyant');
        $cfg = $this->config->item('transvoyant');
        $this->cfg = is_array($cfg) ? $cfg : [];
    }

    /**
     * =========================================================
     * AUTH GUARD
     * - kalau CLI: selalu boleh
     * - kalau HTTP: wajib token jika diset di config
     * =========================================================
     */
    protected function authorize_request()
    {
        // CLI selalu boleh
        if ($this->input->is_cli_request()) {
            return true;
        }

        $cronToken = $this->cfg['cron_token'] ?? null;

        // kalau token belum diset, sementara izinkan
        if (empty($cronToken)) {
            return true;
        }

        $requestToken = (string) $this->input->get('token', true);

        if (!hash_equals((string)$cronToken, $requestToken)) {
            $this->respond_json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
            return false;
        }

        return true;
    }

    /**
     * =========================================================
     * OUTPUT JSON
     * =========================================================
     */
    protected function respond_json(array $data, $httpCode = 200)
    {
        $this->output
            ->set_status_header((int)$httpCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * =========================================================
     * PARSE LIMIT
     * - HTTP: dari query string ?limit=
     * - CLI : argumen ke-5 => php index.php cron transvoyant_cron run 20
     * =========================================================
     */
    protected function get_limit($default = 100)
    {
        if ($this->input->is_cli_request()) {
            global $argv;
            if (isset($argv[4]) && is_numeric($argv[4])) {
                $limit = (int)$argv[4];
                return ($limit > 0) ? $limit : $default;
            }
            return $default;
        }

        $limit = (int) $this->input->get('limit');
        return ($limit > 0) ? $limit : $default;
    }

    /**
     * =========================================================
     * PARSE ID
     * - HTTP: dari query string ?id=
     * - CLI : argumen ke-5 => php index.php cron transvoyant_cron process_one 123
     * =========================================================
     */
    protected function get_sync_id()
    {
        if ($this->input->is_cli_request()) {
            global $argv;
            if (isset($argv[4]) && is_numeric($argv[4])) {
                return (int)$argv[4];
            }
            return 0;
        }

        return (int) $this->input->get('id');
    }

    /**
     * =========================================================
     * RUN SCHEDULER
     *
     * HTTP:
     * /index.php/cron/transvoyant_cron/run?token=RAHASIA&limit=20
     *
     * CLI:
     * php index.php cron transvoyant_cron run
     * php index.php cron transvoyant_cron run 20
     * =========================================================
     */
    public function run()
    {
        if (!$this->authorize_request()) {
            return;
        }

        $limit = $this->get_limit(100);

        try {
            $result = $this->transvoyant_sync_service->run_scheduler($limit);

            $result['meta'] = [
                'mode'      => $this->input->is_cli_request() ? 'cli' : 'http',
                'limit'     => $limit,
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            $this->respond_json($result, 200);
        } catch (Exception $e) {
            $this->respond_json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'stage'     => 'run',
                'timestamp' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    /**
     * =========================================================
     * PROCESS ONE SYNC ROW
     *
     * HTTP:
     * /index.php/cron/transvoyant_cron/process_one?token=RAHASIA&id=1
     *
     * CLI:
     * php index.php cron transvoyant_cron process_one 1
     * =========================================================
     */
    public function process_one()
    {
        if (!$this->authorize_request()) {
            return;
        }

        $id = $this->get_sync_id();

        if ($id <= 0) {
            $this->respond_json([
                'success' => false,
                'message' => 'Parameter id wajib dan harus numerik',
            ], 400);
            return;
        }

        $row = $this->Transvoyant_shipment_sync_model->find_by_id($id);

        if (!$row) {
            $this->respond_json([
                'success' => false,
                'message' => 'Sync row tidak ditemukan',
                'sync_id' => $id,
            ], 404);
            return;
        }

        try {
            $result = $this->transvoyant_sync_service->process_sync($row, $this->input->is_cli_request());

            $result['meta'] = [
                'mode'      => $this->input->is_cli_request() ? 'cli' : 'http',
                'sync_id'   => $id,
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            $this->respond_json($result, 200);
        } catch (Exception $e) {
            $this->respond_json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'stage'     => 'process_one',
                'sync_id'   => $id,
                'timestamp' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
    /** CLI only: inspect saved snapshot and mapper without resending. */
    public function audit_one()
    {
        if (!$this->input->is_cli_request()) {
            $this->respond_json(['success' => false, 'message' => 'CLI only'], 403);
            return;
        }
        $id = $this->get_sync_id();
        $row = $id > 0 ? $this->Transvoyant_shipment_sync_model->find_by_id($id) : null;
        if (!$row) {
            $this->respond_json(['success' => false, 'message' => 'Sync row not found'], 404);
            return;
        }
        try {
            $this->respond_json($this->transvoyant_sync_service->audit_sync_snapshot($row));
        } catch (Throwable $e) {
            $this->respond_json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}