<?php
class M_proforma_invoice extends CI_Model
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
	function get_pph($data)
	{
		$q = $this->dbx->get_where("x_proforma",array("number"=>$data[0],"region_id"=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) 
		{
			return $q->row()->pph23;
		}
		else 
		{
			return " ";
		}
	}
	function get_ttl_inv_pph($id,$cur){
		$this->dbx->select('price');
		$this->dbx->where_not_in('x_proforma_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2', 'TAX1.2'));
		$query = $this->dbx->get_where('x_proforma_item',array('number'=>$id,'currency'=>$cur));
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
		$this->dbx->where_in('x_proforma_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2', 'TAX1.2'));
		$query = $this->dbx->get_where('x_proforma_item',array('number'=>$id,'currency'=>$cur));
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
	function get_x_proforma_detail($data)
	{
		$q = $this->dbx->get_where("x_proforma_detail",array("number"=>$data[0]));
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
		
	function get_ttl_inv($id,$cur){
		$this->dbx->select('price');
		$query = $this->dbx->get_where('x_proforma_item',array('number'=>$id,'currency'=>$cur));
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
		$query = $this->dbx->get_where('x_proforma_item',array('number'=>$id,'currency'=>$cur));
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
		$q = $this->dbx->get_where('x_proforma',array('job_no'=>$code,'jenis'=>'INVOICE','region_id'=>$this->session->userdata('region_id')));
		if($q->num_rows()>0) {
			return 1;
		} else {
			return 0;
		}
	}

	function get_destination($kode){
		$this->dbx->select(array('kode_ref,destination'),false);
		return $this->dbx->get_where('trnstuffingplandetail',array('kode_ref'=>$kode))->row()->destination;
	}

	function get_table_satu($post,$limit){
		$this->dbx->order_by($post['sidx'], $post['sord']);
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);	
		}
		return $this->dbx->get_where('x_proforma',array('job_no'=>$post['job_no'],'jenis'=>'INVOICE','region_id'=>$this->session->userdata('region_id')))->result();
	}
	
	function get_table_dua($post,$limit){
		$this->dbx->select(array(
				'trnstuffingplan.kode_ref as kode_ref',
				'trnstuffingplan.carrier as carrier',
				'trnstuffingplan.vessel as vessel',
				'trnstuffingplan.voyage as voyage',
				'trnstuffingplan.kurs as kurs',
				'trnstuffingplan.sp_id as sp_id',
				'trnstuffingplan.stuffing_date as stuffing_date',
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
				'trnstuffingplandetail.trk_inv as trk_inv',
		));
		
		$this->dbx->from('trnstuffingplan');
		//$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.code=trnstuffingplan.kode_ref','left');		
		$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_id=trnstuffingplan.sp_id','left');		
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));		
		$this->dbx->where('trnstuffingplan.kode_ref !=','');
		$this->dbx->where('trnstuffingplandetail.kode_ref !=','');
		
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
		
		$this->dbx->order_by($post['sidx'], $post['sord']);
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);	
		}
		return $this->dbx->get()->result();
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
		return $this->dbx->get_where('x_proforma',array('jenis'=>'INVOICE','kepada'=>'SHIPPER','region_id'=>$region))->result();
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
	
	/**
	 * Implementasi bersama untuk "Tarik Data Invoice" di proforma.
	 *
	 * Disamakan dengan M_inv_to_shipper::tarik_data_invoice() (kriteria income
	 * yang diambil, perhitungan PPN, pembulatan) supaya isi proforma yang
	 * disetujui customer = isi invoice sungguhan yang nanti benar-benar dibuat.
	 *
	 * PENTING (jangan diubah tanpa alasan kuat): fungsi ini TIDAK PERNAH
	 * menandai income sebagai billed. Tidak ada update 'ditarik' di
	 * inf_income_selling_price maupun 'trk_inv' di trnstuffingplandetail di
	 * sini. Status billed hanya boleh berubah lewat
	 * M_inv_to_shipper::tarik_data_invoice() (tarik ke INVOICE/DN sungguhan).
	 * Proforma hanya BACA data income yang belum dimiliki invoice manapun
	 * (ship_co kosong) - proforma boleh ditarik berkali-kali tanpa efek samping.
	 *
	 * $with_ppn = true -> jalur "PROFORMA FAKTUR PAJAK" (hitung & sisipkan
	 * baris VAT, mirip jenis_code=='3' di invoice). false -> jalur "PROFORMA
	 * INVOICE" polos (PREPAID saja, tanpa VAT).
	 */
	private function _tarik_data_proforma_common($POST, $with_ppn)
	{
		$region = trim((string)$this->session->userdata('region_id'));

		$proforma_id = isset($POST['number']) ? (int)$POST['number'] : 0; // x_proforma.id
		$kode_ref    = isset($POST['kode_ref']) ? trim((string)$POST['kode_ref']) : '';
		$group       = isset($POST['group']) ? trim((string)$POST['group']) : '';

		if ($region === '') {
			return array('status' => '0', 'message' => 'REGION_NOT_FOUND');
		}
		if ($proforma_id <= 0) {
			return array('status' => '0', 'message' => 'INVALID_PROFORMA_ID');
		}

		// Ambil job_no/group dari header x_proforma kalau ada (source of truth),
		// fallback ke POST kalau header belum lengkap.
		$header = $this->dbx
			->select("id, job_no, `group`", false)
			->from('x_proforma')
			->where('id', $proforma_id)
			->where('region_id', $region)
			->limit(1)->get()->row();

		if ($header) {
			if (trim((string)$header->job_no) !== '') $kode_ref = trim((string)$header->job_no);
			if (isset($header->group) && trim((string)$header->group) !== '') $group = trim((string)$header->group);
		}

		if ($kode_ref === '') {
			return array('status' => '0', 'message' => 'EMPTY_JOB_NO');
		}

		// 1) Income selling price PREPAID yang MASIH BELUM dimiliki invoice
		// manapun (ship_co kosong) - persis kriteria "tersedia untuk ditarik"
		// yang dipakai saat invoice sungguhan pertama kali tarik data.
		$this->dbx->from('inf_income_selling_price')
			->where('region_id', $region)
			->where('kode_ref', $kode_ref)
			->where('prep_coll', 'PREPAID');

		if ($group === '') {
			$this->dbx->group_start()
				->where('g IS NULL', null, false)
				->or_where("TRIM(g) = ''", null, false)
				->group_end();
		} else {
			$this->dbx->where('g', $group);
		}

		if ($with_ppn) {
			$this->dbx
				->where("ppn IS NOT NULL", null, false)
				->where("TRIM(ppn) != ''", null, false)
				->where("code_service NOT LIKE '%TAX%'", null, false);
		}

		$this->dbx->group_start()
			->where('ship_co IS NULL', null, false)
			->or_where("TRIM(ship_co) = ''", null, false)
			->group_end();

		$q1 = $this->dbx->get()->result();

		// 2) x_glbincome (global income), sama seperti invoice
		$this->dbx->from('x_glbincome')
			->where('region_id', $region)
			->where('job_no', substr($kode_ref, 0, 15))
			->where('prep_coll', 'PREPAID')
			->where('currency', 'RP.');

		if ($group === '') {
			$this->dbx->group_start()
				->where('`group` IS NULL', null, false)
				->or_where("TRIM(`group`) = ''", null, false)
				->group_end();
		} else {
			$this->dbx->where('group', $group);
		}

		$q2 = $this->dbx->get()->result();

		$q = array_merge($q1, $q2);

		if (empty($q)) {
			return array(
				'status'      => '0',
				'message'     => 'NO_CURRENT_INCOME_FOUND',
				'proforma_id' => $proforma_id,
				'job_no'      => $kode_ref,
				'group'       => $group,
			);
		}

		$bl_no   = $this->get_bl_num($kode_ref);
		$kepada  = isset($POST['kepada']) ? $POST['kepada'] : '';

		$items   = array();
		$overdue = array();

		foreach ($q as $row) {
			$line_price = isset($row->price) ? (int)round((float)$row->price) : 0;

			$items[] = array(
				'kepada'     => $kepada,
				'jenis'      => 'INVOICE',
				'kode_ref'   => $kode_ref,
				'bl_no'      => $bl_no,
				'number'     => $proforma_id,
				'code'       => isset($row->code_service) ? $row->code_service : '',
				'item'       => isset($row->item_service) ? $row->item_service : '',
				'qty'        => isset($row->qty) ? $row->qty : 0,
				'currency'   => isset($row->currency) ? $row->currency : '',
				'unit_price' => isset($row->unit_price) ? $row->unit_price : 0,
				'price'      => $line_price,
				'remarks'    => isset($row->remark) ? $row->remark : '',
				'pre_col'    => 'PREPAID',
				'id_tarik'   => '1',
				'ppn'        => isset($row->ppn) ? $row->ppn : '',
			);

			$overdue[] = $line_price;
		}

		// PPN - persis logika di M_inv_to_shipper::tarik_data_invoice()
		$grand_total  = array_sum($overdue);
		$ppn_total    = 0;
		$kode_pajak   = null;
		$jumlah_pajak = 0;
		$vat          = 'ok';

		if ($with_ppn && $grand_total > 0) {

			$ppn_values = array();
			foreach ($q1 as $row) {
				$nilai_ppn = isset($row->ppn) ? trim((string)$row->ppn) : '';
				if ($nilai_ppn !== '') $ppn_values[$nilai_ppn] = $nilai_ppn;
			}
			$ppn_values = array_values($ppn_values);

			if (count($ppn_values) > 1) {
				return array('status' => '0', 'message' => 'MULTIPLE_PPN_FOUND', 'data' => $ppn_values);
			} elseif (count($ppn_values) === 0) {
				return array('status' => '0', 'message' => 'PPN_NOT_FOUND');
			}

			$nilai_ppn = $ppn_values[0];

			if ($nilai_ppn === '1.1%') {
				$kode_pajak = 'TAX 1.1'; $jumlah_pajak = 0.011;
				foreach ($overdue as $d) $ppn_total += $this->ppn_half_up_int((int)$d, 11, 1000);
			} elseif ($nilai_ppn === '1.2%') {
				$kode_pajak = 'TAX 1.2'; $jumlah_pajak = 0.012;
				foreach ($overdue as $d) $ppn_total += $this->ppn_half_up_int((int)$d, 12, 1000);
			} elseif ($nilai_ppn === '11%') {
				$kode_pajak = 'TAX 11'; $jumlah_pajak = 0.11;
				foreach ($overdue as $d) $ppn_total += $this->ppn_half_up_int((int)$d, 11, 100);
			} elseif ($nilai_ppn === '12%') {
				$kode_pajak = 'TAX 12'; $jumlah_pajak = 0.11;
				foreach ($overdue as $d) $ppn_total += $this->ppn_half_up_int((int)$d, 11, 100);
			} else {
				return array('status' => '0', 'message' => 'UNKNOWN_PPN', 'ppn' => $nilai_ppn);
			}
		}

		$this->dbx->trans_begin();

		// Rebuild aman: hapus item lama punya proforma ini saja.
		$this->dbx->where('number', $proforma_id)
			->where('kode_ref', $kode_ref)
			->delete('x_proforma_item');

		$insert_ok = $this->dbx->insert_batch('x_proforma_item', $items);

		if ($insert_ok === false) {
			$error = $this->dbx->error();
			$this->dbx->trans_rollback();
			return array('status' => '0', 'message' => 'FAILED_INSERT_PROFORMA_ITEM', 'error' => $error);
		}

		if ($with_ppn && $kode_pajak !== null && $jumlah_pajak > 0 && $ppn_total > 0) {
			$data_pajak = array(
				'kepada'     => $kepada,
				'jenis'      => 'INVOICE',
				'kode_ref'   => $kode_ref,
				'bl_no'      => $bl_no,
				'number'     => $proforma_id,
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
			);

			$vat_insert = $this->dbx->insert('x_proforma_item', $data_pajak);

			if (!$vat_insert) {
				$error = $this->dbx->error();
				$this->dbx->trans_rollback();
				return array('status' => '0', 'message' => 'FAILED_INSERT_VAT', 'error' => $error);
			}
		}

		$grand_total_with_vat = $grand_total + $ppn_total;

		$header_ok = $this->dbx->where('id', $proforma_id)
			->where('region_id', $region)
			->update('x_proforma', array('grand_total' => $grand_total_with_vat));

		if (!$header_ok) {
			$error = $this->dbx->error();
			$this->dbx->trans_rollback();
			return array('status' => '0', 'message' => 'FAILED_UPDATE_PROFORMA_HEADER', 'error' => $error);
		}

		if ($this->dbx->trans_status() === false) {
			$error = $this->dbx->error();
			$this->dbx->trans_rollback();
			return array('status' => '0', 'message' => 'PROFORMA_TRANSACTION_FAILED', 'error' => $error);
		}

		$this->dbx->trans_commit();

		return array(
			'status'          => '1',
			'message'         => 'PROFORMA_ITEMS_UPDATED',
			'proforma_id'     => $proforma_id,
			'job_no'          => $kode_ref,
			'group'           => $group,
			'income_rows'     => count($q1),
			'global_rows'     => count($q2),
			'total_rows'      => count($q),
			'grand_total'     => $grand_total,
			'ppn_total'       => $ppn_total,
			'grand_total_ppn' => $grand_total_with_vat,
			'pre-col'         => 'PREPAID',
			'ppn'             => $vat,
		);
	}

	function tarik_data_invoice($POST)
	{
		return $this->_tarik_data_proforma_common($POST, false);
	}

	function tarik_data_proforma_pajak($POST)
	{
		return $this->_tarik_data_proforma_common($POST, true);
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

	function ppn_half_up_int(int $dpp, int $num, int $den): int {
		return intdiv($dpp * $num + intdiv($den, 2), $den);
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
		$this->dbx->from('x_proforma');
		$this->dbx->join('x_proforma_item','x_proforma_item.number=x_proforma.id');
		$this->dbx->where('x_proforma_item.number', $id);
		$this->dbx->where('x_proforma_item.price !=', 0 );
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}
	
		function get_form_inv_pph($id){
		
		$this->dbx->select('*');
		$this->dbx->from('x_proforma');
		$this->dbx->join('x_proforma_item','x_proforma_item.number=x_proforma.id');
		$this->dbx->where('x_proforma_item.number', $id);
		$this->dbx->where('x_proforma_item.price !=', 0 );
		$this->dbx->where_not_in('x_proforma_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2', 'TAX1.2'));
		//$this->dbx->order_by("x_proforma_item.item", "asc");
		$query = $this->dbx->get();
		//print_r($this->dbx->last_query());
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}
	
	function get_code_pajak($data)
	{
		$this->dbx->select('code');
		$this->dbx->where_in('x_proforma_item.code', array('TAX10', 'TAX1', 'TAX 11', 'TAX 1.1', 'TAX 12', 'TAX 1.2', 'TAX1.2'));
		$q = $this->dbx->get_where("x_proforma_item",array("number"=>$data));

		if($q->num_rows()>0) 
		{
		$tax = $q->row()->code;
		if ($tax == 'TAX 11'){
		$vat = 'PPN 11%';	
		}else if($tax == 'TAX 1.1'){
		$vat = 'PPN 1.1%';	
		}else if($tax == 'TAX 12'){
		$vat = 'PPN 12%';	
		}else if($tax == 'TAX 1.2' || $tax == 'TAX1.2'){
		$vat = 'PPN 1.2%';	
		}
			return $vat;
		}
		else 
		{
			return " ";
		}
	}
	
	function get_report_pdf($kode_ref){
		// NOTE: eta / destination(as port_dest) / incoterms / shipper ditambahkan
		// supaya PDF proforma bisa menampilkan TERM, ETD/ETA dan POL/POD persis
		// seperti PDF invoice (lihat C_proforma_invoice::print_invoice()).
		$this->dbx->select(array(
			'trnstuffingplan.vessel',
			'trnstuffingplan.voyage',
			'trnstuffingplan.etd_jkt',
			'trnstuffingplan.eta',
			'trnstuffingplan.port_loading',
			'trnstuffingplan.destination as port_dest',
			'trnstuffingplan.mother_bl',
			'trnstuffingplan.kurs',
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
         'trnstuffingplandetail.shipper',
         'trnstuffingplandetail.consignee',
         'trnstuffingplandetail.consignee_add',
         'trnstuffingplandetail.note',
			)
		);
		$this->dbx->from('trnstuffingplandetail');
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');		
		$this->dbx->where('trnstuffingplandetail.kode_ref', $kode_ref);	
		$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
		$q = $this->dbx->get();
		return $q->row_array();
	}
	
	function get_table_tiga($post,$limit){
	
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);
		}
		$this->dbx->order_by($post['sidx'],$post['sord']);
		return $this->dbx->get_where('x_proforma_item',array('number'=>$post['id']))->result();
	}
	
	function getInvoice($job_no){
		$sql = "SELECT * FROM `v_sp_gabung` where kode_ref='$job_no'"; 		
		$query = $this->dbx->query($sql);			
		return $query->row();
	}
	
	function getKostumer($id_kost){
		$sql = "SELECT * FROM `mstShipper` WHERE shipper_id='$id_kost'"; 
		$query = $this->dbx->query($sql);	
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
	
	function get_nama_shipperu($id){
		$this->dbx->select(array('id_shipper','shipper_name'));
		$this->dbx->from('mstshipper');
		$this->dbx->where('id_shipper',$id);
		$q = $this->dbx->get();
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->shipper_name;
			} else {
			return "";	
		}
	}
	
	function get_alamat_shipperu($shipper_name){
		$this->dbx->select(array('id_shipper','shipper_address'));
		$this->dbx->from('mstshipper');
		$this->dbx->where('shipper_name',$shipper_name);
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
		if($q->num_rows() > 0 ) {
			$row = $q->row();
			return $row->consignee_import_address;
			} else {
			return "";	
		}
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$table = $data['table'];
		$id = $data['id'];
		unset($data['oper']);
		unset($data['table']);
		unset($data['id']);
		
		if($oper=='add') 
		{
			$shipper_name = strtoupper($data['shipper_name']);
			$shipper_add = strtoupper($data['shipper_address']);
			unset($data['shipper_name']);
			unset($data['shipper_address']);
			
			$act = $this->dbx->get_where("x_proforma",
				array(
					"number"=>$data["number"],
					"region_id"=>$this->session->userdata("region_id"),
				)
			);
			if($act->num_rows()>0) {
				return 3;
			}
			else 
			{
				$this->dbx->insert($table,$data);	
			}
				
			if($this->dbx->affected_rows() > 0 && $table == 'x_proforma'){

				// PENTING: menyimpan header proforma TIDAK BOLEH mengubah status
				// billing income manapun. 'ditarik' (inf_income_selling_price) dan
				// 'trk_inv' (trnstuffingplandetail) hanya boleh berubah ketika data
				// benar-benar ditarik ke INVOICE/DN sungguhan (lihat
				// M_inv_to_shipper::tarik_data_invoice()), bukan saat membuat/menyimpan
				// proforma. Sebelumnya di sini ada update trk_inv='1' yang melanggar
				// aturan itu - sudah dihapus.
				$this->dbx->insert("x_proforma_detail",
					array(
						"number"=>$data["number"],
						"shipper_name"=>$shipper_name,
						"shipper_address"=>$shipper_add,
						"region_id"=>$this->session->userdata("region_id"),
					)
				);

				return $this->dbx->affected_rows();
			}
		}
		elseif($oper=='edit') 
		{
			$shipper_name = strtoupper($data['shipper_name']);
			$shipper_add = strtoupper($data['shipper_address']);
			unset($data['shipper_name']);
			unset($data['shipper_address']);
			$this->dbx->where('id',$id);
			$this->dbx->update($table,$data);
			$qn = $this->dbx->get_where("x_proforma_detail",array("number"=>$data["number"]));
			if($qn->num_rows()>0) {
				$this->dbx->update("x_proforma_detail",array("shipper_name"=>$shipper_name,"shipper_address"=>$shipper_add),array("number"=>$data["number"]));
			} else {
				$this->dbx->insert("x_proforma_detail",array("number"=>$data["number"],"shipper_name"=>$shipper_name,"shipper_address"=>$shipper_add));
			}
			return $this->dbx->affected_rows();
		}
		else 
		{
			$idx = explode(",",$id);
			for($i=0;$i<=count($idx)-1;$i++) {
			$this->dbx->delete($table,array("id"=>$idx[$i]));
		}
		}
	}
	
	function manage_data_table($data){
		$table = $data['table'];
		$id = $data['id'];
		$oper = $data['oper'];
		unset($data['oper']);
		unset($data['table']);
		unset($data['id']);
		if($oper=='add') {
			$region_id = substr($this->session->userdata('region_id'),0,1);
			$destination_id = $this->fungsi->get_country_id($data['destination']);
			$kode_ref = substr($data['kode_ref'],11,6);
			$data['bl_no']=$region_id.$destination_id.'-GCL'.$kode_ref;
			$this->dbx->insert($table,$data);
			return $this->dbx->affected_rows();
		}
		if($oper=='edit') {
			$this->dbx->where('id',$id);
			$this->dbx->update($table,array($data['field']=>$data['value']));
			return $this->dbx->affected_rows();
		}
	}

}




?>