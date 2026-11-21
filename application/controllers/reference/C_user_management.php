<?php
use thiagoalessio\TesseractOCR\TesseractOCR;
class C_user_management extends CI_Controller {	

	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model(array('reference/unit_model','menu_model','user_model','reference/region_model','m_login'));
		$this->fungsi->dbx = $this->user_model->dbx;
		$this->dbx = $this->user_model->dbx;
	}
	
	function index() {
		$this->fungsi->auth(21,'User Management');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_user_management', 'h1_title' => 'Management User','title' => 'Management User' );
		$this->load->view('afg_template', $data);
	}
	
	function getDataUserBooking(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->user_model->getAllUser($_POST,false);
		//print_r($hal);
		//die();
		$query = $this->user_model->getAllUser($_POST,true);
		//print_r($query);
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
		
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;		
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($query as $row) {
			$data->rows[$i]['id'] = $row->id_shipper;
			$data->rows[$i]['cell'] = array(
				'', // upload_file
				'', // upload_ccf
				'', // upload_npwp
				$row->consignee, // is Consignee Import (posisi diperbaiki mengikuti urutan colModel)
				$row->shipper_id,
				$row->password,
				$row->shipper_name,
				$row->shipper_address,
				$row->npwp_name,
				$row->shipper_npwp,
				$row->npwp_address,
				$row->nib,
				$row->cp_name,
				$row->cp_phone,
				$row->cp_email,
				$row->region_id,
				$row->isActive,
				$row->sales_names, // Ambil dari alias GROUP_CONCAT di model
				$row->isActive,
				$row->password_no_encript,
				$row->created_by,
				$row->created_at,
				$row->sales_ids,   // ID sales (untuk keperluan value di multiselect edit form)
				$row->account,
				$row->payment,
				$row->type,
			);
			$i++;
		}
		echo json_encode($data);
	}
	
	function dropdownregion() {
		$this->load->model(array('reference/region_model'));
		$region = $this->region_model->get_data();
		$data['option_region'][''] = '';
		foreach ($region as $row)
		{	
			$data['option_region'][$row->region_name] = $row->region_name;
		}
		echo form_dropdown('dropdownregion', $data['option_region'],'', 'id="dropdownregion"');
	}
	
	function manage_user_booking() {
		$oper = $_POST['oper'];
		$id = $_POST['id'];
		
		unset($_POST['oper']);
		unset($_POST['id']);
		
		$_POST['id_shipper'] = $id;
		$send_mail ="";
		$message = ""; // Tambahan variabel untuk menyimpan pesan error (opsional)
		
		if($oper == 'add') {
			// Validasi NPWP tidak boleh kosong
			if(empty($_POST['shipper_npwp'])) {
				$status = -1;
				$message = "NPWP Number wajib diisi.";
			} else {
				//$_POST['password_no_encript'] = $_POST['password'];
				$_POST['created_by'] = $this->session->userdata('fullname');
				$_POST['created_at'] = date('Y-m-d H:i:s');
				$_POST['shipper_npwp'] = $this->clean_string($_POST['shipper_npwp']);
				
				$status = $this->user_model->insertDataUserBooking($_POST);
			}
		} else if($oper == 'edit' ){
			// Validasi NPWP tidak boleh kosong
			if(empty($_POST['shipper_npwp'])) {
				$status = -1;
				$message = "NPWP Number wajib diisi.";
			} else {
				//$_POST['password_no_encript'] = $_POST['password'];
				$_POST['updated_by'] = $this->session->userdata('fullname');
				$_POST['updated_at'] = date('Y-m-d H:i:s');
				$_POST['shipper_npwp'] = $this->clean_string($_POST['shipper_npwp']);
				
				$status = $this->user_model->updateDataUserBooking($_POST);
				//print_r($this->session->userdata('db'));
				//die();
				/* if($aktivasi == 'No' && $_POST['isActive']=='Yes') {
				//	$send_mail = $this->send_mail($id,$status);
					$send_mail = '1';
				} */
			}
		} else if($oper == 'del') {
			$status = $this->user_model->deleteDataUserBooking($_POST);
 		}	
		
		// Menambahkan parameter message di JSON respons untuk dibaca oleh frontend jika dibutuhkan
		echo json_encode(array('status'=>$status, 'oper'=>$oper, 'send_mail'=>$send_mail,'id_user'=>$id, 'message'=>$message));
	}
	
	/* =====================================================================
	   ALAMAT KIRIM & INFO TAMBAHAN
	   Skema baru: mstshipper_address + reg_villages/reg_districts/reg_regencies
	   Dipakai oleh tab "ALAMAT KIRIM" & "INFO TAMBAHAN" di modal edit customer
	   ===================================================================== */

	function get_shipper_addresses() {
		$id_shipper = $this->input->post('id_shipper');
		$rows = $this->user_model->get_shipper_addresses($id_shipper);
		echo json_encode(array('status' => 1, 'rows' => $rows));
	}

	function save_shipper_address() {
		$p = $this->input->post();

		if (empty($p['id_shipper']) || empty($p['address']) || empty($p['regency_id'])) {
			echo json_encode(array('status' => -1, 'message' => 'Alamat dan wilayah (min. Kota/Kabupaten) wajib diisi. Pilih wilayah dari daftar autocomplete.'));
			return;
		}

		// Koordinat (opsional). Terima koma sbg desimal, validasi rentang.
		$lat = $this->normalize_coord(isset($p['latitude'])  ? $p['latitude']  : '');
		$lng = $this->normalize_coord(isset($p['longitude']) ? $p['longitude'] : '');
		if (($lat === null) !== ($lng === null)) {
			echo json_encode(array('status' => -1, 'message' => 'Latitude dan Longitude harus diisi berpasangan (atau kosongkan keduanya).'));
			return;
		}
		if ($lat === false || $lng === false || ($lat !== null && (abs($lat) > 90 || abs($lng) > 180))) {
			echo json_encode(array('status' => -1, 'message' => 'Format koordinat tidak valid. Latitude -90 s/d 90, Longitude -180 s/d 180 (cth: -6.3052, 107.2891).'));
			return;
		}

		$data = array(
			'id_shipper'   => $p['id_shipper'],
			'address_type' => !empty($p['address_type']) ? $p['address_type'] : 'PICKUP',
			'label'        => strtoupper(trim(isset($p['label']) ? $p['label'] : '')),
			'address'      => strtoupper(trim($p['address'])),
			'village_id'   => !empty($p['village_id'])  ? $p['village_id']  : null,
			'district_id'  => !empty($p['district_id']) ? $p['district_id'] : null,
			'regency_id'   => $p['regency_id'],
			'postal_code'  => isset($p['postal_code']) ? trim($p['postal_code']) : null,
			'pic_name'     => strtoupper(trim(isset($p['pic_name']) ? $p['pic_name'] : '')),
			'pic_phone'    => isset($p['pic_phone']) ? trim($p['pic_phone']) : null,
			'notes'        => isset($p['notes']) ? strtoupper(trim($p['notes'])) : null,
			'latitude'     => $lat,
			'longitude'    => $lng,
			'is_default'   => !empty($p['is_default']) ? 1 : 0,
			'isActive'     => 1,
		);

		if (empty($p['id_address'])) {
			$data['created_by'] = $this->session->userdata('fullname');
			$data['created_at'] = date('Y-m-d H:i:s');
			$status = $this->user_model->insert_shipper_address($data);
		} else {
			$data['updated_by'] = $this->session->userdata('fullname');
			$data['updated_at'] = date('Y-m-d H:i:s');
			$status = $this->user_model->update_shipper_address($p['id_address'], $data);
		}

		echo json_encode(array('status' => $status ? 1 : 0, 'message' => $status ? '' : 'Gagal menyimpan alamat.'));
	}

	/*
	 * Normalisasi 1 nilai koordinat:
	 * ''  -> null (kosong), angka valid -> float (7 desimal, sesuai DECIMAL(10,7)),
	 * selain itu -> false (tidak valid).
	 */
	private function normalize_coord($v) {
		$v = trim((string)$v);
		if ($v === '') return null;
		if (strpos($v, ',') !== false && strpos($v, '.') === false) {
			$v = str_replace(',', '.', $v); // "-6,3052" -> "-6.3052"
		}
		if (!is_numeric($v)) return false;
		return round((float)$v, 7);
	}

	function delete_shipper_address() {
		$id_address = $this->input->post('id_address');
		$status = $this->user_model->delete_shipper_address($id_address);
		echo json_encode(array('status' => $status ? 1 : 0));
	}

	/*
	 * Autocomplete wilayah.
	 * Urutan pencarian sesuai kesepakatan skema:
	 * (1) reg_villages -> (2) reg_districts -> (3) reg_regencies
	 */
	function autocomplete_wilayah() {
		$term = trim($this->input->get('term'));
		if (strlen($term) < 3) {
			echo json_encode(array());
			return;
		}
		echo json_encode($this->user_model->search_wilayah($term));
	}

	/*
	 * Ambil field Info Tambahan (kolom baru di mstshipper)
	 * utk mengisi tab "INFO TAMBAHAN" saat modal edit dibuka.
	 */
	function get_shipper_extra() {
		$row = $this->user_model->getUserBooking($this->input->post('id_shipper'));
		echo json_encode(array(
			'credit_limit'     => isset($row->credit_limit)     ? $row->credit_limit     : '',
			'credit_term_days' => isset($row->credit_term_days) ? $row->credit_term_days : '',
			'npwp_16digit'     => isset($row->npwp_16digit)     ? $row->npwp_16digit     : '',
			'customs_id'       => isset($row->customs_id)       ? $row->customs_id       : '',
		));
	}

	function clean_string($string) {  
    // Remove spaces  
    $string = preg_replace('/\s+/', '', $string);  
    // Remove punctuation  
    $string = preg_replace('/[^\w]/', '', $string);  
    
    return $string;  
	}
	
	function send_mail($id,$status) {
		$nama_admin	= $this->session->userdata('fullname');
		$email_admin	= $this->session->userdata('email');
		
		$shipper = $this->user_model->getUserBooking($id);
		$shipper_id = $shipper->shipper_id;
		$shipper_email = $shipper->cp_email;
		$shipper_password = $shipper->password_no_encript;
		
		$message_content =  "\nDear  ".$shipper_id."
		\r\nThank you for your patience.  We are happy to inform you that your registration with Gateway Container Line Indonesia Online Booking has been successful.
		\n\nYour Username     : ".$shipper_id."
		\nYour Password     : ".$shipper_password."
		\n\nYou are kindly requested to visit Gateway Container Line Indonesia Online Booking at http://gateway-id.com/online_booking
		\n\r\n\nThis email and any attachments are confidential and may also be privileged. If you are not the addressee, do not disclose, copy, circulate or in any other way use or rely on the information contained in this email or any attachments. If received in error, notify the sender immediately and delete this email and any attachments from your system. Emails cannot be guaranteed to be secure or error free as the message and any attachments could be intercepted, corrupted, lost, delayed, incomplete or amended. PT Gateway Container Line do not accept liability for damage caused by this email or any attachments and may monitor email traffic.\r\nYou will be notified by email of the progress of your registration process.
		\n\nKind Regards,
		\nWebsite Administration Team\nPT. Gateway Container Line
		\n\nNOTE: This email was automatically generated from http://gateway-id.com\nReplies to this message are routed to an unmonitored mailbox. If you have questions please go to http://www.gateway-id.com/contactus.
		\nYou may also call us at +622185918669.\n"; 
		$this->load->library('email');		
	   $this->email->from('admin@gateway-id.com');
		$this->email->to($shipper_email); 
		$this->email->cc($email_admin);	
		$this->email->bcc(''); 	
		$this->email->subject('Your Registration is Complete');
		$this->email->message($message_content);	
		$this->email->send();
		return $this->email->print_debugger();
	}
	
	function getDataUserAdmin(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
	 	
	 	$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
	 	$q = $this->user_model->getAllUserAdmin($_POST,false);
	 	$query = $this->user_model->getAllUserAdmin($_POST,true);
	 	
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 	
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->user_id;
		    $data->rows[$i]['cell']=array(
												$row->username,
												$row->password,
												$row->fullname,
												$row->email,
												$this->fungsi->get_level($row->level),												
												$row->region_id,
												$row->user_menu,
												$row->password_encript,
												$row->level,
												);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function open_form() {
		$user_level = $this->session->userdata('level_id'); // misalnya dari session
		$option_level = $this->user_model->get_user_levels($user_level); // ambil dari model

		$data['option_level'] = ['' => ''] + $option_level;
		$data['disabled'] = ''; // atau isi dengan kondisi tertentu
		$data['user_id'] = $this->input->post('id');
		$data['mode'] = $this->input->post('mode');
		$this->load->view('reference/v_user_management_form', $data);
	}
	
	function manage_user_admin() {
	
		$id = $this->input->post('id'); 			
 		$opr = $this->input->post('oper');		
 		$user_unit = $this->input->post('unit_user');
 		if($user_unit!='') {
 			$user_unit = '+'. implode('+', $user_unit).'+';
 		}
		if($this->input->post('password')) {
 			$user_pass = md5($this->input->post('password'));
 		}else{
			$user_pass = $this->input->post('password');
		}
 		
		$data = array(
				'fullname' => $this->input->post('fullname'),
				'username' => $this->input->post('username'),
				'password' => $user_pass,
 				'email' => $this->input->post('email'),
 				'region_id' => $this->session->userdata('region_id'),
 				'level' => $this->input->post('level'),
 				'user_unit' => $user_unit,
 				'password_encript'=>$this->input->post('password'),
 		);
 		
 		if($opr=='add')
 		{
 			$status = $this->user_model->insertDataUserAdmin($data);
			////print_r($this->dbx->last_query());
			//die();
 			echo json_encode(array('status'=>$status, 'oper'=>'add'));
		}
		else if($opr=='edit')
 		{
 			$status = $this->user_model->updateDataUserAdmin($id,$data);
 			echo json_encode(array('status'=>$status, 'oper'=>'edit'));
 		}
 		else if($opr=='del')
 		{
			 $status = $this->user_model->deleteDataUserAdmin($id);
			 echo json_encode(array('status'=>$status, 'oper'=>'delete'));
 		}
 
	}

	function get_menu(){
		$menu = $this->dbx->get('x_menu')->result();  
		foreach ($menu as $row) {
			$data = array('user_id'=>$_POST['user_id'],'menu_id'=>$row->id);
			$checked = $this->user_model->get_checked_menu($data); 
			$nama = str_replace('_',' ',$row->menu);
    		$object[] = '<div style="float: left; min-width: 250px"><input type="checkbox" name="user_menu[]" value="'.$row->id.'" '.$checked.'> '. strtoupper($nama).'</div>';
    		$obj = new stdClass();
			$obj->Menu_User = $object;
		}		
		echo json_encode($obj);
	}

	function update_menu_user(){
		$data = $this->user_model->update_menu_user($_POST);
		echo json_encode($data);
	}
	
	function process_file() {
     if (isset($_FILES['file'])) {
        $uploadDir = APPPATH . 'third_party/file/form/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
		    // Get the file extension to check if it's a PDF or an image
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $fileExtension = strtolower($fileExtension);
		//print_r($fileExtension);
		//die();
        $pdfFile = $uploadDir . basename($_FILES['file']['name']);
		
		
        // Check for any file upload errors
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["error" => "File upload error. Error code: " . $_FILES['file']['error']]);
            exit;
        }

        // Move uploaded file to server directory
        if (move_uploaded_file($_FILES['file']['tmp_name'], $pdfFile)) {
            // Create directory for images
            $imageDir = $uploadDir . "pdf_images";
            if (!is_dir($imageDir)) {
                mkdir($imageDir, 0777, true);
            }
			  // Check if the file is a PDF
            if ($fileExtension === 'pdf') {
    // Extract images from PDF using `pdfimages`
    $command = "pdfimages -png " . escapeshellarg($pdfFile) . " " . escapeshellarg($imageDir . "/page");
    $output = shell_exec($command);

    // Check if any images were generated
    $imageFiles = glob($imageDir . "/page*.png"); // Get all pages as images
    if (empty($imageFiles)) {
        echo json_encode(["error" => "pdfimages did not generate any images. Command output: $output"]);
        exit;
    }
} else {
    // If the file is an image (PNG), use it directly
    $imageFiles = [$pdfFile];
}

// Initialize an array to store OCR results for all pages
$ocrResults = [];

// Process OCR on each image
require_once 'vendor/autoload.php';
foreach ($imageFiles as $imageFile) {
    $ocr = new TesseractOCR($imageFile);
    // Specify the language, if needed (e.g., English 'eng')
    $text = $ocr->lang('ind')->run();
    $ocrResults[] = $text; // Add the result to the array
}

// Combine OCR results into a single string (if desired)
$combinedText = implode("\n\n--- Page Break ---\n\n", $ocrResults);

// Output the extracted text as JSON
$jsonData = [
    "content" => $combinedText
];
            header('Content-Type: application/json');
            echo json_encode($jsonData);

            // Cleanup
			foreach (glob("$imageDir/*") as $file) {
					unlink($file); // Delete each file in the directory
			}
			rmdir($imageDir); // Now remove the directory as it should be empty
			unlink($pdfFile); // Remove the uploaded PDF file
        } else {
            echo json_encode(["error" => "Failed to upload the PDF files."]);
        }
    } else {
        echo json_encode(["error" => "No file uploaded."]);
    }
}

function proses_npwp(){
	if (isset($_FILES['file'])) {
		$uploadDir = APPPATH . 'third_party/file/form/';
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0777, true);
			}
		$filePath = $uploadDir . basename($_FILES['file']['name']);
		
		// Move uploaded file to the server
		if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
			// Use `pdftotext` to extract text
			$outputFile = $filePath . ".txt";
			$command = "pdftotext " . escapeshellarg($filePath) . " " . escapeshellarg($outputFile);
			shell_exec($command);
			
			// Read extracted text
			$ocrText = file_get_contents($outputFile);

			// Convert the extracted text to JSON format
			$jsonData = [
				"content" => $ocrText
			];

			header('Content-Type: application/json');
			echo json_encode($jsonData);
		} else {
			echo json_encode(["error" => "File upload failed"]);
		}
	} else {
		echo json_encode(["error" => "No file uploaded"]);
	}
	
}

function proses_ccf(){
if (isset($_FILES['file'])) {
    $uploadDir = APPPATH . 'third_party/file/form/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    $filePath = $uploadDir . basename($_FILES['file']['name']);
    
    // Move uploaded file to the server
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        // Use `pdftotext` to extract text
        $outputFile = $filePath . ".txt";
        $command = "pdftotext " . escapeshellarg($filePath) . " " . escapeshellarg($outputFile);
        shell_exec($command);
        
        // Read extracted text
        $ocrText = file_get_contents($outputFile);

        // Convert the extracted text to JSON format
        $jsonData = [
            "content" => $ocrText
        ];

        header('Content-Type: application/json');
        echo json_encode($jsonData);
    } else {
        echo json_encode(["error" => "File upload failed"]);
    }
} else {
    echo json_encode(["error" => "No file uploaded"]);
}
	
}

function proses_crf(){
if (isset($_FILES['file'])) {
    $uploadDir = APPPATH . 'third_party/file/form/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    $filePath = $uploadDir . basename($_FILES['file']['name']);
    
    // Move uploaded file to the server
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        // Use `pdftotext` to extract text
        $outputFile = $filePath . ".txt";
        $command = "pdftotext " . escapeshellarg($filePath) . " " . escapeshellarg($outputFile);
        shell_exec($command);
        
        // Read extracted text
        $ocrText = file_get_contents($outputFile);

        // Convert the extracted text to JSON format
        $jsonData = [
            "content" => $ocrText
        ];

        header('Content-Type: application/json');
        echo json_encode($jsonData);
    } else {
        echo json_encode(["error" => "File upload failed"]);
    }
} else {
    echo json_encode(["error" => "No file uploaded"]);
}
	
}

public function get_access_user()
{
    $login_level = $this->session->userdata('level_id');
    $region_id   = $this->session->userdata('region_id');

    if (!in_array((string)$login_level, ['0', '1'], true)) {
        return $this->output
            ->set_status_header(403)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => false,
                'message' => 'Anda tidak memiliki akses untuk membuka pengaturan hak akses.'
            ]));
    }

    /*
     * Rule baru sementara hanya untuk Surabaya.
     * Samakan dengan Access_control.
     */
    if (strcasecmp((string)$region_id, 'Surabaya') !== 0) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => false,
                'message' => 'Pengaturan hak akses baru sementara hanya berlaku untuk region Surabaya.'
            ]));
    }

    $user_id = $this->input->post('user_id');

    if (empty($user_id)) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => false,
                'message' => 'User ID kosong.'
            ]));
    }

    /*
     * Master menu utama.
     * Key harus sama dengan yang dipakai di:
     * $this->access_control->can_menu('...')
     */
    $menuMaster = [
        [
            'key'   => 'schedule',
            'label' => 'Schedule',
        ],
        [
            'key'   => 'booking',
            'label' => 'Booking',
        ],
        [
            'key'   => 'transaction',
            'label' => 'Stuffing Plan',
        ],
		[
            'key'   => 'do_import',
            'label' => 'DO Import',
        ],
        [
            'key'   => 'finance',
            'label' => 'Finance',
        ],
        [
            'key'   => 'report',
            'label' => 'Report',
        ],
    ];

    /*
     * Master feature dalam Stuffing Plan.
     * Key harus sama dengan yang dipakai di:
     * $this->access_control->can_feature('...')
     */
    $featureMaster = [
        [
            'key'   => 'stuffing_income',
            'label' => 'Income',
        ],
        [
            'key'   => 'stuffing_global_income',
            'label' => 'Global Income',
        ],
        [
            'key'   => 'stuffing_cost',
            'label' => 'Cost',
        ],
        [
            'key'   => 'stuffing_global_cost',
            'label' => 'Global Cost',
        ],
        [
            'key'   => 'stuffing_job_sheet',
            'label' => 'Job Sheet',
        ],
        [
            'key'   => 'all_job_sheet',
            'label' => 'All Consol Job Sheet',
        ],
        [
            'key'   => 'list_si',
            'label' => 'List SI',
        ],
        [
            'key'   => 'stuffing_rekap',
            'label' => 'Print Rekap',
        ],
        [
            'key'   => 'stuffing_pre_alert_agent',
            'label' => 'Pre Alert Agent',
        ],
        [
            'key'   => 'stuffing_booking_confirmation',
            'label' => 'Booking Confirmation',
        ],
        [
            'key'   => 'stuffing_manifest',
            'label' => 'Manifest',
        ],
        [
            'key'   => 'stuffing_plan',
            'label' => 'Stuffing Plan',
        ],
        [
            'key'   => 'stuffing_add_detail',
            'label' => 'Add New Detail',
        ],
        [
            'key'   => 'stuffing_delete_detail',
            'label' => 'Delete Detail',
        ],
        [
            'key'   => 'stuffing_upload_tracking',
            'label' => 'Upload Tracking',
        ],
    ];

    /*
     * Master job code.
     *
     * Di DB access tetap simpan job_code angka:
     * 100, 101, 102, dst.
     *
     * Tapi label user-friendly menampilkan type_of_trans.
     */
    $jobCodeMaster = [
        [
            'key'           => '100',
            'label'         => 'Job 100 - LCL',
            'type_of_trans' => 'LCL',
        ],
        [
            'key'           => '101',
            'label'         => 'Job 101 - FCL',
            'type_of_trans' => 'FCL',
        ],
        [
            'key'           => '102',
            'label'         => 'Job 102 - COLOAD',
            'type_of_trans' => 'COLOAD',
        ],
        [
            'key'           => '200',
            'label'         => 'Job 200 - AIR FREIGHT',
            'type_of_trans' => 'AIR FREIGHT',
        ],
        [
            'key'           => '300',
            'label'         => 'Job 300 - SF IMPORT LCL',
            'type_of_trans' => 'SF IMPORT LCL',
        ],
        [
            'key'           => '301',
            'label'         => 'Job 301 - SF IMPORT FCL',
            'type_of_trans' => 'SF IMPORT FCL',
        ],
        [
            'key'           => '302',
            'label'         => 'Job 302 - COLOAD IMPORT',
            'type_of_trans' => 'COLOAD IMPORT',
        ],
        [
            'key'           => '400',
            'label'         => 'Job 400 - CUSTOM IMPORT',
            'type_of_trans' => 'CUSTOM IMPORT',
        ],
        [
            'key'           => '401',
            'label'         => 'Job 401 - CUSTOM',
            'type_of_trans' => 'CUSTOM',
        ],
        [
            'key'           => '402',
            'label'         => 'Job 402 - PROFIT SHARE PPJK',
            'type_of_trans' => 'PROFIT SHARE PPJK',
        ],
        [
            'key'           => '500',
            'label'         => 'Job 500 - MESCELLENEOUS',
            'type_of_trans' => 'MESCELLENEOUS',
        ],
        [
            'key'           => '600',
            'label'         => 'Job 600 - AIR FREIGHT IMPORT',
            'type_of_trans' => 'AIR FREIGHT IMPORT',
        ],
        [
            'key'           => '700',
            'label'         => 'Job 700 - TRUCKING ONLY',
            'type_of_trans' => 'TRUCKING ONLY',
        ],
        [
            'key'           => '701',
            'label'         => 'Job 701 - TRUCKING VENDOR',
            'type_of_trans' => 'TRUCKING VENDOR',
        ],
        [
            'key'           => '702',
            'label'         => 'Job 702 - TRUCKING CUSTOMER',
            'type_of_trans' => 'TRUCKING CUSTOMER',
        ],
        [
            'key'           => '703',
            'label'         => 'Job 703 - TRUCKING EXTERNAL',
            'type_of_trans' => 'TRUCKING EXTERNAL',
        ],
        [
            'key'           => '800',
            'label'         => 'Job 800 - WAREHOUSE',
            'type_of_trans' => 'WAREHOUSE',
        ],
        [
            'key'           => '900',
            'label'         => 'Job 900 - DOMESTICS',
            'type_of_trans' => 'DOMESTICS',
        ],
        [
            'key'           => '901',
            'label'         => 'Job 901 - FCL DOMESTICS',
            'type_of_trans' => 'FCL DOMESTICS',
        ],
        [
            'key'           => '902',
            'label'         => 'Job 902 - OTHER DOMESTICS',
            'type_of_trans' => 'OTHER DOMESTICS',
        ],
    ];

    /*
     * Ambil saved menu access.
     */
    $menuRows = $this->db
        ->where('user_id', $user_id)
        ->get('mst_user_menu_access')
        ->result_array();

    $menuMap = [];

    foreach ($menuRows as $row) {
        $menuMap[$row['menu_key']] = $row;
    }

    foreach ($menuMaster as &$menu) {
        $saved = isset($menuMap[$menu['key']]) ? $menuMap[$menu['key']] : [];

        $menu['can_view']   = (int)($saved['can_view'] ?? 0);
        $menu['can_create'] = (int)($saved['can_create'] ?? 0);
        $menu['can_edit']   = (int)($saved['can_edit'] ?? 0);
        $menu['can_delete'] = (int)($saved['can_delete'] ?? 0);
    }
    unset($menu);

    /*
     * Ambil saved feature access.
     */
    $featureRows = $this->db
        ->where('user_id', $user_id)
        ->get('mst_user_feature_access')
        ->result_array();

    $featureMap = [];

    foreach ($featureRows as $row) {
        $featureMap[$row['feature_key']] = $row;
    }

    foreach ($featureMaster as &$feature) {
        $saved = isset($featureMap[$feature['key']]) ? $featureMap[$feature['key']] : [];

        $feature['can_view']   = (int)($saved['can_view'] ?? 0);
        $feature['can_create'] = (int)($saved['can_create'] ?? 0);
        $feature['can_edit']   = (int)($saved['can_edit'] ?? 0);
        $feature['can_delete'] = (int)($saved['can_delete'] ?? 0);
    }
    unset($feature);

    /*
     * Ambil saved job access.
     *
     * Kalau user tidak punya row di mst_user_job_access,
     * semua checkbox akan unchecked.
     *
     * Arti unchecked semua:
     * default all job boleh.
     */
    $jobRows = $this->db
        ->select('job_code')
        ->where('user_id', $user_id)
        ->get('mst_user_job_access')
        ->result_array();

    $jobMap = [];

    foreach ($jobRows as $row) {
        $jobCode = trim((string)$row['job_code']);

        if ($jobCode !== '') {
            $jobMap[$jobCode] = true;
        }
    }

    foreach ($jobCodeMaster as &$job) {
        $job['checked'] = isset($jobMap[$job['key']]) ? 1 : 0;
    }
    unset($job);

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'status'    => true,
            'user_id'   => $user_id,
            'menus'     => $menuMaster,
            'features'  => $featureMaster,
            'job_codes' => $jobCodeMaster,
            'notes'     => [
                'job_code_empty_meaning' => 'Jika tidak ada job code yang dicentang, user boleh akses semua type_of_trans.',
                'prepared_rule'          => 'Jika ada job code yang dicentang, user tetap boleh akses job yang prepared oleh dirinya sendiri.'
            ],
        ]));
}

public function update_access_user()
{
    $user_id        = $this->input->post('user_id');
$menu_access    = $this->input->post('menu_access');
$feature_access = $this->input->post('feature_access');
$job_codes      = $this->input->post('job_codes');

if (!is_array($menu_access)) {
    $menu_access = [];
}

if (!is_array($feature_access)) {
    $feature_access = [];
}

if (!is_array($job_codes)) {
    $job_codes = [];
}

$this->db->trans_begin();

$this->db->where('user_id', $user_id)->delete('mst_user_menu_access');
$this->db->where('user_id', $user_id)->delete('mst_user_feature_access');
$this->db->where('user_id', $user_id)->delete('mst_user_job_access');

// insert menu
foreach ($menu_access as $row) {
    if (empty($row['menu_key'])) {
        continue;
    }

    $this->db->insert('mst_user_menu_access', [
        'user_id'    => $user_id,
        'menu_key'   => $row['menu_key'],
        'can_view'   => !empty($row['can_view']) ? 1 : 0,
        'can_create' => !empty($row['can_create']) ? 1 : 0,
        'can_edit'   => !empty($row['can_edit']) ? 1 : 0,
        'can_delete' => !empty($row['can_delete']) ? 1 : 0,
    ]);
}

// insert feature
foreach ($feature_access as $row) {
    if (empty($row['feature_key'])) {
        continue;
    }

    $this->db->insert('mst_user_feature_access', [
        'user_id'     => $user_id,
        'feature_key' => $row['feature_key'],
        'can_view'    => !empty($row['can_view']) ? 1 : 0,
        'can_create'  => !empty($row['can_create']) ? 1 : 0,
        'can_edit'    => !empty($row['can_edit']) ? 1 : 0,
        'can_delete'  => !empty($row['can_delete']) ? 1 : 0,
    ]);
}

// insert job code access
foreach ($job_codes as $code) {
    $code = trim((string)$code);

    if ($code === '') {
        continue;
    }

    $this->db->insert('mst_user_job_access', [
        'user_id'    => $user_id,
        'job_code'   => $code,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $this->session->userdata('fullname'),
    ]);
}

if ($this->db->trans_status() === false) {
    $this->db->trans_rollback();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'status'  => false,
            'message' => 'Gagal menyimpan hak akses.'
        ]));
}

$this->db->trans_commit();

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'status'  => true,
            'message' => 'Hak akses user berhasil disimpan.'
        ]));
}

}