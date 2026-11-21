<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Competitor extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('api/crm/Competitor_model');
    }

	public function get_competitors() {
		$search = $this->input->get('q');
		$type = $this->input->get('type');

		$data = $this->Competitor_model->get_competitors($search, $type);
		
		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'status' => 'success',
				'data'   => $data
			]));
	}
}