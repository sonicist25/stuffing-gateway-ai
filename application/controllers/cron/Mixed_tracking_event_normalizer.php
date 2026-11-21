<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mixed_tracking_event_normalizer extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_mixed_tracking_event_normalize', 'normalizer');
    }

    /**
     * Manual test:
     * /cron/mixed_tracking_event_normalizer/run?shipper_id=4407&limit=20
     *
     * Cron:
     * /cron/mixed_tracking_event_normalizer/run?shipper_id=4407&limit=100
     */
    public function run()
    {
        $shipper_id = (int) $this->input->get('shipper_id');
        $limit      = (int) $this->input->get('limit');

        if ($shipper_id <= 0) {
            $shipper_id = 4407; // Firmenich
        }

        $limit = $this->input->get('limit');

        if ($limit === null || $limit === '') {
            $limit = null; // null artinya semua
        } else {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = null;
            }
        }

        $result = $this->normalizer->normalize_latest_snapshots($shipper_id, $limit);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Test satu sync_id:
     * /cron/mixed_tracking_event_normalizer/run_one/79
     */
    public function run_one($sync_id = null)
    {
        $sync_id = (int) $sync_id;

        if ($sync_id <= 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'sync_id is required'
                ]));
            return;
        }

        $result = $this->normalizer->normalize_one_latest_snapshot($sync_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
}