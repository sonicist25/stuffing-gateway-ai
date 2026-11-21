<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Port extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('api/Port_model');
    }

    // Endpoint 1: Export
    // POL: Indonesia (ID), POD: Luar Indonesia (!ID)
    // Endpoint 1: Export
	// POL: Indonesia (ID), POD: Luar Indonesia (!ID)
	public function get_dropdown_export($type = '') {
		$q = $this->input->get('q'); // ambil query string ?q=...

		if ($type === 'pol') {
			// POL Export: Indonesia saja
			$data = $this->Port_model->get_locations(TRUE, $q);
		} elseif ($type === 'pod') {
			// POD Export: Luar Indonesia saja
			$data = $this->Port_model->get_locations(FALSE, $q);
		} else {
			// Jika tidak ada parameter atau salah parameter
			return $this->output->set_status_header(404);
		}

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));
	}

    // URL: domain.com/api/get_dropdown_import/pol
    // URL: domain.com/api/get_dropdown_import/pod
    public function get_dropdown_import($type = '') {
		$q = $this->input->get('q'); // ambil query string ?q=...
        if ($type === 'pod') {
            // POD Import: Indonesia saja
            $data = $this->Port_model->get_locations(TRUE, $q);
        } elseif ($type === 'pol') {
            // POL Import: Luar Indonesia saja
            $data = $this->Port_model->get_locations(FALSE, $q);
        } else {
            return $this->output->set_status_header(404);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
	}

    // Endpoint 3: Domestik
    public function get_domestic() {
        $keyword = $this->input->get('q'); // User bebas ketik apa saja
        
        if (strlen($keyword) < 3) {
            // Opsional: Batasi pencarian minimal 3 karakter agar tidak berat
            return $this->output->set_content_type('application/json')
                        ->set_output(json_encode(["message" => "Ketik minimal 3 karakter"]));
        }

        $res = $this->Port_model->search_domestic($keyword);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }
	
	public function get_airport() {
        $keyword = $this->input->get('q'); // User bebas ketik apa saja
        
        if (strlen($keyword) < 3) {
            // Opsional: Batasi pencarian minimal 3 karakter agar tidak berat
            return $this->output->set_content_type('application/json')
                        ->set_output(json_encode(["message" => "Ketik minimal 3 karakter"]));
        }

        $res = $this->Port_model->search_airport($keyword);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }
	
	public function get_dropdown_port($type = '') {
		$q = $this->input->get('q'); // ambil query string ?q=...

		if ($type === 'all') {
			// POL Export: Indonesia saja
			$data = $this->Port_model->get_port($q);
		} else {
			// Jika tidak ada parameter atau salah parameter
			return $this->output->set_status_header(404);
		}

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));
	}
}