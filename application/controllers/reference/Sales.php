<?php
class Sales extends CI_Controller {
	function __construct(){
		parent::__construct();
		$this->load->library('fungsi');
		$this->fungsi->restrict();
		$this->load->model('reference/sales_model');
		$this->fungsi->dbx = $this->sales_model->dbx;
		$this->dbx = $this->sales_model->dbx;
	}

	function index(){
		$this->fungsi->auth(14,'Sales');
		$data = array('menu_navigation' => 'navigation', 'main_view' => 'reference/v_sales', 'h1_title' => 'Management Warehose', 'title' => 'Management Warehose' );
		$this->load->view('afg_template', $data);
	}
	
function getDatarefsales(){
    $hal   = isset($_POST['page']) ? (int)$_POST['page'] : 1; 
    $batas = isset($_POST['rows']) ? (int)$_POST['rows'] : 10;
    $sidx  = isset($_POST['sidx']) ? $_POST['sidx'] : 'id';
 
    $start = $batas * $hal - $batas;
    $start = ($start < 0) ? 0 : $start;            
    $_POST['start'] = $start;
    
    // ambil total & data
    $q      = $this->sales_model->getDatarefsales($_POST,false);
    $query  = $this->sales_model->getDatarefsales($_POST,true);
 
    $hitung = count($q);
    $total_hal = ($hitung > 0) ? ceil($hitung/$batas) : 0;
    if ($hal > $total_hal) $hal = $total_hal;
 
    $data = new stdClass();     
    $data->page    = $hal;
    $data->total   = $total_hal;
    $data->records = $hitung;
    $i=0;
    foreach($query as $row) {
        $data->rows[$i]['id'] = $row->id; // primary key
        $data->rows[$i]['cell'] = array(
            $row->id,          // hidden
            $row->user_id,     // hidden
            $row->fullname,
            $row->management,
            $row->percentage,
            $row->type,
            $row->status,
            $row->is_active,
            $row->remark,      // hidden
            $row->region_id    // hidden
        );
        $i++;
    }
    echo json_encode($data);
}

public function manage_data(){
    $post = $this->input->post();

    // Ambil fullname dari tabel users berdasarkan user_id
    if (!empty($post['user_id'])) {
        $user = $this->db->select('fullname')
                         ->from('mstadmin') // sesuaikan nama tabel user
                         ->where('user_id', $post['user_id'])
                         ->get()
                         ->row();
        if ($user) {
            $post['fullname'] = $user->fullname;
        }
    }

    // Isi region_id dari session jika kosong
    if (empty($post['region_id'])) {
        $post['region_id'] = $this->session->userdata('region_id');
    }

    // Simpan lewat model
    $data = $this->sales_model->manage_data($post);
    echo json_encode($data);
}


}
