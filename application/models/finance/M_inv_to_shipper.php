<?php
class M_inv_to_shipper extends CI_Model
{
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
    }
	
   
	function get_address($data,$index) {
		$this->dbx->select('shipper_id,shipper_name,shipper_address,region_id');
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get_where("mstshipper",$data);	
		if($q->num_rows() == 1) {
			if($index=='code') {
			return $q->result()[0]->shipper_id;
			}
			 else 
			{
			return array ('status'=>1,'data'=>$q->result());	
			}
				
		} else {
			
			if($index=='code') {
				return '';
			}
			
			return array ('status'=>0);
		}
	}

	function get_kurs($kode)
	{
		$kode = trim((string)$kode);

		if ($kode === '') {
			return 0;
		}

		$jobno = substr($kode, 0, 15);
		$region = trim((string)$this->session->userdata('region_id'));

		$this->dbx->select('kode_ref,kurs');
		$this->dbx->from('trnstuffingplan');
		$this->dbx->where('kode_ref', $jobno);

		if ($region !== '') {
			$this->dbx->where('region_id', $region);
		}

		$row = $this->dbx->limit(1)->get()->row();

		return $row ? (float)$row->kurs : 0;
	}


	function get_x_invoice($data)
	{
		$q = $this->dbx->get_where("x_invoice",array("number"=>$data[0],"region_id"=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) 
		{
			$set=$data[1];
			return $q->row()->$set;
		}
		else 
		{
			return " ";
		}
	}
	
	function get_pph($data)
	{
		$q = $this->dbx->get_where("x_finance",array("number"=>$data[0],"region_id"=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) 
		{
			return $q->row()->pph23;
		}
		else 
		{
			return " ";
		}
	}
	
	function get_code_pajak($data)
	{
		$this->dbx->select('code');
		$this->dbx->where_in('x_finance_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2'));
		$q = $this->dbx->get_where("x_finance_item",array("number"=>$data,"region_id"=>$this->session->userdata('region_id')));

		if($q->num_rows()>0) 
		{
		$tax = $q->row()->code;
		if ($tax == 'TAX 11'){
		$vat = 'PPN 11%';	
		}else if($tax == 'TAX 1.1'){
		$vat = 'PPN 1.1%';	
		}else if($tax == 'TAX 12'){
		$vat = 'PPN 12%';	
		}else if($tax == 'TAX 1.2'){
		$vat = 'PPN 1.2%';	
		}
			return $vat;
		}
		else 
		{
			return " ";
		}
	}
		
	function get_ttl_inv($id,$cur){
		$this->dbx->select('price');
		$query = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($query->num_rows() > 0 ) {
			foreach($query->result_array() as $row => $key)
			{
				//$ttl[] = $key['qty']*$key['price'];
				$ttl[] = $key['price'];
			}
			return $ttl;
		} else {
			return null;	
		}
	}
	
	function get_ttl_inv_pph($id,$cur){
		$this->dbx->select('price');
		$this->dbx->where_not_in('x_finance_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2'));
		$query = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($query->num_rows() > 0 ) {
			foreach($query->result_array() as $row => $key)
			{
				//$ttl[] = $key['qty']*$key['price'];
				$ttl[] = $key['price'];
			}
			return $ttl;
		} else {
			return null;	
		}
	}
	
	function get_ttl_tax_pph($id,$cur){
		$this->dbx->select('price');
		$this->dbx->where_in('x_finance_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2'));
		$query = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($query->num_rows() > 0 ) {
			foreach($query->result_array() as $row => $key)
			{
				//$ttl[] = $key['qty']*$key['price'];
				$ttl[] = $key['price'];
			}
			return $ttl;
		} else {
			return null;	
		}
	}
	
	function get_ttl_db_note($id,$cur){
		$this->dbx->select('price');
		$query = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($query->num_rows() > 0 ) {
			foreach($query->result_array() as $row => $key)
			{
				$ttl[] = $key['price'];
			}
			return $ttl;
		} else {
			return null;	
		}
	}
	
	function get_blue($code) {
		$q = $this->dbx->get_where('x_finance',array('job_no'=>$code,'jenis'=>'INVOICE','region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function get_blue_baru($code) {
    // Ambil semua baris sesuai kode_ref
    $q = $this->dbx->get_where('inf_income_selling_price', [
        'kode_ref' => $code
    ]);

    if ($q->num_rows() == 0) {
        return 0; // tidak ada data sama sekali
    }

    $allOne = true;
    $allZero = true;

    foreach ($q->result_array() as $row) {
        if ($row['ditarik'] == 1) {
            $allZero = false;
        } else {
            $allOne = false;
        }
    }

    if ($allOne) {
        return 1; // semua 1
    } elseif ($allZero) {
        return 0; // semua 0
    } else {
        return 2; // campuran
    }
}
	
	function get_kunci($code)
{
    $region = trim((string)$this->session->userdata('region_id'));
    $code   = trim((string)$code);

    if ($code === '') {
        return 0;
    }

    /*
     * ==========================================================
     * JAKARTA
     * ==========================================================
     * Logic lama tetap dipertahankan.
     */
    if (strcasecmp($region, 'Jakarta') === 0) {

        $this->dbx->limit(1);

        $query = $this->dbx->get_where('inf_income', array(
            'kode_ref'  => $code,
            'region_id' => $region
        ));

        if ($query->num_rows() > 0) {

            $created_by = trim((string)$query->row()->created_by);

            if ($created_by !== '') {
                return 1;
            }
        }

        return 0;
    }


    /*
     * ==========================================================
     * NON JAKARTA
     * ==========================================================
     *
     * Jika kode_ref sudah mempunyai data di SALAH SATU tabel
     * berikut, anak job tidak boleh dihapus.
     *
     * Jika kode_ref tidak ditemukan sama sekali, boleh dihapus.
     */
    $tables = array(
        'inf_cost_refund_discount',
        'inf_cost_refund_sc',
        'inf_cost_tbl_satu',
        'inf_income_from_agent',
        'inf_income_from_coloader',
        'inf_income_selling_price'
    );

    foreach ($tables as $table) {

        /*
         * Safety apabila deployment cabang tertentu
         * belum mempunyai salah satu tabel.
         */
        if (!$this->dbx->table_exists($table)) {
            continue;
        }

        $this->dbx->select('id');
        $this->dbx->from($table);
        $this->dbx->where('kode_ref', $code);
        $this->dbx->where('region_id', $region);
        $this->dbx->limit(1);

        $query = $this->dbx->get();

        if ($query->num_rows() > 0) {
            return 1;
        }
    }

    /*
     * Tidak ada data terkait kode_ref di semua tabel.
     * Anak job boleh dihapus.
     */
    return 0;
}
	
	function get_kosong($code) {
		$q = $this->dbx->get_where('trnstuffingplandetail',array('code'=>$code,'region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) {
			return 1;
		} else {
			return 0;
		}
	}

	function get_destination($kode)
	{
		$kode = trim((string)$kode);

		if ($kode === '') {
			return [
				'destination' => '',
				'incoterms'   => '',
			];
		}

		$region = trim((string)$this->session->userdata('region_id'));

		$this->dbx->select('kode_ref,destination,incoterms');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where('kode_ref', $kode);

		if ($region !== '') {
			$this->dbx->where('region_id', $region);
		}

		$row = $this->dbx->limit(1)->get()->row();

		return [
			'destination' => $row ? (string)$row->destination : '',
			'incoterms'   => $row ? (string)$row->incoterms : '',
		];
	}

	function get_table_satu($post,$limit){
		$this->dbx->select(array(
           'x_finance.id as id',
           'x_finance.job_no as job_no',
           'x_finance.jenis as jenis',
		   'x_finance.number as number',
		   'x_finance.date as date',
           'x_invoice.shipper_name as shipper_name',
		   'x_finance.group as group',
           'x_finance.kurs as kurs',
           'x_finance.status as status',
           'x_finance.grand_total as grand_total',
           'x_finance.created_by as created_by',
           'x_finance.region_id as region_id',
      ));
		$this->dbx->join('x_invoice','x_invoice.number=x_finance.number','left');
		$this->dbx->order_by($post['sidx'], $post['sord']);
		/* if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);	
		} */
		return $this->dbx->get_where('x_finance',array('x_finance.job_no'=>$post['job_no'],'x_finance.jenis'=>'INVOICE','x_finance.region_id'=>$this->session->userdata('region_id')))->result();
	}
	
	// Kolom yang boleh dipakai untuk sorting/searching di grid get_table_dua.
	// Whitelist ini juga jadi pertahanan terhadap SQL injection lewat sidx/searchField.
	// Termasuk versi "table.column" karena colModel di v_inv_to_shipper_script.php
	// memakai index seperti 'trnstuffingplandetail.kode_ref' untuk sort by klik header.
	private $table_dua_allowed_columns = array(
		'kode_ref', 'carrier', 'vessel', 'voyage', 'kurs', 'sp_id', 'tanggal_job',
		'region_id', 'sp_detail_id', 'code', 'code_job', 'shipper', 'shipper_print',
		'note', 'sales', 'destination', 'shipper_import_code', 'shipper_import',
		'shipper_import_add', 'incoterms', 'house_bl',
		'trnstuffingplandetail.kode_ref', 'trnstuffingplandetail.destination',
		'trnstuffingplan.kode_ref', 'trnstuffingplan.carrier', 'trnstuffingplan.vessel',
		'trnstuffingplan.tanggal_job',
	);

	// Builder dasar (from/join/where/search) dipisah supaya bisa dipakai ulang
	// baik untuk hitung total (count) maupun ambil data (select+limit),
	// tanpa harus menjalankan query JOIN penuh dua kali.
	private function _table_dua_base_query($post){
		$this->dbx->from('trnstuffingplan');
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.code=trnstuffingplan.kode_ref');
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
		$this->dbx->where('trnstuffingplandetail.sp_id !=',0);

		if(isset($post['_search']) && $post['_search']=='true') {
			$searchField = $post['searchField'];
			$searchString = $post['searchString'];
			$searchOper  =  $post['searchOper'];

			if($searchField=='note') $searchField='shipper_print';

			// Hanya proses jika kolom ada di whitelist -> mencegah SQL injection
			// lewat searchField dan menghindari where() dengan string mentah.
			if(in_array($searchField, $this->table_dua_allowed_columns, true)) {

				$ops = array(
					'eq'=>'=', 'ne'=>'!=', 'lt'=>'<', 'le'=>'<=', 'gt'=>'>', 'ge'=>'>=',
				);
				$like_ops = array('bw','bn','in','ni','ew','en','cn','nc');

				if(array_key_exists($searchOper, $ops)) {
					$this->dbx->where($searchField.' '.$ops[$searchOper], $searchString);
				} elseif(in_array($searchOper, $like_ops, true)) {
					if($searchOper=='bw') $this->dbx->like($searchField, $searchString, 'after');
					if($searchOper=='bn') { $this->dbx->not_like($searchField, $searchString, 'after'); }
					if($searchOper=='ew') $this->dbx->like($searchField, $searchString, 'before');
					if($searchOper=='en') { $this->dbx->not_like($searchField, $searchString, 'before'); }
					if($searchOper=='cn' || $searchOper=='in') $this->dbx->like($searchField, $searchString, 'both');
					if($searchOper=='nc' || $searchOper=='ni') { $this->dbx->not_like($searchField, $searchString, 'both'); }
				}
			}
		}
	}

	// Hitung total baris untuk paging. Pakai count_all_results() bawaan CI
	// (SELECT COUNT(*) atas builder yang sama) alih-alih menjalankan
	// query JOIN penuh tanpa limit lalu menghitung array-nya di PHP.
	function count_table_dua($post){
		$this->_table_dua_base_query($post);
		return $this->dbx->count_all_results();
	}

	function get_table_dua($post,$limit){
		$this->_table_dua_base_query($post);

		$this->dbx->select(array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.carrier as carrier',
				'trnstuffingplan.vessel as vessel',
				'trnstuffingplan.voyage as voyage',
				'trnstuffingplan.kurs as kurs',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplan.tanggal_job as tanggal_job',
				'trnstuffingplan.region_id as region_id',
				'trnstuffingplandetail.sp_detail_id as sp_detail_id',
				'trnstuffingplandetail.code as code',
				'trnstuffingplandetail.kode_ref as code_job',
				'trnstuffingplandetail.shipper as shipper',
				'trnstuffingplandetail.shipper_print as shipper_print',
				'trnstuffingplandetail.note as note',
				'trnstuffingplandetail.sales as sales',
				'trnstuffingplandetail.destination as destination',
				'trnstuffingplandetail.shipper_import_code as shipper_import_code',
				'trnstuffingplandetail.shipper_import as shipper_import',
				'trnstuffingplandetail.shipper_import_add as shipper_import_add',
				'trnstuffingplandetail.incoterms as incoterms',
				'trnstuffingplandetail.hb_l as house_bl',
		));

		$sidx = in_array($post['sidx'], $this->table_dua_allowed_columns, true) ? $post['sidx'] : 'trnstuffingplan.tanggal_job';
		$sord = strtolower($post['sord'])=='asc' ? 'asc' : 'desc';
		$this->dbx->order_by($sidx, $sord);

		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}
		return $this->dbx->get()->result();
	}

	// ==== Batch lookup untuk menggantikan pemanggilan per-baris (N+1 query) ====
	// Dipanggil sekali per halaman grid dengan kumpulan id/nama dari semua baris,
	// hasilnya di-map di PHP saat membangun response, bukan query per baris.

	function map_nama_sales(array $ids){
		$ids = array_values(array_unique(array_filter($ids, function($v){ return $v !== null && $v !== ''; })));
		if(empty($ids)) return array();

		$this->dbx->select(array('user_id','fullname'));
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->where_in('user_id',$ids);
		$rows = $this->dbx->get('mstadmin')->result();

		$map = array();
		foreach($rows as $r) { $map[$r->user_id] = $r->fullname; }
		return $map;
	}

	// Gabungan get_code_shipper + get_alamat_shipper + get_address('code'):
	// ketiganya query mstshipper dengan shipper_name yang sama, jadi cukup 1 query.
	// Tiap hasil menyertakan match_count supaya controller bisa mereplikasi
	// perilaku get_address('code') yang HANYA mengembalikan shipper_id
	// kalau nama shipper itu persis 1 kecocokan (ambigu -> kosong),
	// sementara get_code_shipper lama tetap ambil kecocokan pertama walau duplikat.
	function map_shipper_info(array $names){
		$names = array_values(array_unique(array_filter($names, function($v){ return $v !== null && $v !== ''; })));
		if(empty($names)) return array();

		$this->dbx->select(array('shipper_id','shipper_name','shipper_address'));
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->where_in('shipper_name',$names);
		$rows = $this->dbx->get('mstshipper')->result();

		$grouped = array();
		foreach($rows as $r) { $grouped[$r->shipper_name][] = $r; }

		$map = array();
		foreach($grouped as $name => $matches) {
			$first = $matches[0];
			$first->match_count = count($matches);
			$map[$name] = $first;
		}
		return $map;
	}

	function map_blue_baru(array $codes)
{
    $codes = array_values(array_unique(array_filter($codes, function($v) {
        return $v !== null && $v !== '';
    })));

    if (empty($codes)) {
        return array();
    }

    /*
     * Hanya income yang benar-benar punya nilai tagihan
     * yang ikut menentukan warna job.
     *
     * price = 0 diabaikan.
     */
    $this->dbx->select(array(
        'kode_ref',
        'ditarik'
    ));

    $this->dbx->where_in('kode_ref', $codes);
    $this->dbx->where('region_id', $this->session->userdata('region_id'));

    // Hanya billable shipper invoice yang menentukan warna.
    $this->dbx->where('COALESCE(price, 0) <> 0', null, false);
    $this->dbx->where('prep_coll', 'PREPAID');
    $this->dbx->where('invoice_db', 'INVOICE');

    $rows = $this->dbx
        ->get('inf_income_selling_price')
        ->result();

    $grouped = array();

    foreach ($rows as $r) {
        $grouped[$r->kode_ref][] = (int)$r->ditarik;
    }

    $map = array();

    foreach ($grouped as $code => $values) {

        $allOne  = true;
        $allZero = true;

        foreach ($values as $ditarik) {

            if ((int)$ditarik === 1) {
                $allZero = false;
            } else {
                $allOne = false;
            }
        }

        /*
         * 1 = semua billable income sudah ditagihkan  -> WHITE
         * 0 = semua billable income belum ditagihkan -> BLUE
         * 2 = sebagian sudah / sebagian belum         -> YELLOW
         */
        if ($allOne) {
            $map[$code] = 1;
        } elseif ($allZero) {
            $map[$code] = 0;
        } else {
            $map[$code] = 2;
        }
    }

    return $map;
}
		
	function get_tabel_empat($post,$limit){
		
		if($post['sidx']=='nama_shipper' || $post['sidx']=='alamat_shipper') {
      	$post['sidx'] = 'kepada_kode';
      }
      
		$region = $this->session->userdata('region_id');
		$this->dbx->order_by($post['sidx'],$post['sord']);
		$this->dbx->limit($post['rows'],$post['start']);
		
		if($post['_search']=='true') {
			$searchField = $post['searchField'];
			$searchString = $post['searchString'];
         $searchOper  =  $post['searchOper'];
			
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
			$this->dbx->limit($post['rows'],$post['start']);
		}
	
		$this->dbx->order_by($post['sidx'],$post['sord']);
		return $this->dbx->get_where('x_finance',array('jenis'=>'INVOICE','kepada'=>'SHIPPER','region_id'=>$region))->result();
	}
	
	function get_proses($data){
		$job = substr($data['job_no'],4,1);
		$q = $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$data['job_no']));
		if($job != '3' && $q->num_rows() > 0 ) {
			return 'proses';
		} else {
			return 'not_proses';
		}
	}

	function getListGroup($data){
		$table = $data['table'];
		unset($data['table']);
		$this->dbx->select('kode_ref,g,ditarik,invoice_db',false);
		$q = $this->dbx->get_where($table,$data);
		if($q->num_rows() > 0 ) {
			return $q;
		} else {
			return false;
		}
	}
	
	function ppn_half_up_int(int $dpp, int $num, int $den): int {
		return intdiv($dpp * $num + intdiv($den, 2), $den);
	}
	
	private function _invoice_source_ids_mapped_elsewhere($table, array $ids, $finance_id, $region)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$finance_id = (int)$finance_id;
		$region = trim((string)$region);

		if (
			empty($ids)
			|| $finance_id <= 0
			|| $region === ''
			|| !$this->dbx->table_exists('x_finance_item')
		) {
			return [];
		}

		$blocked = [];

		if (
			$this->dbx->field_exists('income_source_table', 'x_finance_item')
			&& $this->dbx->field_exists('income_source_id', 'x_finance_item')
		) {
			$rows = $this->dbx
				->select('income_source_id')
				->where('region_id', $region)
				->where('number !=', $finance_id)
				->where('income_source_table', $table)
				->where_in('income_source_id', $ids)
				->get('x_finance_item')
				->result_array();

			foreach ($rows as $r) {
				$id = (int)($r['income_source_id'] ?? 0);
				if ($id > 0) $blocked[] = $id;
			}
		}

		if (
			$table === 'inf_income_selling_price'
			&& $this->dbx->field_exists('source_income_id', 'x_finance_item')
		) {
			$rows = $this->dbx
				->select('source_income_id')
				->where('region_id', $region)
				->where('number !=', $finance_id)
				->where_in('source_income_id', $ids)
				->get('x_finance_item')
				->result_array();

			foreach ($rows as $r) {
				$id = (int)($r['source_income_id'] ?? 0);
				if ($id > 0) $blocked[] = $id;
			}
		}

		return array_values(array_unique($blocked));
	}


	/**
	 * Filter x_glbincome sebelum digabung ke invoice.
	 *
	 * Rule:
	 * - source yang sudah dipakai finance lain -> skip, bukan error;
	 * - legacy ditarik=1 tanpa mapping -> skip;
	 * - source milik finance yang sedang direbuild/edit -> tetap boleh masuk lagi.
	 */
	private function _invoice_filter_available_global_income_rows(array $rows, $finance_id, $region)
	{
		$finance_id = (int)$finance_id;
		$region = trim((string)$region);

		if (empty($rows) || $finance_id <= 0 || $region === '') {
			return [];
		}

		$ids = [];
		foreach ($rows as $row) {
			$id = isset($row->id) ? (int)$row->id : 0;
			if ($id > 0) {
				$ids[] = $id;
			}
		}

		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return [];
		}

		// Yang sudah dipetakan ke finance LAIN tidak boleh ikut invoice ini.
		$blocked = $this->_invoice_source_ids_mapped_elsewhere(
			'x_glbincome',
			$ids,
			$finance_id,
			$region
		);
		$blockedMap = array_fill_keys($blocked, true);

		// Saat edit/rebuild, source milik finance INI harus boleh ditarik ulang.
		$currentMap = [];
		if (
			$this->dbx->table_exists('x_finance_item')
			&& $this->dbx->field_exists('income_source_table', 'x_finance_item')
			&& $this->dbx->field_exists('income_source_id', 'x_finance_item')
		) {
			$currentRows = $this->dbx
				->select('income_source_id')
				->where('region_id', $region)
				->where('number', $finance_id)
				->where('income_source_table', 'x_glbincome')
				->where_in('income_source_id', $ids)
				->get('x_finance_item')
				->result_array();

			foreach ($currentRows as $r) {
				$id = (int)($r['income_source_id'] ?? 0);
				if ($id > 0) {
					$currentMap[$id] = true;
				}
			}
		}

		$available = [];

		foreach ($rows as $row) {
			$id = isset($row->id) ? (int)$row->id : 0;
			if ($id <= 0) {
				continue;
			}

			// Sudah dipakai invoice/finance lain: abaikan global income ini.
			if (isset($blockedMap[$id])) {
				continue;
			}

			$isCurrentFinance = isset($currentMap[$id]);
			$isPulled = isset($row->ditarik) && (int)$row->ditarik === 1;

			// Legacy protection: ditarik=1 tetapi mapping tidak tersedia.
			// Jangan tarik lagi, kecuali memang milik finance yang sedang diedit.
			if ($isPulled && !$isCurrentFinance) {
				continue;
			}

			$available[] = $row;
		}

		return $available;
	}


	private function _invoice_lock_selected_sources(array $rows, $finance_id, $invoice_no, $region)
	{
		$finance_id = (int)$finance_id;
		$invoice_no = trim((string)$invoice_no);
		$region = trim((string)$region);

		$allowed = [
			'inf_income_selling_price',
			'x_glbincome',
		];

		$byTable = [];

		foreach ($rows as $row) {
			$table = isset($row->_income_source_table)
				? trim((string)$row->_income_source_table)
				: '';
			$id = isset($row->_income_source_id)
				? (int)$row->_income_source_id
				: 0;

			if (in_array($table, $allowed, true) && $id > 0) {
				$byTable[$table][] = $id;
			}
		}

		foreach ($byTable as $table => $ids) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			if (empty($ids)) continue;

			$idList = implode(',', $ids);

			if ($table === 'inf_income_selling_price') {
				$sql = "SELECT id,ship_co FROM `inf_income_selling_price`
						WHERE region_id = ? AND id IN ({$idList}) FOR UPDATE";
				$locked = $this->dbx->query($sql, [$region])->result_array();

				if (count($locked) !== count($ids)) {
					return [
						'ok' => false,
						'error' => 'SOURCE_LOCK_FAILED_OR_SOURCE_CHANGED',
						'table' => $table,
					];
				}

				foreach ($locked as $r) {
					$current = trim((string)($r['ship_co'] ?? ''));
					if ($current !== '' && $current !== $invoice_no) {
						return [
							'ok' => false,
							'error' => 'SOURCE_OWNED_BY_OTHER_INVOICE',
							'table' => $table,
							'id' => (int)$r['id'],
							'current_invoice' => $current,
						];
					}
				}
			} else {
				$sql = "SELECT id FROM `x_glbincome`
						WHERE region_id = ? AND id IN ({$idList}) FOR UPDATE";
				$locked = $this->dbx->query($sql, [$region])->result_array();

				if (count($locked) !== count($ids)) {
					return [
						'ok' => false,
						'error' => 'SOURCE_LOCK_FAILED_OR_SOURCE_CHANGED',
						'table' => $table,
					];
				}
			}

			$blocked = $this->_invoice_source_ids_mapped_elsewhere(
				$table,
				$ids,
				$finance_id,
				$region
			);

			if (!empty($blocked)) {
				return [
					'ok' => false,
					'error' => 'SOURCE_ALREADY_MAPPED_TO_OTHER_FINANCE',
					'table' => $table,
					'ids' => $blocked,
				];
			}
		}

		return ['ok' => true];
	}


	private function _invoice_release_old_sources($finance_id, $invoice_no, $region)
	{
		$finance_id = (int)$finance_id;
		$invoice_no = trim((string)$invoice_no);
		$region = trim((string)$region);

		if ($finance_id <= 0 || $invoice_no === '' || $region === '') {
			return false;
		}

		$allowed = [
			'inf_income_selling_price',
			'x_glbincome',
		];

		$byTable = [];

		if ($this->dbx->table_exists('x_finance_item')) {
			$select = ['id'];

			if ($this->dbx->field_exists('source_income_id', 'x_finance_item')) {
				$select[] = 'source_income_id';
			}
			if ($this->dbx->field_exists('income_source_table', 'x_finance_item')) {
				$select[] = 'income_source_table';
			}
			if ($this->dbx->field_exists('income_source_id', 'x_finance_item')) {
				$select[] = 'income_source_id';
			}

			$oldItems = $this->dbx
				->select($select)
				->where('number', $finance_id)
				->where('region_id', $region)
				->where('id_tarik !=', 0)
				->get('x_finance_item')
				->result_array();

			foreach ($oldItems as $item) {
				if (!empty($item['source_income_id'])) {
					$byTable['inf_income_selling_price'][] = (int)$item['source_income_id'];
				}

				$table = trim((string)($item['income_source_table'] ?? ''));
				$id = (int)($item['income_source_id'] ?? 0);

				if (in_array($table, $allowed, true) && $id > 0) {
					$byTable[$table][] = $id;
				}
			}
		}

		foreach ($byTable as $table => $ids) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			if (empty($ids)) continue;

			$blocked = $this->_invoice_source_ids_mapped_elsewhere(
				$table,
				$ids,
				$finance_id,
				$region
			);

			if (!empty($blocked)) {
				$ids = array_values(array_diff($ids, $blocked));
			}

			if (empty($ids)) continue;

			if ($table === 'inf_income_selling_price') {
				$update = [
					'ditarik' => 0,
					'ship_co' => '',
				];

				if ($this->dbx->field_exists('billing_status', $table)) {
					$update['billing_status'] = 'UNBILLED';
				}
				if ($this->dbx->field_exists('billed_at', $table)) {
					$update['billed_at'] = null;
				}
				if ($this->dbx->field_exists('billed_by', $table)) {
					$update['billed_by'] = null;
				}

				$this->dbx
					->where('region_id', $region)
					->where_in('id', $ids)
					->group_start()
						->where('ship_co', $invoice_no)
						->or_where('ship_co', '')
						->or_where('ship_co IS NULL', null, false)
					->group_end()
					->update($table, $update);
			} elseif ($table === 'x_glbincome') {
				if ($this->dbx->field_exists('ditarik', $table)) {
					$this->dbx
						->where('region_id', $region)
						->where_in('id', $ids)
						->update($table, ['ditarik' => 0]);
				}
			}
		}

		/*
		 * Legacy fallback untuk selling price yang belum punya direct mapping.
		 * Exact ownership by invoice number, bukan broad kode_ref/LIKE.
		 */
		$legacyUpdate = [
			'ditarik' => 0,
			'ship_co' => '',
		];

		if ($this->dbx->field_exists('billing_status', 'inf_income_selling_price')) {
			$legacyUpdate['billing_status'] = 'UNBILLED';
		}
		if ($this->dbx->field_exists('billed_at', 'inf_income_selling_price')) {
			$legacyUpdate['billed_at'] = null;
		}
		if ($this->dbx->field_exists('billed_by', 'inf_income_selling_price')) {
			$legacyUpdate['billed_by'] = null;
		}

		$this->dbx
			->where('region_id', $region)
			->where('ship_co', $invoice_no)
			->update('inf_income_selling_price', $legacyUpdate);

		return $this->dbx->trans_status() !== false;
	}


	function tarik_data_invoice($POST)
	{
		$region = trim((string)$this->session->userdata('region_id'));

		/*
		 * ============================================================
		 * 0. VALIDASI DASAR
		 * ============================================================
		 *
		 * POST['number'] pada flow existing adalah ID x_finance,
		 * BUKAN nomor invoice string.
		 */
		$finance_id = isset($POST['number'])
			? (int)$POST['number']
			: 0;

		if ($region === '') {
			return [
				'status'  => '0',
				'message' => 'REGION_NOT_FOUND'
			];
		}

		if ($finance_id <= 0) {
			return [
				'status'  => '0',
				'message' => 'INVALID_FINANCE_ID'
			];
		}


		/*
		 * ============================================================
		 * 1. AMBIL HEADER INVOICE DARI DATABASE
		 * ============================================================
		 *
		 * IMPORTANT:
		 * job_no, nomor invoice dan group JANGAN percaya frontend.
		 *
		 * Source of truth = x_finance.
		 */
		$finance = $this->dbx
			->select('
				id,
				job_no,
				number,
				`group`,
				jenis,
				jenis_code,
				status,
				region_id
			', false)
			->from('x_finance')
			->where('id', $finance_id)
			->where('region_id', $region)
			->where('jenis', 'INVOICE')
			->limit(1)
			->get()
			->row();

		if (!$finance) {
			return [
				'status'     => '0',
				'message'    => 'INVOICE_HEADER_NOT_FOUND',
				'finance_id' => $finance_id
			];
		}

		$kode_ref      = trim((string)$finance->job_no);
		$invoice_no    = trim((string)$finance->number);
		$group         = trim((string)$finance->group);
		$invoice_status = strtolower(trim((string)$finance->status));

		/*
		 * PAID boleh direvisi, tetapi harus dibuka melalui revision flow.
		 */
		if ($invoice_status === 'paid') {
			return $this->paid_revision_required_response((array)$finance);
		}

		/*
		 * jenis invoice masih boleh berasal dari POST karena pada flow ADD
		 * jenis_code x_finance baru diisi oleh fungsi ini.
		 *
		 * 1 = Invoice
		 * 2 = Reimbursement
		 * 3 = Faktur Pajak
		 */
		$jenis_code = isset($POST['jenis'])
			? trim((string)$POST['jenis'])
			: trim((string)$finance->jenis_code);

		if (!in_array($jenis_code, ['1', '2', '3'], true)) {
			$jenis_code = '1';
		}

		if ($kode_ref === '') {
			return [
				'status'  => '0',
				'message' => 'EMPTY_JOB_NO'
			];
		}

		if ($invoice_no === '') {
			return [
				'status'  => '0',
				'message' => 'EMPTY_INVOICE_NO'
			];
		}


		/*
		 * ============================================================
		 * 2. AMBIL CURRENT INCOME SELLING PRICE
		 * ============================================================
		 *
		 * Untuk invoice baru:
		 * - ambil yang belum ditarik
		 *
		 * Untuk edit/rebuild:
		 * - income yang sudah milik invoice ini tetap boleh diambil lagi.
		 *
		 * Ini mencegah invoice A mengambil income yang sebenarnya
		 * sudah menjadi milik invoice B.
		 */
		$this->dbx
			->from('inf_income_selling_price')
			->where('region_id', $region)
			->where('kode_ref', $kode_ref)
			->where('prep_coll', 'PREPAID')
			->where('invoice_db', 'INVOICE');

		/*
		 * Handle g = NULL dan g = '' sebagai group kosong yang sama.
		 */
		if ($group === '') {

			$this->dbx
				->group_start()
					->where('g IS NULL', null, false)
					->or_where("TRIM(g) = ''", null, false)
				->group_end();

		} else {

			$this->dbx->where('g', $group);
		}

		/*
		 * Faktur Pajak hanya ambil income yang punya setting PPN.
		 */
		if ($jenis_code === '3') {
			$this->dbx
				->where("ppn IS NOT NULL", null, false)
				->where("TRIM(ppn) != ''", null, false)
				->where("code_service NOT LIKE '%TAX%'", null, false);
		}

		/*
		 * Income boleh ditarik bila:
		 * 1. belum dimiliki invoice mana pun (ship_co NULL / kosong), ATAU
		 * 2. memang milik invoice yang sedang direbuild/edit.
		 *
		 * ditarik tidak dipakai sebagai lock utama karena pada data lama
		 * bisa terjadi ditarik=1 tetapi ship_co kosong.
		 */
		$this->dbx
			->group_start()
				->where('ship_co IS NULL', null, false)
				->or_where("TRIM(ship_co) = ''", null, false)
				->or_where('ship_co', $invoice_no)
			->group_end();

		$q1 = $this->dbx->get()->result();

		/*
		 * Tandai asal source supaya x_finance_item bisa menyimpan
		 * direct mapping ke row operational yang tepat.
		 */
		foreach ($q1 as $row) {
			$source_id = isset($row->id) ? (int)$row->id : 0;
			$row->_income_source_table = 'inf_income_selling_price';
			$row->_income_source_id    = $source_id > 0 ? $source_id : null;
			$row->_source_income_id    = $source_id > 0 ? $source_id : null;
		}


		/*
		 * ============================================================
		 * 3. AMBIL GLOBAL INCOME
		 * ============================================================
		 *
		 * Pertahankan behavior existing.
		 */
		$this->dbx
			->from('x_glbincome')
			->where('region_id', $region)
			->where('job_no', substr($kode_ref, 0, 15))
			->where('prep_coll', 'PREPAID')
			->where('currency', 'RP.');

		if ($group === '') {

			$this->dbx
				->group_start()
					->where('`group` IS NULL', null, false)
					->or_where("TRIM(`group`) = ''", null, false)
				->group_end();

		} else {

			$this->dbx->where('group', $group);
		}

		$q2 = $this->dbx->get()->result();

		/*
		 * Jangan gagalkan seluruh invoice hanya karena ada global income
		 * yang sebelumnya sudah dipakai invoice lain. Global income seperti itu
		 * cukup dilewati; income lain yang masih available tetap diproses.
		 *
		 * Saat edit/rebuild invoice yang sama, global income milik finance ini
		 * tetap diizinkan supaya item existing tidak hilang.
		 */
		$q2 = $this->_invoice_filter_available_global_income_rows(
			$q2,
			$finance_id,
			$region
		);

		foreach ($q2 as $row) {
			$source_id = isset($row->id) ? (int)$row->id : 0;
			$row->_income_source_table = 'x_glbincome';
			$row->_income_source_id    = $source_id > 0 ? $source_id : null;
			$row->_source_income_id    = null;
		}


		/*
		 * ============================================================
		 * 4. GABUNGKAN SOURCE
		 * ============================================================
		 */
		$q = array_merge($q1, $q2);

		if (empty($q)) {
			return [
				'status'     => '0',
				'message'    => 'NO_CURRENT_INCOME_FOUND',
				'finance_id' => $finance_id,
				'invoice_no' => $invoice_no,
				'job_no'     => $kode_ref,
				'group'      => $group
			];
		}


		/*
		 * ============================================================
		 * 5. SIMPAN ID INCOME YANG BENAR-BENAR DIPAKAI
		 * ============================================================
		 *
		 * Inilah yang nantinya di-set ditarik=1.
		 *
		 * Jangan UPDATE seluruh job/group secara membabi buta.
		 */
		$income_ids = [];

		foreach ($q1 as $row) {
			if (isset($row->id) && (int)$row->id > 0) {
				$income_ids[] = (int)$row->id;
			}
		}

		$income_ids = array_values(array_unique($income_ids));


		/*
		 * ============================================================
		 * 6. SIAPKAN x_finance_item
		 * ============================================================
		 */
		$items   = [];
		$overdue = [];

		$has_source_income_id = $this->dbx->field_exists(
			'source_income_id',
			'x_finance_item'
		);
		$has_income_source_table = $this->dbx->field_exists(
			'income_source_table',
			'x_finance_item'
		);
		$has_income_source_id = $this->dbx->field_exists(
			'income_source_id',
			'x_finance_item'
		);

		$bl_no = $this->get_bl_num($kode_ref);

		foreach ($q as $row) {

			$line_price = isset($row->price)
				? (int)round((float)$row->price)
				: 0;

			$qty = isset($row->qty)
				? (float)$row->qty
				: 0;

			/*
			 * x_glbincome mungkin memakai struktur sedikit berbeda.
			 * kode_ref fallback ke current job.
			 */
			$row_kode_ref = isset($row->kode_ref)
				? trim((string)$row->kode_ref)
				: $kode_ref;

			if ($row_kode_ref === '') {
				$row_kode_ref = $kode_ref;
			}

			$itemData = [
				'jenis'      => 'INVOICE',
				'kode_ref'   => $kode_ref,
				'bl_no'      => $bl_no,
				'number'     => $finance_id,

				'code' => isset($row->code_service)
					? $row->code_service
					: '',

				'item' => isset($row->item_service)
					? $row->item_service
					: '',

				'qty' => $qty,

				'currency' => isset($row->currency)
					? $row->currency
					: '',

				'unit_price' => isset($row->unit_price)
					? $row->unit_price
					: 0,

				'price' => $line_price,

				'remarks' => isset($row->remark)
					? $row->remark
					: '',

				'pre_col'   => 'PREPAID',
				'id_tarik'  => '1',

				'ppn' => isset($row->ppn)
					? $row->ppn
					: '',

				'region_id' => $region,
				'anak_job'  => $row_kode_ref,
			];

			/*
			 * Direct source mapping untuk invoice item baru.
			 * source_income_id khusus inf_income_selling_price.
			 */
			if ($has_source_income_id) {
				$itemData['source_income_id'] = (
					isset($row->_source_income_id)
					&& (int)$row->_source_income_id > 0
				)
					? (int)$row->_source_income_id
					: null;
			}

			if ($has_income_source_table) {
				$itemData['income_source_table'] = !empty($row->_income_source_table)
					? trim((string)$row->_income_source_table)
					: null;
			}

			if ($has_income_source_id) {
				$itemData['income_source_id'] = (
					isset($row->_income_source_id)
					&& (int)$row->_income_source_id > 0
				)
					? (int)$row->_income_source_id
					: null;
			}

			$items[] = $itemData;
			$overdue[] = $line_price;
		}

		if (empty($items)) {
			return [
				'status'  => '0',
				'message' => 'NO_INVOICE_ITEM'
			];
		}


		/*
		 * ============================================================
		 * 7. HITUNG DPP
		 * ============================================================
		 */
		$grand_total = array_sum($overdue);

		$ppn_total    = 0;
		$kode_pajak   = null;
		$jumlah_pajak = 0;
		$vat          = 'ok';


		/*
		 * ============================================================
		 * 8. TENTUKAN PPN
		 * ============================================================
		 *
		 * Tidak perlu query getSinglePPN() lagi.
		 *
		 * Gunakan income YANG SAMA yang sedang dibuat invoice.
		 * Ini juga otomatis bebas problem g NULL vs ''.
		 */
		if ($jenis_code === '3' && $grand_total > 0) {

			$ppn_values = [];

			foreach ($q1 as $row) {

				$nilai_ppn = isset($row->ppn)
					? trim((string)$row->ppn)
					: '';

				if ($nilai_ppn !== '') {
					$ppn_values[$nilai_ppn] = $nilai_ppn;
				}
			}

			$ppn_values = array_values($ppn_values);

			if (count($ppn_values) > 1) {

				return [
					'status'  => '0',
					'message' => 'MULTIPLE_PPN_FOUND',
					'data'    => $ppn_values
				];

			} elseif (count($ppn_values) === 0) {

				return [
					'status'  => '0',
					'message' => 'PPN_NOT_FOUND'
				];
			}

			$nilai_ppn = $ppn_values[0];

			if ($nilai_ppn === '1.1%') {

				$kode_pajak   = 'TAX 1.1';
				$jumlah_pajak = 0.011;

				foreach ($overdue as $line_dpp) {
					$ppn_total += $this->ppn_half_up_int(
						(int)$line_dpp,
						11,
						1000
					);
				}

			} elseif ($nilai_ppn === '1.2%') {

				$kode_pajak   = 'TAX 1.2';
				$jumlah_pajak = 0.012;

				foreach ($overdue as $line_dpp) {
					$ppn_total += $this->ppn_half_up_int(
						(int)$line_dpp,
						12,
						1000
					);
				}

			} elseif ($nilai_ppn === '11%') {

				$kode_pajak   = 'TAX 11';
				$jumlah_pajak = 0.11;

				foreach ($overdue as $line_dpp) {
					$ppn_total += $this->ppn_half_up_int(
						(int)$line_dpp,
						11,
						100
					);
				}

			} elseif ($nilai_ppn === '12%') {

				/*
				 * Pertahankan logic existing Anda:
				 * label 12%, tetapi perhitungan menggunakan 11/100.
				 *
				 * Contoh invoice Anda:
				 * DPP 3,370,538
				 * PPN = 370,759
				 */
				$kode_pajak   = 'TAX 12';
				$jumlah_pajak = 0.11;

				foreach ($overdue as $line_dpp) {
					$ppn_total += $this->ppn_half_up_int(
						(int)$line_dpp,
						11,
						100
					);
				}

			} else {

				return [
					'status'  => '0',
					'message' => 'UNKNOWN_PPN',
					'ppn'     => $nilai_ppn
				];
			}
		}


		/*
		 * ============================================================
		 * 9. MULAI TRANSACTION
		 * ============================================================
		 */
		$this->dbx->trans_begin();


		/*
		 * ============================================================
		 * 10. LOCK + VALIDATE CURRENT SOURCE, LALU RELEASE SOURCE LAMA
		 * ============================================================
		 *
		 * - exact source rows dikunci selama transaction;
		 * - source yang masih dipetakan ke finance lain ditolak;
		 * - source lama invoice ini dilepas berdasarkan direct mapping;
		 * - legacy fallback hanya exact ownership ship_co = invoice_no.
		 */
		$sourceLock = $this->_invoice_lock_selected_sources(
			$q,
			$finance_id,
			$invoice_no,
			$region
		);

		if (empty($sourceLock['ok'])) {
			$this->dbx->trans_rollback();

			return [
				'status'  => '0',
				'message' => $sourceLock['error'] ?? 'SOURCE_LOCK_OR_OWNERSHIP_FAILED',
				'detail'  => $sourceLock,
			];
		}

		if (!$this->_invoice_release_old_sources($finance_id, $invoice_no, $region)) {
			$error = $this->dbx->error();
			$this->dbx->trans_rollback();

			return [
				'status'  => '0',
				'message' => 'FAILED_RELEASE_OLD_INVOICE_SOURCES',
				'error'   => $error,
			];
		}


		/*
		 * ============================================================
		 * 11. HAPUS ITEM AUTO-TARIK LAMA
		 * ============================================================
		 *
		 * Item manual id_tarik=0 tetap dipertahankan mengikuti
		 * behavior fungsi lama.
		 */
		$this->dbx
			->where('number', $finance_id)
			->where('region_id', $region)
			->where('id_tarik !=', 0)
			->delete('x_finance_item');


		/*
		 * ============================================================
		 * 12. INSERT ITEM CURRENT INCOME
		 * ============================================================
		 */
		$insert_ok = $this->dbx->insert_batch(
			'x_finance_item',
			$items
		);

		if ($insert_ok === false) {

			$error = $this->dbx->error();

			$this->dbx->trans_rollback();

			return [
				'status'  => '0',
				'message' => 'FAILED_INSERT_INVOICE_ITEM',
				'error'   => $error
			];
		}


		/*
		 * Tandai x_glbincome yang benar-benar dipakai berdasarkan ID.
		 * Tidak menggunakan LIKE job/group.
		 */
		$global_income_ids = [];
		foreach ($q2 as $row) {
			if (isset($row->id) && (int)$row->id > 0) {
				$global_income_ids[] = (int)$row->id;
			}
		}
		$global_income_ids = array_values(array_unique($global_income_ids));

		if (
			!empty($global_income_ids)
			&& $this->dbx->field_exists('ditarik', 'x_glbincome')
		) {
			$this->dbx
				->where('region_id', $region)
				->where_in('id', $global_income_ids)
				->update('x_glbincome', ['ditarik' => 1]);
		}


		/*
		 * ============================================================
		 * 13. MARK EXACT INCOME AS INVOICED
		 * ============================================================
		 *
		 * UPDATE BERDASARKAN ID.
		 *
		 * Jadi yang ditandai hanya income yang memang masuk q1 /
		 * x_finance_item saat ini.
		 */
		if (!empty($income_ids)) {

			$bill_update = [
				'ditarik' => 1,
				'ship_co' => $invoice_no
			];

			if ($this->dbx->field_exists('billing_status', 'inf_income_selling_price')) {
				$bill_update['billing_status'] = 'BILLED';
			}
			if ($this->dbx->field_exists('billed_at', 'inf_income_selling_price')) {
				$bill_update['billed_at'] = date('Y-m-d H:i:s');
			}
			if ($this->dbx->field_exists('billed_by', 'inf_income_selling_price')) {
				$bill_update['billed_by'] = (string)$this->session->userdata('fullname');
			}

			$this->dbx
				->where('region_id', $region)
				->where_in('id', $income_ids)
				->update('inf_income_selling_price', $bill_update);
		}


		/*
		 * ============================================================
		 * 14. INSERT VAT
		 * ============================================================
		 */
		if (
			$jenis_code === '3'
			&& $kode_pajak !== null
			&& $jumlah_pajak > 0
			&& $ppn_total > 0
		) {

			$data_pajak = [
				'jenis'      => 'INVOICE',
				'kode_ref'   => $kode_ref,
				'bl_no'      => $bl_no,
				'number'     => $finance_id,
				'code'       => $kode_pajak,
				'item'       => 'VAT',
				'qty'        => $jumlah_pajak,
				'currency'   => 'RP.',
				'unit_price' => (int)$grand_total,
				'price'      => (int)$ppn_total,
				'remarks'    => '',
				'pre_col'    => 'PREPAID',
				'id_tarik'   => '1',
				'ppn'        => $vat,
				'region_id'  => $region,
				'anak_job'   => $kode_ref
			];

			/* Generated VAT tidak punya satu source income langsung. */
			if ($has_source_income_id) {
				$data_pajak['source_income_id'] = null;
			}
			if ($has_income_source_table) {
				$data_pajak['income_source_table'] = null;
			}
			if ($has_income_source_id) {
				$data_pajak['income_source_id'] = null;
			}

			$vat_insert = $this->dbx->insert(
				'x_finance_item',
				$data_pajak
			);

			if (!$vat_insert) {

				$error = $this->dbx->error();

				$this->dbx->trans_rollback();

				return [
					'status'  => '0',
					'message' => 'FAILED_INSERT_VAT',
					'error'   => $error
				];
			}
		}


		/*
		 * ============================================================
		 * 15. UPDATE TOTAL HEADER
		 * ============================================================
		 */
		/*
		 * Header total harus mengikuti isi x_finance_item aktual.
		 * Ini ikut menghitung item manual id_tarik=0 yang memang dipertahankan.
		 */
		$sum_row = $this->dbx
			->select_sum('price', 'total')
			->where('number', $finance_id)
			->where('region_id', $region)
			->get('x_finance_item')
			->row_array();

		$grand_total_with_vat = isset($sum_row['total'])
			? (float)$sum_row['total']
			: 0;

		$header_ok = $this->dbx
			->where('id', $finance_id)
			->where('region_id', $region)
			->update('x_finance', [
				'grand_total' => $grand_total_with_vat,
				'ppn'         => $ppn_total,
				'jenis_code'  => $jenis_code,
				'tarif_ppn'   => (
					$ppn_total > 0
						? $jumlah_pajak
						: 0
				)
			]);

		if (!$header_ok) {

			$error = $this->dbx->error();

			$this->dbx->trans_rollback();

			return [
				'status'  => '0',
				'message' => 'FAILED_UPDATE_INVOICE_HEADER',
				'error'   => $error
			];
		}


		/*
		 * ============================================================
		 * 16. FINAL TRANSACTION CHECK
		 * ============================================================
		 */
		if ($this->dbx->trans_status() === false) {

			$error = $this->dbx->error();

			$this->dbx->trans_rollback();

			return [
				'status'  => '0',
				'message' => 'INVOICE_TRANSACTION_FAILED',
				'error'   => $error
			];
		}

		$this->dbx->trans_commit();

		$this->mark_revision_stage($finance_id, $region, 'REBUILT');


		/*
		 * ============================================================
		 * 17. SUCCESS
		 * ============================================================
		 */
		return [
			'status'         => '1',
			'message'        => 'INVOICE_ITEMS_UPDATED',
			'finance_id'     => $finance_id,
			'invoice_no'     => $invoice_no,
			'job_no'         => $kode_ref,
			'group'          => $group,
			'jenis_code'     => $jenis_code,
			'income_rows'    => count($q1),
			'global_rows'    => count($q2),
			'total_rows'     => count($q),
			'income_ids'     => $income_ids,
			'grand_total'    => $grand_total,
			'ppn_total'      => $ppn_total,
			'grand_total_ppn'=> $grand_total_with_vat,
			'pre-col'        => 'PREPAID',
			'ppn'            => $vat
		];
	}

	function getSinglePPN($dbx, $region, $kode_ref, $group) {
			$group = trim($group);

			$dbx->select('ppn')
				->where("ppn IS NOT NULL AND TRIM(ppn) != ''")
				->where("code_service NOT LIKE '%TAX%'")
				->group_by('ppn');

			$result = $dbx->get_where(
				'inf_income_selling_price',
				array(
					'region_id' => $region,
					'kode_ref'  => $kode_ref,
					'prep_coll' => 'PREPAID',
					'g'         => $group,
				)
			)->result();

			if (count($result) > 1) {
				return array(
					'error' => true,
					'message' => 'Ditemukan lebih dari satu nilai PPN yang berbeda.',
					'data' => $result
				);
			} elseif (count($result) === 0) {
				return array(
					'error' => true,
					'message' => 'Tidak ditemukan data PPN yang valid.',
					'data' => []
				);
			} else {
				return array(
					'error' => false,
					'ppn' => $result[0]->ppn
				);
			}
	}

	function get_id_akhir(){
		$this->dbx->select('sp_detail_id');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->order_by('sp_detail_id', 'desc');
		$this->dbx->limit(1);
		$get = $this->dbx->get()->row_array();
		return $get['sp_detail_id'];
	}

	function get_form_inv($id){
		
		$this->dbx->select('*');
		$this->dbx->from('x_finance');
		$this->dbx->join('x_finance_item','x_finance_item.number=x_finance.id');
		$this->dbx->where('x_finance_item.number', $id);
		$this->dbx->where('x_finance_item.price !=', 0 );
		//$this->dbx->order_by("x_finance_item.item", "asc");
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}
	
	function get_form_inv_pph($id){
		
		$this->dbx->select('*');
		$this->dbx->from('x_finance');
		$this->dbx->join('x_finance_item','x_finance_item.number=x_finance.id');
		$this->dbx->where('x_finance_item.number', $id);
		$this->dbx->where('x_finance_item.price !=', 0 );
		$this->dbx->where_not_in('x_finance_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2'));
		//$this->dbx->order_by("x_finance_item.item", "asc");
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}
	
	function get_report_pdf($kode_ref){
		$region_id = $this->session->userdata('region_id') ?? 'Jakarta';
		$this->dbx->select(array(
			'trnstuffingplan.vessel',
			'trnstuffingplan.voyage',
			'trnstuffingplan.etd_jkt',
			'trnstuffingplan.stuffing_date',
			'trnstuffingplan.eta',
			'trnstuffingplan.port_loading',
			'trnstuffingplan.destination as port_dest',
			'trnstuffingplan.mother_bl',
			'trnstuffingplan.kurs',
			'trnstuffingplan.cont_seal_no',
			'trnstuffingplan.shipment_type_multi',
			'trnstuffingplan.region_id as region_id',
			'trnstuffingplandetail.destination',
			'trnstuffingplandetail.no_of_pack',
			'trnstuffingplandetail.no_pack_qty',
			'trnstuffingplandetail.no_pack_unit',
			'trnstuffingplandetail.freight',
			'trnstuffingplandetail.bl_meas',
			'trnstuffingplandetail.code',
			'trnstuffingplandetail.kode_ref',
			'trnstuffingplandetail.incoterms',
         'trnstuffingplandetail.weight',
         'trnstuffingplandetail.hb_l',
         'trnstuffingplandetail.sp_detail_id',
         'trnstuffingplandetail.note',
         'trnstuffingplandetail.shipper',
         'trnstuffingplandetail.sp_id as sp_id',
			)
		);
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where('trnstuffingplandetail.kode_ref', $kode_ref);	
		$this->dbx->where('trnstuffingplan.region_id', $region_id);
		$q = $this->dbx->get();
		//echo $this->dbx->last_query(); // cek langsung di model
		return $q->row_array();
	}
	function get_bl_num($job_no)
	{
		$q = $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$job_no));
		if($q->num_rows()>0) 
		{
			return $q->row()->hb_l;
			//return $spd;
		}
		else 
		{
			return "";
		}
	}

	/**
	 * Ambil nilai CO2 emission aktual dari sistem tracking berdasarkan
	 * house B/L ($hb_l). Ini sumber data PALING DIUTAMAKAN untuk badge CO2
	 * di invoice - kalau ada, dipakai langsung tanpa perlu hitung estimasi.
	 *
	 * Urutan cek: x_tracking dulu, baru fallback x_tracking_export.
	 *
	 * NOTE: nama kolom join masih placeholder 'hbl' - sesuaikan dengan
	 * nama kolom asli di masing-masing tabel kalau berbeda.
	 */
	function get_co2_emission($hb_l)
	{
		$hb_l = trim((string)$hb_l);
		log_message('error', '[CO2_DEBUG][model] get_co2_emission() dipanggil dengan hb_l="'.$hb_l.'"');

		if ($hb_l === '') {
			log_message('error', '[CO2_DEBUG][model] hb_l kosong -> return null');
			return null;
		}

		$this->dbx->reset_query(); // jaga-jaga ada query lain sebelumnya yang gagal & belum ke-reset

		$row = null;
		try {
			$row = $this->dbx->select('co2')
							  ->from('x_tracking')
							  ->where('hbl_no', $hb_l)   // kolom terkonfirmasi via DESCRIBE x_tracking (2026-11-12) - bukan 'hbl'
							  ->limit(1)
							  ->get()
							  ->row();
			$db_err = $this->dbx->error();
			log_message('error', '[CO2_DEBUG][model] query x_tracking -> row='.var_export($row, true).' | db_error='.var_export($db_err, true));
		} catch (\Throwable $e) {
			log_message('error', '[CO2_DEBUG][model] EXCEPTION query x_tracking: '.$e->getMessage());
			$row = null;
			$this->dbx->reset_query(); // penting: bersihkan query builder yang gagal, biar query berikutnya tidak numpuk
		}

		if (!$row || $row->co2 === null || $row->co2 === '') {
			try {
				$row = $this->dbx->select('co2')
								  ->from('x_tracking_export')
								  ->where('hbl_no', $hb_l)   // kolom terkonfirmasi via DESCRIBE x_tracking_export (2026-11-12) - bukan 'hbl'
								  ->limit(1)
								  ->get()
								  ->row();
				$db_err = $this->dbx->error();
				log_message('error', '[CO2_DEBUG][model] fallback query x_tracking_export -> row='.var_export($row, true).' | db_error='.var_export($db_err, true));
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][model] EXCEPTION query x_tracking_export: '.$e->getMessage());
				$row = null;
				$this->dbx->reset_query();
			}
		}

		$result = ($row && $row->co2 !== null && $row->co2 !== '') ? (float)$row->co2 : null;
		log_message('error', '[CO2_DEBUG][model] get_co2_emission() return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Cari kode UN/LOCODE (5 karakter, mis. 'SGSIN', 'IDJKT') dari nama
	 * pelabuhan/kota di tabel master `tableName`.
	 *
	 * NOTE: 'tableName' masih nama placeholder dari dump SQL yang di-upload -
	 * sesuaikan kalau tabelnya sudah di-rename di database production.
	 *
	 * Matching dibuat 2 tahap:
	 * 1) Exact match (TRIM+UPPER) pada kolom `name`, is_active='TRUE'.
	 * 2) Kalau tidak ketemu, fallback LIKE '%nama%' (ambil hasil pertama).
	 *    Ini berisiko salah pilih kalau ada nama kota yang mirip/duplikat
	 *    di negara berbeda - kalau perlu presisi, tambahkan filter country_id.
	 */
	function get_uncode($name)
	{
		$name = trim((string)$name);
		log_message('error', '[CO2_DEBUG][model] get_uncode() dipanggil dengan name="'.$name.'"');

		if ($name === '') {
			log_message('error', '[CO2_DEBUG][model] name kosong -> return null');
			return null;
		}

		$this->dbx->reset_query(); // jaga-jaga ada query lain sebelumnya yang gagal & belum ke-reset

		$row = null;
		try {
			$row = $this->dbx
				->select('code')
				->from('tableName')
				->where("UPPER(TRIM(name)) =", strtoupper($name))
				->where('is_active', 'TRUE')
				->limit(1)
				->get()
				->row();
			log_message('error', '[CO2_DEBUG][model] exact match "'.$name.'" -> '.var_export($row, true).' | db_error='.var_export($this->dbx->error(), true));
		} catch (\Throwable $e) {
			log_message('error', '[CO2_DEBUG][model] EXCEPTION exact match tableName: '.$e->getMessage());
			$this->dbx->reset_query();
		}

		if (!$row) {
			try {
				$row = $this->dbx
					->select('code')
					->from('tableName')
					->like('name', $name, 'both')
					->where('is_active', 'TRUE')
					->limit(1)
					->get()
					->row();
				log_message('error', '[CO2_DEBUG][model] LIKE match "'.$name.'" -> '.var_export($row, true).' | db_error='.var_export($this->dbx->error(), true));
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][model] EXCEPTION LIKE match tableName: '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		$result = $row ? $row->code : null;
		log_message('error', '[CO2_DEBUG][model] get_uncode("'.$name.'") return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Beberapa kode UN/LOCODE hasil lookup dari tabel `tableName` valid
	 * secara UN/LOCODE, tapi ternyata tidak dikenali oleh database internal
	 * maritime API (docker). Override manual di sini kalau nemu kasus lain
	 * yang serupa (API return "Cannot read properties of null").
	 *
	 * Format: 'KODE_DARI_TABLENAME' => 'KODE_YANG_DIKENALI_API'
	 */
	private function normalize_maritime_code($code)
	{
		$code = strtoupper(trim((string)$code));

		$overrides = array(
			'IDTPP' => 'IDJKT', // Tanjung Priok -> API cuma kenal kode Jakarta
		);

		if (isset($overrides[$code])) {
			log_message('error', '[CO2_DEBUG][model] override kode maritime API: '.$code.' -> '.$overrides[$code]);
			return $overrides[$code];
		}

		return $code;
	}

	/**
	 * Panggil maritime distance API (docker container) untuk dapat jarak
	 * pelayaran riil antara 2 UN/LOCODE. Return jarak dalam KM (API asalnya
	 * kasih nautical miles, dikonversi di sini: 1 nm = 1.852 km).
	 *
	 * PENTING: sesuaikan MARITIME_API_BASE_URL kalau PHP app tidak jalan di
	 * host yang sama dengan docker container (mis. pakai nama service di
	 * docker network, bukan 'localhost').
	 */
	function call_maritime_distance_api($origin_code, $destination_code)
	{
		$origin_code      = $this->normalize_maritime_code($origin_code);
		$destination_code = $this->normalize_maritime_code($destination_code);

		$base_url = 'http://localhost:3000/api/distance'; // TODO: sesuaikan host kalau perlu

		$url = $base_url.'?origin='.urlencode($origin_code).'&destination='.urlencode($destination_code);
		log_message('error', '[CO2_DEBUG][model] call_maritime_distance_api() GET '.$url);

		if (!function_exists('curl_init')) {
			log_message('error', '[CO2_DEBUG][model] EXTENSI curl TIDAK AKTIF di server ini -> return null');
			return null;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$response  = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_err  = curl_error($ch);
		curl_close($ch);

		log_message('error', '[CO2_DEBUG][model] response API: http_code='.$http_code.' curl_error="'.$curl_err.'" body='.var_export($response, true));

		if ($response === false || $http_code != 200) {
			log_message('error', 'Maritime distance API gagal ('.$origin_code.' -> '.$destination_code.'): '
				.$curl_err.' | HTTP '.$http_code);
			return null;
		}

		$data = json_decode($response, true);
		if (!isset($data['distance_nm']) || !is_numeric($data['distance_nm'])) {
			log_message('error', '[CO2_DEBUG][model] response tidak punya field distance_nm yang valid. decoded='.var_export($data, true));
			return null;
		}

		$distance_nm = (float)$data['distance_nm'];
		$distance_km = $distance_nm * 1.852; // nm -> km
		log_message('error', '[CO2_DEBUG][model] distance_nm='.$distance_nm.' -> distance_km='.$distance_km);
		return $distance_km;
	}

	/**
	 * Ambil jarak tempuh (km) untuk rute POL -> POD, dipakai untuk
	 * perhitungan estimasi emisi CO2 di halaman lampiran invoice.
	 *
	 * Urutan sumber data:
	 * 1) Maritime distance API (docker), berdasarkan UN/LOCODE hasil lookup
	 *    dari tabel `tableName` - paling akurat karena rute pelayaran riil.
	 * 2) Fallback ke tabel master manual x_route_distance kalau kode
	 *    UN/LOCODE tidak ketemu atau API tidak bisa diakses.
	 * 3) Kalau keduanya gagal, return null - controller akan menampilkan
	 *    "-" di PDF, bukan mengarang angka jarak.
	 */
	function get_distance_km($pol, $pod)
	{
		$pol = trim((string)$pol);
		$pod = trim((string)$pod);
		log_message('error', '[CO2_DEBUG][model] get_distance_km() pol="'.$pol.'" pod="'.$pod.'"');

		if ($pol === '' || $pod === '') {
			log_message('error', '[CO2_DEBUG][model] pol/pod kosong -> return null');
			return null;
		}

		// 1) Coba lewat maritime API (UN/LOCODE)
		$origin_code      = $this->get_uncode($pol);
		$destination_code = $this->get_uncode($pod);
		log_message('error', '[CO2_DEBUG][model] origin_code='.var_export($origin_code, true).' destination_code='.var_export($destination_code, true));

		if ($origin_code && $destination_code) {
			$distance_km = $this->call_maritime_distance_api($origin_code, $destination_code);
			if ($distance_km !== null) {
				log_message('error', '[CO2_DEBUG][model] PAKAI hasil API = '.$distance_km.' km');
				return $distance_km;
			}
			log_message('error', '[CO2_DEBUG][model] API gagal/null -> lanjut ke fallback x_route_distance');
		} else {
			log_message('error', '[CO2_DEBUG][model] UN/LOCODE tidak lengkap -> skip API, langsung ke fallback x_route_distance');
		}

		// 2) Fallback ke tabel manual x_route_distance
		$row = null;
		$this->dbx->reset_query(); // jaga-jaga kalau ada query sebelumnya yang gagal & belum ke-reset
		try {
			$row = $this->dbx
				->select('distance_km')
				->from('x_route_distance')
				->where("LOWER(TRIM(pol)) =", strtolower($pol))
				->where("LOWER(TRIM(pod)) =", strtolower($pod))
				->limit(1)
				->get()
				->row();

			if (!$row) {
				$row = $this->dbx
					->select('distance_km')
					->from('x_route_distance')
					->where("LOWER(TRIM(pol)) =", strtolower($pod))
					->where("LOWER(TRIM(pod)) =", strtolower($pol))
					->limit(1)
					->get()
					->row();
			}
			log_message('error', '[CO2_DEBUG][model] fallback x_route_distance -> row='.var_export($row, true).' | db_error='.var_export($this->dbx->error(), true));
		} catch (\Throwable $e) {
			log_message('error', '[CO2_DEBUG][model] EXCEPTION query x_route_distance: '.$e->getMessage());
			$this->dbx->reset_query();
		}

		$result = $row ? (float)$row->distance_km : null;
		log_message('error', '[CO2_DEBUG][model] get_distance_km() FINAL return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Ambil hasil emisi CO2 untuk job AIR FREIGHT (200/600) dari tabel cache
	 * `air_tracking_cache` - hasil tracking AWB lewat API eksternal yang
	 * SUDAH menghitung carbonEmission riil per-leg penerbangan (bukan
	 * estimasi weight x distance x factor seperti jalur ocean/trucking).
	 *
	 * Lebih akurat daripada jalur ocean (yang pakai UN/LOCODE + jarak
	 * garis-besar), karena di sini jaraknya dari rute penerbangan riil
	 * (great-circle antar bandara per-leg, bukan estimasi).
	 *
	 * PENTING soal kode_ref: `air_tracking_cache.kode_ref` disimpan TANPA
	 * huruf akhiran revisi/sub-shipment (mis. cache = "GCL-20026100813",
	 * sedangkan kode_ref di invoice/trnstuffingplandetail = "GCL-20026100813A").
	 * Jadi exact-match kode_ref SERING tidak ketemu walau datanya sebenarnya
	 * ada. Makanya urutan pencarian di bawah TIDAK mengandalkan kode_ref
	 * exact match duluan - prioritasnya:
	 * 1) $sp_id (FK trnstuffingplandetail.sp_id -> trnstuffingplan.sp_id,
	 *    DIKIRIM dari controller via $query['sp_id']) - ini paling presisi,
	 *    tidak kena masalah suffix huruf sama sekali.
	 * 2) $awb (dari pilihan radio MAWB/HAWB di editor invoice), dicocokkan
	 *    lewat kolom awb_digits (angka AWB saja, tanpa strip/spasi, supaya
	 *    tidak masalah beda format "232-18418853" vs "23218418853").
	 * 3) kode_ref EXACT match + region (jaga-jaga kalau memang persis sama).
	 * 4) kode_ref TANPA huruf akhiran terakhir (mis. "GCL-20026100813A" jadi
	 *    "GCL-20026100813") + region - fallback utama untuk kasus suffix di
	 *    atas kalau sp_id/awb juga tidak ada/tidak ketemu.
	 * Berhenti begitu salah satu ketemu baris; kalau beberapa baris cocok,
	 * dipakai yang fetched_at PALING BARU.
	 *
	 * api_response_json formatnya array hasil tracking (1 elemen per AWB).
	 * Field 'carbonEmission' di tiap elemen berupa STRING seperti
	 * "944.53 kg (est)" atau "102.71 kg" - bukan angka murni, jadi di-parse
	 * dengan regex. Kalau lebih dari 1 elemen (jarang, mis. shipment
	 * terpecah), nilainya DIJUMLAHKAN.
	 *
	 * Return null kalau tidak ada cache sama sekali, atau JSON-nya tidak
	 * punya field carbonEmission yang valid di elemen manapun.
	 */
	function get_air_tracking_co2($kode_ref, $region, $awb = '', $sp_id = null)
	{
		$kode_ref = trim((string)$kode_ref);
		$region   = trim((string)$region);
		$awb_digits = preg_replace('/\D/', '', (string)$awb);
		$sp_id = ($sp_id !== null && $sp_id !== '') ? (int)$sp_id : null;

		log_message('error', '[CO2_DEBUG][air][model] get_air_tracking_co2() kode_ref="'.$kode_ref.'" region="'.$region.'" awb_digits="'.$awb_digits.'" sp_id='.var_export($sp_id, true));

		if ($kode_ref === '' || $region === '') {
			return null;
		}

		$row = null;

		// 1) Paling presisi: sp_id (FK langsung, tidak kena masalah suffix huruf).
		if ($sp_id !== null && $sp_id > 0) {
			$this->dbx->reset_query();
			try {
				$row = $this->dbx
					->where('sp_id', $sp_id)
					->where('region_id', $region)
					->order_by('fetched_at', 'desc')
					->limit(1)
					->get('air_tracking_cache')
					->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][air][model] EXCEPTION query air_tracking_cache (by sp_id): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		// 2) AWB (MAWB/HAWB sesuai pilihan radio button).
		if (!$row && $awb_digits !== '') {
			$this->dbx->reset_query();
			try {
				$row = $this->dbx
					->where('region_id', $region)
					->where('awb_digits', $awb_digits)
					->order_by('fetched_at', 'desc')
					->limit(1)
					->get('air_tracking_cache')
					->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][air][model] EXCEPTION query air_tracking_cache (by awb): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		// 3) kode_ref exact match.
		if (!$row) {
			$this->dbx->reset_query();
			try {
				$row = $this->dbx
					->where('kode_ref', $kode_ref)
					->where('region_id', $region)
					->order_by('fetched_at', 'desc')
					->limit(1)
					->get('air_tracking_cache')
					->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][air][model] EXCEPTION query air_tracking_cache (by kode_ref exact): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		// 4) kode_ref tanpa huruf akhiran terakhir (suffix revisi/sub-shipment).
		if (!$row && preg_match('/^(.+\d)[A-Za-z]$/', $kode_ref, $mm)) {
			$kode_ref_base = $mm[1];
			$this->dbx->reset_query();
			try {
				$row = $this->dbx
					->where('kode_ref', $kode_ref_base)
					->where('region_id', $region)
					->order_by('fetched_at', 'desc')
					->limit(1)
					->get('air_tracking_cache')
					->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][air][model] EXCEPTION query air_tracking_cache (by kode_ref base): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		if (!$row || empty($row->api_response_json)) {
			log_message('error', '[CO2_DEBUG][air][model] tidak ada cache air_tracking_cache untuk kode_ref="'.$kode_ref.'" -> return null');
			return null;
		}

		$decoded = json_decode($row->api_response_json, true);
		if (!is_array($decoded) || empty($decoded)) {
			log_message('error', '[CO2_DEBUG][air][model] api_response_json tidak valid/kosong untuk kode_ref="'.$kode_ref.'" -> return null');
			return null;
		}

		$total_kg = 0.0;
		$total_distance_km = 0.0;
		$found = false;

		foreach ($decoded as $shipment) {
			$raw = is_array($shipment) ? ($shipment['carbonEmission'] ?? null) : null;
			if ($raw === null || $raw === '') {
				continue;
			}

			// Format dari API: "944.53 kg (est)" atau "102.71 kg" - ambil angka pertama.
			if (preg_match('/([\d]+(?:\.[\d]+)?)/', (string)$raw, $m)) {
				$total_kg += (float)$m[1];
				$found = true;
			}

			if (isset($shipment['distance']) && is_numeric($shipment['distance'])) {
				$total_distance_km += (float)$shipment['distance'];
			}
		}

		if (!$found || $total_kg <= 0) {
			log_message('error', '[CO2_DEBUG][air][model] field carbonEmission tidak ketemu/invalid di api_response_json untuk kode_ref="'.$kode_ref.'" -> return null');
			return null;
		}

		$result = array(
			'total_kg'    => round($total_kg, 1),
			'distance_km' => $total_distance_km > 0 ? round($total_distance_km, 1) : null,
			'status'      => isset($row->status) ? $row->status : null,
		);
		log_message('error', '[CO2_DEBUG][air][model] get_air_tracking_co2() return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Cari koordinat (lat/lng) alamat pengiriman shipper dari tabel
	 * `mstshipper_address`, dipakai untuk hitung jarak trucking (job 400)
	 * dari pelabuhan ke alamat shipper.
	 *
	 * Prioritas baris yang dipakai kalau shipper punya beberapa alamat:
	 * 1) address_type = 'DROP' (alamat tujuan pengiriman)
	 * 2) is_default = 1
	 * 3) baris aktif (isActive=1) pertama yang ketemu
	 *
	 * Return null kalau shipper belum punya alamat dengan lat/long terisi -
	 * ini DISENGAJA (bukan bug): sesuai arahan, untuk sekarang cuma shipper
	 * yang sudah punya titik koordinat yang dapat badge CO2.
	 */
	function get_shipper_coordinates($shipper_code)
	{
		$shipper_code = trim((string)$shipper_code);
		log_message('error', '[CO2_DEBUG][model] get_shipper_coordinates() shipper_code="'.$shipper_code.'"');

		if ($shipper_code === '') {
			log_message('error', '[CO2_DEBUG][model] shipper_code kosong -> return null');
			return null;
		}

		$this->dbx->reset_query();

		$row = null;
		try {
			$row = $this->dbx
				->select('latitude, longitude, address_type, is_default')
				->from('mstshipper_address')
				->where('id_shipper', $shipper_code)
				->where('isActive', 1)
				->where('latitude IS NOT NULL', null, false)
				->where('longitude IS NOT NULL', null, false)
				->order_by("FIELD(address_type,'DROP')", 'DESC', false) // DROP diutamakan
				->order_by('is_default', 'DESC')
				->limit(1)
				->get()
				->row();
			log_message('error', '[CO2_DEBUG][model] mstshipper_address -> row='.var_export($row, true).' | db_error='.var_export($this->dbx->error(), true));
		} catch (\Throwable $e) {
			log_message('error', '[CO2_DEBUG][model] EXCEPTION query mstshipper_address: '.$e->getMessage());
			$this->dbx->reset_query();
		}

		if (!$row || $row->latitude === null || $row->longitude === null || $row->latitude === '' || $row->longitude === '') {
			log_message('error', '[CO2_DEBUG][model] shipper "'.$shipper_code.'" belum punya lat/long terdaftar -> return null');
			return null;
		}

		$result = array('lat' => (float)$row->latitude, 'lng' => (float)$row->longitude);
		log_message('error', '[CO2_DEBUG][model] get_shipper_coordinates() return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Cari koordinat (lat/lng) sebuah KOTA dari tabel master
	 * `mstcity_coordinates` (dibangun dari data GeoNames, 447 kota di
	 * Indonesia dengan populasi >= 15.000, lihat file mstcity_coordinates.sql).
	 *
	 * Dipakai untuk job trucking antar-kota (900/901/902) - kota tujuan
	 * belum tentu terdaftar sebagai alamat shipper, jadi dicari dari tabel
	 * master kota, bukan dari mstshipper_address.
	 *
	 * Strategi pencarian (berurutan, berhenti begitu ketemu):
	 * 1) Exact match nama kota (abaikan spasi & besar/kecil huruf).
	 * 2) Match di kolom alt_names (varian nama lain, dipisah titik-koma).
	 * 3) Partial match (LIKE) di nama kota, untuk jaga-jaga penulisan yang
	 *    sedikit beda dari master (mis. ada kata tambahan).
	 *
	 * Pakai raw parameterized query ($this->dbx->query($sql, array(...)))
	 * bukan query builder where()/like() biasa, karena kondisi WHERE di
	 * sini adalah ekspresi SQL (REPLACE(UPPER(...))), bukan nama kolom
	 * polos - supaya nilai pencarian tetap di-escape otomatis lewat
	 * parameter binding (aman dari SQL injection), bukan ditulis manual.
	 *
	 * Return null kalau kota tidak ketemu di master -> badge CO2 tidak
	 * ditampilkan untuk shipment ini (bukan bug, disengaja).
	 */
	function get_city_coordinates($city_name)
	{
		$city_name = trim((string)$city_name);
		log_message('error', '[CO2_DEBUG][model] get_city_coordinates() city_name="'.$city_name.'"');

		if ($city_name === '') {
			log_message('error', '[CO2_DEBUG][model] city_name kosong -> return null');
			return null;
		}

		$needle = str_replace(' ', '', strtoupper($city_name));
		$this->dbx->reset_query();

		$row = null;

		try {
			$sql = "SELECT name, latitude, longitude FROM mstcity_coordinates
					WHERE REPLACE(UPPER(name), ' ', '') = ? AND is_active = 1 LIMIT 1";
			$row = $this->dbx->query($sql, array($needle))->row();
		} catch (\Throwable $e) {
			log_message('error', '[CO2_DEBUG][model] EXCEPTION query mstcity_coordinates (exact): '.$e->getMessage());
			$this->dbx->reset_query();
		}

		if (!$row) {
			try {
				$sql = "SELECT name, latitude, longitude FROM mstcity_coordinates
						WHERE REPLACE(UPPER(alt_names), ' ', '') LIKE ? AND is_active = 1 LIMIT 1";
				$row = $this->dbx->query($sql, array('%'.$needle.'%'))->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][model] EXCEPTION query mstcity_coordinates (alt_names): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		if (!$row) {
			try {
				$sql = "SELECT name, latitude, longitude FROM mstcity_coordinates
						WHERE REPLACE(UPPER(name), ' ', '') LIKE ? AND is_active = 1 LIMIT 1";
				$row = $this->dbx->query($sql, array('%'.$needle.'%'))->row();
			} catch (\Throwable $e) {
				log_message('error', '[CO2_DEBUG][model] EXCEPTION query mstcity_coordinates (partial): '.$e->getMessage());
				$this->dbx->reset_query();
			}
		}

		if (!$row || $row->latitude === null || $row->longitude === null) {
			log_message('error', '[CO2_DEBUG][model] kota "'.$city_name.'" tidak ketemu di mstcity_coordinates -> return null');
			return null;
		}

		$result = array('lat' => (float)$row->latitude, 'lng' => (float)$row->longitude);
		log_message('error', '[CO2_DEBUG][model] get_city_coordinates() return = '.var_export($result, true));
		return $result;
	}

	/**
	 * Panggil OpenRouteService Directions API (instance HeiGIT) untuk dapat
	 * jarak darat riil antara 2 titik koordinat. Return jarak dalam KM (API
	 * asalnya kasih meter).
	 *
	 * $profile: 'driving-car' (default) atau 'driving-hgv'.
	 *
	 * PENTING - kenapa default-nya 'driving-car', bukan 'driving-hgv':
	 * Untuk rute pendek Tanjung Priok <-> Karawang (BMJ, job 400/702/703),
	 * profile 'driving-hgv' ORS mengembalikan rute yang RUSAK/tidak masuk
	 * akal - muter lewat "Pelabuhan Feri Tanjung Ru"/"Pelabuhan Feri Sadai"
	 * (penyeberangan ferry ke Bangka-Belitung) dan "Tol Bakauheni-Terbanggi
	 * Besar" (jalan tol di Sumatra), menghasilkan jarak ~1.693 km padahal
	 * jarak lurusnya cuma ~48 km. Ini sudah dikonfirmasi via testing Postman
	 * langsung ke API (bukan salah koordinat - koordinat sudah dicek benar
	 * di Google Maps/Wikimapia) dan ternyata graf jalan khusus HGV di ORS
	 * punya gap/cacat konektivitas di area ini, jadi router "nekat" nyari
	 * jalan muter lewat pulau lain. Profile 'driving-car' untuk rute yang
	 * sama terbukti (via Postman) mengembalikan rute yang wajar.
	 *
	 * 'driving-hgv' TETAP dipakai (lewat parameter $profile) khusus untuk
	 * job 900/901/902 (trucking antar-kota, mis. Karawang -> Pematang
	 * Siantar) sesuai permintaan user - rute jarak jauh antar kota ini
	 * tidak mengalami masalah connectivity gap yang sama seperti rute
	 * pendek Priok-Karawang di atas.
	 *
	 * Endpoint ini pakai GET dengan api_key di query string (bukan header
	 * Authorization), dan hasilnya GeoJSON FeatureCollection - beda dari
	 * dokumentasi ORS standar yang biasa dipakai (POST + routes[].summary).
	 *
	 * Catatan urutan koordinat: ORS pakai "lon,lat" (KEBALIK dari urutan
	 * lat/lng biasa), sudah ditangani di dalam method ini.
	 *
	 * TODO: API key di bawah sebaiknya dipindah ke file config/environment
	 * variable, bukan hardcoded di source - tapi untuk sekarang cukup di sini.
	 */
	function call_road_distance_api($origin_lat, $origin_lng, $dest_lat, $dest_lng, $profile = 'driving-car')
	{
		$profile = in_array($profile, array('driving-car', 'driving-hgv'), true) ? $profile : 'driving-car';

		$api_key = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImI3YjlkYTA4MzFkODQzMGFhOTcxMjExNDA1Y2E0Zjc1IiwiaCI6Im11cm11cjY0In0=';
		$base_url = 'https://api.heigit.org/openrouteservice/v2/directions/'.$profile;

		$start = $origin_lng.','.$origin_lat; // ORS: lon,lat
		$end   = $dest_lng.','.$dest_lat;

		$url = $base_url.'?'.http_build_query(array(
			'api_key' => $api_key,
			'start'   => $start,
			'end'     => $end,
		));

		log_message('error', '[CO2_DEBUG][model] call_road_distance_api() GET start='.$start.' end='.$end);

		if (!function_exists('curl_init')) {
			log_message('error', '[CO2_DEBUG][model] EXTENSI curl TIDAK AKTIF di server ini -> return null');
			return null;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8',
			'Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
		));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 8);
		$response  = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_err  = curl_error($ch);
		curl_close($ch);

		log_message('error', '[CO2_DEBUG][model] response ORS: http_code='.$http_code.' curl_error="'.$curl_err.'" body='.var_export($response, true));

		if ($response === false || $http_code != 200) {
			log_message('error', 'Road distance API (ORS) gagal: '.$curl_err.' | HTTP '.$http_code);
			return null;
		}

		$data = json_decode($response, true);
		$distance_m = $data['features'][0]['properties']['summary']['distance'] ?? null;

		if (!is_numeric($distance_m)) {
			log_message('error', '[CO2_DEBUG][model] response ORS tidak punya features[0].properties.summary.distance yang valid. decoded='.var_export($data, true));
			return null;
		}

		$distance_m  = (float)$distance_m;
		$distance_km = $distance_m / 1000;
		log_message('error', '[CO2_DEBUG][model] distance_m='.$distance_m.' -> distance_km='.$distance_km);
		return $distance_km;
	}
	
	function get_table_tiga($post,$limit){
	
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}
		$this->dbx->order_by($post['sidx'],$post['sord']);
		return $this->dbx->get_where('x_finance_item',array('number'=>$post['id']))->result();
	}
	
	function getInvoice($job_no){
		$sql = "SELECT * FROM `v_sp_gabung` where kode_ref='$job_no'"; 		
		$query = $this->dbx->query($sql);			
		return $query->row();
	}
	
	function getKostumer($id_kost){
		$this->dbx->select('*');
		$this->dbx->from('mstadmin');
		$this->dbx->where('shipper_id',$id_kost);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();	
		if($query->num_rows() > 0 ) {		
			return $query->row();
			} else {
			return null;
		}
	}
	
	function get_nama_sales($id){
		$this->dbx->select(array('user_id','fullname'));
		$this->dbx->from('mstadmin');
		$this->dbx->where('user_id',$id);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get();
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->fullname;
			} else {
			return "";	
		}
	}
	
	function get_code_shipper($shipper_name){

		$this->dbx->select(array('shipper_id'));
		$this->dbx->from('mstshipper');
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$this->dbx->where('shipper_name',$shipper_name);
		$q = $this->dbx->get();	
		
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->shipper_id;
			} else {
			return "";	
		}
	}
	
	function get_nama_shipper($id){
	
		$this->dbx->select(array('id_shipper','shipper_name'));
		$this->dbx->from('mstshipper');
		$this->dbx->where('id_shipper',$id);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get();
		
	
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->shipper_name;
			} else {
			return "";	
		}
	}
	
	function get_alamat_shipper($shipper_name){

		$this->dbx->select(array('id_shipper','shipper_address'));
		$this->dbx->from('mstshipper');
		$this->dbx->where('shipper_name',$shipper_name);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get();	
	
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->shipper_address;
			} else {
			return "";	
		}
	}
	
	function get_nama_consigne($id){
		$this->dbx->select(array('consignee_import_code','consignee_import_name'));
		$this->dbx->from('refconsigneeimport');
		$this->dbx->where('consignee_import_code',$id);
		$q = $this->dbx->get();
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->consignee_import_name;
			} else {
			return "";	
		}
	}
	
	function get_alamat_consignee($id){
		$this->dbx->select(array('consignee_import_code','consignee_import_address'));
		$this->dbx->from('refconsigneeimport');
		$this->dbx->where('consignee_import_code',$id);
		$q = $this->dbx->get();
		//print_r($this->dbx->last_query());
		//die();
		if($q->num_rows() == 1 ) {
			$row = $q->row();
			return $row->consignee_import_address;
			} else {
			return "";	
		}
	}
	
	function get_alamat_npwp($npwp){
		$this->dbx->select('*');
		$this->dbx->from('ref_account');
		$this->dbx->where('tax_id',$npwp);
		$q = $this->dbx->get();
		//print_r($this->dbx->last_query());
		//die();
		if($q->num_rows() == 1 ) {
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
			return $row->account_address.$blok.$nomor.$rt.$rw.$kelurahan.$kecamatan.$kabupaten.$propinsi.$kodepos;
			} else {
			return "";	
		}
	}
	
	// =========================================================================
	// FUNCTION UTAMA: MANAGE DATA (ANTI BENTROK & AUTO FORMAT)
	// =========================================================================
	public function manage_data($data)
    {
        $oper = $data['oper'];
        $table = $data['table'];
        $id_form = isset($data['id']) ? $data['id'] : $this->input->post('id');

        unset($data['oper']);
        unset($data['table']);
        unset($data['id']);

        // ----------------------------------------------------------------
        // 1. KONEKSI DB (ANTI-SALAH TAHUN)
        // ----------------------------------------------------------------
        $job_no = isset($data['job_no']) ? $data['job_no'] : '';
        $target_year = $this->session->userdata('db'); // Default
        
        if(strlen($job_no) >= 9) {
            $short_year = substr($job_no, 7, 2); 
            $target_year = '20' . $short_year;
        }
        
        $db_target = $this->load->database(switch_db($target_year), TRUE);

        // ----------------------------------------------------------------
        // 2. LOGIKA ADD (Insert Baru)
        // ----------------------------------------------------------------
        if ($oper == 'add') {
            
            $region = $this->session->userdata('region_id');
            $user   = $this->session->userdata('fullname');
            $now    = date('Y-m-d H:i:s');

            $jenis_code = isset($data['jenis_code']) ? $data['jenis_code'] : '1';
            unset($data['jenis_code']); 

            // Bersihkan data
            $shipper_name = isset($data['shipper_name'])    ? strtoupper(trim($data['shipper_name']))    : '';
            $shipper_add  = isset($data['shipper_address']) ? strtoupper(trim($data['shipper_address'])) : '';
            $nama         = isset($data['nama'])            ? trim($data['nama'])                        : '';
            $npwp         = isset($data['npwp'])            ? trim($data['npwp'])                        : '';
            $alamat       = (!empty($npwp) && isset($data['alamat'])) ? trim($data['alamat']) : '';
            $pph23_val    = isset($data['pph23']) ? $data['pph23'] : '';

            unset($data['shipper_name'], $data['shipper_address'], $data['nama'], $data['npwp'], $data['alamat'], $data['pph23']);

            $data['created_by'] = $user;
            $data['created_at'] = $now;
            $data['status']     = 'unpaid';
            $data['region_id']  = $region;

            // --- LOOPING INSERT (ANTI-BENTROK) ---
            $saved = false;
            $attempt = 0;
            $max_retries = 10;
            $final_number_str = '';
            $new_id = 0;

            while (!$saved && $attempt < $max_retries) {

                // A. Generate Angka Urut (Misal: 13275)
                $param_gen = [
                    'job_no' => $data['job_no'],
                    'jenis'  => $jenis_code
                ];
                $seq_number = $this->fungsi->get_nomer_invoice($param_gen);
				//print_r ($seq_number);
				//die();
                
                // Jika library error dan return kosong/0, paksa minimal 1
                if(!$seq_number || $seq_number == 0) $seq_number = 1;

                // B. FORMAT NOMOR (INLINE LOGIC)
                // Kita rakit string di sini agar TIDAK MUNGKIN jadi angka saja
                $prefix = "IN/"; 
                $job_head = substr($job_no, 4, 1); 

                if ($jenis_code == '1') { // INVOICE
                    if (in_array($job_head, ['4','9'])) {
                        if ($region == "Cikarang") $prefix = "INC/";
                        elseif ($region == "Surabaya") $prefix = "SYINF/";
                        elseif ($region == "Semarang") $prefix = "SMINF/";
                        elseif ($region == "Medan") $prefix = "MINF/";
                        elseif ($region == "Bandung") $prefix = "BINF/";
                        elseif ($region == "Makassar") $prefix = "MKSIN/";
                        elseif ($region == "GCL WAREHOUSE") $prefix = "INWHM/";
                        elseif ($region == "Winfast") $prefix = "INWIN/";
                        elseif ($region == "BPLINE") $prefix = "INBPL/";
                        else $prefix = "INV/";
                    }
                    elseif ($job_head == '7') {
                        if ($region == "Cikarang") $prefix = "INC/";
                        elseif ($region == "Surabaya") $prefix = "SYINF/";
                        elseif ($region == "Semarang") $prefix = "SMINF/";
                        elseif ($region == "Medan") $prefix = "MINF/";
                        elseif ($region == "Bandung") $prefix = "BINF/";
                        elseif ($region == "Makassar") $prefix = "MKSIN/";
                        elseif ($region == "Winfast") $prefix = "INWIN/";
                        elseif ($region == "BPLINE") $prefix = "INBPL/";
                        else $prefix = "INF/";
                    }
                    else { // Default Region Logic
                        if ($region == "Cikarang") $prefix = "INC/";
                        elseif ($region == "Makassar") $prefix = "MKSIN/";
                        elseif ($region == "GCL WAREHOUSE") $prefix = "INWHM/";
                        elseif ($region == "Surabaya") $prefix = "SYINF/";
                        elseif ($region == "Semarang") $prefix = "SMINF/";
                        elseif ($region == "Medan") $prefix = "MINF/";
                        elseif ($region == "Bandung") $prefix = "BINF/";
                        else $prefix = "IN/";
                    }
                }
                elseif ($jenis_code == '2') { // REIMBURSEMENT
                    if ($region == "Cikarang") $prefix = "CINR/";
                    elseif ($region == "Surabaya") $prefix = "SINR/";
                    elseif ($region == "Semarang") $prefix = "SRINR/";
                    elseif ($region == "Medan") $prefix = "MINR/";
                    elseif ($region == "Bandung") $prefix = "BINR/";
                    elseif ($region == "Makassar") $prefix = "MKSINR/";
                    else $prefix = "INR/";
                }
                elseif ($jenis_code == '3') { // FAKTUR PAJAK
                    if ($region == "Cikarang") $prefix = "ICFP/";
                    elseif ($region == "Surabaya") $prefix = "SUBFP/";
                    elseif ($region == "Semarang") $prefix = "SMGFP/";
                    elseif ($region == "Medan") $prefix = "MINFP/";
                    elseif ($region == "Bandung") $prefix = "BINFP/";
                    elseif ($region == "Makassar") $prefix = "MKSINFP/";
                    elseif ($region == "GCL WAREHOUSE") $prefix = "INWHM/";
                    else $prefix = "INFP/";
                }

                $thn_short = substr($job_no, 7, 2); 
                // Format Akhir: Prefix + Tahun + Angka (Padding 5 nol)
                $final_number_str = $prefix . $thn_short . str_pad($seq_number, 5, '0', STR_PAD_LEFT);

                // Override nomor di array data
                $data['number'] = $final_number_str;

                // C. TRY INSERT
                $db_target->trans_begin();
                $db_target->db_debug = FALSE; // PENTING: Matikan debug DB

                try {
                    // Cek Insert Header
                    if (!$db_target->insert($table, $data)) {
                        $error = $db_target->error();
                        // Lempar error ke catch manual
                        throw new \Exception($error['message'], $error['code']);
                    }

                    $new_id = $db_target->insert_id(); 

                    // Insert Detail
                    $db_target->insert('x_invoice', [
                        'number'          => $final_number_str,
                        'shipper_name'    => $shipper_name,
                        'shipper_address' => $shipper_add,
                        'nama'            => $nama,
                        'npwp'            => $npwp,
                        'alamat'          => $alamat,
                        'pph23'           => $pph23_val,
                        'region_id'       => $region,
                    ]);

                    $db_target->trans_commit();
                    $saved = true;

                } catch (\Exception $e) { // Gunakan backslash \Exception (Global)
                    $db_target->trans_rollback();
                    
                    // Cek Error Duplicate (Code 1062 atau pesan duplicate)
                    if ($e->getCode() == 1062 || stripos($e->getMessage(), 'Duplicate entry') !== false) {
                        $attempt++; // Gagal, coba lagi dengan seq_number berikutnya (jika fungsi get_nomer_invoice membaca DB real-time)
                        continue; 
                    } else {
                        // Error SQL lain (Fatal)
                        $db_target->close();
                        return ['status' => 0, 'msg' => 'DB Error: ' . $e->getMessage()];
                    }
                }
            }

            $db_target->close();

            if ($saved) {
                return [
                    'status' => 1,
                    'new_number' => $final_number_str,
                    'new_id' => $new_id
                ];
            } else {
                return ['status' => 0, 'msg' => 'Gagal generate nomor unik. Mohon coba lagi.'];
            }
        } 
        
        // ----------------------------------------------------------------
        // 3. LOGIKA EDIT
        // ----------------------------------------------------------------
        else if ($oper == 'edit') {
            $region = trim((string)$this->session->userdata('region_id'));
            $user   = (string)$this->session->userdata('fullname');
            $now    = date('Y-m-d H:i:s');
            $idEdit = (int)$id_form;

            if ($table !== 'x_finance' || $region === '' || $idEdit <= 0) {
                $db_target->close();
                return ['status'=>0, 'msg'=>'INVALID_INVOICE_EDIT_CONTEXT'];
            }

            /* Header DB adalah source of truth; jangan percaya status/number dari POST. */
            $current = $db_target
                ->where('id', $idEdit)
                ->where('region_id', $region)
                ->limit(1)
                ->get('x_finance')
                ->row_array();

            if (!$current) {
                $db_target->close();
                return ['status'=>0, 'msg'=>'INVOICE_NOT_FOUND'];
            }

            if (strtolower(trim((string)($current['status'] ?? ''))) === 'paid') {
                $response = $this->paid_revision_required_response($current);
                $response['msg'] = $response['message'];
                $db_target->close();
                return $response;
            }

            $shipper_name = isset($data['shipper_name'])    ? strtoupper(trim($data['shipper_name']))    : '';
            $shipper_add  = isset($data['shipper_address']) ? strtoupper(trim($data['shipper_address'])) : '';
            $nama         = isset($data['nama'])            ? trim($data['nama'])                        : '';
            $npwp         = isset($data['npwp'])            ? trim($data['npwp'])                        : '';
            $alamat       = (!empty($npwp) && isset($data['alamat'])) ? trim($data['alamat']) : '';
            $pph23_val    = isset($data['pph23']) ? $data['pph23'] : '';

            unset(
                $data['shipper_name'], $data['shipper_address'], $data['nama'],
                $data['npwp'], $data['alamat'], $data['pph23'], $data['jenis_code'],
                $data['status'], $data['region_id'], $data['created_by'], $data['created_at']
            );

            /* Ownership identity tidak boleh dipindah lewat edit header. */
            $data['number']    = (string)($current['number'] ?? '');
            $data['job_no']    = (string)($current['job_no'] ?? '');
            $data['region_id'] = $region;
            $data['updated_by'] = $user;
            $data['updated_at'] = $now;

            $db_target->trans_begin();

            $db_target
                ->where('id', $idEdit)
                ->where('region_id', $region)
                ->where('status !=', 'paid')
                ->update('x_finance', $data);

            if ($db_target->trans_status() === false) {
                $err = $db_target->error();
                $db_target->trans_rollback();
                $db_target->close();
                return ['status'=>0, 'msg'=>'INVOICE_EDIT_FAILED', 'error'=>$err];
            }

            $invoiceNo = (string)($current['number'] ?? '');
            $data_inv = [
                'shipper_name'    => $shipper_name,
                'shipper_address' => $shipper_add,
                'nama'            => $nama,
                'npwp'            => $npwp,
                'alamat'          => $alamat,
                'pph23'           => $pph23_val,
            ];

            $cek_inv = $db_target
                ->where('number', $invoiceNo)
                ->where('region_id', $region)
                ->get('x_invoice');

            if ($cek_inv->num_rows() > 0) {
                $db_target
                    ->where('number', $invoiceNo)
                    ->where('region_id', $region)
                    ->update('x_invoice', $data_inv);
            } else {
                $data_inv['number'] = $invoiceNo;
                $data_inv['region_id'] = $region;
                $db_target->insert('x_invoice', $data_inv);
            }

            if ($db_target->trans_status() === false) {
                $err = $db_target->error();
                $db_target->trans_rollback();
                $db_target->close();
                return ['status'=>0, 'msg'=>'INVOICE_DETAIL_EDIT_FAILED', 'error'=>$err];
            }

            $db_target->trans_commit();
            $db_target->close();

            $this->mark_revision_stage($idEdit, $region, 'EDITED');

            return ['status'=>1];
        }
        
        // ----------------------------------------------------------------
        // 4. LOGIKA DELETE
        // ----------------------------------------------------------------
        else if ($oper == 'del') {
            /*
             * Header invoice tidak boleh dihapus mentah dari editor.
             * Flow delete yang benar adalah List Invoice, karena di sana source
             * direct mapping dilepas, audit delete dibuat, lalu header/detail dihapus.
             */
            $db_target->close();
            return [
                'status' => 0,
                'msg'    => 'DELETE_INVOICE_USE_LIST_INVOICE_FLOW'
            ];
        }
        
        return 0;
    }

	// HELPER: AMBIL TAHUN DARI JOB NO
	private function get_db_year($job_no) {
		if(strlen($job_no) >= 9) {
			$short_year = substr($job_no, 7, 2); 
			return '20' . $short_year; 
		}
		return $this->session->userdata('db');
	}
	
	
	function manage_data_table($data){
		$table = $data['table'];
		$id = $data['id'];
		$oper = $data['oper'];
		if($oper=='add') {
			$region_id = substr($this->session->userdata('region_id'),0,1);
			$destination_id = $this->fungsi->get_country_id($data['destination']);
			$kode_ref = substr($data['kode_ref'],11,6);
			$data['bl_no']=$this->get_bl_num($row->kode_ref);
			$this->dbx->insert($table,$data);
			return $this->dbx->affected_rows();
		}
		if($oper=='edit') {
			$this->dbx->where('id',$id);
			$this->dbx->update($table,array($data['field']=>$data['value']));
			return $this->dbx->affected_rows();
		}
	}
public function get_data_pajak($ids)
{
    // pastikan $ids array & integer
    if (!is_array($ids)) {
        $ids = array($ids);
    }

    $clean_ids = array();
    foreach ($ids as $v) {
        $v = trim($v);
        if ($v !== '' && is_numeric($v)) {
            $clean_ids[] = (int)$v;
        }
    }

    if (empty($clean_ids)) {
        return array();
    }

    // kalau mau debug:
    // log_message('debug', 'get_data_pajak IDS: '.print_r($clean_ids,true));
    $this->dbx->reset_query(); // penting, supaya sisa query sebelumnya tidak nyangkut

    $this->dbx->select("
        x_finance.id              AS id,
        x_finance.job_no          AS job_no,
        x_finance.jenis           AS jenis,
        x_finance.kepada          AS kepada,
        x_finance.kepada_kode     AS kepada_kode,
        x_finance.`group`         AS group_finance,
        x_finance.number          AS number,
        x_finance.date            AS date,
        x_finance.region_id       AS region_id,
        x_finance.currency        AS currency,
        x_finance.kurs            AS kurs,
        x_finance.se_exportir     AS si,
        x_finance.tgl_bayar       AS tgl_bayar,
        x_finance.fin_proses      AS fin_proses,

        x_finance_item.id         AS id_item,
        x_finance_item.kepada     AS kepada_item,
        x_finance_item.jenis      AS item_jenis,
        x_finance_item.kode_ref   AS kode_ref,
        x_finance_item.bl_no      AS bl_no,
        x_finance_item.number     AS number_item,
        x_finance_item.code       AS code,
        x_finance_item.item       AS item,
        x_finance_item.qty        AS qty,
        x_finance_item.unit_price AS unit_price,
        x_finance_item.price      AS price,
        x_finance_item.currency   AS currency_item,
        x_finance_item.remarks    AS remarks,
        x_finance_item.pre_col    AS pre_col,
        x_finance_item.ppn        AS ppn,
        x_finance_item.anak_job   AS anak_job,

        x_invoice.number          AS inv_number,
        x_invoice.shipper_name    AS shipper_name,
        x_invoice.shipper_address AS shipper_address,
        x_invoice.nama            AS nama,
        x_invoice.npwp            AS npwp,
        x_invoice.alamat          AS alamat
    ", FALSE);

    $this->dbx->from('x_finance');
    $this->dbx->join('x_finance_item','x_finance_item.number = x_finance.id','inner');
    $this->dbx->join('x_invoice','x_invoice.number = x_finance.number','inner');

    // di sinilah bedanya: WHERE IN dengan array integer yang sudah bersih
    $this->dbx->where_in('x_finance.id', $clean_ids);
    $this->dbx->where('x_finance_item.price !=', 0);
    $this->dbx->not_like('x_finance_item.item', 'TAX 1');
    $this->dbx->where('x_finance_item.item !=', 'VAT');

    // untuk sementara, JANGAN group_by dulu
    $this->dbx->group_by('x_finance.number');

    $this->dbx->order_by('x_finance.id', 'ASC');
    $this->dbx->order_by('x_finance_item.id', 'ASC');

    $query = $this->dbx->get();

    // untuk debug kalau perlu:
    // log_message('debug', 'SQL get_data_pajak: '.$this->dbx->last_query());

    if (!$query) {
        return array();
    }

    $result = $query->result();
    return $result ? $result : array();
}


	function get_data_pajak_array($no_inv){
		$this->dbx->select('x_finance.id as id,
x_finance.job_no as job_no,
x_finance.jenis as jenis,
x_finance.kepada as kepada,
x_finance.kepada_kode as kepada_kode,
x_finance.group as group,
x_finance.number as number,
x_finance.date as date,
x_finance.region_id as region_id,
x_finance.currency as currency,
x_finance.kurs as kurs,
x_finance.se_exportir as si,
x_finance.tgl_bayar as tgl_bayar,
x_finance.fin_proses as fin_proses,
x_finance_item.id as id_item,
x_finance_item.kepada as kepada_item,
x_finance_item.jenis as item_jenis,
x_finance_item.kode_ref as kode_ref,
x_finance_item.bl_no as bl_no,
x_finance_item.number as number_item,
x_finance_item.code as code,
x_finance_item.item as item,
x_finance_item.qty as qty,
x_finance_item.unit_price as unit_price,
x_finance_item.price as price,
x_finance_item.currency as currency_item,
x_finance_item.remarks as remarks,
x_finance_item.pre_col as pre_col,
x_finance_item.ppn as ppn,
x_finance_item.anak_job as anak_job,
x_invoice.number as inv_number,
x_invoice.shipper_name as shipper_name,
x_invoice.shipper_address as shipper_address,
x_invoice.nama as nama,
x_invoice.npwp as npwp,
x_invoice.alamat as alamat
');
		$this->dbx->from('x_finance');
		$this->dbx->join('x_finance_item','x_finance_item.number=x_finance.id');
		$this->dbx->join('x_invoice','x_invoice.number=x_finance.number');
		$this->dbx->where_in('x_finance.number', $no_inv);
		$this->dbx->where('x_finance_item.price !=', 0 );
		$this->dbx->not_like('x_finance_item.item', 'TAX 1');
		$this->dbx->where('x_finance_item.item !=', 'VAT');
		$query = $this->dbx->get()->result();
		if (count($query) > 0)  {
			return $query;
		} else {
			return null;	
		}
	}
	


	public function get_active_db()
	{
		return $this->dbx;
	}

	public function find_finance_for_post($finance_id = 0, $invoice_no = '')
	{
		$region = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)$finance_id;
		$invoice_no = trim((string)$invoice_no);

		if ($region === '' || ($finance_id <= 0 && $invoice_no === '')) {
			return null;
		}

		$this->dbx->from('x_finance')
			->where('region_id', $region);

		if ($finance_id > 0) {
			$this->dbx->where('id', $finance_id);
		}

		if ($invoice_no !== '') {
			$this->dbx->where('number', $invoice_no);
		}

		return $this->dbx->limit(1)->get()->row_array();
	}


	/**
	 * CONTROLLED PAID REVISION
	 *
	 * Invoice PAID boleh direvisi, tetapi harus dibuka eksplisit.
	 * Reopen hanya mengubah status legacy PAID -> UNPAID dan mencatat snapshot.
	 * Payment/allocation/GL history tidak dihapus di fungsi ini.
	 */
	private function revision_schema_ready()
	{
		return (
			$this->dbx->table_exists('x_finance_revision_log')
			&& $this->dbx->field_exists('revision_no', 'x_finance')
			&& $this->dbx->field_exists('revision_state', 'x_finance')
			&& $this->dbx->field_exists('revision_reason', 'x_finance')
			&& $this->dbx->field_exists('revision_previous_status', 'x_finance')
			&& $this->dbx->field_exists('revision_opened_at', 'x_finance')
			&& $this->dbx->field_exists('revision_opened_by', 'x_finance')
			&& $this->dbx->field_exists('revision_updated_at', 'x_finance')
		);
	}

	private function revision_snapshot($finance_id, $region)
	{
		$header = $this->dbx
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->limit(1)
			->get('x_finance')
			->row_array();

		$items = $this->dbx
			->where('number', (int)$finance_id)
			->where('region_id', $region)
			->order_by('id', 'asc')
			->get('x_finance_item')
			->result_array();

		return [
			'header' => $header ?: [],
			'items'  => $items ?: [],
		];
	}

	private function record_revision_event($finance_id, $region, $action, $reason = '', $status_before = null, $status_after = null, $snapshot = null)
	{
		if (!$this->revision_schema_ready()) {
			return false;
		}

		if ($snapshot === null) {
			$snapshot = $this->revision_snapshot($finance_id, $region);
		}

		$header = (isset($snapshot['header']) && is_array($snapshot['header'])) ? $snapshot['header'] : [];
		$items  = (isset($snapshot['items']) && is_array($snapshot['items'])) ? $snapshot['items'] : [];

		if (empty($header)) {
			return false;
		}

		return (bool)$this->dbx->insert('x_finance_revision_log', [
			'finance_id'      => (int)$finance_id,
			'invoice_no'      => (string)($header['number'] ?? ''),
			'job_no'          => (string)($header['job_no'] ?? ''),
			'region_id'       => (string)$region,
			'revision_no'     => (int)($header['revision_no'] ?? 0),
			'action'          => strtoupper(trim((string)$action)),
			'reason'          => trim((string)$reason),
			'status_before'   => $status_before !== null ? (string)$status_before : (string)($header['status'] ?? ''),
			'status_after'    => $status_after !== null ? (string)$status_after : (string)($header['status'] ?? ''),
			'grand_total'     => isset($header['grand_total']) ? (float)$header['grand_total'] : 0,
			'ppn'             => isset($header['ppn']) ? (float)$header['ppn'] : 0,
			'snapshot_header' => json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'snapshot_items'  => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'created_by'      => (string)$this->session->userdata('fullname'),
			'created_at'      => date('Y-m-d H:i:s'),
		]);
	}

	private function mark_revision_stage($finance_id, $region, $stage)
	{
		if (!$this->revision_schema_ready()) {
			return;
		}

		$header = $this->dbx
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->limit(1)
			->get('x_finance')
			->row_array();

		if (!$header || (int)($header['revision_no'] ?? 0) <= 0) {
			return;
		}

		$this->dbx
			->where('id', (int)$finance_id)
			->where('region_id', $region)
			->update('x_finance', [
				'revision_state'      => strtoupper(trim((string)$stage)),
				'revision_updated_at' => date('Y-m-d H:i:s'),
			]);

		$this->record_revision_event($finance_id, $region, $stage);
	}

	public function reopen_paid_invoice_for_revision($finance_id, $reason)
	{
		$region     = trim((string)$this->session->userdata('region_id'));
		$finance_id = (int)$finance_id;
		$reason     = trim((string)$reason);

		if ($region === '' || $finance_id <= 0) {
			return ['status'=>0, 'http'=>422, 'message'=>'INVALID_REVISION_CONTEXT'];
		}

		if (mb_strlen($reason) < 5) {
			return ['status'=>0, 'http'=>422, 'message'=>'REVISION_REASON_REQUIRED'];
		}

		if (!$this->revision_schema_ready()) {
			return ['status'=>0, 'http'=>500, 'message'=>'REVISION_SCHEMA_REQUIRED'];
		}

		$this->dbx->trans_begin();

		$finance = $this->dbx->query(
			"SELECT * FROM x_finance WHERE id = ? AND region_id = ? LIMIT 1 FOR UPDATE",
			[$finance_id, $region]
		)->row_array();

		if (!$finance) {
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>404, 'message'=>'INVOICE_NOT_FOUND'];
		}

		$current_status = strtolower(trim((string)($finance['status'] ?? '')));

		if ($current_status !== 'paid') {
			$this->dbx->trans_commit();
			return [
				'status'         => 1,
				'http'           => 200,
				'message'        => 'INVOICE_ALREADY_OPEN_FOR_REVISION',
				'finance_id'     => $finance_id,
				'invoice_no'     => (string)($finance['number'] ?? ''),
				'current_status' => (string)($finance['status'] ?? ''),
				'revision_no'    => (int)($finance['revision_no'] ?? 0),
				'revision_state' => (string)($finance['revision_state'] ?? ''),
			];
		}

		$before        = $this->revision_snapshot($finance_id, $region);
		$next_revision = ((int)($finance['revision_no'] ?? 0)) + 1;
		$now           = date('Y-m-d H:i:s');
		$user          = (string)$this->session->userdata('fullname');

		$update = [
			'status'                   => 'unpaid',
			'revision_no'              => $next_revision,
			'revision_state'           => 'OPEN',
			'revision_reason'          => $reason,
			'revision_previous_status' => (string)($finance['status'] ?? 'paid'),
			'revision_opened_at'       => $now,
			'revision_opened_by'       => $user,
			'revision_updated_at'      => $now,
		];

		if ($this->dbx->field_exists('updated_by', 'x_finance')) {
			$update['updated_by'] = $user;
		}
		if ($this->dbx->field_exists('updated_at', 'x_finance')) {
			$update['updated_at'] = $now;
		}

		$ok = $this->dbx
			->where('id', $finance_id)
			->where('region_id', $region)
			->where('status', 'paid')
			->update('x_finance', $update);

		if (!$ok || $this->dbx->affected_rows() !== 1) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>409, 'message'=>'FAILED_REOPEN_PAID_INVOICE', 'error'=>$err];
		}

		/* Audit snapshot harus mewakili kondisi sebelum status dibatalkan. */
		$before['header']['revision_no'] = $next_revision;

		if (!$this->record_revision_event(
			$finance_id,
			$region,
			'REOPEN_PAID',
			$reason,
			'paid',
			'unpaid',
			$before
		)) {
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>500, 'message'=>'FAILED_WRITE_REVISION_AUDIT'];
		}

		if ($this->dbx->trans_status() === false) {
			$err = $this->dbx->error();
			$this->dbx->trans_rollback();
			return ['status'=>0, 'http'=>500, 'message'=>'REVISION_TRANSACTION_FAILED', 'error'=>$err];
		}

		$this->dbx->trans_commit();

		return [
			'status'          => 1,
			'http'            => 200,
			'message'         => 'PAID_STATUS_CANCELLED_FOR_REVISION',
			'finance_id'      => $finance_id,
			'invoice_no'      => (string)($finance['number'] ?? ''),
			'previous_status' => 'paid',
			'current_status'  => 'unpaid',
			'revision_no'     => $next_revision,
			'revision_state'  => 'OPEN',
		];
	}

	private function paid_revision_required_response($finance)
	{
		return [
			'status'            => 0,
			'http'              => 409,
			'message'           => 'PAID_INVOICE_REQUIRES_REVISION',
			'requires_revision' => 1,
			'finance_id'        => (int)($finance['id'] ?? 0),
			'invoice_no'        => (string)($finance['number'] ?? ''),
			'job_no'            => (string)($finance['job_no'] ?? ''),
		];
	}


	function post_invoice_to_ar($POST)
	{
		$region = trim((string)$this->session->userdata('region_id'));

		// Safety net: Jakarta tidak boleh masuk direct ERP regional dari model ini.
		if (strcasecmp($region, 'Jakarta') === 0) {
			return [
				'status'  => '0',
				'http'    => 409,
				'message' => 'JAKARTA_MUST_USE_LEGACY_CURL_HELPER',
			];
		}

		$finance_id = isset($POST['finance_id']) ? (int)$POST['finance_id'] : 0;
		$invoice_no = isset($POST['invoice_no']) ? trim($POST['invoice_no']) : '';

		if ($finance_id <= 0) {
			return [
				'status'  => '0',
				'message' => 'finance_id required',
				'data'    => null,
			];
		}

		if ($invoice_no === '') {
			return [
				'status'  => '0',
				'message' => 'invoice_no required',
				'data'    => null,
			];
		}

		$invoice = $this->dbx
			->where('id', $finance_id)
			->where('number', $invoice_no)
			->where('region_id', $region)
			->get('x_finance')
			->row();

		if (!$invoice) {
			return [
				'status'  => '0',
				'message' => 'INVOICE_NOT_FOUND',
				'data'    => [
					'finance_id' => $finance_id,
					'invoice_no' => $invoice_no,
				],
			];
		}

		if (!empty($invoice->status) && strtolower($invoice->status) === 'paid') {
			return $this->paid_revision_required_response((array)$invoice);
		}

		$items = $this->dbx
			->where('number', $finance_id)
			->where('region_id', $region)
			->where('price !=', 0)
			->get('x_finance_item')
			->result();

		if (empty($items)) {
			return [
				'status'  => '0',
				'message' => 'INVOICE_ITEM_EMPTY',
				'data'    => [
					'finance_id' => $finance_id,
					'invoice_no' => $invoice_no,
				],
			];
		}

		$tax_codes = ['TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2'];

		$dpp_total = 0;
		$ppn_total = 0;
		$grand_total = 0;

		foreach ($items as $row) {
			$price = (float)$row->price;
			$grand_total += $price;

			if (in_array(strtoupper(trim($row->code)), $tax_codes)) {
				$ppn_total += $price;
			} else {
				$dpp_total += $price;
			}
		}

		if ($grand_total <= 0) {
			return [
				'status'  => '0',
				'message' => 'INVOICE_TOTAL_ZERO',
				'data'    => [
					'finance_id'  => $finance_id,
					'invoice_no'  => $invoice_no,
					'grand_total' => $grand_total,
				],
			];
		}

		/**
		 * Sinkronkan total invoice sebelum posting.
		 * Ini penting supaya M_posting membaca nilai terbaru.
		 */
		$this->dbx->where('id', $finance_id);
		$this->dbx->where('region_id', $region);
		$this->dbx->update('x_finance', [
			'grand_total' => $grand_total,
			'ppn'         => $ppn_total,
			'updated_by'  => $this->session->userdata('fullname'),
			'updated_at'  => date('Y-m-d H:i:s'),
		]);

		$this->load->model('api/finance/ar/M_posting', 'mpost');

		/**
		 * Precheck:
		 * kalau invoice sudah pernah dibayar / allocated,
		 * jangan boleh repost.
		 */
		$precheck = $this->mpost->can_repost_ar_invoice([
			'finance_id' => $finance_id,
		]);

		if (!$precheck['ok']) {
			return [
				'status'  => '0',
				'message' => $precheck['message'],
				'data'    => $precheck['data'] ?? null,
			];
		}

		/**
		 * Posting AR.
		 *
		 * M_posting harus menjurnal:
		 *
		 * Dr Accounts Receivable        grand_total
		 *     Cr Revenue                dpp_total
		 *     Cr PPN Output             ppn_total, jika ada
		 *
		 * Jika ppn_total = 0:
		 *
		 * Dr Accounts Receivable        grand_total
		 *     Cr Revenue                grand_total
		 */
		$postRes = $this->mpost->post_ar_to_gl([
			'finance_id'   => $finance_id,
			'force_repost' => 1,
		]);

		if (!$postRes['ok']) {
			return [
				'status'  => '0',
				'message' => $postRes['message'],
				'data'    => $postRes['data'] ?? null,
				'post_ar' => $postRes,
			];
		}
		/**
		 * Tandai source invoice sudah posted.
		 *
		 * x_finance adalah tabel operational legacy.
		 * Jangan memaksa kolom ar_posted* tersedia di semua tenant.
		 */
		$updatePosted = [];

		/*
		 * Kolom utama yang memang sudah digunakan oleh operational system.
		 */
		if ($this->dbx->field_exists('fin_proses', 'x_finance')) {
			$updatePosted['fin_proses'] = 'POSTED';
		}

		/*
		 * Kolom tambahan hanya dipakai kalau schema memang memilikinya.
		 */
		if ($this->dbx->field_exists('ar_posted', 'x_finance')) {
			$updatePosted['ar_posted'] = 1;
		}

		if ($this->dbx->field_exists('ar_posted_at', 'x_finance')) {
			$updatePosted['ar_posted_at'] = date('Y-m-d H:i:s');
		}

		if ($this->dbx->field_exists('ar_posted_by', 'x_finance')) {
			$updatePosted['ar_posted_by'] =
				$this->session->userdata('fullname');
		}

		if ($this->dbx->field_exists('ar_post_message', 'x_finance')) {
			$updatePosted['ar_post_message'] = 'POST_INVOICE_SUCCESS';
		}

		if ($this->dbx->field_exists('updated_by', 'x_finance')) {
			$updatePosted['updated_by'] =
				$this->session->userdata('fullname');
		}

		if ($this->dbx->field_exists('updated_at', 'x_finance')) {
			$updatePosted['updated_at'] = date('Y-m-d H:i:s');
		}

		/*
		 * Update hanya jika memang ada field yang bisa ditulis.
		 */
		if (!empty($updatePosted)) {

			$this->dbx
				->where('id', $finance_id)
				->where('region_id', $region)
				->update('x_finance', $updatePosted);

			if ($this->dbx->error()['code'] ?? 0) {
				$dbErr = $this->dbx->error();

				return [
					'status'  => '0',
					'message' => 'SOURCE_UPDATE_POSTED_FAILED',
					'data'    => [
						'finance_id' => $finance_id,
						'invoice_no' => $invoice_no,
						'region_id'  => $region,
						'db_error'   => $dbErr,
					],
				];
			}
		}

		$this->mark_revision_stage($finance_id, $region, 'REPOSTED');

		return [
			'status'  => '1',
			'message' => 'POST_INVOICE_SUCCESS',
			'data'    => [
				'finance_id'  => $finance_id,
				'invoice_no'  => $invoice_no,
				'dpp_total'   => $dpp_total,
				'ppn_total'   => $ppn_total,
				'grand_total' => $grand_total,
				'post_ar'     => $postRes,
			],
		];
	}

// m_inv_to_shipper.php
public function getListGroup_fast(string $table, string $kode_ref)
{
    $region = $this->session->userdata('region_id');

    $this->dbr->select('DISTINCT g', false);
    $this->dbr->from($table);
    $this->dbr->where('kode_ref', $kode_ref);
    $this->dbr->where('region_id', $region);
    $this->dbr->order_by('g', 'asc');

    return $this->dbr->get();
}
// m_inv_to_shipper.php
public function get_invoice_header(string $inv_no)
{
    $region = $this->session->userdata('region_id');

    return $this->dbr
        ->select('shipper_name, shipper_address, nama, npwp, alamat AS alamat_npwp')
        ->from('x_invoice')
        ->where('number', $inv_no)
        ->where('region_id', $region)
        ->limit(1)
        ->get()
        ->row();
}

	function si_exportir($job)
	{
		$job = trim((string)$job);

		if ($job === '') {
			return '';
		}

		$region = trim((string)$this->session->userdata('region_id'));

		$this->dbx->select('trnstuffingplandetail.sp_detail_id');
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join(
			'trnstuffingplan',
			'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id',
			'inner'
		);
		$this->dbx->where('trnstuffingplandetail.kode_ref', $job);

		if ($region !== '') {
			$this->dbx->where('trnstuffingplan.region_id', $region);
		}

		$detail = $this->dbx->limit(1)->get()->row();

		if (!$detail) {
			return '';
		}

		$row = $this->dbx
			->select('no_from_shipper')
			->where('detail_id', (int)$detail->sp_detail_id)
			->limit(1)
			->get('tbl_detail')
			->row();

		return $row ? (string)$row->no_from_shipper : '';
	}


	// =========================================================================
	// INVOICE GABUNGAN (CONSOLIDATED): 1 PDF berisi invoice Faktur Pajak (INFP)
	// + invoice non-tax (IN/INR) dari job yang sama. HANYA untuk cetak - tidak
	// mengubah x_finance/x_finance_item, posting AR, maupun export XML Coretax.
	// =========================================================================

	// Prefix nomor invoice Faktur Pajak per region (sama dengan logic manage_data
	// & editor). INWHM sengaja tidak dimasukkan karena dipakai juga untuk invoice
	// biasa GCL WAREHOUSE - untuk region itu deteksi FP lewat jenis_code.
	private $fp_invoice_prefixes = array('INFP', 'ICFP', 'SUBFP', 'SMGFP', 'MINFP', 'BINFP', 'MKSINFP');

	private $tax_item_codes = array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2');

	public function is_faktur_pajak_invoice($finance)
	{
		$finance = (array)$finance;

		if (trim((string)($finance['jenis_code'] ?? '')) === '3') {
			return true;
		}

		$prefix = strtoupper(trim(explode('/', (string)($finance['number'] ?? ''))[0]));
		return in_array($prefix, $this->fp_invoice_prefixes, true);
	}

	public function is_tax_item_code($code)
	{
		return in_array(strtoupper(trim((string)$code)), $this->tax_item_codes, true);
	}

	/**
	 * Header x_finance by id, dibatasi region session.
	 */
	public function get_finance_header_by_id($finance_id)
	{
		$finance_id = (int)$finance_id;
		$region     = trim((string)$this->session->userdata('region_id'));

		if ($finance_id <= 0 || $region === '') {
			return null;
		}

		$row = $this->dbx
			->select('id, job_no, number, date, `group` AS grp, jenis, jenis_code, status, grand_total, region_id', false)
			->from('x_finance')
			->where('id', $finance_id)
			->where('region_id', $region)
			->where('jenis', 'INVOICE')
			->limit(1)
			->get()
			->row_array();

		return $row ?: null;
	}

	/**
	 * Invoice lain (bukan Faktur Pajak) di job yang sama - kandidat untuk
	 * digabung ke cetakan invoice FP $finance_id.
	 *
	 * Return:
	 *   null                         -> invoice tidak ditemukan
	 *   ['error' => 'NOT_FAKTUR_PAJAK', ...]
	 *   ['main' => [...], 'candidates' => [[...], ...]]
	 */
	public function get_combined_invoice_candidates($finance_id)
	{
		$main = $this->get_finance_header_by_id($finance_id);

		if (!$main) {
			return null;
		}

		if (!$this->is_faktur_pajak_invoice($main)) {
			return array('error' => 'NOT_FAKTUR_PAJAK', 'main' => $main);
		}

		$rows = $this->dbx
			->select('id, job_no, number, date, `group` AS grp, jenis_code, status, grand_total', false)
			->from('x_finance')
			->where('job_no', $main['job_no'])
			->where('region_id', $main['region_id'])
			->where('jenis', 'INVOICE')
			->where('id !=', (int)$main['id'])
			->order_by('id', 'asc')
			->get()
			->result_array();

		$candidates = array();
		foreach ($rows as $r) {
			if ($this->is_faktur_pajak_invoice($r)) {
				continue; // FP lain tidak boleh ikut digabung
			}
			$candidates[] = $r;
		}

		return array('main' => $main, 'candidates' => $candidates);
	}

	/**
	 * Item invoice (price != 0), urut sesuai input. Header sudah divalidasi
	 * region-nya oleh caller, jadi item tidak difilter region (item manual
	 * lama tidak selalu punya region_id).
	 */
	public function get_finance_items_for_print($finance_id)
	{
		return $this->dbx
			->select('id, code, item, qty, currency, unit_price, price')
			->from('x_finance_item')
			->where('number', (int)$finance_id)
			->where('price !=', 0)
			->order_by('id', 'asc')
			->get()
			->result_array();
	}

}