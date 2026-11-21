<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UploadController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('upload');
    }

    public function index() {
        $this->load->view('upload_form');
    }

    public function uploadFile() {
        // Set upload options
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';  // Adjust allowed types as needed
        $config['max_size'] = 2048; // 2MB limit

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed, return errors
            $error = array('error' => $this->upload->display_errors());
            $this->load->view('upload_form', $error);
        } else {
            // Upload successful, proceed with OCR
            $fileData = $this->upload->data();
            $this->processOCR($fileData['full_path']);
        }
    }
    
    public function processOCR($filePath) {
        // Implement OCR functionality here
    }
}
?>
