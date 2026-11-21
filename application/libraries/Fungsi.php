<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fungsi {
	public $dbx; // koneksi dinamis di-inject dari controller
	protected $CI;

   public function __construct($params = [])
    {
        $this->CI =& get_instance();

        // Pastikan session & database loaded
        $this->CI->load->library('session');
        $this->CI->load->database();

        // 1) jika controller mengirim koneksi (via $params['db']), pakai itu
        if (isset($params['db']) && $params['db'] instanceof CI_DB) {
            $this->dbx = $params['db'];
        } else {
            // 2) fallback: pakai koneksi default CI
            $this->dbx = $this->CI->db;
        }
    }
	
	/* update dropdown agen_penagihan */
	
	function get_data_object_agent($tbl,$fld,$sord,$reg,$index) {
		$this->dbx->select($fld);
		$this->dbx->order_by($sord,'asc');
		if($reg==TRUE) :
			$q=$this->dbx->get_where($tbl,array('region_id'=>$this->CI->session->userdata('region_id'),'penagihan'=>'YES'));
		else :
			$q=$this->dbx->get($tbl);
		endif;
		if($index==TRUE):
			if($q->num_rows()>=1) :
				foreach($q->result_object() as $a => $b){
					$c[]=$b;
				}
			else :
				$c=array('id'=>'','code'=>'','name'=>'','address'=>'');
			endif;
		else:
			if($q->num_rows()>=1):
				$d=$fld[0];
				$e=$fld[1];
				foreach($q->result() as $a => $b){
					$c[$b->$d]=$b->$e;
				}
			else :
				$c=array();
			endif;
		endif;
		return json_encode($c);
   }
	
	
	/***
	new fungsi
	*/
	
	function list_type_of_trans () {
		return array('FCL'=>'FCL EXPORT','LCL'=>'LCL EXPORT','COLOAD'=>'COLOAD EXPORT','AIR FREIGHT'=>'AIR FREIGHT EXPORT','AIR FREIGHT IMPORT'=>'AIR FREIGHT IMPORT','SF IMPORT FCL'=>'FCL IMPORT','SF IMPORT LCL'=>'LCL IMPORT','COLOAD IMPORT'=>'COLOAD IMPORT','MESCELLENEOUS'=>'MISCELLANEOUS','DOMESTICS'=>'LCL DOMESTICS','FCL DOMESTICS'=>'FCL DOMESTICS','OTHER DOMESTICS'=>'OTHER DOMESTICS','CUSTOM'=>'CUSTOM EXPORT','CUSTOM IMPORT'=>'CUSTOM IMPORT','TRUCKING ONLY'=>'TRUCKING ONLY','TRUCKING VENDOR'=>'TRUCKING VENDOR','TRUCKING CUSTOMER'=>'TRUCKING CUSTOMER','TRUCKING EXTERNAL'=>'TRUCKING EXTERNAL','PROFIT SHARE PPJK'=>'PROFIT SHARE PPJK','WAREHOUSE'=>'WAREHOUSE');
	}
	
	function get_data_object($tbl,$fld,$sord,$reg,$index) {
		$this->dbx->select($fld);
		$this->dbx->order_by($sord,'asc');
		if($reg==TRUE) :
			$q=$this->dbx->get_where($tbl,array('region_id'=>$this->CI->session->userdata('region_id')));
		else :
			$q=$this->dbx->get($tbl);
		endif;
		if($index==TRUE):
			if($q->num_rows()>=1) :
				foreach($q->result_object() as $a => $b){
					$c[]=$b;
				}
			else :
				$c=array('id'=>'','code'=>'','name'=>'','address'=>'');
			endif;
		else:
			if($q->num_rows()>=1):
				$d=$fld[0];
				$e=$fld[1];
				foreach($q->result() as $a => $b){
					$c[$b->$d]=$b->$e;
				}
			else :
				$c=array();
			endif;
		endif;
		
		return json_encode($c);
   }
	
	function get_data_level() {
		$level=array(
			''=>'',
			'0'=>'General',
			'1'=>'Supervisor',
			'2'=>'Marketing',
			'3'=>'Keuangan',
			'4'=>'Document',
			'5'=>'Customer Service',
			'6'=>'Manager',
		);
		return json_encode($level);
	}
	
	function get_data_object_flexBox($tbl,$fld,$reg) {
		$this->dbx->select($fld);
		$this->dbx->order_by($fld[1],'asc');
		if($reg==TRUE) :
			$q=$this->dbx->get_where($tbl,array('region_id'=>$this->CI->session->userdata('region_id')));
		else :
			$q=$this->dbx->get($tbl);
		endif;
		if($q->num_rows()>=1) :
			$d=$fld[0];
			$e=$fld[1];
			foreach($q->result() as $a => $b){
				$c[]=array('id'=>$b->$d,'name'=>strtoupper($b->$e));
			}
		else :
			$c=array('id'=>'','name'=>'');
		endif;
		return json_encode($c);
   }
	

	function option_year($db) {
		$thn = array(
			$db-1=>$db-1,
			$db => $db,
			$db+1=>$db+1,
		);
		foreach ($thn as $key => $value) {
			$data['opt'][$key]= $value;
		}
		return $data;
	}
	
	function get_define() {
		define('LOGIN',$this->CI->session->userdata('login'));
		//define('REGID',$this->CI->session->userdata('region_id'));
	}
	
	function tbl_name(){
		$data=array(
			'transaction_bill_of_ladding' 			=> 'tbl_detail',
			'transaction_stuffing_plan' 				=> 'trnstuffingplan',
			'transaction_stuffing_plan_detail' 		=> 'trnstuffingplandetail',
			'transaction_job_sheet'						=> 'a_job_form',
			'transaction_income_sp'						=> 'inf_income_selling_price',
			'transaction_income_coloader'				=> 'inf_income_from_coloader',
			'transaction_income_agent'					=> 'inf_income_from_agent',
			'transaction_cost_satu'						=> 'inf_cost_tbl_satu',
			'transaction_cost_dua'						=> 'inf_cost_refund_discount',
			'transaction_cost_tiga'						=> 'inf_cost_refund_sc',
			'transaction_cost_empat'					=> 'x_glbcost',
		);
		return $data;
	}
	
	/* end */
	
	function get_prev_month($tgl){
		$thn = substr($tgl,0,4);
		$bln = substr($tgl,4,2);
		if($bln=='01'){$bln='12';$thn=$thn-1; return $thn.''.$bln;}
		if($bln=='02'){$bln='01';}
		if($bln=='03'){$bln='02';}
		if($bln=='04'){$bln='03';}
		if($bln=='05'){$bln='04';}
		if($bln=='06'){$bln='05';}
		if($bln=='07'){$bln='06';}
		if($bln=='08'){$bln='07';}
		if($bln=='09'){$bln='08';}
		if($bln=='10'){$bln='09';}
		if($bln=='11'){$bln='10';}
		if($bln=='12'){$bln='11';}
		return $thn.''.$bln;
	}

	function authentic_level($level){
		$session = $this->CI->session->userdata('level');		
		$query = array_search($session, $level, $strict = '' ); 		
		if($query == $session) {
			return true;
		} else {
			return"
			$('input[id=\'btnNew\']').attr('onClick','messages(\'Create New\')');
			$('input[id=\'btnEdit\']').attr('onClick','messages(\'Edit\')');
			$('input[id=\'btnDelete\']').attr('onClick','messages(\'Delete\')');	
			$('input[id=\'btnAddDetail\']').attr('onClick','messages(\'Delete\')');
			$('input[id=\'btnEditDetail\']').attr('onClick','messages(\'Delete\')');
			$('input[id=\'btnDelDetail\']').attr('onClick','messages(\'Delete\')');
			";
		}
   }
   
   function auth($menu,$int){
   	$user = $this->CI->session->userdata('user_id');
   	if($user=='0') {
   		return true;	
   	} else {
   		$this->dbx->select('user_id,user_menu');
   		$this->dbx->from('mstadmin');
   		$this->dbx->where('user_id',$user);
   		$this->dbx->like('user_menu','+'.$menu.'+');
   		$query = $this->dbx->get();
   		if($query->num_rows()==1) {
   			return true;
   		} else {
   			$this->CI->session->set_flashdata('restrict', str_replace('_',' ',$int));
				redirect(base_url('afg_c_stuffing_plan'),'refresh');
			}
   	}
   }
	
	function get_first_id($kepada,$jenis) {
		$region = $this->CI->session->userdata('region_id');
		$this->dbx->select(array('id','region_id'));
		$this->dbx->order_by('id','asc');
		$this->dbx->limit(1);
		$query = $this->dbx->get_where('x_finance',array('region_id'=>$region,'kepada'=>$kepada,'jenis'=>$jenis));
		if ( $query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		else
		{
			return "";		
		}
	}
	
	function get_last_id() {
		$this->dbx->select('id');
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$query = $this->dbx->get('x_finance');
		return $query;
		
	}
		function get_id_terakhir($kepada,$jenis) {
		$region = $this->CI->session->userdata('region_id');
		$this->dbx->select(array('id','region_id'));
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$query = $this->dbx->get_where('x_proforma',array('kepada'=>$kepada,'jenis'=>$jenis));
		return $query;
		
	}
	
	function get_first_id_proses($data) {
		$this->dbx->select(array('id','region_id','fin_proses'));
		$this->dbx->order_by('id','asc');
		$this->dbx->limit(1);
		$query = $this->dbx->get_where('x_finance',$data);
		if ( $query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		else
		{
			return "";		
		}
	}
	
	function get_last_id_proses($data) {
		$this->dbx->select(array('id','region_id','fin_proses'));
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$query = $this->dbx->get('x_finance');
		if ( $query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		else
		{
			return "";		
		}
	}

	function get_sales($job) {
		$this->dbx->select('kode_ref,sales',false);
		$query = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$job));
		if ( $query->num_rows() > 0)
		{
			$user = $query->row()->sales;
			return $this->get_user_by_id($user);
		}
		else
		{
			return "";		
		}
	}
	
	function get_kurs($job) {
		$this->dbx->select('kode_ref,sp_id',false);
		$query = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$job));
		if ( $query->num_rows() > 0)
		{
			$spid = $query->row()->sp_id;
			return $this->get_kurs_spid($spid);
		}
		else
		{
			return "";		
		}
	}
	
	function get_kurs_spid($spid) {
		$this->dbx->select('sp_id,kurs',false);
		$query = $this->dbx->get_where('trnstuffingplan',array('sp_id'=>$spid));
		if ( $query->num_rows() > 0)
		{
			return $query->row()->kurs;
		}
		else
		{
			return "";		
		}
	}
	
	function manage_data($data){
		$table = $data['table'];
		if($data['oper']=='add') {
			unset($data['oper']);
			unset($data['table']);
			$this->dbx->insert($table,$data);
		}
		elseif($data['oper']=='edit') {
			unset($data['oper']);
			unset($data['table']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->update($table,$data);
		}
		elseif($data['oper']=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->delete($table);
		}
		if($this->dbx->affected_rows() > 0 ) {
    		return "1";
    		} else {
    		return "0";
    	}
	}
	
	function get_eta($sp_id){
		$query = $this->dbx->get_where('trnexport',array('sp_id'=>$sp_id));
		if ( $query->num_rows() > 0)
		{
			return $query->row()->eta_destination;
		}
		else
		{
			return false;
		}
	}
	
	function get_new_id(){
		$region = $this->CI->session->userdata('region_id');
		$this->dbx->select_max('id');
		$query = $this->dbx->get_where('x_cn_to_shipper',array('region'=>$region));
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->id+1;
		}
	}
	
	function get_id_akhir_official_receipt(){
		$region = $this->CI->session->userdata('region_id');
		$this->dbx->select_max('id');
		$query = $this->dbx->get_where('x_official_receipt',array('region_id'=>$region));
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->id;
		}
	}
	
	function get_id_akhir_faktur_pajak($seri){
		$region = $this->CI->session->userdata('region_id');
		$this->dbx->select_max('id');
		$query = $this->dbx->get_where('x_faktur_pajak',array('seri'=>$seri,'region_id'=>$region));
		if ( $query->result() == '')
		{
			return 1;
		}
		else
		{
			return $query->row()->id;
		}
	}
	
function get_nomer_invoice($data) {
    $y = substr($data["job_no"], 7, 2);
    $region = $this->CI->session->userdata('region_id');
    $jenis = $data["jenis"];

    if ($jenis == 0) return false;

    // --- 1. Tentukan Prefix, Panjang Prefix ($q), dan Tabel ---
    $config = $this->_get_invoice_config($jenis, $region, $data["job_no"], $y);
    $prefix = $config['prefix']; // Contoh: "INFP/26"
    $q = $config['q'];           // Panjang prefix
    $table = $config['table'];   // x_finance atau x_proforma

    // --- 2. Gunakan Transaction & Locking ---
    // Kita mulai transaksi untuk mengunci baris agar tidak dibaca user lain saat proses
    $this->dbx->trans_begin();

    $this->dbx->select('number');
    $this->dbx->from($table);
    $this->dbx->like('number', $prefix, 'after');
    $this->dbx->where("region_id", $region);
    $this->dbx->order_by('number', 'DESC');
    $this->dbx->limit(1);
    
    // FOR UPDATE akan mengunci baris ini di level Database (MySQL/PostgreSQL)
    $query = $this->dbx->get(null, null, null, false); 
    // Catatan: Jika CI3 tidak support string 'FOR UPDATE' di get(), gunakan:
    // $query = $this->dbx->query($this->dbx->last_query() . " FOR UPDATE");

    if ($query->num_rows() > 0) {
        $last_number = $query->row()->number;
        // Ambil bagian angka setelah prefix
        $current_val = (int) str_replace($prefix, '', $last_number);
        $next_val = $current_val + 1;
    } else {
        // Jika belum ada nomor sama sekali untuk prefix/tahun ini
        $next_val = 1;
    }

    // Selesaikan transaksi
    if ($this->dbx->trans_status() === FALSE) {
        $this->dbx->trans_rollback();
        return false;
    } else {
        $this->dbx->trans_commit();
        return $next_val;
    }
}

/**
 * Helper untuk memisahkan logika penentuan prefix yang sangat panjang
 */
private function _get_invoice_config($jenis, $region, $job_no, $y) {
    $table = 'x_finance';
    $prefix = "";
    $q = 0;

    switch ($jenis) {
        case 5:
            $table = 'x_proforma';
            $prefix = "PRFP/$y";
            $q = 7;
            break;
            
        case 4:
            $table = 'x_proforma';
            $prefix = "PRIV/$y";
            $q = 7;
            break;
            
        case 3:
            $map = [
                "Cikarang"      => ["ICFP/", 7],
                "Medan"         => ["MINFP/", 8],
                "Surabaya"      => ["SUBFP/", 8],
                "GCL WAREHOUSE" => ["INWHM/", 8],
                "Semarang"      => ["SMGFP/", 8],
                "Bandung"       => ["BINFP/", 8],
                "Makassar"      => ["MKSINFP/", 10]
            ];
            // Default jika region tidak ada di list adalah INFP/
            $res = isset($map[$region]) ? $map[$region] : ["INFP/", 7];
            $prefix = $res[0] . $y;
            $q = $res[1];
            break;
            
        case 2:
            $map = [
                "Medan"    => ["MINR/", 7],
                "Surabaya" => ["SINR/", 7],
                "Semarang" => ["SRINR/", 8],
                "Bandung"  => ["BINR/", 7],
                "Cikarang" => ["CINR/", 7],
                "Makassar" => ["MKSINR/", 9]
            ];
            // Default jika region tidak ada di list adalah INR/
            $res = isset($map[$region]) ? $map[$region] : ["INR/", 6];
            $prefix = $res[0] . $y;
            $q = $res[1];
            break;
            
        case 1:
            $job_sub = substr($job_no, 4, 3);
            
            // KELOMPOK 1: Job 900, 400, 401, 402
            if (in_array($job_sub, ["900", "901", "902", "400", "401", "402"])) {
                $map = [
                    "Cikarang"      => ["INC/", 6],
                    "Makassar"      => ["MKSIN/", 8],
                    "Surabaya"      => ["SYINF/", 8],
                    "GCL WAREHOUSE" => ["INWHM/", 8],
                    "Semarang"      => ["SMINF/", 8],
                    "Medan"         => ["MINF/", 7],
                    "Bandung"       => ["BINF/", 7],
                    "Jakarta"       => ["INV/", 6]
                ];
                $res = isset($map[$region]) ? $map[$region] : ["INV/", 6];
            } 
            // KELOMPOK 2: Job 700, 701, 702, 703, 800 (INI YANG SEBELUMNYA TERLEWAT)
            else if (in_array($job_sub, ["700", "701", "702", "703", "800"])) {
                $map = [
                    "Jakarta"       => ["INF/", 6],
                    "Cikarang"      => ["INC/", 6],
                    "Makassar"      => ["MKSIN/", 8],
                    "GCL WAREHOUSE" => ["INWHM/", 8],
                    "Surabaya"      => ["SYINF/", 8],
                    "Semarang"      => ["SMINF/", 8],
                    "Medan"         => ["MINF/", 7],
                    "Bandung"       => ["BINF/", 7]
                ];
                $res = isset($map[$region]) ? $map[$region] : ["INF/", 6];
            } 
            // KELOMPOK 3: Selain nomor seri di atas
            else {
                $map = [
                    "Cikarang"      => ["INC/", 6],
                    "Makassar"      => ["MKSIN/", 8],
                    "GCL WAREHOUSE" => ["INWHM/", 8],
                    "Surabaya"      => ["SYINF/", 8],
                    "Semarang"      => ["SMINF/", 8],
                    "Medan"         => ["MINF/", 7],
                    "Bandung"       => ["BINF/", 7],
                    "Jakarta"       => ["IN/", 5]
                ];
                $res = isset($map[$region]) ? $map[$region] : ["IN/", 5];
            }
            
            $prefix = $res[0] . $y;
            $q = $res[1];
            break;
    }

    return ['prefix' => $prefix, 'q' => $q, 'table' => $table];
}
	
	
function get_new_num_inv($data) {
    // Ambil tahun dari session db (misal 'db_2026' -> '26')
    $db_name = $this->CI->session->userdata('db');
    $y = substr($db_name, 2, 2);
    $region = $this->CI->session->userdata('region_id');

    // 1. Definisikan mapping region ke prefix dan panjangnya (q)
    // Format: 'Region Name' => ['Prefix/', Panjang_Q]
    $map = [
        "Cikarang"      => ["INC/", 6],
        "Surabaya"      => ["SYINF/", 8],
        "Semarang"      => ["SMINF/", 8],
        "Medan"         => ["MINF/", 7],
        "Bandung"       => ["BINF/", 7],
        "Makassar"      => ["MKSIN/", 8],
        "GCL WAREHOUSE" => ["INWHM/", 8],
        "Jakarta"       => ["IN/", 5]
    ];

    // Jika region tidak terdaftar, default ke Jakarta atau berikan fallback
    $config = isset($map[$region]) ? $map[$region] : ["IN/", 5];
    $prefix_base = $config[0];
    $q = $config[1];
    $thn = $prefix_base . $y;

    // --- MULAI TRANSAKSI UNTUK MENGHINDARI RUSH CONDITION ---
    $this->dbx->trans_begin();

    // 2. Query untuk mengambil nomor terakhir dengan Locking (FOR UPDATE)
    $this->dbx->select('number');
    $this->dbx->from('x_finance');
    $this->dbx->like('number', $thn, 'after'); // Pastikan hanya cari yang prefix + tahun ini
    if (!in_array($region, ["Cikarang", "Surabaya", "Bandung", "GCL WAREHOUSE"])) {
        // Tambahkan where region jika di kode lama Anda menggunakan where tersebut
        $this->dbx->where("region_id", $region);
    }
    $this->dbx->order_by('number', 'DESC');
    $this->dbx->limit(1);
    
    // FOR UPDATE memaksa user lain menunggu sampai transaksi ini selesai (Commit/Rollback)
    $query = $this->dbx->get(null, null, null, false);
    // Jika CI3 tidak otomatis menambahkan FOR UPDATE, aktifkan baris di bawah:
    // $query = $this->dbx->query($this->dbx->last_query() . " FOR UPDATE");

    if ($query->num_rows() > 0) {
        $last_full_number = $query->row()->number;
        $prefix_found = substr($last_full_number, 0, $q);

        if ($prefix_found !== $thn) {
            // Jika prefix tahun berbeda (misal loncat tahun), mulai dari angka tertentu
            $next_val = 10; 
        } else {
            // Ambil angka di belakang prefix, lalu tambah 1
            $current_val = (int) str_replace($prefix_found, '', $last_full_number);
            $next_val = $current_val + 1;
        }
    } else {
        // Jika belum ada data sama sekali untuk prefix/tahun ini
        $next_val = 1;
    }

    // --- SELESAIKAN TRANSAKSI ---
    if ($this->dbx->trans_status() === FALSE) {
        $this->dbx->trans_rollback();
        return false;
    } else {
        $this->dbx->trans_commit();
        return $next_val;
    }
}
	
	function get_shipper_byJOB($job){
		$region = $this->CI->session->userdata('region_id');
    	$data = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$job,'region_id'=>$region));
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->shipper;
    	} else {
    		return "";
    	}
	}

	function get_country_id($city){
		$this->dbx->select('*');
		$this->dbx->from('mstcities');
		$this->dbx->where('city_name',$city);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_id;
    	} else {
    		return "";
    	}
	}
	
	function getLastNoInvoice($region){
		$this->dbx->select('*');
		$this->dbx->from('x_inv_number');
		$this->dbx->where('region',$region);
		$this->dbx->order_by('id', 'desc');
		$this->dbx->limit(1);
		return $this->dbx->get();
	}
	
	function get_level($level){
		$q = $this->dbx->get_where('x_level',array('id'=>$level)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->nama;
		} else {
			return"";
		}
	}
	
	function get_user_by_id($id){
		$q = $this->dbx->get_where('mstadmin',array('user_id'=>$id)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->fullname;
		} else {
			return"";
		}
	}
	function get_user_by_id_array($id){
		$ids = explode(",",$id);
		foreach($ids as $key) {    
		$q[] = $this->dbx->get_where('mstadmin',array('user_id'=>$key))->row('fullname'); 
		}
		return $q;
	}
	function get_id_by_username($username){
		$q = $this->dbx->get_where('mstadmin',array('fullname'=>$username)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->user_id;
		} else {
			return"";
		}
	}
	
	function get_id_by_username_array($username){
		$usernames = explode(",",$username);
		foreach($usernames as $key) { 
		$q[] = $this->dbx->get_where('mstadmin',array('fullname'=>$key,'region_id'=>'Jakarta'))->row('user_id');
		}	
		//print_r($this->dbx->last_query());
		//print_r($q);
		return $q;
	}
	
	function get_item_service($code){
		$q = $this->dbx->get_where('reftypeservice',array('typeservice_code'=>$code)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->typeservice_name;
		} else {
			return "";
		}
	}
	
	function get_code_service($nama){
		$q=$this->dbx->get_where('reftypeservice',array('typeservice_name'=>$nama)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->typeservice_code;
		} else {
			return"";
		}
	}
	
	function get_nama_shipper($shipper){
		$q = $this->dbx->get_where('mstshipper',array('shipper_id'=>$shipper)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->shipper_name;
		} else {
			return"";
		}
	}
	
	function get_alamat_shipper($shipper){
		$y= substr($this->CI->session->userdata('db'),2,2);
		if ($y == '25'){
		$CI->second_db = $this->load->database('reference', TRUE);
		$q = $CI->second_db->get_where('mstshipper',array('shipper_id'=>$shipper));
		}else{
		$q = $this->dbx->get_where('mstshipper',array('shipper_id'=>$shipper)); 
		}
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->shipper_address;
		} else {
			return"";
		}
	}
	
	function get_nama_code($table,$kolom,$rownya,$code){
		$q = $this->dbx->get_where($table,array($kolom=>$code,"region_id"=>$this->CI->session->userdata('region_id'))); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->$rownya;
		} else {
			return"";
		}
	}
	
	function get_alamat_code($table,$kolom,$rownya,$code){
		$q = $this->dbx->get_where($table,array($kolom=>$code,"region_id"=>$this->CI->session->userdata('region_id'))); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->$rownya;
		} else {
			return"";
		}
	}
	
	function get_nama_consignee($code){

		$q = $this->dbx->get_where('refconsigneeimport',array('consignee_import_code'=>$code));
		if($q->num_rows() > 0 ) {
			return;
		}
		$q = $this->dbx->get_where('refconsigneenotify',array('consignee_notify_code'=>$code))->row();
		return $q->nama;
	}
	
	function get_alamat_consignee($code){
		$q = $this->dbx->get_where($table,array($kolom=>$code)); 
		if($q->num_rows() > 0) {
			$row = $q->row();
			return $row->$rownya;
		} else {
			return"";
		}
	}
	
	function get_data_stuffhead($code){
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>$code)); 
		if($q->num_rows() > 0) {
			return $q->row_array();
		} else {
			return"";
		}
	}
	
	function bulan()
	{
		$input=date('n');
		if($input=='1'){$output='January';}
		if($input=='2'){$output='February';}
		if($input=='3'){$output='March';}
		if($input=='4'){$output='April';}
		if($input=='5'){$output='May';}
		if($input=='6'){$output='June';}
		if($input=='7'){$output='July';}
		if($input=='8'){$output='August';}
		if($input=='9'){$output='September';}
		if($input=='10'){$output='October';}
		if($input=='11'){$output='November';}
		if($input=='12'){$output='December';}
		return $output;
	}
	
	function bulan1($input)
	{
		if($input=='1'){$output='January';}
		if($input=='2'){$output='February';}
		if($input=='3'){$output='March';}
		if($input=='4'){$output='April';}
		if($input=='5'){$output='May';}
		if($input=='6'){$output='June';}
		if($input=='7'){$output='July';}
		if($input=='8'){$output='August';}
		if($input=='9'){$output='September';}
		if($input=='10'){$output='October';}
		if($input=='11'){$output='November';}
		if($input=='12'){$output='December';}
		return $output;
	}
	
	function bulan2($rrr)
	{
		if($rrr=='1' || $rrr=='01'){$ttt='Januari';}
		if($rrr=='2' || $rrr=='02'){$ttt='Februari';}
		if($rrr=='3' || $rrr=='03'){$ttt='Maret';}
		if($rrr=='4' || $rrr=='04'){$ttt='April';}
		if($rrr=='5' || $rrr=='05'){$ttt='Mei';}
		if($rrr=='6' || $rrr=='06'){$ttt='Juni';}
		if($rrr=='7' || $rrr=='07'){$ttt='Juli';}
		if($rrr=='8' || $rrr=='08'){$ttt='Agustus';}
		if($rrr=='9' || $rrr=='09'){$ttt='September';}
		if($rrr=='10'){$ttt='Oktober';}
		if($rrr=='11'){$ttt='November';}
		if($rrr=='12'){$ttt='Desember';}
		if($rrr==''){$ttt='Tahun';}
		return $ttt;
	}
	
	function bulan3($rrr)
	{
		if($rrr=='1' || $rrr=='01'){$ttt='Jan';}
		if($rrr=='2' || $rrr=='02'){$ttt='Feb';}
		if($rrr=='3' || $rrr=='03'){$ttt='Mar';}
		if($rrr=='4' || $rrr=='04'){$ttt='Apr';}
		if($rrr=='5' || $rrr=='05'){$ttt='Mei';}
		if($rrr=='6' || $rrr=='06'){$ttt='Jun';}
		if($rrr=='7' || $rrr=='07'){$ttt='Jul';}
		if($rrr=='8' || $rrr=='08'){$ttt='Ags';}
		if($rrr=='9' || $rrr=='09'){$ttt='Sep';}
		if($rrr=='10'){$ttt='Okt';}
		if($rrr=='11'){$ttt='Nop';}
		if($rrr=='12'){$ttt='Des';}
		return $ttt;
	}
	
	function date_to_tanggal($in)
	{
		$tgl = substr($in,8,2);
		$bln = substr($in,5,2);
		$thn = substr($in,0,4);
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out=substr($in,8,2)." ".$this->bulan2(substr($in,5,2))." ".substr($in,0,4);
		}
		else
		{
	   	$out = " ";
		}
		return $out;
	}
	
	function date_to_tanggal2($in)
	{
		$tgl = substr($in,8,2);
		$bln = substr($in,5,2);
		$thn = substr($in,0,4);
		
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out=substr($in,8,2)." ".$this->bulan3(substr($in,5,2))." ".substr($in,0,4);
		}
		else
		{
	   	$out = "";
		}
		return $out.' '.$tgl.' '.$bln.' '.$thn;
	}
	
	function date_to_tanggal3($in)
	{
		
		$tgl = substr($in,6,2);
		$bln = substr($in,4,2);
		$thn = substr($in,0,4);
		
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out= $this->bulan1($bln)." ". $tgl.", ". $thn;
		}
		else
		{
	   	$out = "";
		}
		return $out;
	}
	
	function date_to_tanggal4($in)
	{

		$tgl = substr($in,6,2);
		$bln = substr($in,4,2);
		$thn = substr($in,0,4);
		
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out=substr($in,6,2)." ".$this->bulan3(substr($in,4,2))." ".substr($in,0,4);
		}
		else
		{
	   	$out = "";
		}
		return $out;
	}	
	
	function date_to_tanggal5($in)
	{
		$tgl = substr($in,0,2);
		$bln = substr($in,3,2);
		$thn = substr($in,6,4);
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out= $this->bulan($bln)." ". $tgl.", ". $thn;
		}
		else
		{
	   	$out = "";
		}	
		return $out;
	}

	function date_to_tanggal6($in)
	{
		if($in=="") return "";
		$date = explode("/",$in);
		$tgl = $date[0];
		$bln = $date[1];
		$thn = $date[2];
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out= $tgl." ".$this->bulan2($bln)." ". $thn;
		}
		else
		{
	   	$out = "";
		}	
		return $out;
	}
	
	function date_to_report($in)
	{

		$tgl = substr($in,6,2);
		$bln = substr($in,4,2);
		$thn = substr($in,0,4);
		
		if(checkdate($bln,$tgl,$thn))
		{
	   	$out=$tgl."-".$bln."-".$thn;
		}
		else
		{
	   	$out = "";
		}
		return $out;
	}
	
	function getInterval($export_id)
	{
		$date = date('Y-m-d');
		$sql=mysql_query("select ('$date' - INTERVAL 1 week ) as minggu");
		$row = mysql_fetch_array($sql);
		$output=$row['minggu'];
		return $output;
	}
	
	function showAgent()
	{
		$this->CI->load->library(array('user_agent'));
		if ($this->CI->agent->is_browser())
		{
			 $agent = $this->CI->agent->browser().' '.$this->CI->agent->version();
		}
		elseif ($this->CI->agent->is_robot())
		{
			 $agent = $this->CI->agent->robot();
		}
		elseif ($this->CI->agent->is_mobile())
		{
			 $agent = $this->CI->agent->mobile();
		}
		else
		{
			 $agent = 'Not Detected';
		}
		$data['agent']= $agent;
		$data['platform']=$this->CI->agent->platform(); // Platform info (Windows, Linux, Mac, etc.) 
		return $data;
	}
	
	function no_ie(){
		$agen = $this->showAgent();
		$browser = $agen['agent'];
		if(stristr($browser,'internet') != FALSE)
		{
			exit(include "no_ie.php");
		}
	}
	
	function no_mozilla(){
		$agen = $this->showAgent();
		$browser = $agen['agent'];
		if(stristr($browser,'fire') != FALSE)
		{
			exit(include "no_ie.php");
		}
	}
	
	function restrict($logged_out = FALSE){
		if ($logged_out && $this->logged_in()){
		  	return true;
		}
		if ( ! $logged_out && ! $this->logged_in()) 
		{
		 	redirect('','refresh');
		}
   }
   
   function logged_in(){
		if ($this->CI->session->userdata('username') == FALSE) {
			return FALSE;
		} else  {
	  		return TRUE;
		}
	} 
	
	function jin_date_sql($date){
		$exp = explode('/',$date??'');
		if(count($exp) == 3) 
		{
			$date = $exp[2].$exp[1].$exp[0];
		}
		return $date;
	}
	
	function jin_date_str($date){
		if($date!='') {
			$exp1 = substr ($date,0,4);
			$exp2 = substr ($date,4,2);
			$exp3 = substr ($date,6,2);
			return $exp3.'/'.$exp2.'/'.$exp1;
		} else {
			return '';
		}
	}
	
	function jin_date_str2($date){
		if($date!='') {
			$exp1 = substr ($date,0,4);
			$exp2 = substr ($date,4,2);
			$exp3 = substr ($date,6,2);
			return $exp3.'/'.$exp2.'/'.$exp1;
		} else {
			return '';
		}
	}
	
	function jin_date_str3($date){
		if($date!='') {
			$exp1 = substr ($date,0,4);
			$exp2 = substr ($date,4,2);
			$exp3 = substr ($date,6,2);
			return $exp1.'/'.$exp2.'/'.$exp3;
		} else {
			return '';
		}
	}
	
	function jin_date_str_strip($date){
		if($date!='') {
			$exp1 = substr ($date,0,4);
			$exp2 = substr ($date,4,2);
			$exp3 = substr ($date,6,2);
			return $exp3.'-'.$exp2.'-'.$exp1;
		} else {
			return '';
		}
	}
	function jin_date_str_strip2($date){
		if($date!='') {
			$exp1 = substr ($date,0,4);
			$exp2 = substr ($date,5,2);
			$exp3 = substr ($date,8,2);
			return $exp1.$exp2.$exp3;
		} else {
			return '';
		}
	}
	
	function terbilang($bilangan){
    if($bilangan=='' || $bilangan==0)
      return "nol";
      $angka = array('0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0');
      $kata = array('','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan');
      $tingkat = array('','ribu','juta','milyar','triliun');
    	$panjang_bilangan = strlen($bilangan);
    
      /* pengujian panjang bilangan */
      if ($panjang_bilangan > 15) {
        $kalimat = "Diluar Batas";
        return $kalimat;
      }
    
      /* mengambil angka-angka yang ada dalam bilangan,
      dimasukkan ke dalam array */
      for ($i = 1; $i <= $panjang_bilangan; $i++) {
        $angka[$i] = substr($bilangan,-($i),1);
      }
    
      $i = 1;
      $j = 0;
      $kalimat = "";
    
    
      /* mulai proses iterasi terhadap array angka */
      while ($i <= $panjang_bilangan) {
    
        $subkalimat = "";
        $kata1 = "";
        $kata2 = "";
        $kata3 = "";
    
        /* untuk ratusan */
        if ($angka[$i+2] != "0") {
          if ($angka[$i+2] == "1") {
            $kata1 = "seratus";
          } else {
            $kata1 = $kata[$angka[$i+2]] . " ratus";
          }
        }
    
        /* untuk puluhan atau belasan */
        if ($angka[$i+1] != "0") {
          if ($angka[$i+1] == "1") {
            if ($angka[$i] == "0") {
              $kata2 = "sepuluh";
            } elseif ($angka[$i] == "1") {
              $kata2 = "sebelas";
            } else {
              $kata2 = $kata[$angka[$i]] . " belas";
            }
          } else {
            $kata2 = $kata[$angka[$i+1]] . " puluh";
          }
        }
    
        /* untuk satuan */
        if ($angka[$i] != "0") {
          if ($angka[$i+1] != "1") {
            $kata3 = $kata[$angka[$i]];
          }
        }
    
        /* pengujian angka apakah tidak nol semua,
           lalu ditambahkan tingkat */
        if (($angka[$i] != "0") OR ($angka[$i+1] != "0") OR
            ($angka[$i+2] != "0")) {
          $subkalimat = "$kata1 $kata2 $kata3 " . $tingkat[$j] . " ";
        }
    
        /* gabungkan variabe sub kalimat (untuk satu blok 3 angka)
           ke variabel kalimat */
        $kalimat = $subkalimat . $kalimat;
        $i = $i + 3;
        $j = $j + 1;
    
      }
    
      /* mengganti satu ribu jadi seribu jika diperlukan */
      if (($angka[5] == "0") AND ($angka[6] == "0")) {
        $kalimat = str_replace("satu ribu","seribu",$kalimat);
      }    
      return trim($kalimat);
	}
	
	function get_num_name($num){
        switch($num){
            case 1:return 'ONE';
	    		case 2:return 'TWO';
	    		case 3:return 'THREE';
	    		case 4:return 'FOUR';
	    		case 5:return 'FIVE';
	    		case 6:return 'SIX';
	    		case 7:return 'SEVEN';
	    		case 8:return 'EIGHT';
	    		case 9:return 'NINE';
        }
   }

   function num_to_words($number, $real_name, $decimal_digit, $decimal_name){
        $res = '';
        $real = 0;
        $decimal = 0;

        if($number == 0)
            return ''.(($real_name == '')?'':' '.$real_name);
        if($number >= 0){
            $real = floor($number);
            $decimal = number_format(floatval($number) - floatval($real), floatval($decimal_digit), '.', ',');
        }else{
            $real = ceil(floatval($number)) * (-1);
            $number = abs(floatval($number));
            $decimal = number_format(floatval($number) - $real, $decimal_digit, '.', ',');
        }
        $decimal = substr($decimal, strpos($decimal, '.') +1);

		  $unit_name[0] = '';
        $unit_name[1] = 'THOUSAND';
        $unit_name[2] = 'MILLION';
        $unit_name[3] = 'BILLION';
        $unit_name[4] = 'TRILLION';

        $packet = array();	

        $number = strrev($real);
        $packet = str_split($number,3);

        for($i=0;$i<count($packet);$i++){
            $tmp = strrev($packet[$i]);
            $unit = $unit_name[$i];
            if((int)$tmp == 0)
                continue;
            $tmp_res = '';
            if(strlen($tmp) >= 2){
                $tmp_proc = substr($tmp,-2);
                switch($tmp_proc){
                    case '10':
                        $tmp_res = 'TEN';
                        break;
                    case '11':
                        $tmp_res = 'ELEVEN';
                        break;
                    case '12':
                        $tmp_res = 'TWELVE';
                        break;
                    case '13':
                        $tmp_res = 'THIRTEEN';
                        break;
                    case '15':
                        $tmp_res = 'FIFTEEN';
                        break;
                    case '18':
                        $tmp_res = 'EIGHTEEN';
                        break;
                    case '20':
                        $tmp_res = 'TWENTY';
                        break;
                    case '30':
                        $tmp_res = 'THIRTY';
                        break;
                    case '40':
                        $tmp_res = 'FORTY';
                        break;
                    case '50':
                        $tmp_res = 'FIFTY';
                        break;
                    case '70':
                        $tmp_res = 'SEVENTY';
                        break;
                    case '80':
                        $tmp_res = 'EIGHTY';
                        break;
                    default:
                        $tmp_begin = substr($tmp_proc,0,1);
                        $tmp_end = substr($tmp_proc,1,1);

                        if($tmp_begin == '1')
                            $tmp_res = $this->get_num_name($tmp_end).'TEEN';
                        elseif($tmp_begin == '0')
                            $tmp_res = $this->get_num_name($tmp_end);
                        elseif($tmp_end == '0')
                            $tmp_res = $this->get_num_name($tmp_begin).'TY';
                        else{
                            if($tmp_begin == '2')
                                $tmp_res = 'TWENTY';
                            elseif($tmp_begin == '3')
                                $tmp_res = 'THIRTY';
                            elseif($tmp_begin == '4')
                                $tmp_res = 'FORTY';
                            elseif($tmp_begin == '5')
                                $tmp_res = 'FIFTY';
                            elseif($tmp_begin == '6')
                                $tmp_res = 'SIXTY';
                            elseif($tmp_begin == '7')
                                $tmp_res = 'SEVENTY';
                            elseif($tmp_begin == '8')
                                $tmp_res = 'EIGHTY';
                            elseif($tmp_begin == '9')
                                $tmp_res = 'NINETY';

                            $tmp_res = $tmp_res.' '. $this->get_num_name($tmp_end);
                        }
                        break;
                }

                if(strlen($tmp) == 3){
                    $tmp_begin = substr($tmp,0,1);

                    $space = '';
                    if(substr($tmp_res??'',0,1) != ' ' && $tmp_res != '')
                        $space = ' ';

                    if($tmp_begin != 0){
                        if($tmp_begin != '0'){
                            if($tmp_res != '')
                                $tmp_res = 'AND'.$space.$tmp_res;
                        }
                        $tmp_res = $this->get_num_name($tmp_begin).' HUNDRED'.$space.$tmp_res;
                    }
                }
            }else
                $tmp_res = $this->get_num_name($tmp);
            $space = '';
            if(substr($res,0,1) != ' ' && $res != '')
                $space = ' ';
            $res = $tmp_res.' '.$unit.$space.$res;
        }

        $space = '';
        if(substr($res,-1) != ' ' && $res != '')
            $space = ' ';

        if($res)
            $res .= $space.$real_name.(($real > 1 && $real_name != '')?'s':'');

        if($decimal > 0)
            $res .= ' '. num_to_words($decimal, '', 0, '').' '.$decimal_name.(($decimal > 1 && $decimal_name != '')?'s':'');
        return ucfirst($res);
   }
    
   function cek_export($sp_id){
		$q = $this->dbx->get_where('trnstuffingplan',array('sp_id'=>$sp_id,'kode_ref'=>'','closing_date_time >'=>date('Ymd')));
		if($q->num_rows() > 0 ) {
			return "1";	
		}
		else 
		{
			return "0";
		}
	}
	
	function delete_schedule_expired(){
		$q = $this->dbx->get_where('trnstuffingplan',array('kode_ref'=>'','closing_date_time <='=>date('Ymd'))); 
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			$sp_id = $row->sp_id;
			$this->dbx->select('*'); 
			$this->dbx->from('trnexport');
			$this->dbx->join('trnshipmentrequest','trnshipmentrequest.export_id=trnexport.export_id');
			$this->dbx->where('trnexport.sp_id',$sp_id);
			$q2 =  $this->dbx->get();
			if($q->num_rows() > 0 ) {
				return "schedule not deleted because there are any data request";
			} else {
					$this->dbx->delete('trnstuffingplan',array('sp_id'=>$sp_id));
					$this->dbx->delete('trnexport',array('sp_id'=>$sp_id));
					$this->dbx->delete('trnexportconecting',array('sp_id'=>$sp_id));
					if($this->dbx->affected_rows() > 0) 
					{	
						return "schedule expired deleted";
					}
					else 
					{
						return "schedule expired not deleted";
					}
			}
		}
		else 
		{
			return "no schedule expired";
		}
	}
	
	function delete_trucking(){
	//	$this->dbx->delete('x_tracking',array('actual_eta !='=>'0000-00-00','actual_eta <='=>date('Y-m-d'))); 
	}
	
	function pecah($uang,$delimiter='.'){
		if($uang == '' || $uang == 0 ){
			$rupiah = '';
			return $rupiah;
		}
		$neg = false;
		if($uang<0){
			$neg = true;
			$uang = abs($uang);
		}
		$rupiah = number_format(floatval($uang),2,'.',$delimiter);
		
		if($neg){
			$rupiah = '- '.$rupiah;
		}
		return $rupiah;
	}
	
	function pecah2($uang,$delimiter='.'){
		return $uang;
	}
	
	function pecah3($uang,$delimiter='.'){
		if($uang == '' || $uang == 0 ){
			$rupiah = '';
			return $rupiah;
		}
		$neg = false;
		if($uang<0){
			$neg = true;
			$uang = abs($uang);
		}
		$rupiah = number_format(floatval($uang),3);
		
		if($neg){
			$rupiah = '- '.$rupiah;
		}
		return $rupiah;
	}
	
	function pecah4($uang,$delimiter='.'){
		
		return $uang;
		
		if($uang == '' || $uang == 0 ){
			$rupiah = '';
			return $rupiah;
		}
		$neg = false;
		if($uang<0){
			$neg = true;
			$uang = abs($uang);
		}
		$rupiah = number_format($uang,4,'.',$delimiter);
		
		if($neg){
			$rupiah = '- '.$rupiah;
		}
		return $rupiah;
	}
	
	function odbc_define() {
		define('ODBC_DSN', $this->CI->session->userdata('odbc_dsn'));
		define('ODBC_HOST', $this->CI->session->userdata('odbc_host'));
		define('ODBC_USR', $this->CI->session->userdata('odbc_usr'));
		define('ODBC_PWD', $this->CI->session->userdata('odbc_pwd'));
	}
	
}
?>