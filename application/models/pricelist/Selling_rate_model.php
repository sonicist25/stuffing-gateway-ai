<?php
class Selling_rate_model extends CI_Model
{
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
	
	function copy_data($data)
	{
		$q = $this->dbx->get_where("a_selling",array("id_selling"=>$data["id"]))->result_array();
		unset($q[0]["id_selling"]);
		$q[0]["ref_no"]= $this->get_ref_no($q[0]["kode_shipper"]);
		$query = $this->dbx->insert("a_selling",$q[0]);
		if($this->dbx->affected_rows()>0) 
		{
			$this->dbx->select_max("id_selling");
			$id = $this->dbx->get("a_selling")->row()->id_selling;
			$qr = $this->dbx->get_where("a_selling_rate",array("id_selling"=>$data["id"]))->result();
			foreach($qr as $row)
			{
				unset($row->id);
				$row->id_selling = $id;
				$d[]= $row;
			}
			
			foreach($d as $isi) {
				$this->dbx->insert("a_selling_rate",$isi);
			}
			
		}
		return array("status"=>$this->dbx->affected_rows(),"row"=>count($d));
	}
	
	function get_ref_no($shipper)
	{
		$this->dbx->select_max("ref_no");
		$row = $this->dbx->get_where("a_selling",array("kode_shipper"=>$shipper))->row();
		$q = ($row && !empty($row->ref_no)) ? $row->ref_no : null;
		// Kalau shipper ini belum pernah punya data selling sama sekali, mulai dari 0001
		// (sebelumnya substr(null,...) memicu deprecation warning di PHP 8.1+).
		$refno = $q ? substr($q, 6, 4) : '0000';
		$hasil = (int)$refno + 1;
		return date("y")."-".date("m")."-".str_pad($hasil, 4, "0", STR_PAD_LEFT);
	}
	
	// Ref No untuk form "Tambah Data Selling" via autocomplete shipper.
	// SENGAJA tidak pakai penomoran berurutan per-shipper seperti get_ref_no()
	// di atas -- cukup dijamin unik di tabel a_selling. Format: YYMMDD-XXXXXX
	// (XXXXXX dari uniqid(), praktis selalu unik); dicek ulang ke DB sebagai
	// jaring pengaman kalau-kalau bentrok.
	function generate_unique_ref_no()
	{
		do {
			$ref_no = date('ymd').'-'.strtoupper(substr(uniqid(), -6));
			$exists = $this->dbx->get_where('a_selling', array('ref_no' => $ref_no))->num_rows();
		} while ($exists > 0);
		return $ref_no;
	}

	function insert_data($data)
	{
		$this->dbx->insert("a_selling_rate", $data);
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function saving_data($data)
	{
		unset($data['myData']["space"]);
		$this->dbx->update("a_selling_rate",
			$data['myData'],
			array('id'=>$data['id'])
		);
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function get_value_emkl($id)
	{
		return $this->dbx->get_where('a_selling',array('id_selling'=>$id))->row()->of_emkl;
	}
	
	function print_data($data){
		//$this->dbx->select_max('a_selling.tanggal');
		$today = date('Ymd');
		//exit();
		
		$this->dbx->select('
			a_selling_rate.nama_service,
			a_selling_rate.destination,
			a_selling_rate.currency,
			a_selling_rate.price,
			a_selling_rate.kondisi,
			a_selling_rate.container,
			a_selling_rate.type,
			a_selling.tanggal,
			a_selling.valid,
		');
		$this->dbx->join('a_selling','a_selling.id_selling=a_selling_rate.id_selling','left');
		$this->dbx->where('a_selling.kepada',$data);
		$this->dbx->where('a_selling.valid>',$today);
		$this->dbx->where('a_selling_rate.of_emkl','0');
		$this->dbx->order_by('a_selling_rate.destination');
		$q = $this->dbx->get('a_selling_rate');
	/* 	print_r($this->dbx->last_query());
		die(); */
		return $q;
	}
	
	function print_data_misc($data){
		//$this->dbx->select_max('a_selling.tanggal');
		$today = date('Ymd');
		
		////print_r($refno);
		//exit();
		
		$this->dbx->select('
			a_selling_rate.nama_service,
			a_selling_rate.destination,
			a_selling_rate.currency,
			a_selling_rate.price,
			a_selling_rate.kondisi,
			a_selling_rate.container,
			a_selling_rate.type,
			a_selling.tanggal
		');
		$this->dbx->join('a_selling','a_selling.id_selling=a_selling_rate.id_selling','left');
		$this->dbx->where('a_selling.kepada',$data);
		$this->dbx->where('a_selling.valid>',$today);
		$this->dbx->where('a_selling_rate.of_emkl','1');
		$this->dbx->order_by('a_selling_rate.nama_service');
		$q = $this->dbx->get('a_selling_rate');
		/* print_r($this->dbx->last_query());
		die(); */
		return $q;
	}
	// Kolom a_selling_rate yang boleh diubah lewat cell-edit (table_selling di
	// v_selling_rate_script.php). Sebelumnya $data['kolom'] dari client dipakai
	// langsung sebagai nama kolom DB tanpa validasi -- request yang dimodifikasi
	// (mis. lewat devtools) bisa menimpa kolom sembarang seperti id_selling.
	private $editable_columns_selling_rate = array(
		'nama_service','kondisi','set_m3','destination','location','type','container',
		'currency','sales_comission','discount','price','base_rate','mchgs','remarks'
	);

	function manage_data($data){

		$oper  = isset($data['oper']) ? $data['oper'] : '';
		$kolom = isset($data['kolom']) ? $data['kolom'] : '';

		if (($oper=='add' || $oper=='edit') && !in_array($kolom, $this->editable_columns_selling_rate, true)) {
			return array('status' => 0, 'error' => 'Kolom tidak diizinkan diubah lewat endpoint ini.');
		}

		if($oper=='add'){
			$this->dbx->insert('a_selling_rate', array(
				'id_selling'=>$data['id_selling'],
				$kolom=>$data['isi'],
				'kondisi'=>'PREPAID',
				'set_m3'=>'SET',
				'type'=>'LCL',
				'currency'=>'USD',
				'of_emkl'=>$data['of_emkl'],
			));	
		}
		if($oper=='edit'){
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('a_selling_rate', array($kolom=>$data['isi'],'of_emkl'=>$data['of_emkl']));	
		}
		if($oper=='del'){
			$id=$data["id"];
			$idx = explode(",",$id);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete('a_selling_rate',array("id"=>$idx[$i]));
			}	
		}
		return array('status' => 1);
	}
	
	// NOTE: fungsi ini tidak dipanggil dari Selling_rate.php manapun yang ada
	// saat ini (kemungkinan sisa/belum dipakai). Nama tabel sebelumnya salah
	// ketik ('a_sellin' -> 'a_selling'), sudah diperbaiki untuk jaga-jaga.
	function get_data_print($shipper,$from){
		$this->dbx->select(array('shipper_id','dari','kode_shipper','destination'));
		$this->dbx->from('a_selling');
		$this->dbx->join('a_selling_rate','a_selling_rate.shipper_id=a_selling.kode_shipper');
		$this->dbx->where('a_selling_rate.shipper_id',$shipper);
		$this->dbx->where('a_selling.dari',$from);
		$this->dbx->order_by('a_selling_rate.destination','asc');
		$q = $this->dbx->get();
		return $q->result_array();
		
	}
	
	// Kolom yang boleh dipakai jqGrid untuk pencarian pada get_selling_rate().
	private $searchable_columns_shipper = array('shipper_id','shipper_name','sales_names');

	// Terapkan filter pencarian jqGrid secara aman (whitelist kolom + value ter-escape).
	// Dipakai bersama oleh get_selling_rate(), get_table_selling(), get_table_listing().
	private function apply_grid_search($data, $allowed_columns, $column_alias = array())
	{
		$ops = array(
			'eq'=>'=', 'ne'=>'<>', 'bw'=>'LIKE', 'lt'=>'<', 'le'=>'<=', 'gt'=>'>', 'ge'=>'>=',
			'bn'=>'NOT LIKE', 'in'=>'LIKE', 'ni'=>'NOT LIKE', 'ew'=>'LIKE', 'en'=>'NOT LIKE',
			'cn'=>'LIKE', 'nc'=>'NOT LIKE'
		);

		$searchField  = isset($data['searchField']) ? $data['searchField'] : '';
		$searchOper   = isset($data['searchOper']) ? $data['searchOper'] : '';
		$searchString = isset($data['searchString']) ? $data['searchString'] : '';

		// Tolak kolom & operator yang tidak dikenal (whitelist), bukan pakai apa adanya
		// dari input user — sebelumnya $searchField langsung masuk ke SQL mentah.
		if (!in_array($searchField, $allowed_columns, true) || !isset($ops[$searchOper])) {
			return;
		}

		$sqlOp = $ops[$searchOper];

		if (in_array($searchOper, array('bw','bn'), true)) {
			$searchString .= '%';
		} elseif (in_array($searchOper, array('ew','en'), true)) {
			$searchString = '%'.$searchString;
		} elseif (in_array($searchOper, array('cn','nc','in','ni'), true)) {
			$searchString = '%'.$searchString.'%';
		}

		if (isset($column_alias[$searchField])) {
			$searchField = $column_alias[$searchField];
		}

		$this->dbx->where("$searchField $sqlOp", $searchString);
	}

	// Terapkan filter "hanya shipper region/sales user ini", dan (defaultnya)
	// "hanya shipper yang punya minimal 1 data selling yang masih valid".
	// Dipakai bersama oleh get_selling_rate() & count_selling_rate() supaya
	// hasil count dan hasil data selalu konsisten.
	private function apply_shipper_visibility($data)
	{
		$level  = $this->session->userdata('level_id');
		$region = $this->session->userdata('region_id');

		if ($level == 2) {
			$sales_id_session = (int) $this->session->userdata('user_id');
			$this->dbx->where('mstshipper.id_shipper IN (SELECT id_shipper FROM shipper_sales WHERE sales_id = '.$sales_id_session.')');
		} else {
			$this->dbx->where('mstshipper.region_id', $region);
		}

		// Poin utama: hanya tampilkan shipper yang punya data a_selling yang
		// masih valid ATAU baru expired dalam <= 1 bulan terakhir (toleransi
		// supaya customer tidak langsung "hilang" dari list begitu tanggal
		// valid-nya lewat -- masih ada waktu 1 bulan buat renewal sebelum
		// benar-benar didrop dari list). Relasinya: a_selling.kepada =
		// mstshipper.shipper_name (dikonfirmasi dari data contoh: baris
		// kode_shipper='OSLINE' punya kepada='OCEAN SKY LINE', yang cocok
		// dengan shipper_name shipper itu, BUKAN shipper_id/kode-nya).
		// Dikontrol lewat flag only_valid_selling supaya masih fleksibel
		// kalau suatu saat dibutuhkan tampilan tanpa filter; default-nya
		// SELALU difilter.
		if (!isset($data['only_valid_selling']) || $data['only_valid_selling'] != '0') {
			$oneMonthAgo = date('Ymd', strtotime('-1 month'));
			$this->dbx->where(
				"mstshipper.shipper_name IN (SELECT kepada FROM a_selling WHERE valid >= '$oneMonthAgo')",
				null,
				false
			);
		}
	}

	// Sumber data select2 "Kepada" di form Add Data Selling. Mengikuti guard
	// yang sama dengan apply_shipper_visibility(): kalau level_id==2, hanya
	// shipper yang sales-nya adalah user yang sedang login (join ke
	// shipper_sales) yang boleh muncul.
	function dropdown_shipper($term)
	{
		$level  = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');
		$region = $this->session->userdata('region_id');

		$this->dbx->select('mstshipper.id_shipper, mstshipper.shipper_id, mstshipper.shipper_name');
		$this->dbx->from('mstshipper');

		if (!empty($term)) {
			$this->dbx->group_start();
			$this->dbx->like('mstshipper.shipper_name', $term);
			$this->dbx->or_like('mstshipper.shipper_id', $term);
			$this->dbx->group_end();
		}

		if ($level == 2) {
			$user_id = (int) $user_id;
			$this->dbx->where("mstshipper.id_shipper IN (SELECT id_shipper FROM shipper_sales WHERE sales_id = $user_id)", null, false);
		} else {
			$this->dbx->where('mstshipper.region_id', $region);
		}

		$this->dbx->order_by('mstshipper.shipper_name', 'asc');
		$this->dbx->limit(30);
		$rows = $this->dbx->get()->result();

		$out = array();
		foreach ($rows as $row) {
			$out[] = array(
				'id'         => $row->id_shipper,
				// Format ini mengikuti kontrak jQuery UI Autocomplete: 'label'
				// yang tampil di daftar saran, 'value' yang otomatis diisi ke
				// kotak input kalau item dipilih.
				'label'      => $row->shipper_name.' ('.$row->shipper_id.')',
				'value'      => $row->shipper_name,
				// Kode shipper (bukan id numerik) -- dibutuhkan untuk langsung
				// membuka modal "SELLING PRICE TO" tanpa round-trip tambahan
				// (lihat show_table_selling_rate_new() di v_selling_rate.php).
				'shipper_id' => $row->shipper_id,
			);
		}
		return $out;
	}

	// Detail shipper (termasuk contact person) untuk auto-fill form "Tambah
	// Data Selling" di dalam modal customer yang SUDAH terpilih -- dicari
	// lewat kode shipper (bukan id numerik), karena itulah yang tersedia di
	// context (#shipperId) saat modal customer sudah terbuka.
	function get_shipper_by_code($shipper_id)
	{
		$this->dbx->select('id_shipper, shipper_id, shipper_name, cp_name, cp_email');
		$this->dbx->where('shipper_id', $shipper_id);
		return $this->dbx->get('mstshipper')->row();
	}

	function get_selling_rate($data, $limit) {
		$today = date('Ymd');
		$oneMonthAgoForSelect = date('Ymd', strtotime('-1 month'));

		// Memilih kolom, menggabungkan nama sales, dan tanggal valid terdekat
		// (dipakai kolom "Valid Terdekat" di v_selling_rate.php).
		// Prioritas: (1) tanggal valid TERDEKAT yang masih akan datang;
		// kalau tidak ada sama sekali, (2) tanggal valid PALING BARU yang
		// sudah lewat tapi masih dalam masa toleransi 1 bulan (supaya
		// customer yang lagi masa tenggang tetap menampilkan tanggal yang
		// masuk akal, bukan kosong).
		$this->dbx->select("mstshipper.id_shipper, mstshipper.shipper_id, mstshipper.shipper_name, "
			."GROUP_CONCAT(DISTINCT shipper_sales.sales_name SEPARATOR ', ') as sales_names, "
			."COALESCE("
			."(SELECT MIN(valid) FROM a_selling WHERE a_selling.kepada = mstshipper.shipper_name AND a_selling.valid >= '$today'),"
			."(SELECT MAX(valid) FROM a_selling WHERE a_selling.kepada = mstshipper.shipper_name AND a_selling.valid < '$today' AND a_selling.valid >= '$oneMonthAgoForSelect')"
			.") as valid_terdekat",
			false);
		$this->dbx->from('mstshipper');
		$this->dbx->join('shipper_sales', 'mstshipper.id_shipper = shipper_sales.id_shipper', 'left');
		$this->dbx->group_by('mstshipper.id_shipper');

		if (isset($data['_search']) && $data['_search'] == 'true') {
			$this->apply_grid_search($data, $this->searchable_columns_shipper, array('shipper_name' => 'mstshipper.shipper_name'));
		}

		if ($limit == true) {
			$this->dbx->limit($data['rows'], $data['start']);
		}

		$this->dbx->order_by($data['sidx'], $data['sord']);
		$this->apply_shipper_visibility($data);

		return $this->dbx->get()->result();
	}

	// Hitung total baris tanpa GROUP_CONCAT/limit, dipakai controller untuk
	// paginasi supaya tidak perlu fetch seluruh data 2x.
	function count_selling_rate($data) {
		$this->dbx->select('mstshipper.id_shipper', false);
		$this->dbx->from('mstshipper');
		$this->dbx->join('shipper_sales', 'mstshipper.id_shipper = shipper_sales.id_shipper', 'left');
		$this->dbx->group_by('mstshipper.id_shipper');

		if (isset($data['_search']) && $data['_search'] == 'true') {
			$this->apply_grid_search($data, $this->searchable_columns_shipper, array('shipper_name' => 'mstshipper.shipper_name'));
		}

		$this->apply_shipper_visibility($data);

		return count($this->dbx->get()->result());
	}
	
	// Kolom yang boleh dicari lewat jqGrid pada a_selling_rate (tab detail baris harga).
	private $searchable_columns_selling_rate = array(
		'nama_service','kondisi','set_m3','destination','location','type',
		'container','currency','sales_comission','discount','price','base_rate',
		'mchgs','remarks','rate_rgl','approval'
	);

	function get_table_selling($data,$limit){
		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_selling_rate);
		}
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('a_selling_rate',array('id_selling'=>$data['id_selling']))->result();
	}

	function count_table_selling($data){
		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_selling_rate);
		}
		return $this->dbx->get_where('a_selling_rate',array('id_selling'=>$data['id_selling']))->num_rows();
	}
	
	// Kolom yang boleh dicari lewat jqGrid pada a_selling (tab "Data Selling").
	private $searchable_columns_selling = array('ref_no','tanggal','valid','kepada','attn','of_emkl','dari');

	function get_table_listing($data,$limit){
		$user_level = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');

		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_selling);
		}

		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->where('kepada',$data['shipper_id']);
		if($user_level==2) {
			$this->dbx->where_in ('dari',$user_id);
		}

		$query = $this->dbx->get_where('a_selling',array('region_id'=>$this->session->userdata('region_id')));
		return $query->result();
	}

	function count_table_listing($data){
		$user_level = $this->session->userdata('level_id');
		$user_id = $this->session->userdata('user_id');

		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_selling);
		}

		$this->dbx->where('kepada',$data['shipper_id']);
		if($user_level==2) {
			$this->dbx->where_in ('dari',$user_id);
		}

		return $this->dbx->get_where('a_selling',array('region_id'=>$this->session->userdata('region_id')))->num_rows();
	}

	function get_selling($data){
		$this->dbx->select('ref_no,tanggal,valid,of_emkl,kode_shipper,kepada,id_selling as aktiv_id,dari,attn,peti_kemas,remarks');
		if($data['mode']=='last') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','desc');
			$query = $this->dbx->get_where('a_selling',array('kepada'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
			//print_r($this->dbx->last_query());
			//die();
		}
		if($data['mode']=='prev') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','desc');
			$this->dbx->where('id_selling <',$data['id']);
			$query = $this->dbx->get_where('a_selling',array('kepada'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($data['mode']=='next') {
			$this->dbx->limit('1');
			$this->dbx->order_by('id_selling','asc');
			$this->dbx->where('id_selling >',$data['id']);
			$query = $this->dbx->get_where('a_selling',array('kepada'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
		}
		if($data['mode']=='pilih') {
			$this->dbx->where('id_selling',$data['id']);
			$query = $this->dbx->get_where('a_selling',array('kepada'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row();
			//print_r($this->dbx->last_query());
			//die();
		}
		if($query) {
			$query->max_id = $this->get_max_id($data);
			$query->min_id = $this->get_min_id($data);
			$query->tanggal = $this->fungsi->jin_date_str($query->tanggal);
			$query->valid = $this->fungsi->jin_date_str($query->valid);
			return $query;
		} else {
			return false;
		}
	}
	
	function get_max_id($data){
		$this->dbx->select_max('id_selling');
		return $this->dbx->get_where('a_selling',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row()->id_selling;
	}
	
	function get_min_id($data){
		$this->dbx->select_min('id_selling');
		return $this->dbx->get_where('a_selling',array('kode_shipper'=>$data['shipper_id'],'region_id'=>$this->session->userdata('region_id')))->row()->id_selling;
	}
	
	function add_data($data){
		$this->dbx->insert('a_selling_rate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data($data,$id){
		$this->dbx->where('id',$id);
		$this->dbx->update('a_selling_rate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data($id){
		$this->dbx->where('id',$id);
		$this->dbx->delete('a_selling_rate');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function add_data_selling($data){
		$this->dbx->insert('a_selling',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_selling($data,$id){
		$this->dbx->where('id_selling',$id);
		$this->dbx->update('a_selling',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_selling($id){
		$this->dbx->where('id_selling',$id);
		$this->dbx->delete('a_selling');
		/*
		$idx = explode(",",$id);
		for($i=0;$i<=count($idx)-1;$i++) {
			$this->dbx->delete('a_selling',array("id"=>$idx[$i],'region_id'=>$this->session->userdata('region_id')));
		}
		*/
		if($this->dbx->affected_rows() > 0) {
			$this->dbx->where('id_selling',$id);
			$this->dbx->delete('a_selling_rate');
			return '1';
		} else {
			return '0';	
		}
	}

}


