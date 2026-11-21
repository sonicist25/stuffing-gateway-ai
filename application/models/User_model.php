<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model { 
private $second_db; // Declare the property
	  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
		$this->second_db = $this->load->database('reference', TRUE);
    }
	public function get_user_levels($user_level) {
    if ($user_level == 0) {
        $query = $this->dbx->get('x_level');
    } else {
        $query = $this->dbx->get_where('x_level', ['id >' => 1]);
    }

    $options = [];
    foreach ($query->result() as $row) {
        $options[$row->id] = $row->nama;
    }

    return $options;
}

   	function getListAirport(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('name,iata_code');
		$this->dbx->like('name',$searchTerm);
		$q = $this->dbx->get('ref_airport');
		return $q;
	}

	function getListAirline(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('name,code');
		$this->dbx->like('name',$searchTerm);
		$q = $this->dbx->get('ref_airline');
		return $q;
	}
	
	function getAirline(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('IATA,Airline');
		$this->dbx->like('Airline',$searchTerm);
		$q = $this->dbx->get('airlines');
		return $q;
	}
	
	function get_data_shipper_update($d, $kode){
		// cari shipper berdasarkan region dan nama
		$q = $this->dbx->get_where(
			'mstshipper',
			array(
				'region_id'    => $this->session->userdata('region_id'),
				'shipper_name' => $d
			)
		);

		if($q->num_rows() == 1) {
			$id_shipper = $q->row()->id_shipper;

			// update trnstuffingplandetail
			$this->dbx->where('kode_ref', $kode);
			$this->dbx->update('trnstuffingplandetail', array('shipper' => $id_shipper));

			// cek isActive, kalau masih 'no' → update ke 'yes'
			if($q->row()->isActive == 'no'){
				$this->dbx->where('id_shipper', $id_shipper);
				$this->dbx->update('mstshipper', array('isActive' => 'yes'));
			}

			return array('shipper' => $id_shipper);
		} else {
			return array('shipper' => '');
		}
	}
	
	private function hash_password($password){
	return password_hash($password, PASSWORD_BCRYPT);
	}
	function get_all_shipper() {
		return $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
		
	}
   
   function update_menu_user($data) {
   	$menu = $data['user_menu'];
		if($menu!='') {
			$menu = implode('+',$menu);
		}
		$this->dbx->where('user_id',$data['user_id']);
		$this->dbx->update('mstadmin',array('user_menu'=>'+'.$menu.'+'));
		if($this->dbx->affected_rows() > 0){
			return(array('status'=>'1', 'message'=>'Data menu akses user updated'));
		} else {
			return(array('status'=>'0', 'message'=>'Failed updated menu user'));
		}
   }
   
   function get_checked_menu($data){
  		$this->dbx->select('user_id,user_menu');
   	$this->dbx->from('mstadmin');
   	$this->dbx->where('user_id',$data['user_id']);
   	$this->dbx->like('user_menu','+'.$data['menu_id'].'+');
   	$query = $this->dbx->get();
   	if($query->num_rows()==1) {
   			return 'checked="checked"';
   	} else {
   			return '';
   	}
   }
   
   function getUserById($shipper_id){
		$query = $this->dbx->get_where('mstshipper',array('shipper_id' => $shipper_id));
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$data = new stdClass();	 
			$data->shipper_name = '';
			$data->shipper_address = '';
			return $data;
		}
	}
	
	function get_consignee_id($consignee_id)	{
		$query = $this->dbx->get_where('refconsigneenotify',array('consignee_notify_code' => $consignee_id));
		return $query->row();
	}
	
	function get_data_sales(){

		$this->dbx->join('mstadmin','mstadmin.user_id=refsales.user_id','left');
		$this->dbx->order_by("mstadmin.fullname","asc");
		$q = $this->dbx->get_where('refsales',array('mstadmin.region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function get_data_invoice($searchTerm){
		$q="";
		if (isset($searchTerm)) {
			// Query your database to filter results based on the search term
			$this->dbx->select(array('id','number','job_no','currency','kepada','kepada_kode','grand_total'));
			$this->dbx->like('number',$searchTerm);
			$q = $this->dbx->get_where('x_finance',array('region_id'=>$this->session->userdata('region_id')));
			}
		return $q;
	}
	
	function get_data_pay($searchTerm){
		$q="";
		$q1="";
		$q2="";
		$q3="";
		if (isset($searchTerm)) {
			// Query your database to filter results based on the search term
			$this->dbx->select(array('id','kode_ref','item_service','payable_to','status','price','currency','"inf_cost_tbl_satu" as table_name'));
			$this->dbx->like('kode_ref',$searchTerm);
			$q = $this->dbx->get_where('inf_cost_tbl_satu',array('region_id'=>$this->session->userdata('region_id')))->result();
			$this->dbx->select(array('id','kode_ref','item_service','payable_to','status','price','currency','"inf_cost_refund_sc" as table_name'));
			$this->dbx->like('kode_ref',$searchTerm);
			$q1 = $this->dbx->get_where('inf_cost_refund_sc',array('region_id'=>$this->session->userdata('region_id')))->result();
			$this->dbx->select(array('id','kode_ref','item_service','payable_to','status','price','currency','"inf_cost_refund_discount" as table_name'));
			$this->dbx->like('kode_ref',$searchTerm);
			$q2 = $this->dbx->get_where('inf_cost_refund_discount',array('region_id'=>$this->session->userdata('region_id')))->result();
			$this->dbx->select(array('id','job_no as kode_ref','item_service','payeble_to as payable_to','status','price','currency','"x_glbcost" as table_name'));
			$this->dbx->like('job_no',$searchTerm);
			$q3 = $this->dbx->get_where('x_glbcost',array('region_id'=>$this->session->userdata('region_id')))->result();
			}
		return array($q,$q1,$q2,$q3);
	}
	
	function get_data_shipper(){
	
		$q = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	function get_data_shipper_print(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('shipper_name,id_shipper');
		$this->dbx->like('shipper_name',$searchTerm);
		$q = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function get_data_shipper_import(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('consignee_import_name');
		$this->dbx->like('consignee_import_name',$searchTerm);
		$q = $this->dbx->get_where('refconsigneeimport',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	function get_data_consignee(){
		$searchTerm = $_GET['term'];
		$this->dbx->select('consignee_notify_name,consignee_notify_id');
		$this->dbx->like('consignee_notify_name',$searchTerm);
		$q = $this->dbx->get_where('refconsigneenotify',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
	
	function insertDataUserBooking($data) {
		$data['shipper_name']   = strtoupper(ltrim($data['shipper_name']));
		$data['shipper_address']= strtoupper($data['shipper_address']);
		$data['account']        = strtoupper(trim($data['account']));

		// insert ke mstshipper
		$q = $this->dbx->insert('mstshipper', array(
			'shipper_id'     => $data['shipper_id'],
			'shipper_npwp'   => $data['shipper_npwp'],
			'shipper_name'   => $data['shipper_name'],
			'shipper_address'=> $data['shipper_address'],
			'cp_email'       => $data['cp_email'],
			'cp_name'        => $data['cp_name'],
			'cp_phone'       => $data['cp_phone'],
			'nib'            => $data['nib'],
			'region_id'      => $data['region_id'],
			'isActive'       => $data['isActive'],
			'sales'          => $data['sales'],
			'account'        => $data['account'],
			'created_by'     => $data['created_by'],
			'created_at'     => $data['created_at'],
			'consignee'      => $data['consignee'],
			'payment'        => $data['payment'],
			'type'           => $data['type']
		));

		if ($q) {
			// === sinkron ke shipper_sales ===
			$sales_ids = explode(',', $data['sales']);
			$id_shipper = $this->dbx->insert_id(); // simpan id_shipper baru sekali saja

			foreach ($sales_ids as $sid) {
				$sid = trim($sid);
				if ($sid !== '') {
					// ambil fullname dari mstadmin
					$sales_name = $this->dbx
						->select('fullname')
						->from('mstadmin')
						->where('user_id', $sid)
						->get()
						->row('fullname');

					// insert ke shipper_sales
					$this->dbx->insert('shipper_sales', array(
						'id_shipper' => $id_shipper,
						'sales_id'   => $sid,
						'sales_name' => $sales_name, // tambahan kolom
						'created_at' => date('Y-m-d H:i:s')
					));
				}
			}

			// sinkron ke DB reference juga (seperti kode lama)
			$second_db = $this->load->database('reference', TRUE);
			$second_inserted = $second_db->insert('mstshipper', array(
				'shipper_id'     => $data['shipper_id'],
				'shipper_npwp'   => $data['shipper_npwp'],
				'shipper_name'   => $data['shipper_name'],
				'shipper_address'=> $data['shipper_address'],
				'cp_email'       => $data['cp_email'],
				'cp_name'        => $data['cp_name'],
				'cp_phone'       => $data['cp_phone'],
				'nib'            => $data['nib'],
				'region_id'      => $data['region_id'],
				'isActive'       => $data['isActive'],
				'sales'          => $data['sales'],
				'account'        => $data['account'],
				'created_by'     => $data['created_by'],
				'created_at'     => $data['created_at'],
				'consignee'      => $data['consignee'],
				'payment'        => $data['payment'],
				'type'           => $data['type']
			));

			return $second_inserted;
		}
		return false;
	}
	
	function updateDataUserBooking($data) {
		$data['shipper_name']   = strtoupper($data['shipper_name']);
		$data['shipper_address']= strtoupper($data['shipper_address']);

		// Field "Info Tambahan" (kolom baru di mstshipper).
		// Hanya ikut di-update bila memang dikirim dari form edit,
		// sehingga proses lain yang memanggil fungsi ini tidak terganggu.
		$extra = array();
		foreach (array('credit_limit', 'credit_term_days', 'npwp_16digit', 'customs_id') as $f) {
			if (isset($data[$f])) {
				$extra[$f] = ($data[$f] === '') ? null : $data[$f];
			}
		}

		$this->dbx->where('id_shipper', $data['id_shipper']);
		$update = $this->dbx->update('mstshipper', array_merge(array(
			'shipper_address'=> $data['shipper_address'],
			'cp_email'       => $data['cp_email'],
			'cp_name'        => $data['cp_name'],
			'cp_phone'       => $data['cp_phone'],
			'region_id'      => $data['region_id'],
			'shipper_npwp'   => $data['shipper_npwp'],
			'sales'          => $data['sales'],
			'updated_by'     => $data['updated_by'],
			'updated_at'     => $data['updated_at'],
			'consignee'      => $data['consignee'],
			'payment'        => $data['payment'],
			'type'           => $data['type']
		), $extra));

		if ($update) {
			// === sinkron ke shipper_sales ===
			// hapus relasi lama dulu
			$this->dbx->delete('shipper_sales', array('id_shipper' => $data['id_shipper']));

			// insert relasi baru
			$sales_ids = explode(',', $data['sales']);
			foreach ($sales_ids as $sid) {
				$sid = trim($sid);
				if ($sid !== '') {
					$sales_name = $this->dbx
						->select('fullname')
						->from('mstadmin')
						->where('user_id', $sid)
						->get()
						->row('fullname');
					$this->dbx->insert('shipper_sales', array(
						'id_shipper' => $data['id_shipper'],
						'sales_id'   => $sid,
						'sales_name' => $sales_name, // tambahan kolom
						'created_at' => date('Y-m-d H:i:s')
					));
				}
			}

			// sinkron ke DB reference juga (seperti kode lama)
				$second_db = $this->load->database('reference', TRUE);
				$second_db->where('id_shipper', $data['id_shipper']); // Gunakan PK id_shipper
				$second_inserted = $second_db->update('mstshipper', array(
				'shipper_id'     => $data['shipper_id'],
				'shipper_npwp'   => $data['shipper_npwp'],
				'npwp_name'      => $data['shipper_npwp_name'],
				'npwp_address'   => $data['npwp_address'],
				'shipper_name'   => $data['shipper_name'],
				'shipper_address'=> $data['shipper_address'],
				'cp_email'       => $data['cp_email'],
				'cp_name'        => $data['cp_name'],
				'cp_phone'       => $data['cp_phone'],
				'nib'            => $data['nib'],
				'region_id'      => $data['region_id'],
				'isActive'       => $data['isActive'],
				'sales'          => $data['sales'],
				'account'        => $data['account'],
				'updated_by'     => $data['updated_by'],
				'updated_at'     => $data['updated_at'],
				'consignee'      => $data['consignee']
			));

			return $second_inserted;
		}
		return false;
	}
	
	function deleteDataUserBooking($data)	{
		$this->dbx->delete('mstshipper', array('id_shipper'=>$data['id_shipper']));
		return $this->dbx->affected_rows();
	}
	
	function getUserBooking($id)	{
		$query = $this->dbx->get_where('mstshipper',array('id_shipper' => $id));
		return $query->row();
	}
	
	function getAktivasiUser($id)	{
		$query = $this->dbx->get_where('mstshipper',array('id_shipper' => $id));
		$data = $query->row();
		return $data->isActive;		
	}
	
	function getAllUser($data, $limit) {
		// Memilih kolom dari mstshipper dan menggabungkan sales_name dari tabel shipper_sales
		$this->dbx->select('mstshipper.*, GROUP_CONCAT(shipper_sales.sales_name SEPARATOR ", ") as sales_names, GROUP_CONCAT(shipper_sales.sales_id SEPARATOR ",") as sales_ids');
		$this->dbx->from('mstshipper');
		$this->dbx->join('shipper_sales', 'mstshipper.id_shipper = shipper_sales.id_shipper', 'left');
		$this->dbx->group_by('mstshipper.id_shipper');

		if($data['_search'] == 'true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
			$searchOper = $data['searchOper'];
			
			// ... (logika $ops tetap sama seperti kode lama Anda) ...
			$ops_map = array('eq'=>'=','ne'=>'<>','bw'=>'LIKE','cn'=>'LIKE'); // dsb
			$op = $ops_map[$searchOper];

			if(in_array($searchOper, ['cn', 'nc', 'in', 'ni'])) $searchString = '%'.$searchString.'%';
			
			// Jika mencari berdasarkan sales, arahkan ke tabel join
			if($searchField == 'sales') {
				$this->dbx->where("shipper_sales.sales_name $op", $searchString);
			} else {
				$this->dbx->where("mstshipper.$searchField $op", $searchString);
			}
		}
		
		if($limit == true) {
			$this->dbx->limit($data['rows'], $data['start']);
		}

		$this->dbx->order_by($data['sidx'], $data['sord']);
		$region = $this->session->userdata('region_id');
		$this->dbx->where('mstshipper.region_id', $region);
		
		return $this->dbx->get()->result(); 
	}
	
	function getAllUserAdmin($data,$limit){
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      	$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'ne'=>'<>',//not equal
            	'bw'=>'LIKE', //begins with
            	'lt'=>'<', //less than
            	'le'=>'<=',//less than or equal
            	'gt'=>'>', //greater than
            	'ge'=>'>=',//greater than or equal 
            	'bn'=>'NOT LIKE', //doesn't begin with
            	'in'=>'LIKE', //is in
            	'ni'=>'NOT LIKE', //is not in
            	'ew'=>'LIKE', //ends with
            	'en'=>'NOT LIKE', //doesn't end with
            	'cn'=>'LIKE', // contains
            	'nc'=>'NOT LIKE'  //doesn't contain
        	);

      	foreach ($ops as $key=>$value){
 				if($searchOper==$key) {
            	$ops = $value;
          	}
     		} 
            
       	if($searchOper == 'eq' ) $searchString = $searchString;
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
	
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		$region = $this->session->userdata('region_id');
		$level = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');
		
		if($level == 0 || $level == 1){
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','region_id'=>$region));
		} else {
			$q = $this->dbx->get_where('mstadmin', array('username !='=>'root','user_id'=>$user_id));
		}
	
		return $q->result();		
	}
	
	function getUserAdmin($username)	{
		$q = $this->dbx->get_where('mstadmin', array('username'=>$username));
		return $q;
	}
	
	function insertDataUserAdmin($data)	{
		$this->dbx->insert('mstadmin',$data);
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function updateDataUserAdmin($id,$data)	{
		$this->dbx->where('user_id', $id);
		$this->dbx->update('mstadmin',$data);
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function deleteDataUserAdmin($id)	{
		$this->dbx->where('user_id', $id);
		$this->dbx->delete('mstadmin');
		if($this->dbx->affected_rows() > 0 ) {
			return "1";
			} else {
			return "0";	
		}
	}
	
	function getListRegion()	{
		$query = $this->dbx->get('refregion');
		return $query->result();
	}
	
	function get_shipper_by_id($id)	{
		$query = $this->dbx->get_where('mstshipper',array('shipper_id'=>$id,'region_id'=>$this->session->userdata("region_id")));
		return $query->row();
	}
	
	function get_shipper_by_id_shipper($id,$id_print)	
	{
		if($id!='') 
		{
			$query = $this->dbx->get_where('mstshipper',array('id_shipper'=>$id));	
			if($query->num_rows()>0) 
			{
				return $query->row();
			}
			else 
			{
				$data = new stdClass();
				$data->shipper_name = '';
				return $data;
			}
		}
		else 
		{
			$query = $this->dbx->get_where('mstshipper',array('shipper_id'=>$id_print));	
			if($query->num_rows()>0) 
			{
				return $query->row();
			}
			else 
			{
				$data = new stdClass();
				$data->shipper_name = '';
				return $data;
			}
		}
	}
	
	function get_user_by_id($id)	{
		$q  = $this->dbx->get_where('mstadmin',array('user_id'=>$id));
		$q2 = $q->row_array();
		return $q2['fullname'];
	}
	
	function check_username($user) {
		return $this->dbx->get_where('mstadmin',array('username'=>$user))->num_rows();
	}
	
	function dropdown_prepared() {
		$this->dbx->select("user_id,fullname");
		$this->dbx->order_by("fullname","asc");
		return $this->dbx->get_where('mstadmin',array("region_id"=>$this->session->userdata("region_id")));
	}
	function dropdown_account() {
		$searchTerm = $_GET['term'];
		$this->dbx->order_by("account_name","asc");
		$this->dbx->like('account_name',$searchTerm);
		$q=$this->dbx->get('ref_account')->result_array();
		return $q;
	}
	function npwp() {
		$this->dbx->where('id',$_POST['id']);
		$q=$this->dbx->get('ref_account');
		if($q->num_rows() > 0) {
			$row = $q->row();
			$blok = empty($row->blok) ? "" : ' Blok '.$row->blok;
			$nomor = empty($row->nomor) ? "" : ' NO.'.$row->nomor;
			$rt = empty($row->rt) ? "" : ' RT.'.sprintf("%'03d", $row->rt);
			$rw = empty($row->rw) ? "" : ' RW.'.sprintf("%'03d", $row->rw);
			$kecamatan = empty($row->kecamatan) ? "" : ' '.$row->kecamatan;
			$kelurahan = empty($row->kelurahan) ? "" : ' '.$row->kelurahan;
			$kabupaten = empty($row->kabupaten) ? "" : ' '.$row->kabupaten;
			$propinsi = empty($row->propinsi) ? "" : ' '.$row->propinsi;
			$kodepos = empty($row->kodepos) ? "" : ' '.$row->kodepos;
    		$npwp = $row->tax_id;
    		$alamat = $row->account_address.$blok.$nomor.$rt.$rw.$kelurahan.$kecamatan.$kabupaten.$propinsi.$kodepos;
    		return array($npwp,$alamat);
    	} else {
    		return "NPWP Belum Terdaftar";
    	}
	}
	
	function get_data_port(){
			$searchTerm = $_GET['term'];
			$this->dbx->select('city_id, name as city_name');
			$this->dbx->like('name', $searchTerm);
			$this->dbx->where('is_active', 'TRUE');
			$this->dbx->order_by('name', 'ASC'); // ⬅️ Tambahkan ini
			$this->dbx->limit(15);
			$q = $this->dbx->get('tableName');
			return $q;
	}
	
	function get_data_port_jadul(){
		$searchTerm = $_GET['term'];
		
		$this->dbx->select('city_id,city_name');
		$this->dbx->like('city_name',$searchTerm);
		$this->dbx->limit(10);
		$q = $this->dbx->get('mstcities');
		
		return $q;
	}
	
	/* =====================================================================
	   ALAMAT KIRIM (mstshipper_address) + pencarian wilayah reg_*
	   CATATAN: tabel reg_villages / reg_districts / reg_regencies diasumsikan
	   berada di database yang sama dengan mstshipper ($this->dbx).
	   Jika ternyata berada di koneksi lain, ganti $this->dbx pada fungsi-fungsi
	   di bawah ini dengan koneksi yang sesuai.
	   ===================================================================== */

	function get_shipper_addresses($id_shipper) {
		$sql = "SELECT a.*, 
					   r.name AS regency_name, 
					   d.name AS district_name, 
					   v.name AS village_name
				FROM mstshipper_address a
				LEFT JOIN reg_regencies r  ON r.id = a.regency_id
				LEFT JOIN reg_districts d ON d.id = a.district_id
				LEFT JOIN reg_villages v  ON v.id = a.village_id
				WHERE a.id_shipper = ? AND a.isActive = 1
				ORDER BY FIELD(a.address_type,'MAIN','PICKUP','DROP','WAREHOUSE','BILLING','NPWP','HBL'),
						 a.is_default DESC, a.id_address";
		return $this->dbx->query($sql, array($id_shipper))->result();
	}


	function insert_shipper_address($data) {
		// jika di-set sebagai default, reset default lama utk tipe yang sama
		if (!empty($data['is_default'])) {
			$this->dbx->update('mstshipper_address', array('is_default' => 0),
				array('id_shipper' => $data['id_shipper'], 'address_type' => $data['address_type']));
		}
		return $this->dbx->insert('mstshipper_address', $data);
	}

	function update_shipper_address($id_address, $data) {
		if (!empty($data['is_default'])) {
			$this->dbx->update('mstshipper_address', array('is_default' => 0),
				array('id_shipper' => $data['id_shipper'], 'address_type' => $data['address_type']));
		}
		$this->dbx->where('id_address', $id_address);
		return $this->dbx->update('mstshipper_address', $data);
	}

	function delete_shipper_address($id_address) {
		// soft delete: riwayat job/pengiriman lama tetap punya referensi alamat
		$this->dbx->where('id_address', $id_address);
		return $this->dbx->update('mstshipper_address', array('isActive' => 0));
	}

	/*
	 * Pencarian wilayah bertingkat: villages dulu, districts, terakhir regencies.
	 * Mengembalikan village_id/district_id/regency_id + label lengkap
	 * (regency_id selalu terisi karena diturunkan dari hierarki kode).
	 */
	function search_wilayah($term) {
		$out = array();
		$pfx = $term . '%';

		// 1) KELURAHAN / DESA
		$sql = "SELECT 'VILLAGE' AS level, v.id AS village_id, d.id AS district_id, r.id AS regency_id,
				       CONCAT(v.name, ', KEC. ', d.name, ', ', r.name) AS label
				FROM reg_villages v
				JOIN reg_districts d ON d.id = v.district_id
				JOIN reg_regencies r ON r.id = d.regency_id
				WHERE v.name LIKE ?
				ORDER BY v.name LIMIT 8";
		foreach ($this->dbx->query($sql, array($pfx))->result() as $r) { $out[] = $r; }

		// 2) KECAMATAN
		$sql = "SELECT 'DISTRICT' AS level, NULL AS village_id, d.id AS district_id, r.id AS regency_id,
				       CONCAT('KEC. ', d.name, ', ', r.name) AS label
				FROM reg_districts d
				JOIN reg_regencies r ON r.id = d.regency_id
				WHERE d.name LIKE ?
				ORDER BY d.name LIMIT 6";
		foreach ($this->dbx->query($sql, array($pfx))->result() as $r) { $out[] = $r; }

		// 3) KOTA / KABUPATEN
		//    pakai %term% agar 'JAKARTA BARAT' ketemu dari 'KOTA ADM. JAKARTA BARAT'
		$sql = "SELECT 'REGENCY' AS level, NULL AS village_id, NULL AS district_id, r.id AS regency_id,
				       r.name AS label
				FROM reg_regencies r
				WHERE r.name LIKE ?
				ORDER BY r.name LIMIT 6";
		foreach ($this->dbx->query($sql, array('%' . $term . '%'))->result() as $r) { $out[] = $r; }

		return array_slice($out, 0, 20);
	}

	function get_data_airport(){
		$searchTerm = $_GET['term'];
		
		$this->dbx->select('name as city_name');
		$this->dbx->like('name',$searchTerm);
		$this->dbx->limit(10);
		$q = $this->dbx->get('ref_airport');
		
		return $q;
	}
}
?>