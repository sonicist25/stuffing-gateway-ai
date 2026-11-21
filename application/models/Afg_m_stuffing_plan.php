<?php
class Afg_m_stuffing_plan extends CI_Model {
	public $dbx;
	public $dbr;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dbr = $this->load->database('replica', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE);
        }

        // Histori BL: dipakai supaya perubahan field (weight, bl_meas, agent_code,
        // consignee, notify, dst) dari modul Stuffing Plan ini ikut tercatat di
        // timeline yang sama dengan form BL, bukan cuma dari form BL saja.
        $this->load->model('m_bl_history');
    }
	public function get_id_by_name($region_name)
    {
		if (empty($region_name)) return null;

		$query = $this->dbx->get_where('refregion', array('region_name' => $region_name));
		
		// Cek apakah data ditemukan sebelum memanggil ->row()
		if ($query->num_rows() > 0) {
			return $query->row()->region_id;
		}

		return null;
	}
	public function check_gocomet_exists($kode_ref)
	{
		$this->dbx->select('mother_bl');
		$this->dbx->where('id_gocomet !=', '');
		$this->dbx->where('kode_ref', $kode_ref);
		$this->dbx->where('region_id', $this->session->userdata('region_id'));
		$query = $this->dbx->get('trnstuffingplan');

		// Mengembalikan TRUE jika ditemukan, FALSE jika tidak
		return ($query->num_rows() > 0);
	}
	
	/* penambahan */
	function get_data_pelayaran()
	{		
		   // Query to get id and code from ref_scac table
        $this->dbx->select('id, code,scac');
        $this->dbx->from('ref_scac');
        $query = $this->dbx->get();

        // Return result as an associative array
        $result = $query->result_array();

        // Convert to key-value pair array for dropdown
        $dropdown_data = [];
        foreach ($result as $row) {
            $dropdown_data[$row['scac']] = $row['code'];
        }

        return json_encode($dropdown_data);
    }
	
	function get_data_shipper(){
		$searchTerm = $_GET['term'];
		$this->dbx->like('shipper_name',$searchTerm);
		$this->dbx->order_by('shipper_name','asc');
		$q = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id')));
		return $q;
	}
		function get_agent_dropdown() {
		$this->dbx->select('agen_penagihan');
		$q = $this->dbx->get_where('trnstuffingplan',array('region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows()>=1):
			foreach($q->result() as $a => $b){
				$c[]= $b->agen_penagihan;
			}
		else :
			$c=array();
		endif;
		
		$this->dbx->select('agent_company_code');
		$q1 = $this->dbx->get_where('refagentcompany',array('region_id'=>$this->session->userdata('region_id')));
		if($q1->num_rows()>=1):
			foreach($q1->result() as $d => $e){
				$f[]= $e->agent_company_code;
			}
		else :
			$f=array();
		endif;
		
		$q2 = array_merge($c,$f);
		$q3 = array_unique($q2);
		$q4 = array_filter($q3);
		
		$i= array();
		foreach($q4 as $g => $h){
			$i[$h]= $h;
		}
		
		////print_r($i); die();
		
		return json_encode($i);
	
	}
	function get_double($sp) {
		$q = $this->dbx->get_where("count_double_seal",array("id_sp"=>$sp));
		return $q;
	} 

	function ambil_kode(){
		$sql = "SELECT kode_ref FROM trnstuffingplandetail
				WHERE region_id = 'Jakarta' order by kode_ref asc";
		return $this->dbx->query($sql);
	}

	function ambil_kode2(){
		$sql = "SELECT * FROM trnstuffingplan";
		return $this->dbx->query($sql);
	}

	function ambil_stuffing(){
		$sql = "SELECT * FROM trnstuffingplan LEFT JOIN trnstuffingplandetail
				ON trnstuffingplan.sp_id = trnstuffingplandetail.sp_id";
		return $this->dbx->query($sql);
	}
	
	function ttl_colloader($code) {
		$iusd="";
		$irp="";
		$usd = $this->dbx->get_where('inf_income_from_coloader',array('kode_ref'=>$code,'currency'=>'USD','region_id'=>$this->session->userdata("region_id")))->result();
		foreach($usd as $i => $row){
			if($row->qty < $row->mchgs){
				$data_usd[] = $row->mchgs * $row->unit_price;
			} else {
				$data_usd[]=floatval($row->qty) * floatval($row->unit_price);
			}
		}
		if(!empty($data_usd)) {
			$iusd = array_sum($data_usd);
		}
		
		$rp = $this->dbx->get_where('inf_income_from_coloader',array('kode_ref'=>$code,'currency'=>'RP.','region_id'=>$this->session->userdata("region_id")))->result();
		foreach($rp as $i => $row){
			if($row->qty < $row->mchgs){
				$data_rp[] = $row->mchgs * $row->unit_price;
			} else {
				$data_rp[] = $row->qty * $row->unit_price;
			}
		}
		if(!empty($data_rp)) {
			$irp = array_sum($data_rp);
		}
		return array($iusd,$irp);
	}


  	function ttl_inv_agent($spd,$job_no)
	{
		$data = array("","","");
		$sum = "";
		
		$this->dbx->select("price");
		$q1 = $this->dbx->get_where("inf_income_selling_price",array("sp_detail_id"=>$spd,"prep_coll"=>"COLLECT","currency"=>"USD",'region_id'=>$this->session->userdata("region_id")))->result();
		
		$this->dbx->select("price");
		$q2 = $this->dbx->get_where("inf_income_from_agent",array("sp_detail_id"=>$spd,"invoice_db"=>"DEBIT NOTE","currency"=>"USD",'region_id'=>$this->session->userdata("region_id")))->result();

		$q = array_merge($q1,$q2);
		
		foreach($q as $row)
		{
			$data[] = $row->price;
		}

		$sum  = array_sum($data);
		
		return $sum;		
	}


	function update_shipco($data)
	{
		$this->dbx->join("trnstuffingplan","trnstuffingplan.kode_ref=trnstuffingplandetail.code","left");
		$q = $this->dbx->get_where("trnstuffingplandetail",
			array(
				"trnstuffingplandetail.code"=>$data["code"],
				"trnstuffingplan.region_id"=>$this->session->userdata("region_id"),			
			)
		);
		
		foreach($q->result() as $row)
		{
			$this->dbx->update("trnstuffingplandetail",array("shipp_co"=>$data["shipp_co"]),array("sp_detail_id"=>$row->sp_detail_id));
		}
		//print_r($this->dbx->last_query()); die();
	}
	function update_pelayaran($data)
	{
		$q = $this->dbx->get_where("trnstuffingplan",
			array(
				"trnstuffingplan.kode_ref"=>$data["nama"],		
			)
		);
		
		foreach($q->result() as $row)
		{
			$this->dbx->where('kode_ref',$data["nama"]);
			$this->dbx->update('trnstuffingplan', array("scac"=>$data["scac"]));
		}
	}

	function saving_data($data)
{
    $type = $data["type"];
    unset($data["type"]);
    
    if($type == "export") 
    { 
        unset($data['myData']['no_from_shipper']);
        unset($data['myData']['manifest_no']);
        unset($data['myData']['snd_vessel']);
        unset($data['myData']['snd_vessel_etd']);
        unset($data['myData']['snd_vessel_eta']);
        unset($data['myData']['snd_vessel_etd_date']);
        unset($data['myData']['snd_vessel_eta_date']);
        unset($data['myData']['last_vessel']);
        unset($data['myData']['last_vessel_etd']);
        unset($data['myData']['last_vessel_eta']);
        unset($data['myData']['last_vessel_etd_date']);
        unset($data['myData']['last_vessel_eta_date']);
        unset($data['myData']['last_snd_vessel']);
        unset($data['myData']['last_snd_vessel_etd']);
        unset($data['myData']['last_snd_vessel_eta']);
        unset($data['myData']['last_snd_vessel_etd_date']);
        unset($data['myData']['last_snd_vessel_eta_date']);
        unset($data['myData']['feeder_vessel_eta']);
        unset($data['myData']['feeder_vessel_eta_date']);
        unset($data['myData']['as_agent']);
        unset($data['myData']['shipper_name']);
        unset($data['myData']['shipper_address']);
        unset($data['myData']['backup_date']);
        unset($data['myData']['bl_type']);
        unset($data['myData']['payable']);
        unset($data['myData']['as_arange']);
        unset($data['myData']['cy_cy']);
        unset($data['myData']['cfs_cfs']);
        unset($data['myData']['diff_status_1']);
        unset($data['myData']['diff_status_2']);
        unset($data['myData']['last_count']);
        unset($data['myData']['date_do_release']);
        unset($data['myData']['no_peb']);
        unset($data['myData']['tanggal_peb']);
        unset($data['myData']['port_of_loading']);
        unset($data['myData']['port_of_discharge']);
        unset($data['myData']['port_of_receive']);
        unset($data['myData']['port_of_delivery']);
        //unset($data['myData']['incoterms']);
        
        $trucking_co = "";
        $location = "";
        $overtime = "";
        $data['myData']['shipper'] = $this->get_shipper_code($data['myData']['shipper_print']);

        // ==============================================================
        // TAMBAHAN: Update ke x_tracking_export
        // ==============================================================
        // ==============================================================
			$kode_ref_export = isset($data['myData']['kode_ref']) ? trim($data['myData']['kode_ref']) : '';

			if ($kode_ref_export !== '' && (!isset($data['myData']['hbl']) || $data['myData']['hbl'] !== 'F')) {
				$qty  = isset($data['myData']['no_pack_qty']) ? $data['myData']['no_pack_qty'] : '';
				$unit = isset($data['myData']['no_pack_unit']) ? $data['myData']['no_pack_unit'] : '';
				
				$update_tracking_export = array(
					'id_shipper'  => isset($data['myData']['shipper']) ? $data['myData']['shipper'] : null,
					'packaging'   => trim($qty . ' ' . $unit),
					'hbl_no'      => !empty($data['myData']['hb_l']) 
									   ? $data['myData']['hb_l'] 
									   : uniqid('HBL_'), // generate random unik
					'shipper'     => isset($data['myData']['shipper_print']) ? $data['myData']['shipper_print'] : null,
					'destination' => isset($data['myData']['destination']) ? $data['myData']['destination'] : null,
					'cbm' => isset($data['myData']['bl_meas']) ? $data['myData']['bl_meas'] : null,
					'region_id'   => $this->session->userdata('region_id'),
				);

				// Update hanya jika no_job ditemukan
				$this->dbx->where('no_job', $kode_ref_export);
				$this->dbx->update('x_tracking_export', $update_tracking_export);
			}
        // ==============================================================
    }
    else 
    {
        $trucking_co = $data['myData']['trucking_co'] ?? "";
        $overtime    = $data['myData']['overtime'] ?? "";

        // HS CODE: gunakan field hs_code langsung. Fallback location untuk JS lama.
        // Jika field HS Code tidak ikut dikirim, jangan overwrite nilai lama menjadi kosong.
        $hs_code_input = null;
        if (array_key_exists('hs_code', $data['myData'])) {
            $hs_code_input = trim((string)$data['myData']['hs_code']);
        } elseif (array_key_exists('location', $data['myData'])) {
            $hs_code_input = trim((string)$data['myData']['location']);
        }

        unset($data['myData']['trucking_co']);
        unset($data['myData']['location']);
        unset($data['myData']['overtime']);
        unset($data['myData']['desc_good']);
        unset($data['myData']['shipping_marks']);
        unset($data['myData']['note']);

        $data['myData']['desc_goods']          = $data['myData']['desc_print'] ?? "";
        $data['myData']['shipper_import_code'] = $this->get_consignee_code($data['myData']['shipper_import'] ?? "");
        $data['myData']['shipper_import_add']  = $this->get_consignee_add($data['myData']['shipper_import'] ?? "");
        $data['myData']['shipper']             = $this->get_shipper_code($data['myData']['shipper_print'] ?? "");
        
        $mysqlDate = '';

        if(isset($data['myData']['agent_code'])) {
            $tanggal_bl = $data['myData']['agent_code'];

            if ($tanggal_bl != "" && $tanggal_bl != "0000-00-00") {
                $date = DateTime::createFromFormat('d-m-Y', $tanggal_bl);
                
                if ($date !== false) {
                    $mysqlDate = $date->format('Y-m-d');
                }
            }
        }
        
        $data['myData']['tanggal_bl'] = $mysqlDate;
        if ($hs_code_input !== null) {
            $data['myData']['hs_code'] = $hs_code_input;
        } else {
            unset($data['myData']['hs_code']);
        }
        
        // ===== Tambahan: jika doc tidak kosong, update x_tracking.hbl_agent =====
        $doc      = trim((string)($data['myData']['doc'] ?? ''));
        $kodeRef  = trim((string)($data['myData']['kode_ref'] ?? ''));

        if ($doc !== '' && $kodeRef !== '') {
            $this->dbx->where('no_job', $kodeRef);
            $this->dbx->update('x_tracking', ['hbl_agent' => $doc]);
        }
    }

    // Perhitungan Weight & Pack
    $weight = $data['myData']['weight'] ?? 0;
    $pack   = $data['myData']['no_pack_qty'] ?? 0;
    
    if($pack) 
    {
        $data['myData']['wg_ctn'] = number_format(floatval($weight) / floatval($pack), 0);
    }
    else 
    {
        $data['myData']['wg_ctn'] = "";
    }
    
    // ==============================================================
    // BL NOTE PROTECTION
    // ==============================================================
    // Dulu setiap saving_data() selalu membuat ulang note dari mstshipper.
    // Akibatnya BL yang sudah punya shipper text sendiri bisa berubah hanya
    // karena Stuffing Plan disimpan / master shipper berubah.
    //
    // Aturan baru:
    // - Jika trnstuffingplandetail.note SUDAH berisi nilai, pertahankan.
    // - Master shipper hanya boleh mengisi note ketika note masih kosong.
    // - Perubahan Note setelah itu harus datang dari form BL (save_detail).
    $generated_note = '';
    if (isset($data['myData']['shipper_print'])) {
        $generated_note = $this->get_note($data['myData']['shipper_print']);
    }

    $existing_note = '';
    if (!empty($data['id'])) {
        $this->dbx->select('note');
        $this->dbx->where('sp_detail_id', $data['id']);
        $this->dbx->where('region_id', $this->session->userdata('region_id'));
        $current_bl = $this->dbx->get('trnstuffingplandetail')->row();

        if ($current_bl && isset($current_bl->note)) {
            $existing_note = trim((string) $current_bl->note);
        }
    }

    if ($existing_note !== '') {
        // Jangan ikutkan note di UPDATE generik Stuffing Plan.
        unset($data['myData']['note']);
    } else {
        // Hanya initial fill ketika record memang belum punya Note.
        $data['myData']['note'] = strtoupper($generated_note);
    }

    $data['myData']['shipper'] = $this->get_shipper_code($data['myData']['shipper_print'] ?? "");
    
    // Update ke trnstuffingplandetail
    /*
     * PATCH (akar kasus sp_id "berubah sendiri" — sp_detail_id 43736):
     * myData dari frontend dulu ditulis UTUH ke tabel (mass assignment).
     * Blacklist unset di atas tidak mencakup kolom identitas & relasi,
     * sehingga form yang membawa sp_id basi / code / num / kode_ref bisa
     * menimpa relasi detail->plan tanpa sepengetahuan siapa pun
     * (kode_ref jadi ...984H tapi sp_id tetap menunjuk plan lama 17880).
     * Kolom-kolom ini HANYA boleh berubah lewat jalur moving resmi
     * (submit_moving / move_job) yang meng-update sp_id + kode_ref serempak.
     */
    unset($data['myData']['booking_code']);
    unset($data['myData']['sp_id']);
    unset($data['myData']['sp_detail_id']);
    unset($data['myData']['code']);
    unset($data['myData']['num']);
    unset($data['myData']['kode_ref']);
    unset($data['myData']['region_id']);
    unset($data['myData']['created_by']);
    unset($data['myData']['created_at']);

    // PATCH: edited_by/at digabung ke update utama (dulu update terpisah,
    // sehingga baris audit trigger membawa edited_by lama yang menyesatkan)
    $data['myData']['edited_by'] = $this->session->userdata('fullname');
    $data['myData']['edited_at'] = date('Y-m-d H:i:s');

    $this->dbx->where('region_id', $this->session->userdata('region_id')); // PATCH: kunci region
    $this->dbx->update("trnstuffingplandetail", $data['myData'], array('sp_detail_id'=>$data['id']));

    if ($this->dbx->affected_rows() > 0)
    {
        // Histori BL: catat field yang benar-benar berubah, siapa yang ubah
        // (session fullname, sama seperti edited_by di atas), kapan.
        $this->m_bl_history->ensure_created($data['id'], $data['myData']['edited_by']);
        $this->m_bl_history->log_field_changes($data['id'], $data['myData'], $data['myData']['edited_by']);

        $this->dbx->where('sp_detail_id', $data['id']);
        $query = $this->dbx->get('trnstuffingplandetail')->row()->booking_code;
        
        if(!empty($query)){
            $this->dbx->where('booking_id', $query);
            $this->dbx->update('trnstuffingplan_booking_detail', array('volume' => $data['myData']['wh_meas'] ?? 0));
        }
    }
    
    return array("status" => $this->dbx->affected_rows());
}
	
	function get_shipper_code($name)
	{
		$q = $this->dbx->get_where('mstshipper',array('region_id'=>$this->session->userdata('region_id'),"shipper_name"=>$name));	
		if($q->num_rows()>0) {
			return $q->row()->id_shipper;
		}
		else 
		{
			return "";
		}
	}
	
	function get_consignee_code($name)
	{
		$q = $this->dbx->get_where('refconsigneeimport',array('region_id'=>$this->session->userdata('region_id'),"consignee_import_name"=>$name));
		if($q->num_rows()>0) {
			return $q->row()->consignee_import_code;
		}
		else 
		{
			return "";
		}
	}
	
	function get_consignee_add($name)
	{
		$q = $this->dbx->get_where('refconsigneeimport',array('region_id'=>$this->session->userdata('region_id'),"consignee_import_name"=>$name));
		if($q->num_rows()>0) {
			return $q->row()->consignee_import_address;
		}
		else 
		{
			return "";
		}
	}
	
	function get_note($shipper)
	{
		$d = $this->dbx->get_where("mstshipper",array("shipper_name"=>$shipper,'region_id'=>$this->session->userdata('region_id')));	
		if($d->num_rows()>0) {
			return $shipper. htmlentities("\n").$d->row()->shipper_address;
		}
		return "";
	}

	function get_address($shipper)
	{
		$d = $this->dbx->get_where("mstshipper",array("shipper_name"=>$shipper,'region_id'=>$this->session->userdata('region_id')));
		if($d->num_rows()>0) {
			return $d->row()->shipper_address;
		}
		return "";
	}
	
	
	function get_agent($spd)
	{
		$q = $this->dbx->get_where("trnstuffingplandetail",array("sp_detail_id"=>$spd))->row()->agent_code;
		return $q;
	}

	function ttl_debit_note($spd)
	{
		$data = array("","","");
		$sum = "";
		$this->dbx->select("price");
		$q2 = $this->dbx->get_where("inf_income_from_agent",array("sp_detail_id"=>$spd,"invoice_db"=>"DEBIT NOTE","currency"=>"USD"))->result();
		//print_r($this->dbx->last_query());

		foreach($q2 as $row)
		{
			$data[] = $row->price;
		}

		$sum  = array_sum($data);
		
		return $sum;		
	}
	
	function perkalian($data) 
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$data[0]));
		if($q->num_rows() > 0 ) {
			$bl_meas = $q->row()->bl_meas;
			$bm = "bl_meas";
			if($bl_meas=="") {
				$bl_meas = $q->row()->no_pack_qty;
				$bm = "no_pack_qty";
			}
		}
		else 
		{
			$bl_meas="1";
			$bm = "1";
		}
		
		$this->dbx->select_sum($bm);
		$this->dbx->like('kode_ref',substr($data[1],0,15));
		$sum_meas = $this->dbx->get('trnstuffingplandetail')->row()->$bm;
		$ttl_bl_meas = $this->fungsi->pecah4($bl_meas," ");
		$sum_bl_meas = $this->fungsi->pecah4($sum_meas," ");
		
		$j = $ttl_bl_meas/$sum_bl_meas;
		return array($j);
	}
	
	function ttl_credit_note($data)
	{
		$d = array();
		$w = array();
		$m = array();
		$n = array();
		$k = array(""=>"",""=>"");
		$sum = "";
		
		$this->dbx->select("qty,unit_price");
		$q1 = $this->dbx->get_where("x_glbcost",array("job_no"=>$data[1],"currency"=>"USD",'region_id'=>$this->session->userdata("region_id")))->result();
		$j = $this->perkalian($data);
		foreach($q1 as $row1)
		{
			$d[] = floatval($row1->qty) * floatval($row1->unit_price);
		}
		$k = array_merge($d,$j);
		if(count($k)==2) 
		{
			$m = $k[0] * $k[1];
		}
		$n = array($m);
		
		$this->dbx->select("qty,unit_price");
		$q2 = $this->dbx->get_where("inf_cost_tbl_satu",array("sp_detail_id"=>$data[0],"prep_coll"=>"COLLECT","currency"=>"USD",'region_id'=>$this->session->userdata("region_id")))->result();
		foreach($q2 as $row)
		{
			$w[] = floatval($row->qty) * floatval($row->unit_price);
		}
		
		$f = array_merge($n,$w);
		return array_sum($f);
	}
	
	function save_stuffing_num($code,$type,$tanggal)
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->insert('trnstuffingplan',
			array('kode_ref'=>$code,
			'tanggal_job'=>$tanggal,
			'type_of_trans'=>$type,
			'region_id'=>$region,
			'prepared'=>$this->session->userdata('user_id'),
			'created_by'=>$this->session->userdata('user_id'),
			'created_at'=>date('Y-m-d H:i:s')),
		);
		if($this->dbx->affected_rows()>0) {
			$this->dbx->select_max("sp_id");
			$id=$this->dbx->get("trnstuffingplan")->row()->sp_id;
			return array('refno'=>$code, 'id'=>$id);
		}
		else 
		{
			return array('refno'=>"", 'id'=>"");
		}
	}
	
	function tambah_countainer_seal($data,$limit)
	{
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
         $searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains

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
		if($limit==true) 
		{
			$this->dbx->limit($data['rows'],$data['start']);
		}
		return $this->dbx->get_where('count_double_seal',array('id_sp'=>$data['id_sp']))->result();
	}
	
	function tambah_countainer($data,$limit)
	{
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
         $searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains

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
		if($limit==true) 
		{
			$this->dbx->limit($data['rows'],$data['start']);
		}
		return $this->dbx->get_where('count_double',array('id_sp'=>$data['id_sp']))->result();
	}

	function tambah_container_manage($data)
	{
		$oper = $data['oper'];
		unset($data['oper']);
		if($oper=='add') {
			//print_r ($data);
			//die();
			$data['count_size']=strtoupper($data['count_size']);
			$data['count_no']=strtoupper($data['count_no']);
			$data['seal_no']=strtoupper($data['seal_no']);
			$this->dbx->insert('count_double',$data);
		}
		if($oper=='edit') {
			$data['count_size']=strtoupper($data['count_size']);
			$data['count_no']=strtoupper($data['count_no']);
			$data['seal_no']=strtoupper($data['seal_no']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('count_double',$data);
		}
		if($oper=='edit-line') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('count_double', array($data['kolom']=>$data['isi']));
		}
		if($oper=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('count_double');
		}
		return array('status'=>$this->dbx->affected_rows());
	}
	
	function tambah_container_manage_seal($data)
	{
		$oper = $data['oper'];
		unset($data['oper']);
		if($oper=='add') {
			$data['count_num']=strtoupper($data['count_num']);
			$data['count_type']=strtoupper($data['count_type']);
			$data['id_sp']=strtoupper($data['id_sp']);
			$this->dbx->insert('count_double_seal',$data);
		}
		if($oper=='edit') {
			$data['count_num']=strtoupper($data['count_num']);
			$data['count_type']=strtoupper($data['count_type']);
			$data['id_sp']=strtoupper($data['id_sp']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('count_double_seal',$data);
		}
		if($oper=='edit-inline') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->update('count_double_seal', array($data['kolom']=>$data['isi']));
		}
		if($oper=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete('count_double_seal');
		}
	
		return array('status'=>$this->dbx->affected_rows());
	}
	
	function edit_container_manage_seal($data)
	{
		$this->dbx->where('id',$data['id']);
		$this->dbx->update('count_double_seal',array('count_num'=>$data['jumlah'],'count_type'=>$data['tipe']));
		
		return array('status'=>$this->dbx->affected_rows());
	}
function manage_table_detail($data)
{
    if (trim((string)($data['booking_code'] ?? '')) !== '') return ['status'=>0,'message'=>'Gunakan Add Booking untuk booking online.'];
    unset($data['booking_code']);
    // 1. Ekstrak data tambahan
    $etd         = isset($data['etd']) ? $data['etd'] : null;
    $eta         = isset($data['eta']) ? $data['eta'] : null;
    $port        = isset($data['port']) ? $data['port'] : null;
    $destination = isset($data['destination']) ? $data['destination'] : null;
    $vessel      = isset($data['vessel']) ? $data['vessel'] : null;
    $voyage      = isset($data['voyage']) ? $data['voyage'] : null;
    $carrier     = isset($data['carrier']) ? $data['carrier'] : null;
    $contSeal    = isset($data['contSeal']) ? $data['contSeal'] : null;
    $motherBL    = isset($data['motherBL']) ? $data['motherBL'] : null;

    // Simpan kode_ref sebelum di-unset untuk logika tracking nanti
    $kode_ref    = isset($data['kode_ref']) ? $data['kode_ref'] : '';
    $sp_id       = isset($data['sp_id']) ? $data['sp_id'] : null;

    // 2. Hapus field yang tidak ada di tabel trnstuffingplandetail
    unset($data['etd'], $data['eta'], $data['port'], $data['destination'], $data['vessel'], $data['voyage'], $data['contSeal'], $data['carrier'], $data['motherBL']);

    // Tambah metadata
    $data['region_id']  = $this->session->userdata('region_id');    
    $data['created_by'] = $this->session->userdata('fullname');    
    $data["created_at"] = date('Y-m-d H:i:s');

    // --- MULAI TRANSAKSI ---
    $this->dbx->trans_begin();

    // 3. Insert ke trnstuffingplandetail
    $this->dbx->insert('trnstuffingplandetail', $data);
    $new_id = $this->dbx->insert_id(); // Ambil ID yang baru saja digenerate

    if (!$new_id) {
        $this->dbx->trans_rollback();
        return array('status' => 0, 'message' => 'Gagal mendapatkan ID Detail');
    }

    // 4. Proses tbl_detail
    // Cari data referensi sebelumnya berdasarkan sp_id (jika ada)
    $this->dbx->order_by('id', 'asc');
    $this->dbx->limit(1);
    $query_ref = $this->dbx->get_where('tbl_detail', array('sp_id' => $sp_id));
    
    $detail_payload = array(
        'detail_id' => $new_id,
        'sp_id'     => $sp_id
    );

    if ($query_ref->num_rows() > 0) {
        $row = $query_ref->row();
        // Copy data lama ke record baru (sesuai logika kode awal Anda)
        $detail_payload['feeder_vessel']          = $row->feeder_vessel;
        $detail_payload['feeder_vessel_etd']      = $row->feeder_vessel_etd;
        $detail_payload['feeder_vessel_eta']      = $row->feeder_vessel_eta;
        $detail_payload['feeder_vessel_etd_date'] = $row->feeder_vessel_etd_date;
        $detail_payload['feeder_vessel_eta_date'] = $row->feeder_vessel_eta_date;
        $detail_payload['snd_vessel']             = $row->snd_vessel;
        $detail_payload['snd_vessel_etd']         = $row->snd_vessel_etd;
        $detail_payload['snd_vessel_eta']         = $row->snd_vessel_eta;
        $detail_payload['snd_vessel_etd_date']    = $row->snd_vessel_etd_date;
        $detail_payload['snd_vessel_eta_date']    = $row->snd_vessel_eta_date;
        $detail_payload['last_vessel']            = $row->last_vessel;
        $detail_payload['last_vessel_etd']        = $row->last_vessel_etd;
        $detail_payload['last_vessel_eta']        = $row->last_vessel_eta;
        $detail_payload['last_vessel_etd_date']   = $row->last_vessel_etd_date;
        $detail_payload['last_vessel_eta_date']   = $row->last_vessel_eta_date;
        $detail_payload['last_snd_vessel']        = $row->last_snd_vessel;
        $detail_payload['last_snd_vessel_etd']    = $row->last_snd_vessel_etd;
        $detail_payload['last_snd_vessel_eta']    = $row->last_snd_vessel_eta;
        $detail_payload['last_snd_vessel_etd_date'] = $row->last_snd_vessel_etd_date;
        $detail_payload['last_snd_vessel_eta_date'] = $row->last_snd_vessel_eta_date;
        $detail_payload['last_count']             = $row->last_count;
    }

    // Tambahkan data ETD/ETA jika kolomnya sudah ada di tbl_detail (Opsional)
    // $detail_payload['etd'] = $etd; 
    // $detail_payload['destination'] = $destination;

    $this->dbx->insert('tbl_detail', $detail_payload);

    // 5. Logika x_tracking_export
    if (strpos($kode_ref, '-1') !== false) {
        $cek_tracking = $this->dbx->get_where('x_tracking_export', array('no_job' => $kode_ref));
        
        if ($cek_tracking->num_rows() == 0) {
            $data_tracking = array(
                'no_job'             => $kode_ref,
                'mbl_no'             => $motherBL,
                'feeder_name'        => trim($vessel . ' ' . $voyage),
                'etd'                => $etd,
                'eta'                => $eta,
                'consol_destination' => $destination,
                'container_no'       => $contSeal
            );
            $this->dbx->insert('x_tracking_export', $data_tracking);
        }
    }

    // --- SELESAIKAN TRANSAKSI ---
    if ($this->dbx->trans_status() === FALSE) {
        $this->dbx->trans_rollback();
        return array('status' => 0, 'message' => 'Transaction Failed');
    } else {
        $this->dbx->trans_commit();
        return array(
			'status'   => 1,
			'new_id'   => $new_id,
			'kode_ref' => $kode_ref
		);
    }
}
	
	function show_manifest($data)
	{
		$region = $this->session->userdata('region_id');
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$data,'region_id'=>$region))->row();
		$q_detail = $this->dbx->order_by('kode_ref', 'ASC')->get_where('trnstuffingplandetail',array('code'=>$data,'region_id'=>$region))->result();
		return array($q,$q_detail);
	}
	

    /**
     * Perbandingan sumber cargo untuk satu job consol (bukan total CBM).
     * -100: Agent = Nomination, Gateway = Freehand.
     * -300: Agent = Freehand, Gateway = Nomination.
     * Scope selalu dikunci memakai parent sp_id + kode_ref + region.
     */
    public function get_cargo_composition($sp_id, $kode_ref)
    {
        $sp_id = (int) $sp_id;
        $kode_ref = trim((string) $kode_ref);
        if ($sp_id <= 0 || !preg_match('/^[^-]+-(100|300)/i', $kode_ref, $m)) {
            return null;
        }

        $region = $this->session->userdata('region_id');
        $parent = $this->dbx->select('sp_id')
            ->get_where('trnstuffingplan', array(
                'sp_id' => $sp_id,
                'kode_ref' => $kode_ref,
                'region_id' => $region,
            ), 1)->row();
        if (!$parent) {
            return null;
        }

        // Sama seperti pembatasan hak akses grid detail untuk level sales.
        if ((int) $this->session->userdata('level_id') === 2) {
            $own_row = $this->dbx->select('sp_detail_id')
                ->get_where('trnstuffingplandetail', array(
                    'sp_id' => $sp_id,
                    'code' => $kode_ref,
                    'region_id' => $region,
                    'sales' => $this->session->userdata('user_id'),
                ), 1)->row();
            if (!$own_row) {
                return null;
            }
        }

        $export = $m[1] === '100';
        $agent_sales = $export
            ? array(540, 376, 329, 309, 91)
            : array(90, 377, 383, 440, 458, 621);

        $details = $this->dbx->select('sp_detail_id, sales, bl_meas, received_date')
            ->get_where('trnstuffingplandetail', array(
                'sp_id' => $sp_id,
                'code' => $kode_ref,
                'region_id' => $region,
            ))->result();

        $agent = 0;
        $gateway = 0;
        $agent_cbm = 0.0;
        $gateway_cbm = 0.0;
        $arrived_ids = array();
        $arrived_dates = array();
        $arrived_agent = 0;
        $arrived_gateway = 0;
        foreach ($details as $detail) {
            // Semua sales selain kelompok agent (termasuk NULL) dianggap Gateway.
            $is_agent = in_array((int) $detail->sales, $agent_sales, true);
            // BL Meas disimpan sebagai VARCHAR; konversi per baris, bukan SUM dari
            // paginasi jqGrid. NULL/kosong/bukan angka dianggap 0 CBM.
            $cbm = $this->_gcl_cargo_parse_cbm($detail->bl_meas);
            if ($is_agent) {
                $agent++;
                $agent_cbm += $cbm;
            } else {
                $gateway++;
                $gateway_cbm += $cbm;
            }

            // Hanya wh_arrival yang benar-benar terisi tanggal valid dianggap arrived.
            // Nilai kosong atau 0000-00-00 dari legacy tidak dianggap tiba.
            $arrival = trim((string) $detail->received_date);

			if (
				$export &&
				$arrival !== '' &&
				$arrival !== '0000-00-00 00:00:00' &&
				preg_match(
					'/\A(\d{4})-(\d{2})-(\d{2}) (?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d\z/',
					$arrival,
					$date_parts
				) &&
				checkdate(
					(int) $date_parts[2],
					(int) $date_parts[3],
					(int) $date_parts[1]
				)
			) {
				$arrived_ids[] = (int) $detail->sp_detail_id;
				$arrived_dates[(string) $detail->sp_detail_id] = $arrival;

				if ($is_agent) {
					$arrived_agent++;
				} else {
					$arrived_gateway++;
				}
			}
        }

        $total = $agent + $gateway;
        $total_cbm = $agent_cbm + $gateway_cbm;
        $arrived = count($arrived_ids);
        return array(
            'job_no' => $kode_ref,
            'job_type' => $export ? 'export' : 'import',
            'total' => $total,
            'agent' => $agent,
            'gateway' => $gateway,
            // Kubikasi untuk komparasi kelompok, dari kolom bl_meas setiap anak job.
            'agent_cbm' => round($agent_cbm, 3),
            'gateway_cbm' => round($gateway_cbm, 3),
            'total_cbm' => round($total_cbm, 3),
            'agent_cbm_pct' => $total_cbm > 0 ? round(100 * $agent_cbm / $total_cbm, 1) : 0,
            'gateway_cbm_pct' => $total_cbm > 0 ? round(100 * $gateway_cbm / $total_cbm, 1) : 0,
            'agent_pct' => $total > 0 ? round(100 * $agent / $total, 1) : 0,
            'gateway_pct' => $total > 0 ? round(100 * $gateway / $total, 1) : 0,
            'agent_label' => $export ? 'Nomination' : 'Freehand',
            'gateway_label' => $export ? 'Freehand' : 'Nomination',
            'warehouse' => $export ? array(
                'arrived' => $arrived,
                'pending' => $total - $arrived,
                'agent_arrived' => $arrived_agent,
                'gateway_arrived' => $arrived_gateway,
                'arrived_detail_ids' => $arrived_ids,
                'arrived_detail_dates' => $arrived_dates,
            ) : null,
        );
    }

    /**
     * Normalisasi nilai BL MEAS lama (VARCHAR) menjadi CBM desimal.
     * Contoh: 10.456 / 10,456 / 1,234.567 / 1.234,567.
     */
    private function _gcl_cargo_parse_cbm($raw)
    {
        $value = preg_replace('/\s+/', '', trim((string) $raw));
        if ($value === '') return 0.0;

        $dot = strrpos($value, '.');
        $comma = strrpos($value, ',');
        if ($dot !== false && $comma !== false) {
            $value = $dot > $comma
                ? str_replace(',', '', $value)
                : str_replace(',', '.', str_replace('.', '', $value));
        } elseif ($comma !== false) {
            $value = str_replace(',', '.', $value);
        }
        if (!preg_match('/^\d+(?:\.\d+)?$/', $value)) return 0.0;
        $number = (float) $value;
        return is_finite($number) ? $number : 0.0;
    }


	function get_stuffing_plan_detail($code)
	{
		$region  = $this->session->userdata('region_id');
		$level   = $this->session->userdata("level_id");
		$user_id = $this->session->userdata('user_id');

		$this->dbx->select(array(
			'trnstuffingplan.kode_ref as kode_ref',
			'trnstuffingplan.port_loading as port_loading',
			'trnstuffingplandetail.sp_detail_id as sp_detail_id',
			'trnstuffingplandetail.num as num',
			'trnstuffingplandetail.shipper as shipper',
			'trnstuffingplandetail.wg_ctn as wg_ctn',
			'trnstuffingplandetail.note as note',
			'trnstuffingplandetail.consignee as consignee',
			'trnstuffingplandetail.consignee_add as consignee_add',
			'trnstuffingplandetail.notify as notify',
			'trnstuffingplandetail.notify_add as notify_add',
			'trnstuffingplandetail.no_pack_qty as no_pack_qty',
			'trnstuffingplandetail.no_pack_unit as no_pack_unit',
			'trnstuffingplandetail.desc_goods as desc_goods',
			'trnstuffingplandetail.weight as weight',
			'trnstuffingplandetail.ship_meas as ship_meas',
			'trnstuffingplandetail.wh_meas as wh_meas',
			'trnstuffingplandetail.chgs_weight as chgs_weight',
			'trnstuffingplandetail.cost_meas as cost_meas',
			'trnstuffingplandetail.wh_agent as wh_agent',
			'trnstuffingplandetail.bl_meas as bl_meas',
			'trnstuffingplandetail.destination as destination',
			'trnstuffingplandetail.doc as doc',
			'trnstuffingplandetail.hb_l as hb_l',
			'trnstuffingplandetail.agent_code as agent_code',
			'trnstuffingplandetail.isSpotRate as isSpotRate',
			'trnstuffingplandetail.freights_charges as freights_charges',
			'trnstuffingplandetail.freight as freight',
			'trnstuffingplandetail.c_o as c_o',
			'trnstuffingplandetail.marking_nos as marking_nos',
			'trnstuffingplandetail.shipping_mark as shipping_mark',
			'trnstuffingplandetail.isN as isN',
			'trnstuffingplandetail.isOptCost as isOptCost',
			'trnstuffingplandetail.status_selling as status_selling',
			'trnstuffingplandetail.express_release as express_release',
			'trnstuffingplandetail.shipped_on_board as shipped_on_board',
			'trnstuffingplandetail.net_weight_tidak_dicetak as net_weight_tidak_dicetak',
			'trnstuffingplandetail.cetak_jml_doc_origi as cetak_jml_doc_origi',
			'trnstuffingplandetail.sp_id as sp_id',
			'trnstuffingplandetail.isLock as isLock',
			'trnstuffingplandetail.code as code',
			'trnstuffingplandetail.lock_job as lock_job',
			'trnstuffingplandetail.shipp_co as shipp_co',
			'trnstuffingplandetail.shipper_print as shipper_print',
			'trnstuffingplandetail.desc_print as desc_print',
			'trnstuffingplandetail.no_of_pack as no_of_pack',
			'trnstuffingplandetail.sales as sales',
			'trnstuffingplandetail.incoterms as incoterms',
			'trnstuffingplandetail.shipper_import as shipper_import',
			'trnstuffingplandetail.shipper_import_code as shipper_import_code',
			'trnstuffingplandetail.shipper_import_add as shipper_import_add',
			'trnstuffingplandetail.hbl as hbl',
			'trnstuffingplandetail.netto as netto',
			'trnstuffingplandetail.region_id as region_id',
			'trnstuffingplandetail.tanggal_bl as tanggal_bl',
			'trnstuffingplandetail.hs_code as hs_code',
		));

		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where('trnstuffingplan.region_id',$region);
		$this->dbx->where('trnstuffingplan.kode_ref',$code);

		// Tambah filter khusus jika level = 2
		if ($level == 2) {
			$this->dbx->where('trnstuffingplandetail.sales', $user_id);
		}

		// Biar 'B' (len=1) muncul sebelum 'AA' (len=2)
		$this->dbx->order_by('CHAR_LENGTH(trnstuffingplandetail.num)', 'asc', false);
		$this->dbx->order_by('trnstuffingplandetail.num', 'asc');

		return $this->dbx->get('trnstuffingplandetail');
	}


	function get_cetak_stuffing($data,$limit)
	{
		$region = $this->session->userdata('region_id');
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
         $searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains
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
		if($limit==true) 
		{
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id');
		$this->dbx->where('trnstuffingplan.region_id',$region);
		$this->dbx->where('trnstuffingplandetail.code',$data['kode_ref']);
		$this->dbx->order_by('trnstuffingplandetail.sp_detail_id','asc');
		return $this->dbx->get('trnstuffingplandetail')->result();
	}

	function manage_stuffing($data) 
	{
		$oper = $data['oper'];
		unset($data['oper']);
		if($oper=='add') {
			$this->dbx->insert('trnstuffingplan',$data);	
		}
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper,'mode'=>3);
	}
	
		
    function delete_stuffing($id,$pesan,$oper)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $tx = new Booking_job_sync($this->dbx);
        $region = $this->session->userdata('region_id');
        try {
            $tx->begin();
            $tx->requireInnoDB(['trnstuffingplan','trnstuffingplandetail','trnstuffingplan_delete']);
            $plan = $tx->lockPlan($id,$region);
            if ($tx->check($this->dbx->query('SELECT sp_detail_id FROM trnstuffingplandetail WHERE sp_id = ? FOR UPDATE',[$id]),'CHECK_PLAN_EMPTY')->num_rows() > 0) throw new DomainException('Job masih memiliki detail.');
            $tx->check($this->dbx->delete('trnstuffingplan',['sp_id'=>$id,'region_id'=>$region]),'DELETE_PLAN');
            $tx->check($this->dbx->insert('trnstuffingplan_delete',['kode_ref'=>$plan['kode_ref'],'deleted_by'=>$this->session->userdata('fullname'),'pesan'=>$pesan,'region_id'=>$region]),'LOG_DELETE_PLAN');
            $tx->commit();
            return ['status'=>1,'oper'=>$oper,'mode'=>1];
        } catch (Throwable $e) {
            log_message('error','[DELETE_PLAN] '.$e->getMessage());
            return ['status'=>0,'oper'=>$oper,'mode'=>2,'message'=>$e->getMessage()];
        } finally { $tx->finish(); }
    }

	function get_stuffing_plan($data, $level)
{
    $region  = $this->session->userdata('region_id');
    $mode    = $data['mode'];
    $id      = $data['sp_id'];
    $user_id = $this->session->userdata('user_id');

    unset($data['mode']);
    unset($data['sp_id']);

    $this->dbx->select('*');

			if ($mode == 'last') {
				$this->dbx->order_by('trnstuffingplan.kode_ref', 'desc');

				if ($level == 0 || $level == 1 || $level == 3 || $level == 4) {
					return $this->dbx
						->get_where('trnstuffingplan', array('region_id' => $region, 'kode_ref !=' => ''))
						->row();
				}
				else if ($level == 2) {
					// join ke detail untuk filter sales
					$this->dbx->join('trnstuffingplandetail', 'trnstuffingplandetail.sp_id = trnstuffingplan.sp_id', 'left');
					$this->dbx->where('trnstuffingplan.region_id', $region);
					$this->dbx->where('trnstuffingplan.kode_ref !=', '');
					$this->dbx->where('trnstuffingplandetail.sales', $user_id);

					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 5)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->group_start();
					$this->dbx->like('kode_ref', '-1');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 6)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->group_start();
					$this->dbx->like('kode_ref', '-3');
					$this->dbx->or_like('kode_ref', '-5');
					$this->dbx->group_end();
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 7)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->where('prepared', 126);
					$this->dbx->group_start();
					$this->dbx->like('destination', 'KEELUNG');
					$this->dbx->or_like('destination', 'SINGAPORE');
					$this->dbx->or_like('destination', 'YANGON');
					$this->dbx->or_like('destination', 'NHAVA SHEVA');
					$this->dbx->or_like('destination', 'NHAVA SHEVA');
					$this->dbx->or_like('destination', 'CHENNAI');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 8)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-100');
					$this->dbx->group_start();
					$this->dbx->like('destination', 'HONG KONG');
					$this->dbx->or_like('destination', 'HAIPHONG');
					$this->dbx->or_like('destination', 'HAMBURG');
					$this->dbx->or_like('destination', 'ABARLI');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 9)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-100');
					$this->dbx->group_start();
					$this->dbx->like('destination', 'JEBEL ALI');
					$this->dbx->or_like('destination', 'KOBE');
					$this->dbx->or_like('destination', 'NAGOYA');
					$this->dbx->or_like('destination', 'TOKYO');
					$this->dbx->or_like('destination', 'OSAKA');
					$this->dbx->or_like('destination', 'CHITTAGONG');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 10)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-100');
					$this->dbx->group_start();
					$this->dbx->like('destination', 'BANGKOK');
					$this->dbx->or_like('destination', 'BUSAN');
					$this->dbx->or_like('destination', 'LAEM CHABANG');
					$this->dbx->or_like('destination', 'PORT KELANG');
					$this->dbx->or_like('destination', 'SHANGHAI');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 11)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->group_start();
					$this->dbx->like('kode_ref', '-3');
					$this->dbx->or_like('kode_ref', '-6');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 12)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-9');
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 13)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-7');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 14)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-101');
					$this->dbx->or_like('trnstuffingplan.kode_ref', '-6');
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 15)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-4');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));	
					return $this->dbx->get('trnstuffingplan')->row();
				}
				else if($level == 16)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->group_start();
					$this->dbx->like('kode_ref', '-2');
					$this->dbx->or_like('kode_ref', '-6');
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
					else if($level == 17)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->like('kode_ref', '-100');
					$this->dbx->group_start();
					$this->dbx->like('destination', 'HO CHI MINH CITY');
					$this->dbx->or_like('destination', 'MANILA');
					$this->dbx->or_like('destination', 'COLOMBO');
					$this->dbx->or_like('destination', 'KARACHI');
					$this->dbx->or_like('destination', 'SHANGHAI');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
					else if($level == 21)
				{
					$this->dbx->where('region_id LIKE', "%$region%");
					$this->dbx->group_start();
					$this->dbx->like('kode_ref', '-100');
					$this->dbx->or_like('kode_ref', '-500');
					$this->dbx->or_where('prepared', $this->session->userdata('user_id'));
					$this->dbx->group_end();
					
					return $this->dbx->get('trnstuffingplan')->row();
				}
		}
		elseif($mode=='create') {
			$this->dbx->order_by('sp_id','desc');
			//$this->dbx->order_by('kode_ref','desc');
			return $this->dbx->get_where('trnstuffingplan',array('kode_ref <'=>$data["kode_ref"], 'region_id'=>$region),1,false)->row();
		}
		elseif($mode=='prev') {
			//$this->dbx->order_by('sp_id','desc');
			$this->dbx->order_by('kode_ref','desc');
			return $this->dbx->get_where('trnstuffingplan',array('kode_ref <'=>$data["kode_ref"], 'region_id'=>$region),1,false)->row();
		}
		elseif($mode=='next') {
			//$this->dbx->order_by('sp_id','asc');
			$this->dbx->order_by('kode_ref','asc');
			return $this->dbx->get_where('trnstuffingplan',array('kode_ref >'=>$data["kode_ref"], 'region_id'=>$region),1,false)->row();
		}
		elseif($mode=='pilih') {
			$this->dbx->join('count_double_seal','count_double_seal.id_sp = trnstuffingplan.sp_id', 'left');  
			return $this->dbx->get_where('trnstuffingplan',array('sp_id'=>$id, 'region_id'=>$region),1,false)->row();
		}
		
	}
	
	function get_id($p)
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->$p('sp_id');
		return $this->dbx->get_where('trnstuffingplan',array('region_id'=>$region))->row()->sp_id;
	}

	
	function get_sales_by_sp_id($spd) 
	{
		if($spd=='')
		{
			return '';
		}
		else 
		{
			$q = $this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$spd))->row()->sales;
			return $q;
		}
	}
	
	
	function get_price($table,$code,$cur)
	{
		$q = $this->dbx->get_where($table,array('kode_ref'=>$code,'currency'=>$cur));
		if($q->num_rows()>0) {
			return $q->row()->price;
		} else {
			return "";
		}
	}

	function get_total($table,$code,$cur) 
	{
		$this->dbx->select_sum('price');
		$q = $this->dbx->get_where($table,array('kode_ref'=>$code,'currency'=>$cur))->row()->price;
		return $q;
	}
	
	function get_total_income($code, $globalRatio = null)
	{
		// Total selling price: satu aggregate query (sebelumnya 2 query USD/RP).
		$selling = $this->dbr
			->select("\n\t\t\t\tSUM(CASE WHEN currency = 'USD' THEN (qty * unit_price) ELSE 0 END) AS usd_total,\n\t\t\t\tSUM(CASE WHEN currency = 'RP.' THEN price ELSE 0 END) AS rp_total\n\t\t\t", false)
			->where('kode_ref', $code)
			->get('inf_income_selling_price')
			->row();

		$iusd = $selling ? (float) $selling->usd_total : 0;
		$irp  = $selling ? (float) $selling->rp_total : 0;

		if ($globalRatio === null) {
			$qty = $this->get_qty_gbl_cost($code, '');
			$globalRatio = ((float)$qty[1] != 0.0) ? ((float)$qty[0] / (float)$qty[1]) : 0;
		}

		// Global income tetap mengikuti logika lama: job_no mengandung prefix 15 karakter.
		// Satu aggregate query menggantikan query USD + RP terpisah.
		$prefix = substr((string)$code, 0, 15);
		$this->dbr->like('job_no', $prefix, 'both');
		$global = $this->dbr
			->select("\n\t\t\t\tSUM(CASE WHEN currency = 'USD' THEN price ELSE 0 END) AS usd_total,\n\t\t\t\tSUM(CASE WHEN currency = 'RP.' THEN unit_price ELSE 0 END) AS rp_total\n\t\t\t", false)
			->get('x_glbincome')
			->row();

		$giusd = ($global ? (float)$global->usd_total : 0) * (float)$globalRatio;
		$girp  = ($global ? (float)$global->rp_total : 0) * (float)$globalRatio;

		return array($iusd, $irp, $giusd, $girp);
	}

	function get_total_cost($code, $globalRatio = null)
	{
		$region = $this->session->userdata('region_id');

		// Tiga tabel cost lokal digabung menjadi satu round-trip database.
		$sql = "
			SELECT currency, SUM(amount) AS total
			FROM (
				SELECT currency, (qty * unit_price) AS amount
				FROM inf_cost_tbl_satu
				WHERE kode_ref = ? AND region_id = ?

				UNION ALL

				SELECT currency, (qty * unit_price) AS amount
				FROM inf_cost_refund_discount
				WHERE kode_ref = ? AND region_id = ?

				UNION ALL

				SELECT currency, (qty * unit_price) AS amount
				FROM inf_cost_refund_sc
				WHERE kode_ref = ? AND region_id = ?
			) c
			GROUP BY currency
		";

		$rows = $this->dbr->query($sql, array(
			$code, $region,
			$code, $region,
			$code, $region,
		))->result();

		$ttl_usd = 0;
		$ttl_rp = 0;
		foreach ($rows as $row) {
			if ($row->currency === 'USD') {
				$ttl_usd += (float)$row->total;
			} elseif ($row->currency === 'RP.') {
				$ttl_rp += (float)$row->total;
			}
		}

		if ($globalRatio === null) {
			$qty = $this->get_qty_gbl_cost($code, '');
			$globalRatio = ((float)$qty[1] != 0.0) ? ((float)$qty[0] / (float)$qty[1]) : 0;
		}

		// Global cost tetap mengikuti logika lama (LIKE prefix 15 karakter),
		// tetapi USD/RP dijumlahkan dalam satu query.
		$prefix = substr((string)$code, 0, 15);
		$this->dbr->like('job_no', $prefix, 'both');
		$global = $this->dbr
			->select("\n\t\t\t\tSUM(CASE WHEN currency = 'USD' THEN price ELSE 0 END) AS usd_total,\n\t\t\t\tSUM(CASE WHEN currency = 'RP.' THEN price ELSE 0 END) AS rp_total\n\t\t\t", false)
			->get('x_glbcost')
			->row();

		$ttl_usd += ($global ? (float)$global->usd_total : 0) * (float)$globalRatio;
		$ttl_rp  += ($global ? (float)$global->rp_total : 0) * (float)$globalRatio;

		return array($ttl_usd, $ttl_rp);
	}

	function get_bl_meas($code)
	{
		$bl_meas = 0;
		$bm = "bl_meas";
		$q = $this->dbr->get_where('trnstuffingplandetail',array('kode_ref'=>$code));
		//print_r($this->dbr->last_query());
		//die();
		if($q->num_rows() > 0 ) {
			$bl_meas = $q->row()->bl_meas;
		}
		return array($bl_meas,$bm);
	}
	
	function get_qty_gbl_cost($code,$cur)
	{
		$prefix = substr((string) $code, 0, 15);

		// Satu query saja: ambil meas job aktif + total meas satu consol/job prefix.
		// LIKE menggunakan prefix% agar index kode_ref masih dapat dipakai.
		$sql = "
			SELECT
				COALESCE(cur.bl_meas, 0) AS bl_meas,
				COALESCE((
					SELECT SUM(allrow.bl_meas)
					FROM trnstuffingplandetail allrow
					WHERE allrow.kode_ref LIKE ?
				), 0) AS sum_meas
			FROM trnstuffingplandetail cur
			WHERE cur.kode_ref = ?
			LIMIT 1
		";

		$row = $this->dbr->query($sql, array($prefix.'%', $code))->row();
		$bl_meas = $row ? $row->bl_meas : 0;
		$sum_meas = $row ? $row->sum_meas : 0;

		if ((float) $sum_meas == 0.0) {
			$ttl_bl_meas = 1;
			$sum_bl_meas = 1;
		} else {
			$ttl_bl_meas = $this->fungsi->pecah4($bl_meas, " " );
			$sum_bl_meas = $this->fungsi->pecah4($sum_meas, " " );
		}

		return array($ttl_bl_meas, $sum_bl_meas);
	}
	
	function get_total_gbl_cost($code,$cur) 
	{
		$this->dbr->select_sum('price');
		$this->dbr->like('job_no',substr($code,0,15));
		$q = $this->dbr->get_where('x_glbcost',array('currency'=>$cur))->row()->price;
		return $q;
	}
	
	function show_table_container($id)
	{
		return $this->dbx->get_where('count_double',array('id_sp'=>$id))->result();
	}
	function get_double_container($id)
	{
		return $this->dbx->get_where('count_double',array('id_sp'=>$id))->result();
	}

	function cetak_stuffing_header($code) 
	{  $reg = $this->session->userdata('region_id');
		return $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code, 'region_id' => $reg))->row();
	}
	
	function cetak_stuffing_detail($code)
	{ 
		
		$region = $this->session->userdata('region_id');
		$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.sp_detail_id as sp_detail_id',
				'trnstuffingplandetail.shipment_request_id as shipment_request_id',
				'trnstuffingplandetail.num as num',
				'trnstuffingplandetail.shipper as shipper',
				'trnstuffingplandetail.wg_ctn as wg_ctn',
				'trnstuffingplandetail.note as note',
				'trnstuffingplandetail.consignee as consignee',
				'trnstuffingplandetail.consignee_add as consignee_add',
				'trnstuffingplandetail.notify as notify',
				'trnstuffingplandetail.notify_add as notify_add',
				'trnstuffingplandetail.no_pack_qty as no_pack_qty',
				'trnstuffingplandetail.no_pack_unit as no_pack_unit',
				'trnstuffingplandetail.desc_goods as desc_goods',
				'trnstuffingplandetail.weight as weight',
				'trnstuffingplandetail.ship_meas as ship_meas',
				'trnstuffingplandetail.wh_meas as wh_meas',
				'trnstuffingplandetail.chgs_weight as chgs_weight',
				'trnstuffingplandetail.cost_meas as cost_meas',
				'trnstuffingplandetail.wh_agent as wh_agent',
				'trnstuffingplandetail.bl_meas as bl_meas',
				'trnstuffingplandetail.destination as destination',
				'trnstuffingplandetail.doc as doc',
				'trnstuffingplandetail.hb_l as hb_l',
				'trnstuffingplandetail.agent_code as agent_code',
				'trnstuffingplandetail.freights_charges as freights_charges',
				'trnstuffingplandetail.freight as freight',
				'trnstuffingplandetail.c_o as c_o',
				'trnstuffingplandetail.marking_nos as marking_nos',
				'trnstuffingplandetail.shipping_mark as shipping_mark',
				'trnstuffingplandetail.isN as isN',
				'trnstuffingplandetail.isOptCost as isOptCost',
				'trnstuffingplandetail.status_selling as status_selling',
				'trnstuffingplandetail.isSpotRate as isSpotRate',
				'trnstuffingplandetail.express_release as express_release',
				'trnstuffingplandetail.shipped_on_board as shipped_on_board',
				'trnstuffingplandetail.net_weight_tidak_dicetak as net_weight_tidak_dicetak',
				'trnstuffingplandetail.cetak_jml_doc_origi as cetak_jml_doc_origi',
				'trnstuffingplandetail.sp_id as sp_id',
				'trnstuffingplandetail.isLock as isLock',
				'trnstuffingplandetail.code as code',
				'trnstuffingplandetail.lock_job as lock_job',
				'trnstuffingplandetail.shipp_co as shipp_co',
				'trnstuffingplandetail.shipper_print as shipper_print',
				'trnstuffingplandetail.desc_print as desc_print',
				'trnstuffingplandetail.no_of_pack as no_of_pack',
				'trnstuffingplandetail.sales as sales',
				
				'trnstuffingplandetail.shipper_import as shipper_import',
				'trnstuffingplandetail.shipper_import_code as shipper_import_code',
				'trnstuffingplandetail.shipper_import_add as shipper_import_add',
				
				'trnstuffingplandetail.hbl as hbl',
				'trnstuffingplandetail.netto as netto',
				'trnstuffingplandetail.booking_code as booking_code',
				
			)
		);
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where('trnstuffingplan.region_id',$region);
		//$this->dbx->where('trnstuffingplandetail.code',$code);
		$this->dbx->order_by('CHAR_LENGTH(trnstuffingplandetail.num)', 'asc', false);
		$this->dbx->order_by('trnstuffingplandetail.num', 'asc');
		//return $this->dbx->get('trnstuffingplandetail')->result_array();
		return $this->dbx->get_where('trnstuffingplandetail',array('code'=>$code))->result_array();
	}

function get_all_stuffing($data, $limit, $level)
{
    $region  = trim((string)$this->session->userdata('region_id'));
    $user_id = trim((string)$this->session->userdata('user_id'));

    $isSurabaya = (strcasecmp($region, 'Surabaya') === 0);
    $isSuperAdmin = ((string)$level === '0');

    $jobCodeToType = array(
        '100' => 'LCL',
        '101' => 'FCL',
        '102' => 'COLOAD',
        '200' => 'AIR FREIGHT',
        '300' => 'SF IMPORT LCL',
        '301' => 'SF IMPORT FCL',
        '302' => 'COLOAD IMPORT',
        '400' => 'CUSTOM IMPORT',
        '401' => 'CUSTOM',
        '402' => 'PROFIT SHARE PPJK',
        '500' => 'MESCELLENEOUS',
        '600' => 'AIR FREIGHT IMPORT',
        '700' => 'TRUCKING ONLY',
        '701' => 'TRUCKING VENDOR',
        '702' => 'TRUCKING CUSTOMER',
        '703' => 'TRUCKING EXTERNAL',
        '800' => 'WAREHOUSE',
        '900' => 'DOMESTICS',
        '901' => 'FCL DOMESTICS',
        '902' => 'OTHER DOMESTICS',
    );

    /*
     * Ambil job access SEBELUM build query utama.
     * Jangan ambil di tengah-tengah query trnstuffingplan.
     */
    $allowedTypes = array();

    if ($isSurabaya && !$isSuperAdmin) {
        $jobAccessRows = $this->dbx
            ->select('job_code')
            ->from('mst_user_job_access')
            ->where('user_id', $user_id)
            ->get()
            ->result_array();

        foreach ($jobAccessRows as $row) {
            $code = trim((string)$row['job_code']);

            if ($code !== '' && isset($jobCodeToType[$code])) {
                $allowedTypes[] = $jobCodeToType[$code];
            }
        }

        $allowedTypes = array_values(array_unique($allowedTypes));
    }

    /*
     * Baru mulai query utama.
     */
    $this->dbx->select('
        trnstuffingplan.sp_id,
        trnstuffingplan.kode_ref,
        trnstuffingplan.type_of_trans,
        trnstuffingplan.tanggal_job,
        trnstuffingplan.etd_jkt,
        trnstuffingplan.vessel,
        trnstuffingplan.voyage,
        trnstuffingplan.carrier,
        trnstuffingplan.mother_bl,
        trnstuffingplan.port_loading,
        trnstuffingplan.destination,
        trnstuffingplan.cont_seal_no,
        trnstuffingplan.prepared,
        trnstuffingplandetail.hb_l
    ');

    $this->dbx->from('trnstuffingplan');
    $this->dbx->join(
        'trnstuffingplandetail',
        'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id',
        'left'
    );

    if ($region !== '') {
        $this->dbx->where('trnstuffingplan.region_id', $region);
    }

    /*
     * Search filtering.
     */
    if (!empty($data['_search']) && $data['_search'] === 'true') {
        $allowedSearchFields = array(
            'sp_id'         => 'trnstuffingplan.sp_id',
            'kode_ref'      => 'trnstuffingplan.kode_ref',
            'type_of_trans' => 'trnstuffingplan.type_of_trans',
            'tanggal_job'   => 'trnstuffingplan.tanggal_job',
            'etd_jkt'       => 'trnstuffingplan.etd_jkt',
            'vessel'        => 'trnstuffingplan.vessel',
            'voyage'        => 'trnstuffingplan.voyage',
            'carrier'       => 'trnstuffingplan.carrier',
            'mother_bl'     => 'trnstuffingplan.mother_bl',
            'port_loading'  => 'trnstuffingplan.port_loading',
            'destination'   => 'trnstuffingplan.destination',
            'cont_seal_no'  => 'trnstuffingplan.cont_seal_no',
            'prepared'      => 'trnstuffingplan.prepared',
            'hb_l'          => 'trnstuffingplandetail.hb_l',
        );

        $searchField  = isset($data['searchField']) ? trim((string)$data['searchField']) : '';
		$searchString = isset($data['searchString']) ? trim((string)$data['searchString']) : '';
		$searchOper   = isset($data['searchOper']) ? trim((string)$data['searchOper']) : 'eq';

		/*
		 * jqGrid kadang kirim:
		 * - kode_ref
		 * - trnstuffingplan.kode_ref
		 *
		 * Whitelist kita pakai key pendek, jadi field full table harus dinormalisasi.
		 */
		if (strpos($searchField, '.') !== false) {
			$parts = explode('.', $searchField);
			$searchField = end($parts);
		}

		if ($searchString !== '' && isset($allowedSearchFields[$searchField])) {
			$field = $allowedSearchFields[$searchField];
			
			if ($searchField === 'kode_ref' && strlen($searchString) > 15) {
				$searchString = substr($searchString, 0, -1);
			}

			switch ($searchOper) {
				case 'cn': // contains
					$this->dbx->like($field, $searchString);
					break;

				case 'bw': // begins with
					$this->dbx->like($field, $searchString, 'after');
					break;

				case 'ew': // ends with
					$this->dbx->like($field, $searchString, 'before');
					break;

				case 'ne': // not equal
					$this->dbx->where($field . ' !=', $searchString);
					break;

				case 'lt':
					$this->dbx->where($field . ' <', $searchString);
					break;

				case 'le':
					$this->dbx->where($field . ' <=', $searchString);
					break;

				case 'gt':
					$this->dbx->where($field . ' >', $searchString);
					break;

				case 'ge':
					$this->dbx->where($field . ' >=', $searchString);
					break;

				case 'eq':
				default:
					$this->dbx->where($field, $searchString);
					break;
			}
		}
    }

    /*
     * Rule khusus Surabaya.
     *
     * Jika allowedTypes kosong = default semua type_of_trans boleh.
     * Jika ada isi = hanya type_of_trans yang dicentang,
     * plus prepared sendiri.
     */
    if ($isSurabaya && !$isSuperAdmin) {
		if (!empty($allowedTypes)) {
			$this->dbx->group_start();
				$this->dbx->where_in('trnstuffingplan.type_of_trans', $allowedTypes);
				$this->dbx->or_where('trnstuffingplan.prepared', $user_id);
			$this->dbx->group_end();
		}

		if ($level == 2) {
			$this->dbx->where('trnstuffingplandetail.sales', $user_id);
		}
	} else {
        /*
         * Rule lama untuk selain Surabaya.
         */
        $levelFilters = array(
            2 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplandetail.sales', $user_id)
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            5 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-1')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            6 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-3')
                    ->or_like('trnstuffingplan.kode_ref', '-4')
                    ->or_like('trnstuffingplan.kode_ref', '-5')
                    ->or_like('trnstuffingplan.kode_ref', '-6')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            7 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->where('trnstuffingplan.prepared', '126')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            8 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->where('trnstuffingplan.prepared', '323')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            9 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->where('trnstuffingplan.prepared', '146')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            10 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->where('trnstuffingplan.prepared', '13')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            11 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-3')
                    ->or_like('trnstuffingplan.kode_ref', '-6')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            12 => function ($dbx) {
                $dbx->like('trnstuffingplan.kode_ref', '-9');
            },

            13 => function ($dbx) {
                $dbx->like('trnstuffingplan.kode_ref', '-7');
            },

            14 => function ($dbx) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-101')
                    ->or_like('trnstuffingplan.kode_ref', '-5')
                    ->group_end();
            },

            15 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-4')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            16 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-2')
                    ->or_like('trnstuffingplan.kode_ref', '-6')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            17 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->where('trnstuffingplan.prepared', '466')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            18 => function ($dbx) {
                $dbx->like('trnstuffingplan.kode_ref', '-3');
            },

            19 => function ($dbx) {
                $dbx->like('trnstuffingplan.kode_ref', '-9');
            },

            20 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-2')
                    ->or_like('trnstuffingplan.kode_ref', '-6')
                    ->or_like('trnstuffingplan.kode_ref', '-401')
                    ->or_like('trnstuffingplan.kode_ref', '-400')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },

            21 => function ($dbx) use ($user_id) {
                $dbx->group_start()
                    ->like('trnstuffingplan.kode_ref', '-100')
                    ->or_like('trnstuffingplan.kode_ref', '-500')
                    ->or_where('trnstuffingplan.prepared', $user_id)
                    ->group_end();
            },
        );

        if (isset($levelFilters[(int)$level])) {
            $levelFilters[(int)$level]($this->dbx);
        }
    }

    $this->dbx->group_by('trnstuffingplan.sp_id');

    $allowedOrderFields = array(
        'sp_id'         => 'trnstuffingplan.sp_id',
        'kode_ref'      => 'trnstuffingplan.kode_ref',
        'type_of_trans' => 'trnstuffingplan.type_of_trans',
        'tanggal_job'   => 'trnstuffingplan.tanggal_job',
        'etd_jkt'       => 'trnstuffingplan.etd_jkt',
        'vessel'        => 'trnstuffingplan.vessel',
        'voyage'        => 'trnstuffingplan.voyage',
        'carrier'       => 'trnstuffingplan.carrier',
        'mother_bl'     => 'trnstuffingplan.mother_bl',
        'destination'   => 'trnstuffingplan.destination',
        'prepared'      => 'trnstuffingplan.prepared',
    );

    $sidx = isset($data['sidx'])
	? trim((string)$data['sidx'])
	: 'kode_ref';

	$sord = isset($data['sord'])
		? strtolower(trim((string)$data['sord']))
		: 'asc';

	/*
	 * jqGrid kadang mengirim:
	 * - kode_ref
	 * - trnstuffingplan.kode_ref
	 *
	 * Normalisasi menjadi nama field pendek.
	 */
	if (strpos($sidx, '.') !== false) {
		$parts = explode('.', $sidx);
		$sidx  = end($parts);
	}

	$orderDir = ($sord === 'desc') ? 'DESC' : 'ASC';

	if ($sidx === 'kode_ref') {
		/*
		 * Natural numeric sorting.
		 *
		 * Contoh:
		 * GCL-10026050001
		 * GCL-10026050002
		 * GCL-10026050010
		 * GCL-10126050001
		 *
		 * Bukan:
		 * GCL-10026050001
		 * GCL-10026050010
		 * GCL-10026050002
		 */

		// Urutkan prefix, misalnya GCL, XLT, dan sebagainya.
		$this->dbx->order_by(
			"SUBSTRING_INDEX(trnstuffingplan.kode_ref, '-', 1)",
			$orderDir,
			false
		);

		// Urutkan bagian angka setelah tanda "-".
		$this->dbx->order_by(
			"CAST(SUBSTRING_INDEX(trnstuffingplan.kode_ref, '-', -1) AS UNSIGNED)",
			$orderDir,
			false
		);

		/*
		 * Tie breaker untuk kode yang angka utamanya sama,
		 * misalnya:
		 * GCL-10026050005
		 * GCL-10026050005A
		 * GCL-10026050005B
		 */
		$this->dbx->order_by(
			'trnstuffingplan.kode_ref',
			$orderDir
		);
	} else {
		$orderField = isset($allowedOrderFields[$sidx])
			? $allowedOrderFields[$sidx]
			: 'trnstuffingplan.sp_id';

		$this->dbx->order_by($orderField, $orderDir);
	}

    if ($limit === true) {
        $rows  = isset($data['rows']) ? (int)$data['rows'] : 20;
        $start = isset($data['start']) ? (int)$data['start'] : 0;

        if ($rows <= 0) {
            $rows = 20;
        }

        if ($start < 0) {
            $start = 0;
        }

        $this->dbx->limit($rows, $start);
    }

    return $this->dbx->get()->result();
}
	
	function tampil_semua_stuff($data)
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->select('
			sp_id,kode_ref,closing_date_time,etd_jkt,vessel,voyage,port_loading,transhipment_port,destination,prepared
		',false);
		
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
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$this->dbx->limit($data['rows'],$data['start']);
		$result = $this->dbx->get_where('trnstuffingplan',array('region_id'=>$region,'kode_ref !='=>''))->result();
		return array('result'=>$result,'last_query'=>$this->dbx->last_query());
	}

    function batal_proses($id,$id_request)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $tx = new Booking_job_sync($this->dbx);
        $region = $this->session->userdata('region_id');
        try {
            $tx->begin();
            $tx->requireInnoDB(['trnstuffingplan','trnstuffingplandetail','trnshipmentrequest']);
            $src = $tx->check($this->dbx->get_where('trnstuffingplandetail',['sp_detail_id'=>$id,'region_id'=>$region]),'LOAD_LEGACY_DETAIL')->row_array();
            if (!$src) throw new DomainException('Detail tidak ditemukan.');
            $tx->lockPlan($src['sp_id'],$region);
            $curr = $tx->check($this->dbx->query('SELECT * FROM trnstuffingplandetail WHERE sp_detail_id = ? AND region_id = ? FOR UPDATE',[$id,$region]),'LOCK_LEGACY_DETAIL')->row_array();
            if (!$curr || (string)$curr['sp_id'] !== (string)$src['sp_id'] || trim((string)$curr['booking_code']) !== '') throw new DomainException('Gunakan Delete Detail untuk booking online.');
            $tx->check($this->dbx->delete('trnstuffingplandetail',['sp_detail_id'=>$id,'region_id'=>$region]),'DELETE_LEGACY_DETAIL');
            $tx->check($this->dbx->where('shipment_request_id',$id_request)->update('trnshipmentrequest',['isProcessed'=>'No']),'RESET_LEGACY_REQUEST');
            $tx->commit();
            return '1';
        } catch (Throwable $e) {
            log_message('error','[BATAL_PROSES] '.$e->getMessage());
            return '0';
        } finally { $tx->finish(); }
    }
function get_status_posted($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return 'Untracked';
    }

    $this->dbx->select(
        'status_gocomet, id_gocomet, kode_ref'
    );

    $this->dbx->where(
        'region_id',
        $this->session->userdata('region_id')
    );

    /*
     * Mendukung pemanggilan menggunakan:
     * - id_gocomet
     * - kode_ref
     */
    $this->dbx->group_start();
    $this->dbx->where('id_gocomet', $value);
    $this->dbx->or_where('kode_ref', $value);
    $this->dbx->group_end();

    $row = $this->dbx
        ->get('trnstuffingplan')
        ->row();

    if (!$row) {
        return 'Untracked';
    }

    $status = trim((string)$row->status_gocomet);

    if ($status !== '') {
        return $status;
    }

    /*
     * Jika status belum diambil tetapi sudah mempunyai
     * id_gocomet, shipment tetap dianggap sudah ditrack.
     */
    if (!empty($row->id_gocomet)) {
        return 'Tracked';
    }

    return 'Untracked';
}

	
	function unlock_request($data)
	{
		$this->dbx->where('sp_detail_id',$data['id']);
		$this->dbx->update('trnstuffingplandetail',array('isLock'=>'0'));
		if($this->dbx->affected_rows() > 0) {
			$this->dbx->where('shipment_request_id',$data['id_request']);
			$this->dbx->update('trnshipmentrequest', array('lock'=>'0'));
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_attc($mode,$kode_ref,$value)
	{
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->update('trnstuffingplandetail',array($mode.'_attc'=>$value));
	}

	function get_first_id()
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->select_min('sp_id');
		return $this->dbx->get_where('trnstuffingplan',array('region_id'=>$region))->row()->sp_id;
	}

	function get_last_id()
	{
		$region = $this->session->userdata('region_id');
		$this->dbx->select_max('sp_id');
		return $this->dbx->get_where('trnstuffingplan',array('region_id'=>$region))->row()->sp_id;
	}
	
	function getNextIDNew($tipe,$bulan,$tahun)
	{
		$region = $this->session->userdata('region_id');
		$sql = "select ifnull(max(right(kode_ref,4)),'0') as hasil from trnstuffingplan where mid(kode_ref,5,3)= '$tipe' and region_id='$region'"; 		
		$query = $this->dbx->query($sql, array($tipe, $tahun, $bulan));			
		return $query->row()->hasil;
	}
	
public function update_stuffing($id, $data)
{
    require_once APPPATH . 'libraries/Booking_job_sync.php';
    $tx = new Booking_job_sync($this->dbx);
    $region = $this->session->userdata('region_id');
    try {
        $tx->begin();
        $tx->requireInnoDB(['trnstuffingplan','trnstuffingplan_booking','trnstuffingplandetail','x_tracking','x_tracking_export']);
        $oldPlan = $tx->lockPlan($id,$region);
        unset($data['prepared']);
        $data['updated_by'] = $this->session->userdata('fullname');
        $data['updated_at'] = date('Y-m-d H:i:s');
        // A header rename is not a moving operation. Reject it while detail exists.
        if (isset($data['kode_ref']) && $data['kode_ref'] !== $oldPlan['kode_ref']) {
            $n = $tx->check($this->dbx->get_where('trnstuffingplandetail',['sp_id'=>$id]),'CHECK_RENAME')->num_rows();
            if ($n) throw new DomainException('Tidak boleh mengganti nomor master job yang mempunyai detail.');
        }
        $tx->check($this->dbx->where('sp_id',$id)->where('region_id',$region)->update('trnstuffingplan',$data),'UPDATE_PLAN');
        $plan = array_merge($oldPlan,$data);
        $details = $tx->check($this->dbx->select('booking_code')->where('sp_id',$id)->where('region_id',$region)->get('trnstuffingplandetail'),'LOAD_PLAN_BOOKINGS')->result_array();
        $codes = [];
        foreach ($details as $row) if (trim((string)$row['booking_code']) !== '') $codes[] = $row['booking_code'];
        $codes = array_unique($codes); sort($codes,SORT_STRING);
        foreach ($codes as $bc) {
            $tx->lockBooking($bc);
            $extra = $tx->planSchedule($plan);
            foreach (['etd_jkt'=>'expect_etd','closing_date_time'=>'closing_cargo'] as $from=>$to) {
                if (!empty($data[$from]) && !in_array($data[$from],['00000000','0000-00-00','0000-00-00 00:00:00'],true)) {
                    $ts = strtotime($data[$from]);
                    if ($ts === false) throw new DomainException('Tanggal '.$from.' tidak valid.');
                    $extra[$to] = date('Y-m-d',$ts);
                }
            }
            if (isset($data['vessel']) || isset($data['voyage'])) $extra['vessel'] = trim(($plan['vessel'] ?? '').' '.($plan['voyage'] ?? ''));
            $tx->sync($bc,$extra);
        }
        // Keep existing tracking/update behavior, even if header affected_rows() = 0.
		// === Tambahan: Update x_tracking.stripping_date ===
		// Syarat: ada x_tracking.no_job yang MENGANDUNG $data['kode_ref']
		//         dan $data['stuffing_date'] tidak kosong.
		if (!empty($data['kode_ref']) && !empty($data['stuffing_date']) && $data['stuffing_date'] !== '00000000') {
			$rawDate = $data['stuffing_date'];

			// Konversi jika format Ymd (panjang 8 digit dan hanya angka)
			if (preg_match('/^\d{8}$/', $rawDate)) {
				$dt = DateTime::createFromFormat('Ymd', $rawDate);
				if ($dt !== false) {
					$strippingDatetime = $dt->format('Y-m-d');
				} else {
					$strippingDatetime = $rawDate; // fallback jika gagal parsing
				}
			} else {
				$strippingDatetime = $rawDate; // gunakan apa adanya jika bukan Ymd
			}

			// Cek keberadaan data di x_tracking
			$this->dbx->select('no_job'); // kolom apa saja, sekadar cek exist
			$this->dbx->like('no_job', $data['kode_ref']);
			$exists = $this->dbx->get('x_tracking');

			if ($exists && $exists->num_rows() > 0) {
				// Update semua baris yang match LIKE
				$this->dbx->set('stripping_date', $strippingDatetime);
				$this->dbx->like('no_job', $data['kode_ref']);
				$this->dbx->update('x_tracking');
				// print_r($this->dbx->last_query());
			}
		}

		// === Tambahan: Update x_tracking_export.id_agent ===
		// Syarat: $data['agen_penagihan'] tidak kosong
		if (!empty($data['agen_penagihan'])) {
			$this->dbx->set('id_agent', $data['agen_penagihan']);
			// match berdasarkan kode_ref (no_job LIKE kode_ref)
			$this->dbx->like('no_job', $data['kode_ref']);
			$this->dbx->update('x_tracking_export');
			// print_r($this->dbx->last_query());
		}

		
		if (!empty($data['kode_ref']) && !empty($data['warehouse'])) {
                $strippingDatetime = $data['stuffing_date'];
                // Cek keberadaan data di x_tracking
                $this->dbx->select('no_job');        // kolom apa saja, sekadar cek exist
                $this->dbx->like('no_job', $data['kode_ref']);
                $exists = $this->dbx->get('x_tracking');

                if ($exists && $exists->num_rows() > 0) {
                    // Update semua baris yang match LIKE
                    $this->dbx->set('warehouse', $data['warehouse']);
                    $this->dbx->like('no_job', $data['kode_ref']);
                    $this->dbx->update('x_tracking');
                    // print_r($this->dbx->last_query());
                }
        }
		// === Tambahan: Update x_tracking_export.dpol ===
		// Syarat: $data['atd'] tidak kosong
		if (!empty($data['atd']) && $data['atd'] !== '00000000') {
			$rawAtd = $data['atd'];
			$formattedAtd = $rawAtd;

			// Konversi jika format Ymd (8 digit angka)
			if (preg_match('/^\d{8}$/', $rawAtd)) {
				$dt = DateTime::createFromFormat('Ymd', $rawAtd);
				if ($dt !== false) {
					$formattedAtd = $dt->format('d/m/Y'); // contoh: 14/06/2026
				}
			}

			// Buat JSON string untuk kolom dpol
			$vesselName = trim(($data['vessel'] ?? '') . ' ' . ($data['voyage'] ?? ''));
			$jsonDpol = json_encode([
				"Actual Time of Departure (ATD)" => $formattedAtd,
				"Vessel Name" => $vesselName,
			], JSON_UNESCAPED_SLASHES);

			// Update x_tracking_export
			$this->dbx->set('dpol', $jsonDpol);
			$this->dbx->like('no_job', $data['kode_ref']);
			$this->dbx->update('x_tracking_export');
			// print_r($this->dbx->last_query());
		}
		
		if (!empty($data['ata']) && $data['ata'] !== '00000000') {
			$rawAta = $data['ata'];
			$formattedAta = $rawAta;

			// Konversi jika format Ymd (8 digit angka)
			if (preg_match('/^\d{8}$/', $rawAta)) {
				$dt = DateTime::createFromFormat('Ymd', $rawAta);
				if ($dt !== false) {
					$formattedAta = $dt->format('d/m/Y'); // contoh: 13/06/2026
				}
			}

			// Update kolom ata
			$this->dbx->set('ata', $formattedAta);
			$this->dbx->like('no_job', $data['kode_ref']);
			$this->dbx->update('x_tracking_export');

			// Jika transhipment_port sama dengan destination → set kolom atp
			if (!empty($data['transhipment_port']) && $data['transhipment_port'] === $data['destination']) {
				$jsonAtp = json_encode([
					"Arrival at Transhipment Port" => $formattedAta,
					"Transhipment Port" => $data['transhipment_port'],
				], JSON_UNESCAPED_SLASHES);

				$this->dbx->set('atp', $jsonAtp);
				$this->dbx->like('no_job', $data['kode_ref']);
				$this->dbx->update('x_tracking_export');
				// print_r($this->dbx->last_query());
			} else {
				// Jika transhipment_port kosong atau berbeda dengan destination → set kolom atd
				$vesselVoyage = trim(($data['vessel'] ?? '') . ' / ' . ($data['voyage'] ?? ''));
				$jsonAtd = json_encode([
					"Actual Time of Arrival (ATA)" => $formattedAta . " 00:00:00",
					"Vessel/Voyage" => $vesselVoyage,
				], JSON_UNESCAPED_SLASHES);

				$this->dbx->set('atd', $jsonAtd);
				$this->dbx->like('no_job', $data['kode_ref']);
				$this->dbx->update('x_tracking_export');
				// print_r($this->dbx->last_query());
			}
		}


        $tx->commit();
        return '1';
    } catch (Throwable $e) {
        log_message('error','[UPDATE_STUFFING] sp_id='.$id.' stage='.$tx->stage.' error='.$e->getMessage());
        return '0';
    } finally { $tx->finish(); }
}

	function manage_tabel_add($data)
	{
        if (trim((string)($data['booking_code'] ?? '')) !== '') return '0';
        unset($data['booking_code']);
		$this->dbx->insert('trnstuffingplandetail',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';			
		} else {
			return '0';	
		}
	}
	function save_id_bl($a,$b)
	{
		$this->dbx->where('kode_ref',$b);
		$this->dbx->update('trnstuffingplan',array('hide'=>$a));
		if($this->dbx->affected_rows() > 0) {
			return '1';			
		} else {
			return '0';	
		}
	}
	
	/**
	 * Simpan ID GoComet ke master job yang stabil.
	 *
	 * Mother BL dapat diedit, sehingga tidak boleh dipakai sebagai kunci update.
	 * sp_id + region_id memastikan ID tracking tetap menempel pada job yang benar.
	 */
	public function save_id_gocomet($tracking_id, $sp_id)
	{
		$this->dbx->where('sp_id', (int)$sp_id);
		$this->dbx->where('region_id', $this->session->userdata('region_id'));
		$this->dbx->update('trnstuffingplan', array(
			'id_gocomet' => trim((string)$tracking_id)
		));

		return $this->dbx->affected_rows() >= 0;
	}

	/** Ambil identitas tracking berdasarkan job, bukan berdasarkan Mother BL. */
	public function get_gocomet_job($sp_id)
	{
		return $this->dbx
			->select('sp_id, kode_ref, mother_bl, carrier, scac, id_gocomet, status_gocomet, region_id')
			->where('sp_id', (int)$sp_id)
			->where('region_id', $this->session->userdata('region_id'))
			->get('trnstuffingplan')
			->row();
	}

	/** Simpan state sinkronisasi GoComet ke job yang sama. */
	public function save_gocomet_state($sp_id, $status)
	{
		$this->dbx->where('sp_id', (int)$sp_id);
		$this->dbx->where('region_id', $this->session->userdata('region_id'));
		$this->dbx->update('trnstuffingplan', array(
			'status_gocomet' => trim((string)$status)
		));

		return $this->dbx->affected_rows() >= 0;
	}
	
	function save_status_gocomet($a,$b)
	{
		$this->dbx->where('kode_ref',$a);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->update('trnstuffingplan',array('status_gocomet'=>$b));
		if($this->dbx->affected_rows() > 0) {
			return '1';			
		} else {
			return '0';	
		}
	}
	
	/**
	 * Kompatibilitas untuk pemanggil lama: parameter sekarang adalah sp_id.
	 * Pemanggil baru sebaiknya menggunakan get_gocomet_job().
	 */
	public function get_gocomet_id($sp_id)
	{
		$this->dbx->select(array(
			'trnstuffingplan.sp_id',
			'trnstuffingplan.mother_bl as mbl',
			'trnstuffingplan.id_gocomet as tracking_id',
		));
		$this->dbx->where('trnstuffingplan.sp_id', (int)$sp_id);
		$this->dbx->where('trnstuffingplan.region_id', $this->session->userdata('region_id'));

		return $this->dbx->get('trnstuffingplan', 1);
	}
	
	function manage_tabel_edit($id, $data)
	{
		// PATCH: sama seperti saving_data — kolom identitas & relasi tidak
		// boleh berubah lewat edit generik; hanya lewat jalur moving resmi.
		unset($data['sp_id']);
		unset($data['sp_detail_id']);
        unset($data['booking_code']);
		unset($data['code']);
		unset($data['num']);
		unset($data['kode_ref']);
		unset($data['region_id']);
		unset($data['created_by']);
		unset($data['created_at']);

		// PATCH: jejak audit aplikasi
		$data['edited_by'] = $this->session->userdata('fullname');
		$data['edited_at'] = date('Y-m-d H:i:s');

		$this->dbx->where('sp_detail_id',$id);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->update('trnstuffingplandetail',$data);
		if($this->dbx->affected_rows() > 0) {
			// Histori BL: sama seperti saving_data() - catat field yang berubah.
			$this->m_bl_history->ensure_created($id, $data['edited_by']);
			$this->m_bl_history->log_field_changes($id, $data, $data['edited_by']);
			return '1';
		} else {
			return '0';	
		}
	}
	
	function check_booking($id)
	{
		$result =$this->dbx->get_where('trnstuffingplandetail',array('sp_detail_id'=>$id,'booking_code IS NOT NULL','region_id'=>$this->session->userdata('region_id')));
		if ($result->num_rows() > 0) {
			$data = $result->result_array();
			return $data[0]['booking_code'];
		} else {
			return FALSE;
		}
	}
    function update_booking($data)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $tx = new Booking_job_sync($this->dbx);
        try {
            $tx->begin();
            $tx->requireInnoDB(['trnstuffingplan_booking','trnstuffingplandetail']);
            $master = $tx->lockBooking($data);
            $region = trim((string)$this->session->userdata('region_id'));
            $allowed = trim((string)($master['stuffing_region'] ?? '')) ?: $master['region_id'];
            if ($region === '' || strcasecmp($allowed,$region) !== 0) throw new DomainException('Region booking tidak sesuai.');
            $tx->sync($data,[],true);
            $tx->commit();
            return '1';
        } catch (Throwable $e) {
            log_message('error','[UPDATE_BOOKING] '.$e->getMessage());
            return '0';
        } finally { $tx->finish(); }
    }
    function manage_tabel_delete($id, $job_no)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $region_id = $this->session->userdata('region_id');
        $tx = new Booking_job_sync($this->dbx);
        try {
            $tx->begin();
            $tables = ['trnstuffingplan','trnstuffingplan_booking','trnstuffingplandetail','tbl_detail','x_form_bl','inf_income','inf_cost','inf_cost_tbl_satu','inf_cost_refund_sc','inf_cost_refund_discount','inf_income_from_agent','inf_income_from_coloader','inf_income_selling_price','save_jobsheet','x_tracking','x_tracking_export'];
            if ($this->dbx->table_exists('actual_sales')) $tables[] = 'actual_sales';
            $tx->requireInnoDB($tables);
            $src = $tx->check($this->dbx->get_where('trnstuffingplandetail',['sp_detail_id'=>$id,'region_id'=>$region_id]),'LOAD_DELETE_DETAIL')->row_array();
            if (!$src) throw new DomainException('Detail tidak ditemukan.');
            $tx->lockPlan($src['sp_id'],$region_id);
            $bc = trim((string)($src['booking_code'] ?? ''));
            if ($bc !== '') $tx->lockBooking($bc);
            $curr = $tx->check($this->dbx->query('SELECT * FROM trnstuffingplandetail WHERE sp_detail_id = ? AND region_id = ? FOR UPDATE',[$id,$region_id]),'LOCK_DELETE_DETAIL')->row_array();
            if (!$curr || $curr['kode_ref'] !== $src['kode_ref'] || (string)$curr['sp_id'] !== (string)$src['sp_id'] || $curr['booking_code'] !== $src['booking_code']) throw new DomainException('Detail berubah. Muat ulang job.');
            if ($job_no !== '' && $job_no !== null && $job_no !== $curr['kode_ref']) throw new DomainException('Job pada browser sudah berubah.');
            $job_no = $curr['kode_ref'];
            $sameRef = $tx->check($this->dbx->query('SELECT sp_detail_id FROM trnstuffingplandetail WHERE kode_ref = ? AND region_id = ? FOR UPDATE',[$job_no,$region_id]),'CHECK_DELETE_JOB_REF')->num_rows();
            if ($job_no !== '' && $sameRef !== 1) throw new DomainException('Nomor anak job duplikat; audit sebelum menghapus relasi.');
            $tx->check($this->dbx->delete('trnstuffingplandetail',['sp_detail_id'=>$id,'region_id'=>$region_id]),'DELETE_DETAIL');
            if ($this->dbx->affected_rows() !== 1) throw new DomainException('Detail tidak berhasil dihapus.');
				// 3. Hapus data relasi
				$tx->check($this->dbx->delete('x_form_bl', array('id_bl' => $id)), 'DELETE_X_FORM_BL');
				$tx->check($this->dbx->delete('inf_income', array('id_sd' => $id)), 'DELETE_INF_INCOME');
				$tx->check($this->dbx->delete('inf_cost', array('id_sd' => $id)), 'DELETE_INF_COST');
				$tx->check($this->dbx->delete('inf_cost_tbl_satu', array('sp_detail_id' => $id)), 'DELETE_INF_COST_TBL_SATU');
				$tx->check($this->dbx->delete('inf_cost_refund_sc', array('sp_detail_id' => $id)), 'DELETE_INF_COST_REFUND_SC');
				$tx->check($this->dbx->delete('inf_cost_refund_discount', array('sp_detail_id' => $id)), 'DELETE_INF_COST_REFUND_DISCOUNT');
				$tx->check($this->dbx->delete('inf_income_from_agent', array('sp_detail_id' => $id)), 'DELETE_INF_INCOME_FROM_AGENT');
				$tx->check($this->dbx->delete('inf_income_from_coloader', array('sp_detail_id' => $id)), 'DELETE_INF_INCOME_FROM_COLOADER');
				$tx->check($this->dbx->delete('inf_income_selling_price', array('sp_detail_id' => $id)), 'DELETE_INF_INCOME_SELLING_PRICE');
				
				// Pastikan tabel krusial juga menggunakan region_id
				$tx->check($this->dbx->delete('tbl_detail', array('detail_id' => $id, 'region_id' => $region_id)), 'DELETE_TBL_DETAIL');
				$tx->check($this->dbx->delete('save_jobsheet', array('kode_ref' => $job_no, 'region_id' => $region_id)), 'DELETE_SAVE_JOBSHEET');
				if ($this->dbx->table_exists('actual_sales')) { 
					$tx->check($this->dbx->delete('actual_sales', array('kode_ref' => $job_no, 'region_id' => $region_id)), 'DELETE_ACTUAL_SALES'); 
				}

				// ---> TAMBAHAN HAPUS DATA TRACKING <---
				$tx->check($this->dbx->delete('x_tracking', array('no_job' => $job_no)), 'DELETE_X_TRACKING');
				$tx->check($this->dbx->delete('x_tracking_export', array('no_job' => $job_no, 'region_id' => $region_id)), 'DELETE_X_TRACKING_EXPORT');


            if ($this->dbx->trans_status() === false) throw new RuntimeException('Penghapusan relasi gagal.');
            if ($bc !== '') $tx->sync($bc, [], true);
            $tx->commit();
        } catch (Throwable $e) {
            log_message('error','[DELETE_DETAIL] id='.$id.' stage='.$tx->stage.' error='.$e->getMessage());
            return '0';
        } finally { $tx->finish(); }
        try { catat_log('Berhasil menghapus seluruh data operasional untuk No Job: '.$job_no,'Modul Operasional/Job'); } catch (Throwable $e) { log_message('error','[DELETE_DETAIL_LOG] '.$e->getMessage()); }
        return '1';
    }

	function get_shipper($id)
	{
		$this->dbx->select(array('shipper_id','shipper_name','shipper_address'));	
		$this->dbx->from('mstshipper');
		$this->dbx->where('shipper_id', $id);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$data = $this->dbx->get();	
		return $data;
	}
	
	function get_shipper_contact($name)
	{
		$this->dbx->select(array('shipper_id','shipper_name','shipper_address','cp_name','cp_email'));	
		$this->dbx->from('mstshipper');
		$this->dbx->where('shipper_name', $name);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$data = $this->dbx->get()->result();	
		return $data;
	}
	
	function get_route($booking_code)
	{
		$this->dbx->select(array('route_type','real_dest','trans_city'));	
		$this->dbx->from('trnstuffingplan_booking');
		$this->dbx->where('booking_code', $booking_code);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$data = $this->dbx->get()->result();	
		return $data;
	}
	
	function getNextID()
	{
		$this->dbx->select_max('sp_id');
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get('trnstuffingplan');
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->sp_id+1;
		}
	}
	
	function getListKodeRef($kode)
	{
		$this->dbx->select('sp_id,kode_ref ',FALSE);
		$this->dbx->from('trnstuffingplan');
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->where('kode_ref !=',$kode);
		$this->dbx->where('kode_ref !=','');
		$this->dbx->where('closing_date_time >=',date('Ymd'));
		return $this->dbx->get();
	}
	
    function move_job($sp,$spd)
    {
        require_once APPPATH . 'libraries/Booking_job_sync.php';
        $tx = new Booking_job_sync($this->dbx);
        try {
            if ((int)$sp <= 0 || (int)$spd <= 0) throw new DomainException('Parameter moving tidak valid.');
            $result = $tx->move((int)$spd,(int)$sp,$this->session->userdata('region_id'),$this->session->userdata('fullname'));
            return (string)$result['status'];
        } catch (Throwable $e) {
            log_message('error','[STUFFING_MOVE] detail='.$spd.' stage='.$tx->stage.' error='.$e->getMessage());
            return '0';
        }
    }

	function updateStuffingPlanDetail()
	{
		$this->dbx->select('sp_detail_id,kode_ref,num',false);
		$this->dbx->from('trnstuffingplandetail');
		return $this->dbx->get(); 
	}

	function get_sp_detail_id_akhir($code)
	{
		$this->dbx->select('code,sp_detail_id',false);
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where('code',$code);
		$this->dbx->order_by('sp_detail_id','desc');
		$this->dbx->limit(1);
		$q = $this->dbx->get();
		$row = $q->row_array();
		return $row['sp_detail_id'];
	}
	
	function get_instruksi($spd)
	{
		$query = $this->dbx->get_where('x_instruksi_stuffing',array('id_sp'=>$spd));
		if($query->num_rows()> 0) {
			$data = new stdClass();	
			$data->to = $query->row()->to;
			$data->instruksi_stuffing = $query->row()->instruksi_stuffing;
			return $data;
		} else {
			$data = new stdClass();	
			$data->to = '';
			$data->instruksi_stuffing = '';
			return $data;
		}
	}

	function instruksi_stuffing($spd)
	{
		$data = $this->dbx->get_where('x_instruksi_stuffing',array('id_sp'=>$spd));
		if($data->num_rows()> 0) {
			return array(
				'status'=>$data->num_rows(),
				'to'=>$data->row()->to,
				'instruksi_stuffing'=>$data->row()->instruksi_stuffing,
			);
		} else {
			return array('status'=>$data->num_rows(),'to'=>'','instruksi_stuffing'=>'');
		}
	}

	function close_form_stuffing($data) 
	{
		$spd = $data['sp_id'];
		unset($data['sp_id']);
		$query = $this->dbx->get_where('x_instruksi_stuffing',array('id_sp'=>$spd));
		if($query->num_rows()> 0) {
			$this->dbx->where('id_sp',$spd);
			$this->dbx->update('x_instruksi_stuffing',$data);
		} else {
			$data['id_sp'] = $spd;
			$this->dbx->insert('x_instruksi_stuffing',$data);
		}
	}
	
	function get_print_rekap($job)
	{
		$region_id=$this->session->userdata('region_id');
		$this->dbr->select(array(
		'trnstuffingplandetail.sp_detail_id as spd',
		'trnstuffingplandetail.num as num',
		'trnstuffingplandetail.code as code',
		'trnstuffingplandetail.kode_ref as kode_ref',
		'trnstuffingplandetail.shipper as shipper',
		'trnstuffingplandetail.bl_meas as bl_meas',
		'trnstuffingplandetail.note as note',
		'trnstuffingplandetail.destination as destination',
		'trnstuffingplandetail.hb_l as hb_l',
		'trnstuffingplandetail.shipper_import as shipper_import',
		'trnstuffingplandetail.shipper_print as shipper_print',
		'trnstuffingplandetail.sales as sales',
		'trnstuffingplan.region_id as region_id',
		));
		
		$this->dbr->join('trnstuffingplan','trnstuffingplandetail.sp_id=trnstuffingplan.sp_id','right');
		$this->dbr->where('trnstuffingplan.region_id',$region_id);
		$this->dbr->where('code',$job);
		
		$this->dbr->order_by('CHAR_LENGTH(trnstuffingplandetail.num)', 'asc', false);
		$this->dbr->order_by('trnstuffingplandetail.num', 'asc');
		return $this->dbr->get('trnstuffingplandetail');
	}
	
	public function check_status($kode_ref)
{
    // Ambil daftar kode_ref dari tabel trnstuffingplandetail berdasarkan sp_id
    $this->dbx->select('kode_ref');
    $this->dbx->from('trnstuffingplandetail');
    $this->dbx->where('code', $kode_ref);
    $query = $this->dbx->get();
    $kode_refs = $query->result_array();

    if (empty($kode_refs)) {
        return ""; // Jika tidak ada kode_ref, return kosong
    }

    // Periksa setiap kode_ref di tabel x_tracking_export
    foreach ($kode_refs as $row) {
        $this->dbx->select('cargo_delivery');
        $this->dbx->from('x_tracking_export');
        $this->dbx->where('no_job', $row['kode_ref']);
        $this->dbx->where('cargo_delivery !=', '[]');
        $this->dbx->where('cargo_delivery !=', '{"Cargo delivered":null}');
        $result = $this->dbx->get();

        if ($result->num_rows() == 0) {
            return ""; // Jika ada cargo_release yang kosong, return kosong
        }
    }

    return "perfect"; // Jika semua cargo_release terisi, return "perfect"
}

	public function check_status_import($kode_ref)
{
    // Ambil daftar kode_ref dari tabel trnstuffingplandetail berdasarkan sp_id
    $this->dbx->select('kode_ref');
    $this->dbx->from('trnstuffingplandetail');
    $this->dbx->where('code', $kode_ref);
    $query = $this->dbx->get();
    $kode_refs = $query->result_array();

    if (empty($kode_refs)) {
        return ""; // Jika tidak ada kode_ref, return kosong
    }

    // Periksa setiap kode_ref di tabel x_tracking_export
    foreach ($kode_refs as $row) {
        $this->dbx->select('pickup_cargo');
        $this->dbx->from('x_tracking');
        $this->dbx->where('no_job', $row['kode_ref']);
        $this->dbx->where('pickup_cargo !=', '');
        $result = $this->dbx->get();

        if ($result->num_rows() == 0) {
            return ""; // Jika ada cargo_release yang kosong, return kosong
        }
    }

    return "perfect"; // Jika semua cargo_release terisi, return "perfect"
}

public function get_data_edit_stuffing_plan($data)
	{
		$region = $this->session->userdata('region_id');
		
			//$this->dbx->order_by('sp_id','desc');
			$this->dbx->order_by('kode_ref','desc');
			return $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$data["kode_ref"], 'region_id'=>$region),1,false)->row();
		
	}
/**
 * Ambil seluruh konteks agregat untuk print_rekap pada satu job (code = 15 char parent).
 * Return:
 *  - rows: list row detail (hasil get_print_rekap yang lama)
 *  - bl_meas[kode_ref] -> float
 *  - ratio[kode_ref]   -> float (bagian BL per kode_ref / total BL per job prefix)
 *  - income[kode_ref]  -> ['iusd','irp','giusd','girp']   // sama urutan get_total_income()
 *  - cost[kode_ref]    -> ['usd','rp']                    // sama urutan get_total_cost()
 *  - dn[spd]           -> float USD
 *  - inv_agent[spd]    -> float USD (COLLECT + DN)
 *  - coloader[kode_ref]-> ['usd','rp']
 */
public function agg_rekap_context($job_code)
{
    $region = $this->session->userdata('region_id');

    // 1) Ambil baris yang akan dicetak (asli versimu)
    $rows = $this->get_print_rekap($job_code)->result();
    if (empty($rows)) {
        return [
            'rows'      => [],
            'bl_meas'   => [],
            'ratio'     => [],
            'income'    => [],
            'cost'      => [],
            'dn'        => [],
            'inv_agent' => [],
            'coloader'  => [],
        ];
    }

    // Kumpulkan list kunci
    $kodeRefs = [];
    $spds     = [];
    $jobPrefixes = []; // map kode_ref -> job_prefix (15 char)
    foreach ($rows as $r) {
        $kodeRefs[$r->kode_ref] = true;
        $spds[$r->spd] = true;
        $jobPrefixes[$r->kode_ref] = substr($r->kode_ref, 0, 15);
    }
    $kodeRefs = array_keys($kodeRefs);
    $spds     = array_keys($spds);

    // ========= 2) BL MEAS per kode_ref & total per prefix (untuk rasio) =========
    // BL per kode_ref
    $bl_meas = $this->_agg_bl_meas_by_kode_ref($kodeRefs, $region);
    // Total BL per job_prefix
    $prefixTotals = $this->_agg_bl_meas_by_job_prefix(array_values($jobPrefixes), $region);

    // Rasio per kode_ref
    $ratio = [];
    foreach ($kodeRefs as $kr) {
        $pref = $jobPrefixes[$kr];
        $bl   = isset($bl_meas[$kr]) ? (float)$bl_meas[$kr] : 0.0;
        $tot  = isset($prefixTotals[$pref]) ? (float)$prefixTotals[$pref] : 0.0;
        $ratio[$kr] = ($tot > 0.0) ? ($bl / $tot) : 0.0;
    }

    // ========= 3) Income lokal per kode_ref =========
    $income_local = $this->_agg_income_selling_by_kode_ref($kodeRefs, $region); // ['iusd','irp']

    // ========= 4) Income global per job_prefix (didistribusi ke kode_ref via rasio) =========
    $inc_global_by_prefix = $this->_agg_x_glbincome_by_prefix(array_values($jobPrefixes), $region); // ['usd','rp']
    $income = []; // final: ['iusd','irp','giusd','girp']
    foreach ($kodeRefs as $kr) {
        $pref = $jobPrefixes[$kr];
        $rat  = $ratio[$kr];
        $giusd = isset($inc_global_by_prefix[$pref]['usd']) ? $inc_global_by_prefix[$pref]['usd'] * $rat : 0.0;
        $girp  = isset($inc_global_by_prefix[$pref]['rp'])  ? $inc_global_by_prefix[$pref]['rp']  * $rat : 0.0;

        $iusd = isset($income_local[$kr]['usd']) ? $income_local[$kr]['usd'] : 0.0;
        $irp  = isset($income_local[$kr]['rp'])  ? $income_local[$kr]['rp']  : 0.0;

        $income[$kr] = [
            'iusd'  => $iusd,
            'irp'   => $irp,
            'giusd' => $giusd,
            'girp'  => $girp,
        ];
    }

    // ========= 5) Cost lokal per kode_ref =========
    $cost_local = $this->_agg_cost_local_by_kode_ref($kodeRefs, $region); // ['usd','rp']

    // ========= 6) Cost global per prefix (x_glbcost) dialokasikan via rasio =========
    $cost_global_by_prefix = $this->_agg_x_glbcost_by_prefix(array_values($jobPrefixes), $region); // ['usd','rp']

    $cost = []; // final per kode_ref: ['usd','rp']
    foreach ($kodeRefs as $kr) {
        $pref = $jobPrefixes[$kr];
        $rat  = $ratio[$kr];

        $g_usd = isset($cost_global_by_prefix[$pref]['usd']) ? $cost_global_by_prefix[$pref]['usd'] * $rat : 0.0;
        $g_rp  = isset($cost_global_by_prefix[$pref]['rp'])  ? $cost_global_by_prefix[$pref]['rp']  * $rat : 0.0;

        $l_usd = isset($cost_local[$kr]['usd']) ? $cost_local[$kr]['usd'] : 0.0;
        $l_rp  = isset($cost_local[$kr]['rp'])  ? $cost_local[$kr]['rp']  : 0.0;

        $cost[$kr] = [
            'usd' => $l_usd + $g_usd,
            'rp'  => $l_rp  + $g_rp,
        ];
    }

    // ========= 7) DN (USD) per sp_detail_id =========
    $dn = $this->_agg_dn_usd_by_spd($spds, $region);

    // ========= 8) Invoice to Agent (USD) per sp_detail_id =========
    $inv_agent = $this->_agg_inv_agent_usd_by_spd($spds, $region);

    // ========= 9) Coloader income per kode_ref =========
    $coloader = $this->_agg_coloader_by_kode_ref($kodeRefs, $region); // ['usd','rp']

    return [
        'rows'      => $rows,
        'bl_meas'   => $bl_meas,
        'ratio'     => $ratio,
        'income'    => $income,    // ['iusd','irp','giusd','girp'] per kode_ref
        'cost'      => $cost,      // ['usd','rp'] per kode_ref (lokal + global teralokasi)
        'dn'        => $dn,        // USD by spd
        'inv_agent' => $inv_agent, // USD by spd
        'coloader'  => $coloader,  // ['usd','rp'] by kode_ref
    ];
}

/* ====================== HELPER AGGREGATION QUERIES ====================== */

/** BL_MEAS per kode_ref */
private function _agg_bl_meas_by_kode_ref(array $kodeRefs, $region)
{
    if (empty($kodeRefs)) return [];
    $this->dbr->select('kode_ref, SUM(bl_meas) AS bl', false);
    $this->dbr->from('trnstuffingplandetail');
    $this->dbr->where_in('kode_ref', $kodeRefs);
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('kode_ref');
    $res = $this->dbr->get()->result();
    $out = [];
    foreach ($res as $r) { $out[$r->kode_ref] = (float)$r->bl; }
    return $out;
}

/** TOTAL BL per job prefix (15 char) */
private function _agg_bl_meas_by_job_prefix(array $prefixes, $region)
{
    if (empty($prefixes)) return [];
    $prefixes = array_values(array_unique($prefixes));

    // Gunakan WHERE (LEFT(kode_ref,15) IN (...)) — aman selama index ada di kode_ref
    $this->dbr->select('LEFT(kode_ref,15) AS pref, SUM(bl_meas) AS ttl', false);
    $this->dbr->from('trnstuffingplandetail');
    $this->dbr->where('region_id', $region);
    // bikin OR group untuk setiap prefix
    $this->dbr->group_start();
    foreach ($prefixes as $i => $p) {
        ($i === 0)
            ? $this->dbr->like('kode_ref', $p, 'after')
            : $this->dbr->or_like('kode_ref', $p, 'after');
    }
    $this->dbr->group_end();
    $this->dbr->group_by('pref');
    $res = $this->dbr->get()->result();

    $out = [];
    foreach ($res as $r) { $out[$r->pref] = (float)$r->ttl; }
    // Pastikan yang tidak ditemukan = 0
    foreach ($prefixes as $p) { if (!isset($out[$p])) $out[$p] = 0.0; }
    return $out;
}

/** Income selling price per kode_ref (USD, RP) */
private function _agg_income_selling_by_kode_ref(array $kodeRefs, $region)
{
    if (empty($kodeRefs)) return [];
    $this->dbr->select("
        kode_ref,
        SUM(CASE WHEN currency='USD' THEN (qty * unit_price) ELSE 0 END) AS usd,
        SUM(CASE WHEN currency='RP.' THEN (qty * unit_price) ELSE 0 END) AS rp
    ", false);
    $this->dbr->from('inf_income_selling_price');
    $this->dbr->where_in('kode_ref', $kodeRefs);
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('kode_ref');
    $res = $this->dbr->get()->result();
    $out = [];
    foreach ($res as $r) {
        $out[$r->kode_ref] = ['usd' => (float)$r->usd, 'rp' => (float)$r->rp];
    }
    // default 0 untuk yang kosong
    foreach ($kodeRefs as $k) { if (!isset($out[$k])) $out[$k] = ['usd'=>0.0,'rp'=>0.0]; }
    return $out;
}

/** x_glbincome per job_prefix (USD, RP) */
private function _agg_x_glbincome_by_prefix(array $prefixes, $region)
{
    if (empty($prefixes)) return [];
    $prefixes = array_values(array_unique($prefixes));

    $this->dbr->select("
        LEFT(job_no,15) AS pref,
        SUM(CASE WHEN currency='USD' THEN price ELSE 0 END) AS usd,
        SUM(CASE WHEN currency='RP.' THEN unit_price ELSE 0 END) AS rp
    ", false);
    $this->dbr->from('x_glbincome');
    $this->dbr->where('region_id', $region);
    $this->dbr->group_start();
    foreach ($prefixes as $i => $p) {
        ($i === 0)
            ? $this->dbr->like('job_no', $p, 'after')
            : $this->dbr->or_like('job_no', $p, 'after');
    }
    $this->dbr->group_end();
    $this->dbr->group_by('pref');
    $res = $this->dbr->get()->result();

    $out = [];
    foreach ($res as $r) {
        $out[$r->pref] = ['usd' => (float)$r->usd, 'rp' => (float)$r->rp];
    }
    foreach ($prefixes as $p) { if (!isset($out[$p])) $out[$p] = ['usd'=>0.0,'rp'=>0.0]; }
    return $out;
}

/** Cost lokal per kode_ref dari 3 tabel (USD, RP) dengan rumus qty vs mchgs */
private function _agg_cost_local_by_kode_ref(array $kodeRefs, $region)
{
    if (empty($kodeRefs)) return [];

    // helper closure bikin SUM untuk tabel dengan kolom qty, mchgs, unit_price, currency
    $sumCost = function($table) use ($kodeRefs, $region) {
        $this->dbr->select("
            kode_ref,
            SUM( qty * unit_price * (currency='USD') ) AS usd,
            SUM( qty * unit_price * (currency='RP.') ) AS rp
        ", false);
        $this->dbr->from($table);
        $this->dbr->where_in('kode_ref', $kodeRefs);
        $this->dbr->where('region_id', $region);
        $this->dbr->group_by('kode_ref');
        return $this->dbr->get()->result();
    };

    $m1 = $sumCost('inf_cost_tbl_satu');
    $m2 = $sumCost('inf_cost_refund_discount');
    $m3 = $sumCost('inf_cost_refund_sc');

    $out = [];
    $addRows = function($rows) use (&$out) {
        foreach ($rows as $r) {
            $k = $r->kode_ref;
            if (!isset($out[$k])) $out[$k] = ['usd'=>0.0,'rp'=>0.0];
            $out[$k]['usd'] += (float)$r->usd;
            $out[$k]['rp']  += (float)$r->rp;
        }
    };
    $addRows($m1); $addRows($m2); $addRows($m3);

    foreach ($kodeRefs as $k) { if (!isset($out[$k])) $out[$k] = ['usd'=>0.0,'rp'=>0.0]; }
    return $out;
}

/** x_glbcost per job_prefix (USD & RP) */
private function _agg_x_glbcost_by_prefix(array $prefixes, $region)
{
    if (empty($prefixes)) return [];
    $prefixes = array_values(array_unique($prefixes));

    $this->dbr->select("
        LEFT(job_no,15) AS pref,
        SUM(CASE WHEN currency='USD' THEN price ELSE 0 END) AS usd,
        SUM(CASE WHEN currency='RP.' THEN price ELSE 0 END) AS rp
    ", false);
    $this->dbr->from('x_glbcost');
    $this->dbr->where('region_id', $region);
    $this->dbr->group_start();
    foreach ($prefixes as $i => $p) {
        ($i === 0)
            ? $this->dbr->like('job_no', $p, 'after')
            : $this->dbr->or_like('job_no', $p, 'after');
    }
    $this->dbr->group_end();
    $this->dbr->group_by('pref');
    $res = $this->dbr->get()->result();

    $out = [];
    foreach ($res as $r) {
        $out[$r->pref] = ['usd' => (float)$r->usd, 'rp' => (float)$r->rp];
    }
    foreach ($prefixes as $p) { if (!isset($out[$p])) $out[$p] = ['usd'=>0.0,'rp'=>0.0]; }
    return $out;
}

/** DN USD per sp_detail_id */
private function _agg_dn_usd_by_spd(array $spds, $region)
{
    if (empty($spds)) return [];
    $this->dbr->select("
        sp_detail_id,
        SUM(price) AS usd
    ", false);
    $this->dbr->from('inf_income_from_agent');
    $this->dbr->where_in('sp_detail_id', $spds);
    $this->dbr->where('invoice_db', 'DEBIT NOTE');
    $this->dbr->where('currency', 'USD');
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('sp_detail_id');
    $res = $this->dbr->get()->result();

    $out = [];
    foreach ($res as $r) { $out[$r->sp_detail_id] = (float)$r->usd; }
    foreach ($spds as $s) { if (!isset($out[$s])) $out[$s] = 0.0; }
    return $out;
}

/** Invoice to Agent USD per sp_detail_id = COLLECT (selling_price) + DN (from_agent) */
private function _agg_inv_agent_usd_by_spd(array $spds, $region)
{
    if (empty($spds)) return [];

    // COLLECT dari selling_price
    $this->dbr->select("
        sp_detail_id,
        SUM(price) AS usd
    ", false);
    $this->dbr->from('inf_income_selling_price');
    $this->dbr->where_in('sp_detail_id', $spds);
    $this->dbr->where('prep_coll', 'COLLECT');
    $this->dbr->where('currency',  'USD');
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('sp_detail_id');
    $collect = $this->dbr->get()->result();
    $map_collect = [];
    foreach ($collect as $r) { $map_collect[$r->sp_detail_id] = (float)$r->usd; }

    // DN dari from_agent
    $this->dbr->select("
        sp_detail_id,
        SUM(price) AS usd
    ", false);
    $this->dbr->from('inf_income_from_agent');
    $this->dbr->where_in('sp_detail_id', $spds);
    $this->dbr->where('invoice_db', 'DEBIT NOTE');
    $this->dbr->where('currency',  'USD');
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('sp_detail_id');
    $dn = $this->dbr->get()->result();
    $map_dn = [];
    foreach ($dn as $r) { $map_dn[$r->sp_detail_id] = (float)$r->usd; }

    // gabungkan
    $out = [];
    foreach ($spds as $s) {
        $out[$s] = (isset($map_collect[$s]) ? $map_collect[$s] : 0.0)
                 + (isset($map_dn[$s])      ? $map_dn[$s]      : 0.0);
    }
    return $out;
}

/** Coloader income per kode_ref (USD, RP) dengan rumus qty vs mchgs */
private function _agg_coloader_by_kode_ref(array $kodeRefs, $region)
{
    if (empty($kodeRefs)) return [];
    $this->dbr->select("
        kode_ref,
        SUM(  qty * unit_price * (currency='USD') ) AS usd,
        SUM( qty * unit_price * (currency='RP.') ) AS rp
    ", false);
    $this->dbr->from('inf_income_from_coloader');
    $this->dbr->where_in('kode_ref', $kodeRefs);
    $this->dbr->where('region_id', $region);
    $this->dbr->group_by('kode_ref');
    $res = $this->dbr->get()->result();

    $out = [];
    foreach ($res as $r) {
        $out[$r->kode_ref] = ['usd' => (float)$r->usd, 'rp' => (float)$r->rp];
    }
    foreach ($kodeRefs as $k) { if (!isset($out[$k])) $out[$k] = ['usd'=>0.0,'rp'=>0.0]; }
    return $out;
}

public function get_agent_for_select2($keyword = '')
{
    // Kita panggil 'agent_company_code' sebagai 'id' dan 'text' (default Select2), 
    // lalu tambahkan 'agent_company_name' sebagai 'name'
    $this->dbx->select('agent_company_code AS id, agent_company_code AS text, agent_company_name AS name');
    $this->dbx->from('refagentcompany');
    
    if (!empty($keyword)) {
        $this->dbx->group_start();
        $this->dbx->like('agent_company_code', $keyword);
        $this->dbx->or_like('agent_company_name', $keyword);
        $this->dbx->group_end();
    }
	
	$region_id = $this->session->userdata('region_id');
    $this->dbx->where('region_id', $region_id);

    $this->dbx->limit(30); 
    $query = $this->dbx->get();
    
    return $query->result_array();
}

	

}



	

	
