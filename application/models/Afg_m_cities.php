<?php
class Afg_m_cities extends CI_Model
{	
	var $table='mstcities';
	
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }
	
	function getListCitiesDrop($searchTerm = ''){
		$searchTerm = isset($_GET['q']) ? $_GET['q'] : $searchTerm;
		$this->dbx->select(array('city_id','city_name'));
		$this->dbx->like('city_name',$searchTerm);
		$this->dbx->from($this->table);
		$this->dbx->order_by('city_name', 'asc');
		$this->dbx->limit(50); 
		return $this->dbx->get();
	}
	function getListLocation(){
		$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
		$this->dbx->select(array('city_id','city_name'));
		$this->dbx->like('city_name',$searchTerm);
		$q = $this->dbx->get('mstcities');
		return $q;
	}
	public function get_filtered_data($searchTerm = '') {
    $this->dbx->select('id, name, code');
    if (!empty($searchTerm)) {
        $this->dbx->like('name', $searchTerm); // Search term filtering
    }
	$this->dbx->where('is_active', 'TRUE');
	$this->dbx->order_by('name',"ASC");
    $this->dbx->limit(50); // Limit results to 50 per request
    return $this->dbx->get('tableName'); // Nama tabel sudah dikonfirmasi benar: 'tableName'
	}
	
	public function get_filtered_data_pricing($searchTerm = '') {
    // Sesuaikan 'country_code' dan 'port_code' dengan nama kolom yang benar di tabel Anda
	$this->dbx->select("
		id,name,code, 
		CONCAT(name, ', ', country_id, ' (', code, ')') AS text
	", FALSE);

	// Contoh lanjutannya biasanya seperti ini:
	if (!empty($searchTerm)) {
        $this->dbx->like('name', $searchTerm); // Search term filtering
    }
	
		$this->dbx->where('is_active', 'TRUE');
		$this->dbx->order_by('name',"ASC");
		$this->dbx->limit(50); // Limit results to 50 per request
		return $this->dbx->get('tableName'); // Nama tabel sudah dikonfirmasi benar: 'tableName'
	}
	
	public function get_filtered_data_air($searchTerm = '') {
    $this->dbx->select('id, name');
    if (!empty($searchTerm)) {
        $this->dbx->like('name', $searchTerm); // Search term filtering
    }
    $this->dbx->limit(50); // Limit results to 50 per request
    return $this->dbx->get('ref_airport'); // (komentar lama salah tempel, tabelnya memang ref_airport)
	}


	
	function getListCities($country_id){
		$this->dbx->select
		('
			city_id,city_name,country_id
		',FALSE);
		return $this->dbx->get($this->table);
	}
	
	function getListCitiesId($country_id){
		$this->dbx->select('city_id,city_name,country_id',FALSE);
		$this->dbx->where('country_id',$country_id);
		return $this->dbx->get($this->table);
	}
	
	function getListCitiesOrigin(){
		$this->dbx->select('city_id,city_name,country_id',FALSE);
		$this->dbx->where('origin_id','1');
		return $this->dbx->get($this->table);
	}
	
	function tampil_semua_city(){
		$q = $this->dbx->query("select * from mstcities");
		return $q;
	}
	
	function tampil_city($country_id){
		// Sebelumnya $country_id digabung mentah ke SQL (celah SQL injection).
		// Sekarang pakai query binding (?) supaya otomatis di-escape oleh CI.
		$q = $this->dbx->query("select * from mstcities where country_id=?", array($country_id));
		return $q;
	}
	
	function tampil_semua_connecting(){
		$q = $this->dbx->query("select * from trnexportconecting");
		return $q;
	}

	function tampil_connecting($sp_id){
		// Sama seperti tampil_city(): $sp_id sebelumnya rawan SQL injection.
		$q = $this->dbx->query("select * from trnexportconecting where sp_id=?", array($sp_id));
		return $q;
	}
	
	function getDataCities($start,$limit,$sidx,$sord,$where){
		$this->dbx->select('
							city_id,
							city_name,
							country_id
							');
		$this->dbx->limit($limit);
		// PERHATIAN: where(...,NULL,FALSE) mematikan auto-escape CI. Ini AMAN
		// hanya jika $where dibangun dari sisi server (bukan langsung dari
		// input user tanpa validasi). Pastikan caller fungsi ini tidak
		// mengoper string yang berasal langsung dari $_GET/$_POST.
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
    	$this->dbx->order_by($sidx,$sord);
    	$query = $this->dbx->get($this->table,$limit,$start);
		return $query;	
	}
	
	function add($city_id,$city_name,$country_id){
		$data = array
		(
			'city_id'=>strtoupper($city_id),
			'city_name'=>ucwords($city_name),
			'country_id'=>strtoupper($country_id)
    	);
    	$this->dbx->insert($this->table,$data);
	}
	
	function update($city_id_old,$city_id,$city_name,$country_id){
    	$data = array
		(
      	'city_id'=>strtoupper($city_id),
			'city_name'=>ucwords($city_name),
			'country_id'=>strtoupper($country_id)
    	);
    	$this->dbx->where('city_id',$city_id_old);
    	$this->dbx->update($this->table,$data);
	}
	
	function delete($city_id_del){
		$this->dbx->delete($this->table, array('city_id' => $city_id_del));
	}
	
	function add_city($city_id,$city_name,$country_id,$group_id){
		$data = array
		(
			'city_id'		=>strtoupper($city_id),
			'city_name'		=>ucwords($city_name),
			'country_id'	=>strtoupper($country_id),
			'group_id'		=>$group_id,
    	);
    	$this->dbx->insert($this->table,$data);
	}
	
	function edit_city($id,$city_id,$city_name,$country_id,$group_id){
		$data = array
		(
			'city_id'		=>strtoupper($city_id),
			'city_name'		=>ucwords($city_name),
			'country_id'	=>strtoupper($country_id),
			'group_id'		=>$group_id,
    	);
    	$this->dbx->where('id_city',$id);
    	$this->dbx->update($this->table,$data);
	}
	
	function del_city($id){
		$this->dbx->delete($this->table, array('id_city' => $id));
	}
	
	function get_country_id_by_city($city_id){
		$this->dbx->select('*');
		$this->dbx->from($this->table);
		$this->dbx->where('city_name',$city_id);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->country_id;
    	} else {
    		return "";
    	}
	}
	
	function get_nama_city($city_id){
		$this->dbx->select('*');
		$this->dbx->from($this->table);
		$this->dbx->where('city_id',$city_id);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_name;
    	} else {
    		return "";
    	}
	}
	
	function get_city_id($city_name){
	$y = $this->session->userdata('db');
	if($y >= '2025'){
		$this->dbx->select('*');
		$this->dbx->from('tableName');
		$this->dbx->where('name',$city_name);
		$this->dbx->where('is_active','TRUE');
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_id;
    	} else {
    		return "";
    	}
		
	}else{
		$this->dbx->select('*');
		$this->dbx->from($this->table);
		$this->dbx->where('city_name',$city_name);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_id;
    	} else {
    		return "";
    	}
		}
	}
public function search($filters) {
    $results = [];

    // Direct schedule
    $this->dbx->from('tbl_schedules_direct')->where('isDeleted', 0);
    if (!empty($filters['region_id'])) {
        $this->dbx->where('region_id', $filters['region_id']);
    }
    if (!empty($filters['city_identifier'])) {
        $this->dbx->where('city_id', $filters['city_identifier']);
    }
    if (!empty($filters['etd_jkt'])) {
        $this->dbx->where('etd_jkt', $filters['etd_jkt']);
    }
    $direct = $this->dbx->get()->result();

    // Tandai bahwa ini hasil dari "direct"
    foreach ($direct as &$row) {
        $row->route_type = 'DIRECT / TRANSSHIPMENT';
    }

    // Via schedule
    $this->dbx->from('tbl_schedules_via')->where('isDeleted', 0);
    if (!empty($filters['region_id'])) {
        $this->dbx->where('region_id', $filters['region_id']);
    }
    if (!empty($filters['city_identifier'])) {
        $this->dbx->where('eta_city_id', $filters['city_identifier']);
    }
    if (!empty($filters['etd_jkt'])) {
        $this->dbx->where('etd_jkt', $filters['etd_jkt']);
    }
    $via = $this->dbx->get()->result();

    // Tandai bahwa ini hasil dari "via"
    foreach ($via as &$row) {
        $row->route_type = 'VIA';
    }

    return array_merge($direct, $via);
}


}
?>