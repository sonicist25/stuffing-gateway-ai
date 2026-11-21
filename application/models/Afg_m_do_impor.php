<?php class Afg_m_do_impor extends CI_Model
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

	function get_pack($data)
	{
		$query = $this->dbx->get_where('x_pack',array('kdi'=>$data));
		if($query->num_rows() > 0 ) {
			return $query->row()->kdri;
		} else {
			return $data;
		}
	}
	
	function update_sales_impor($data) 
	{
		if($data['sp_detail_id']!=0) 
		{
			$this->dbx->update('trnstuffingplandetail',array('sales'=>$data['sales']),array("sp_detail_id"=>$data['sp_detail_id']));
		}
		else 
		{
			$this->dbx->update('trnstuffingplandetail',array('sales'=>$data['sales']),array("kode_ref"=>$data['kode_ref']));
		}
		return array("status"=>$this->dbx->affected_rows());
	}

	function get_code_shipper_import($data)
	{
		//$this->dbx->like("consignee_import_address",$data["alamat_cs"]);
		$query = $this->dbx->get_where('refconsigneeimport',array('consignee_import_name'=>$data['nama_cs']));
		if($query->num_rows() > 0 ) {
			$code = $query->row()->consignee_import_code;
			$this->dbx->where('kode_ref',$data['job']);
			$this->dbx->where('region_id',$this->session->userdata('region_id'));
			$this->dbx->update('trnstuffingplandetail',array('shipper_import_code'=>$code));
		}
		return array('status'=>$query->num_rows());
	}

	function insert_consignee($data)
	{
		$this->dbx->like('consignee_import_code',$data['consignee_import_code']);
		$c=$this->dbx->get('refconsigneeimport');
		if($c->num_rows()>0) 
		{
			$data['consignee_import_code'] = $data['consignee_import_code'].'_'.$c->num_rows();
		}
		
		$this->dbx->where('kode_ref',$data['job']);
		$this->dbx->update('trnstuffingplandetail',array('shipper_import_code'=>$data['consignee_import_code']));
		unset($data['job']);
		$data['region_id'] = $this->session->userdata('region_id');
		$this->dbx->insert('refconsigneeimport',$data);
		return $this->dbx->affected_rows();
	}
	
	function getListJobNoBlForm()
	{       
		$region = $this->session->userdata('region_id');
		$code = array();
		$job = array();
		$this->dbx->select('job_no');
		$q1 = $this->dbx->get_where('x_do_import',array('region_id'=>$region));
		if($q1->num_rows()>0) {
			foreach($q1->result() as $row){
				$j[] = $row->job_no;
			}
			foreach ($j as $i => $value) {
    			//	unset($j[$i]);
    			$job[]= $value;
			}
		}
		
		$this->dbx->select(
			array(
				'trnstuffingplan.kode_ref',
				'trnstuffingplan.shipment_type_multi',
				'trnstuffingplan.sp_id',
				'trnstuffingplandetail.kode_ref',
				'trnstuffingplandetail.sp_id',
			)
		);
		
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->group_start();
		$this->dbx->like('trnstuffingplandetail.kode_ref','-30');
		$this->dbx->or_like('trnstuffingplandetail.kode_ref','-60');
		$this->dbx->group_end();
		$this->dbx->order_by('trnstuffingplandetail.kode_ref','asc');
		$q2 = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.region_id'=>$region));
		
		foreach($q2->result() as $row){
			$k[] = $row->kode_ref;
		}
		
		foreach ($k as $i => $value) {
    		$code[]= $value;
		}
		
		if($q1->num_rows()>0) {
			return array_diff($code,$job);
		} else {
			return $code;
		}
		
	}
	
	function getDataImporByJobNo($data)
	{       $region = $this->session->userdata('region_id');
		$this->dbx->select(
			array(
				'trnstuffingplan.stuffing_date',
				'trnstuffingplan.etd_jkt',
				'trnstuffingplan.eta',
				'trnstuffingplan.sp_id',
				'trnstuffingplan.port_loading',
				'trnstuffingplan.vessel',
				'trnstuffingplan.voyage',
				'trnstuffingplan.warehouse',				
				'trnstuffingplan.cont_seal_no',
				'trnstuffingplandetail.shipping_mark',
				'trnstuffingplandetail.hb_l',
				'trnstuffingplandetail.desc_goods',
				'trnstuffingplandetail.weight',
				'trnstuffingplandetail.ship_meas',
				'trnstuffingplandetail.no_pack_qty',
				'trnstuffingplandetail.no_pack_unit',
				'trnstuffingplandetail.shipper_import',
				'trnstuffingplandetail.shipper_import_add',
				'trnstuffingplandetail.shipper_import_code',
				'trnstuffingplandetail.sp_id',
			)
		);
		$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
		$this->dbx->where('trnstuffingplandetail.kode_ref',$data['no_job']);
		$query = $this->dbx->get_where('trnstuffingplandetail',array('trnstuffingplandetail.region_id'=>$region));
		
		if($query->num_rows() > 0){
			return $query;
		} else {
			return false;
		}
	}
	
	function get_data_print($id)
	{
		$this->dbx->select('*');
		$this->dbx->from('x_do_import');
		$this->dbx->where('do_id', $id);
		$query = $this->dbx->get();
		$row = $query->row_array();

		if (!$row) {
			return array();
		}

		// HS Code disimpan di trnstuffingplandetail, bukan di x_do_import.
		$row['hs_code'] = '';
		$job_no = trim((string)($row['job_no'] ?? ''));
		if ($job_no !== '') {
			$detail = $this->dbx
				->select('hs_code')
				->where('kode_ref', $job_no)
				->where('region_id', $this->session->userdata('region_id'))
				->get('trnstuffingplandetail')
				->row_array();

			if ($detail) {
				$row['hs_code'] = trim((string)($detail['hs_code'] ?? ''));
			}
		}

		return $row;
	}
	
	function getDataImporByJobNoEdit($job_no)
	{
		$query = $this->dbx->get_where('x_do_import',array('job_no'=>$job_no));
		if($query->num_rows() > 0 ) {
			return $query->row_array();
			} else {
			return NULL;	
		}
	}
	
	function tampil_data($data,$limit)
	{
		$this->dbx->select('*');
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
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('x_do_import',array('region_id'=>$this->session->userdata('region_id')))->result();
	}
	
	function add_data_import($data)
	{
		$data['region_id']=$this->session->userdata('region_id');
		$this->dbx->insert('x_do_import',$data);
		if($this->dbx->affected_rows() > 0 ) {
			$this->dbx->where('kode_ref',$data['job_no']);
			$this->dbx->update('trnstuffingplandetail',array('proses_export'=>'1'));
			return '1';
			} else {
			return '0';	
		}
	}
	
	function update_data_import($data,$id)
	{
		$this->dbx->where('do_id',$id);
		$this->dbx->update('x_do_import',$data);
		if($this->dbx->affected_rows() > 0 ) {
			////print_r($data);
			$this->dbx->update("trnstuffingplandetail",
				array(
					"hb_l"=>$data["bl_no"],
					//"tanggal_do"=>$data["tanggal_do"],
					"note"=>$data["consignee"],
					"destination"=>$data["from"],
					"shipping_mark"=>htmlspecialchars($data["shipping_mark"]),
					"desc_goods"=>htmlspecialchars($data["description"]),
					"weight"=>$data["wg"],
					"ship_meas"=>$data["ms"],
					"no_pack_qty"=>$data["total"],
					"no_pack_unit"=>$data["pack"],
				),
				array("kode_ref"=>$data["job_no"])
			);
			return '1';
			} else {
			return '0';	
		}
	}
	
	function delete_data_import($data)
	{
		$this->dbx->where('do_id',$data['id']);
		$this->dbx->delete('x_do_import');
		if($this->dbx->affected_rows() > 0 ) {
			$this->dbx->where('kode_ref',$data['kode_ref']);
			$this->dbx->update('trnstuffingplandetail',array('proses_export'=>'0'));
			return '1';
			} else {
			return '0';	
		}
	}

	function show_file($data,$limit)
	{
		$this->dbx->select('*');
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
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get('x_manifest')->result();
	}

	function manage_file($data) 
	{
		$oper = $data['oper'];
		unset($data['oper']);
		$data['upload_by'] = $this->session->userdata('user_id');
		$data['region_id'] = $this->session->userdata('region_id');
		
		if($oper!='del') {
			$data['tgl_upload'] = $this->fungsi->jin_date_sql($data['tgl_upload']);
		}
		
		if($oper=='add') {
			$this->dbx->insert('x_manifest',$data);
			$this->dbx->select_max('id');
			$data['id'] = $this->dbx->get_where('x_manifest',array('upload_by'=>$this->session->userdata('user_id')))->row()->id;
		}
		elseif($oper=='edit') {
			$this->dbx->update('x_manifest',$data,array('id'=>$data['id']));
		}
		else 
		{
			$nama_file = $this->dbx->get_where('x_manifest',array('id'=>$data['id']))->row()->nama_file;
			$this->dbx->delete('x_manifest',array('id'=>$data['id']));
			$status = $this->dbx->affected_rows();
			if($status > 0 && $nama_file != '' ) {
				unlink($_SERVER["DOCUMENT_ROOT"].'/public/file/mdb/'.$nama_file);
			}
		}
		return array('success'=>$this->dbx->affected_rows(),'oper'=>$oper,'id'=>$data['id']);
	}
	
	function update_file_upload($data)
	{
		if($data['oper']=='edit') {
			$nama_file = $this->dbx->get_where('file_upload',array('id'=>$data['id']))->row()->nama_file;
			unlink($_SERVER["DOCUMENT_ROOT"].'/'.$nama_file);
		}
		$this->dbx->where('id',$data['id']);
		$this->dbx->update('x_manifest',array('nama_file'=>$data['nama_file']));	
	}
	
	function inject_db($data)
	{
		$next = $this->insert_data_stuffing($data);
		return $next;
	}
	function inject_db_ceisa($data)
	{
		$next = $this->inset_data_stuffing($data);
		return $next;
	}
	
	function inset_data_stuffing($data)
	{
		$region_id = $this->session->userdata('region_id');

		// CEISA mengirim detail sebagai array (bukan JSON string).
		$detail = isset($data['detail']) ? $data['detail'] : array();
		if (is_string($detail)) {
			$decoded = json_decode($detail, true);
			$detail = is_array($decoded) ? $decoded : array();
		}

		if (!is_array($detail) || empty($detail)) {
			return array(
				'status' => 0,
				'code' => 'EMPTY_DETAIL',
				'message' => 'Detail CEISA kosong atau format detail tidak valid.'
			);
		}

		// Field minimum yang dipakai untuk membuat job detail.
		// Validasi dilakukan sebelum update/delete agar tidak menghasilkan partial data.
		$required_keys = array(1, 3, 5, 7, 8, 9, 10, 11, 14, 15, 16, 17, 18, 20, 24, 25, 26);
		$invalid_rows = array();

		foreach ($detail as $i => $row) {
			if (!is_array($row)) {
				$invalid_rows[] = array(
					'row' => $i,
					'hbl' => '',
					'missing_keys' => $required_keys,
					'reason' => 'ROW_NOT_ARRAY'
				);
				continue;
			}

			$missing = array();
			foreach ($required_keys as $key) {
				if (!array_key_exists($key, $row) && !array_key_exists((string)$key, $row)) {
					$missing[] = $key;
				}
			}

			if (!empty($missing)) {
				$invalid_rows[] = array(
					'row' => $i,
					'hbl' => isset($row[1]) ? $row[1] : (isset($row['1']) ? $row['1'] : ''),
					'missing_keys' => $missing,
					'available_keys' => array_keys($row)
				);
			}
		}

		if (!empty($invalid_rows)) {
			return array(
				'status' => 0,
				'code' => 'INVALID_CEISA_DETAIL',
				'message' => 'Ada detail CEISA yang tidak lengkap. Data tidak diubah.',
				'invalid_rows' => $invalid_rows
			);
		}

		unset($data['detail']);
		$data['region_id'] = $region_id;

		$sp_id = $this->get_id_stuffing($data['kode_ref']);
		if (empty($sp_id)) {
			return array(
				'status' => 0,
				'code' => 'STUFFING_PLAN_NOT_FOUND',
				'message' => 'Stuffing plan tidak ditemukan untuk kode_ref '.$data['kode_ref']
			);
		}

		$this->dbx->trans_begin();

		$this->dbx->where('kode_ref', $data['kode_ref']);
		$this->dbx->update('trnstuffingplan', $data);

		$this->dbx->delete('trnstuffingplandetail', array(
			'sp_id' => $sp_id,
			'region_id' => $region_id,
		));
		$this->dbx->delete('tbl_detail', array(
			'sp_id' => $sp_id,
			'region_id' => $region_id,
		));

		foreach ($detail as $i => $row) {
			$get = function($key, $default = '') use ($row) {
				if (array_key_exists($key, $row)) return $row[$key];
				$keyString = (string)$key;
				if (array_key_exists($keyString, $row)) return $row[$keyString];
				return $default;
			};

			$ship_code = substr(str_replace(array(' ', '.'), '', (string)$get(10)), 0, 10);

			$number = '';
			for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) {
				$number = chr((($n - 1) % 26) + 65) . $number;
			}

			$mysqlDate = '';
			$dateValue = trim((string)$get(3));
			if ($dateValue !== '') {
				$date = DateTime::createFromFormat('d-m-Y', $dateValue);
				if ($date) {
					$mysqlDate = $date->format('Y-m-d');
				}
			}

			$desc_goods = (string)$get(24);
			$desc = explode("\n", $desc_goods);
			$desc_print = isset($desc[0]) ? trim($desc[0]) : '';

			$insert = array(
				'hb_l' => $get(1),
				'tanggal_bl' => $mysqlDate,
				'code' => $data['kode_ref'],
				'num' => $number,
				'kode_ref' => $data['kode_ref'].$number,
				'weight' => $get(15),
				'ship_meas' => $get(16),
				'wh_meas' => $get(16),
				'chgs_weight' => $get(16),
				'wh_agent' => $get(16),
				'bl_meas' => $get(16),
				'cost_meas' => $get(16),
				'no_pack_qty' => $get(17),
				'no_pack_unit' => $get(18),
				'no_of_pack' => trim((string)$get(17).' '.(string)$get(18)),
				'shipping_mark' => $get(14),
				'destination' => $this->get_nama_kota($get(5)),
				'lock_job' => 0,
				'note' => (string)$get(10)."\n".(string)$get(11),
				'shipper_print' => $get(10),
				'shipper_import' => $get(10),
				'shipper_import_add' => $get(11),
				'shipper_import_code' => $ship_code,
				'consignee' => $get(8),
				'consignee_add' => $get(9),
				'notify' => $get(12),
				'notify_add' => $get(13),
				'express_release' => FALSE,
				'shipped_on_board' => FALSE,
				'net_weight_tidak_dicetak' => FALSE,
				'cetak_jml_doc_origi' => FALSE,
				'desc_goods' => $desc_goods,
				'status_selling' => $get(3),
				'hs_code' => $get(25),
				'desc_print' => $desc_print,
				'isLock' => 0,
				'c_o' => $get(20),
				'isSpotRate' => $this->get_nama_kota($get(7)),
				'shipment_request_id' => $get(26),
				'sp_id' => $sp_id,
				'region_id' => $region_id,
			);

			$this->dbx->insert('trnstuffingplandetail', $insert);
			$db_error = $this->dbx->error();
			if (!empty($db_error['code'])) {
				$this->dbx->trans_rollback();
				return array(
					'status' => 0,
					'code' => 'INSERT_DETAIL_FAILED',
					'row' => $i,
					'hbl' => $get(1),
					'db_error' => $db_error
				);
			}
		}

		$qd = $this->dbx->get_where('trnstuffingplandetail', array('sp_id' => $sp_id));
		if ($qd->num_rows() > 0) {
			foreach ($qd->result() as $nr) {
				$this->dbx->insert('tbl_detail', array(
					'detail_id' => $nr->sp_detail_id,
					'sp_id' => $sp_id,
					'feeder_vessel' => ($data['vessel'] ?? '').' '.($data['voyage'] ?? ''),
					'feeder_vessel_etd_date' => $data['closing_date_time'] ?? '',
					'region_id' => $region_id,
				));

				$this->dbx->where('no_job', $nr->kode_ref);
				$query = $this->dbx->get('x_tracking');

				if ($query->num_rows() == 0) {
					$tracking_data = array(
						'id_shipper' => $nr->shipper,
						'id_request' => '',
						'id_agent' => '',
						'warehouse' => $data['warehouse'] ?? '',
						'mbl_no' => $nr->shipment_request_id,
						'hbl_agent' => $nr->doc,
						'hbl_no' => $nr->hb_l,
						'feeder_name' => ($data['vessel'] ?? '').' '.($data['voyage'] ?? ''),
						'connecting_name' => '',
						'party' => $nr->no_pack_qty.' '.$nr->no_pack_unit,
						'etd' => '',
						'atd' => '',
						'eta' => $data['eta'] ?? '',
						'ata' => $data['eta'] ?? '',
						'shipper' => $nr->shipper_print,
						'consignee' => $nr->shipper_import,
						'destination' => $nr->destination,
						'port_of_destination' => $nr->isSpotRate,
						'container_no' => $nr->c_o,
						'remarks' => '',
						'sp_detail_id' => $nr->sp_detail_id,
						'prepared' => $this->session->userdata('user_id'),
						'no_job' => $nr->kode_ref,
						'region_id' => $region_id,
						'created_by' => $this->session->userdata('fullname'),
						'created_at' => date('Y-m-d H:i:s')
					);
					$this->dbx->insert('x_tracking', $tracking_data);
				}
			}
		}

		if ($this->dbx->trans_status() === FALSE) {
			$db_error = $this->dbx->error();
			$this->dbx->trans_rollback();
			return array(
				'status' => 0,
				'code' => 'DATABASE_ERROR',
				'message' => 'Import CEISA gagal. Semua perubahan dibatalkan.',
				'db_error' => $db_error
			);
		}

		$this->dbx->trans_commit();

		return array(
			'status' => 1,
			'header' => $data,
			'kode_ref' => $data['kode_ref'],
			'detail_count' => count($detail)
		);
	}

	function insert_data_stuffing($data) {
		$region_id = $this->session->userdata('region_id');
		$sp_id = $this->get_id_stuffing($data['kode_ref']);

		// Decode detail safely
		$detail = json_decode($data['detail'], true);
		unset($data['detail']);

		// Update header
		$data['region_id'] = $region_id;
		$this->dbx->where('kode_ref', $data['kode_ref'])->update('trnstuffingplan', $data);

		// Clean up old details
		$this->dbx->delete('trnstuffingplandetail', ['sp_id' => $sp_id, 'region_id' => $region_id]);
		$this->dbx->delete('tbl_detail', ['sp_id' => $sp_id, 'region_id' => $region_id]);
		

		// Insert new detail rows
		foreach ($detail as $i => $row) {
			$ship_code = substr(str_replace([' ', '.'], '', $row[10]), 0, 10);
			// Ubah index 0-based ($i) menjadi A, B, ..., Z, AA, AB, ..., AZ, BA, ..., dst.
			$number = '';
			for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) {
				$number = chr((($n - 1) % 26) + 65) . $number; // 65 = 'A'
			}

			$desc = isset($row[24]) ? explode("\n", $row[24]) : [];
			$desc_print = isset($desc[0]) ? $desc[0] : '';
			$date = DateTime::createFromFormat('d-m-Y', $row[3]);
				if ($date) {
					$mysqlDate = $date->format('Y-m-d');
				} else {
					$mysqlDate = '';
				}

			$detail_data = [
				'hb_l' => $row[1],
				'tanggal_bl'=>$mysqlDate,
				'code' => $data['kode_ref'],
				'num' => $number,
				'kode_ref' => $data['kode_ref'] . $number,
				'weight' => $row[15],
				'ship_meas' => $row[16],
				'wh_meas' => $row[16],
				'chgs_weight' => $row[16],
				'wh_agent' => $row[16],
				'bl_meas' => $row[16],
				'cost_meas' => $row[16],
				'no_pack_qty' => $row[17],
				'no_pack_unit' => $row[18],
				'no_of_pack' => $row[17] . ' ' . $row[18],
				'shipping_mark' => $row[14],
				'destination' => $this->get_nama_kota($row[5]),
				'lock_job' => 0,
				'note' => $row[10] . "\n" . $row[11],
				'shipper_print' => $row[10],
				'shipper_import' => $row[10],
				'shipper_import_add' => $row[11],
				'shipper_import_code' => $ship_code,
				'consignee' => $row[8],
				'consignee_add' => $row[9],
				'notify' => $row[12],
				'notify_add' => $row[13],
				'express_release' => FALSE,
				'shipped_on_board' => FALSE,
				'net_weight_tidak_dicetak' => FALSE,
				'cetak_jml_doc_origi' => FALSE,
				'desc_goods' => $row[24],
				'status_selling' => $row[3],
				'desc_print' => $desc_print,
				'isLock' => 0,
				'c_o' => $row[20],
				'isSpotRate' =>$this->get_nama_kota($row[7]),
				'shipment_request_id' =>$row[26],
				'sp_id' => $sp_id,
				'region_id' => $region_id,
			];
			$this->dbx->insert('trnstuffingplandetail', $detail_data);
		}

		// Insert into tbl_detail and x_tracking
		$qd = $this->dbx->get_where('trnstuffingplandetail', ['sp_id' => $sp_id]);
		if ($qd->num_rows() > 0) {
			foreach ($qd->result() as $nr) {
				$this->dbx->insert('tbl_detail', [
					'detail_id' => $nr->sp_detail_id,
					'sp_id' => $sp_id,
					'feeder_vessel' => $data['vessel'] . ' ' . $data['voyage'],
					'feeder_vessel_etd_date' => $data['closing_date_time'],
					'region_id' => $region_id,
				]);

			// Cek apakah no_job sudah ada
			$this->dbx->where('no_job', $nr->kode_ref);
			$query = $this->dbx->get('x_tracking');

			if ($query->num_rows() == 0) {
				// Jika tidak ada, lakukan insert
				$tracking_data = [
					'id_shipper' => $nr->shipper,
					'id_request' => '',
					'id_agent' => '',
					'warehouse' => $data['warehouse'] ?? '',
					'mbl_no' => $nr->shipment_request_id,
					'hbl_agent' => $nr->doc,
					'hbl_no' => $nr->hb_l,
					'feeder_name' => $data['vessel'].' '.$data['voyage'],
					'connecting_name' => '',
					'party' => $nr->no_pack_qty . ' ' . $nr->no_pack_unit,
					'etd' => '',
					'atd' => '',
					'eta' => $data['eta'],
					'ata' => $data['eta'],
					'shipper' => $nr->shipper_print,
					'consignee' => $nr->shipper_import,
					'destination' => $nr->destination,
					'port_of_destination' => $nr->isSpotRate,
					'container_no' => $nr->c_o,
					'remarks' => '',
					'sp_detail_id' => $nr->sp_detail_id,
					'prepared' => $this->session->userdata('user_id'),
					'no_job' => $nr->kode_ref,
					'region_id' => $this->session->userdata('region_id'),
					'created_by' => $this->session->userdata('fullname'),
					'created_at' => date('Y-m-d H:i:s')
				];
				$this->dbx->insert('x_tracking', $tracking_data);
				}
			}
		}
		return [
			'status' => $this->dbx->affected_rows(),
			'header' => $data,
			'kode_ref' => $data['kode_ref'],
			'detail' => $detail,
		];
	}

	function getNextIDNew($tipe,$bulan,$tahun){
		$region = $this->session->userdata('region_id');
		$reg_id= $this->dbx->get_where('refregion',array('region_name'=>$region))->row()->region_id;
		$sql = "select ifnull(max(right(kode_ref,4)),'0') as hasil from trnstuffingplan where mid(kode_ref,5,3)= '$tipe' and region_id = '$region' "; 		
		$query = $this->dbx->query($sql,array($tipe, $tahun, $bulan));		
		$hasil = $query->row()->hasil ;
		if($hasil=="0")
		{
			$hasil=$reg_id."-300". $tahun. $bulan."0001";
		}
		else
		{
			$hasil=$hasil+1;
			$hasil=$reg_id."-300". $tahun. $bulan . str_pad($hasil, 4, "0", STR_PAD_LEFT);
		}
		return $hasil;
	}
	
	function get_id_stuffing($kode_ref){
		$this->dbx->select('sp_id');
		$this->dbx->where('kode_ref',$kode_ref);
		$query = $this->dbx->get('trnstuffingplan');
		if ( $query->result() == '')
		{
			return 1;
		}  
		else
		{
			return $query->row()->sp_id;
		}
	}
	
	function get_nama_kota($kode_kota)
{
    $kode_kota = trim($kode_kota);

    $query = $this->dbx
        ->select('name')
        ->where('code', $kode_kota)
        ->get('tableName');

    if ($query->num_rows() == 0)
    {
        log_message(
            'error',
            'KOTA TIDAK DITEMUKAN : [' . $kode_kota . ']'
        );

        echo 'KOTA TIDAK DITEMUKAN : [' . $kode_kota . ']';
        die();
    }

    return $query->row()->name;
}
	

}


/*
return $this->getNextIDNew('300',date("m"),date("Y"));
*/









