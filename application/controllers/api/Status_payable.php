<?php
use chriskacerguis\RestServer\RestController;
 
class Status_payable extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
    }
     
public function index_post(){
   $input = json_decode(file_get_contents('php://input'), TRUE);

if (!is_array($input)) {
    $this->response([
        'status' => 'error',
        'message' => 'Input tidak valid atau bukan array JSON'
    ], 400);
    return;
}
	
    $transformed = [];

    foreach ($input as $entry) {
        $items = explode(',', $entry['item_service']);
        foreach ($items as $item) {
            if (strpos($item, ':') !== false) {
                list($code, $name) = explode(':', $item, 2);
                $transformed[] = [
                    'reference_number' => $entry['reference_number'],
                    'code_service'     => trim($code),
                    'item_service'     => trim($name),
                    'status'           => $entry['status'],
                    'tanggal_bayar'    => $entry['tanggal_bayar'],
                    'journal_no'       => $entry['journal_no']
                ];
            } else {
                $transformed[] = [
                    'reference_number' => $entry['reference_number'],
                    'code_service'     => $entry['code_service'],
                    'item_service'     => trim($item),
                    'status'           => $entry['status'],
                    'tanggal_bayar'    => $entry['tanggal_bayar'],
                    'journal_no'       => $entry['journal_no']
                ];
            }
        }
    }

    // Kirim ke model
    $response = $this->m_api->update_cost($transformed);
    $this->response($response);
}
}