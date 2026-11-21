<?php
class Afg_c_income extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->library('access_control');
		$this->load->helper('quotation_crm');
		$this->load->model(array('afg_m_export','user_model','afg_m_countries','m_bill_of_ladding','afg_m_income','afg_m_stuffing_plan','finance/m_inv_to_shipper','reference/type_service_model','reference/type_container_model'));
		$this->fungsi->dbx = $this->afg_m_income->dbx;
		$this->dbx = $this->afg_m_income->dbx;
	}
	
	function index()
	{
		die();
	}
	private function normalize_income_kurs($value)
	{
		$value = trim((string)$value);

		if ($value === '') {
			return 0;
		}

		// Hilangkan spasi.
		$value = preg_replace('/\s+/', '', $value);

		// Format ribuan Indonesia / umum:
		// 16.500 => 16500
		// 16,500 => 16500
		if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) {
			return (float)str_replace([',', '.'], '', $value);
		}

		// Decimal comma: 16500,50 => 16500.50
		if (strpos($value, ',') !== false && strpos($value, '.') === false) {
			$value = str_replace(',', '.', $value);
		} else {
			$value = str_replace(',', '', $value);
		}

		$kurs = (float)$value;

		return $kurs > 0 ? $kurs : 0;
	}
	
	function open_form_global(){
		$this->access_control->require_feature('stuffing_global_income', 'view', true);
		$data['job_no']= $this->input->post('ref_no');
		$row = $this->afg_m_income->check_data_global_income($data['job_no']);
		$data['created']=$row['updated_by'].' '.$row['updated_at'];
		$this->load->view('trucking/form_global_income',$data);
	}
	function open_form_global_edit(){
		$this->access_control->require_feature('stuffing_global_income', 'edit', true);
		$data['id'] = $this->input->post('id');
		$data['oper'] = $this->input->post('oper');
		$this->load->view('trucking/form_global_income_edit',$data);
	}
    function manage_global()
    {
        $mode = $this->input->post('oper');
        $id = $this->input->post('id');

        $data = array(
            'job_no'       => $this->input->post('job_no'),
            'code_service' => $this->input->post('code_service'),
            'item_service' => $this->input->post('item_service'),
            'qty'          => $this->input->post('qty'),
            'container'    => $this->input->post('container'),
            'currency'     => $this->input->post('currency'),
            'unit_price'   => $this->input->post('unit_price'),
            'price'        => $this->input->post('qty') * $this->input->post('unit_price'),
            'prep_coll'    => $this->input->post('prep_coll'),
            'payeble_to'   => $this->input->post('payeble_to'),
            'group'        => $this->input->post('group'),
        );

        if ($mode == 'add') {
            $this->access_control->require_feature('stuffing_global_income', 'create', true);
            $result = $this->afg_m_income->add_global($data);
        } elseif ($mode == 'edit') {
            $this->access_control->require_feature('stuffing_global_income', 'edit', true);
            $result = $this->afg_m_income->update_global($data,$id);
        } elseif ($mode == 'del') {
            $this->access_control->require_feature('stuffing_global_income', 'delete', true);
            $result = $this->afg_m_income->delete_global($id);
        } else {
            $result = ['status'=>0,'error'=>'INVALID_OPER'];
        }

        if (is_array($result)) {
            $result['mode'] = $mode;
            echo json_encode($result);
        } else {
            echo json_encode(['status'=>(int)$result,'mode'=>$mode]);
        }
    }

	function get_global_income(){

		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		
		$q = $this->afg_m_income->get_global_income($_POST,false);
		$query = $this->afg_m_income->get_global_income($_POST,true);
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		   $total_hal = ceil($hitung/$batas);
		} else {
		   $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
		
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;

		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
		    		$row->job_no,
		    		$row->code_service, 
		    		$row->item_service,
		    		$row->qty,
		    		$row->container,  
		    		$row->currency, 
		    		$row->unit_price,
		    		$row->price, 
		    		$row->prep_coll, 
		    		$row->payeble_to, 
		    		$row->group,
					$row->created_by,
					$row->created_at,
					$row->updated_by,
					$row->updated_at,
		    );
		    $i++;
		}
		echo json_encode($data);
	}
	
	function tarik_data_pajak() {
		$data = $this->afg_m_income->tarik_data_pajak($_POST);
		echo json_encode($data);	
		//echo json_encode($_POST);	
	}
	
	function tarik_data_pajak_coloader() {
		$data = $this->afg_m_income->tarik_data_pajak_coloader($_POST);
		echo json_encode($data);	
		//echo json_encode($_POST);	
	}
	
	
	function update_sales()
	{
		$data = $this->afg_m_income->update_sales($_POST);
		//print_r($this->dbx->last_query());
		echo json_encode($data);	
	}
	
	
	function lock_job() {
		$lock = $this->m_inv_to_shipper->get_blue($_POST['kode_ref']);
		$level = $this->session->userdata("level_id");
		$status = ($level === 0 || $level === 3 || $level === 6 || $level === 18 || $level === 19) ? 0 : $lock;
		echo json_encode(array('status'=>$status,"lock"=>$lock,'level'=>$level));
	}
	
	function kunci_job() {
		$lock = $this->m_inv_to_shipper->get_kunci($_POST['kode_ref']);
		$level = $this->session->userdata("level_id");
		$status = ($level === 0 || $level === 3) ? 0 : $lock;
		echo json_encode(array('status'=>$status,"lock"=>$lock,'level'=>$level));
	}
	
	function kosong_job() {
		$lock = $this->m_inv_to_shipper->get_kosong($_POST['kode_ref']);
		$level = $this->session->userdata("level_id");
		$status = ($level === 0 || $level === 3) ? 0 : $lock;
		echo json_encode(array('status'=>$status,"lock"=>$lock,'level'=>$level));
	}
	
	function update_price()
	{
		$data = $this->afg_m_income->update_price($_POST);
		echo json_encode($data);	
	}
	
	function update_ppn()
	{
		$_POST["ppn"]=$this->input->post('ppn');
		$data = $this->afg_m_income->update_ppn($_POST);
		echo json_encode($data);	
	}
	
	function update_ppn_coloader()
	{
		$_POST["ppn"]=$this->input->post('ppn');
		$data = $this->afg_m_income->update_ppn_coloader($_POST);
		echo json_encode($data);	
	}
	
	function saving_income()
	{
		$this->access_control->require_feature('stuffing_income', 'edit', true);
		$data = $this->afg_m_income->saving_income($_POST);
		echo json_encode($data);
	}
	
	function saving_agent()
	{
		$data = $this->afg_m_income->saving_agent($_POST);
		echo json_encode($data);
	}
	
	function saving_coloader()
	{
		$data = $this->afg_m_income->saving_coloader($_POST);
		echo json_encode($data);
	}
	
	function insert_data_selling_price() 
	{
		$this->access_control->require_feature('stuffing_income', 'create', true);
		$data = $this->afg_m_income->insert_data_selling_price($_POST);
		echo json_encode($data);
	}
	
	function tarik_data_selling_price()
	{
	$this->access_control->require_feature('stuffing_income', 'create', true);
   	$data = $this->afg_m_income->tarik_data_selling_price($_POST);
	//print_r($this->dbx->last_query());
   	echo json_encode($data);
	}
	
	function get_confirm()
	{
		$job_no = $this->input->post('job_no');
		$shipper_id = $this->input->post('shipper_id');
		$destination = $this->input->post('destination');
		$query = $this->afg_m_income->get_confirm($job_no, $shipper_id, $destination);
		if($query) {
			echo json_encode($query->row_array());
		} else {
		   return 0;	
		}
	}
	 
	function open_form()
	{
		$this->access_control->require_feature('stuffing_income', 'view', true);
		$data['id'] = $this->input->post('rowid');
		$data['kode_ref'] = $this->input->post('kode_ref');
		$data['lock'] = $this->input->post('lock');
		$data['incoterms'] = $this->input->post('incoterms');
		$data['shipper'] = $this->input->post('shipper');
		$data['customer'] = $this->input->post('customer');
		$row = $this->afg_m_income->check_data_income($data['kode_ref'],$data['id']);
		$data['Intensiv_tidak_diproses'] = $row['Intensiv_tidak_diproses'];
		$data['Tarik_insentiv_per_Doc'] = $row['Tarik_insentiv_per_Doc'];

		/*
		 * Kurs Income:
		 * 1. Pakai kurs yang sudah pernah disimpan di inf_income.
		 * 2. Kalau belum ada, pakai default kurs selling yang dikirim dari jobsheet.
		 * 3. Safety fallback: baca langsung kurs_selling/kurs dari trnstuffingplan.
		 *
		 * Dengan ini form Income tidak blank hanya karena nilai POST `kurs`
		 * dari javascript tidak terkirim / kosong.
		 */
		$storedKurs = isset($row['kurs']) ? (float)$row['kurs'] : 0.0;
		$postKursRaw = trim((string)$this->input->post('kurs'));
		$postKursClean = str_replace([',', ' '], '', $postKursRaw);
		$postKurs = is_numeric($postKursClean) ? (float)$postKursClean : 0.0;

		$resolvedKurs = 0.0;
		if ($storedKurs > 0) {
			$resolvedKurs = $storedKurs;
		} elseif ($postKurs > 0) {
			$resolvedKurs = $postKurs;
		}

		/*
		 * IMPORTANT:
		 * $data['kode_ref'] di form Income adalah kode DETAIL, contoh:
		 *   BDG-90126080005A
		 * sedangkan trnstuffingplan.kode_ref adalah kode HEADER/job, contoh:
		 *   BDG-90126080005
		 *
		 * Karena itu jangan lookup trnstuffingplan memakai kode_ref detail.
		 * Resolve parent secara exact dari sp_detail_id -> sp_id terlebih dahulu.
		 */
		if ($resolvedKurs <= 0
			&& $this->dbx->table_exists('trnstuffingplan')
			&& $this->dbx->table_exists('trnstuffingplandetail')) {

			$region = trim((string)$this->session->userdata('region_id'));
			$spDetailId = (int)$data['id'];
			$sp = [];

			if ($spDetailId > 0) {
				$this->dbx->select('p.kurs_selling, p.kurs, p.kode_ref AS header_kode_ref, d.code AS detail_parent_code');
				$this->dbx->from('trnstuffingplandetail d');
				$this->dbx->join('trnstuffingplan p', 'p.sp_id = d.sp_id', 'left');
				$this->dbx->where('d.sp_detail_id', $spDetailId);

				if ($region !== '') {
					$this->dbx->where('p.region_id', $region);
				}

				$sp = $this->dbx->limit(1)->get()->row_array();
			}

			/*
			 * Compatibility fallback untuk data lama / detail yang sp_id-nya belum rapi:
			 * ambil `code` dari detail, karena field itulah kode parent/header.
			 */
			if (empty($sp) && $spDetailId > 0) {
				$detail = $this->dbx
					->select('code')
					->get_where('trnstuffingplandetail', ['sp_detail_id' => $spDetailId])
					->row_array();

				$parentCode = trim((string)($detail['code'] ?? ''));
				if ($parentCode !== '') {
					$this->dbx->select('kurs_selling, kurs, kode_ref AS header_kode_ref');
					$this->dbx->from('trnstuffingplan');
					$this->dbx->where('kode_ref', $parentCode);
					if ($region !== '') {
						$this->dbx->where('region_id', $region);
					}
					$sp = $this->dbx->limit(1)->get()->row_array();
				}
			}

			if (!empty($sp)) {
				$jobSellingRaw = str_replace([',', ' '], '', trim((string)($sp['kurs_selling'] ?? '')));
				$jobKursRaw    = str_replace([',', ' '], '', trim((string)($sp['kurs'] ?? '')));
				$jobSelling    = is_numeric($jobSellingRaw) ? (float)$jobSellingRaw : 0.0;
				$jobKurs       = is_numeric($jobKursRaw) ? (float)$jobKursRaw : 0.0;

				// Prefer kurs selling. Jika kosong, kurs pajak tetap lebih baik daripada field kosong.
				if ($jobSelling > 0) {
					$resolvedKurs = $jobSelling;
				} elseif ($jobKurs > 0) {
					$resolvedKurs = $jobKurs;
				}
			}
		}

		$data['kurs'] = $resolvedKurs > 0
			? rtrim(rtrim(number_format($resolvedKurs, 2, '.', ''), '0'), '.')
			: '';

		$data['created']=$row['created_by'].' '.$row['created_at'];
		$data['last_update']=$row['last_update_by'].' '.$row['last_update_at'];
		$this->load->view('trucking/form_income',$data);
	}
	
	function tarik_data_coloader()
	{
      $data = $this->afg_m_income->tarik_data_coloader($_POST);
      echo json_encode($data);
	}
	
	public function tampil_selling_price()
{
    // ambil param jqGrid
    $post = $this->input->post();
    $page = isset($post['page']) ? (int)$post['page'] : 1;
    $rows = isset($post['rows']) ? (int)$post['rows'] : 10;

    // minta ke model: satu kali hit untuk count + data (JOIN x_finance buat payment)
    $result = $this->afg_m_income->tampil_selling_price_paged($post);
	//echo "<pre>"; print_r($result['debug']); echo "</pre>"; exit;
    $total_count = (int)$result['total'];
    $total_pages = $total_count > 0 ? (int)ceil($total_count / max(1,$rows)) : 0;
    if ($page > $total_pages) $page = $total_pages;

    $data            = new stdClass();
    $data->page      = $page;
    $data->total     = $total_pages;
    $data->records   = $total_count;
    $data->rows      = [];

    $i = 0;
    foreach ($result['rows'] as $row) {
        // format tanggal updated_at → YmdHis (sesuai versi lama)
        $updatedYmdHis = '';
        if (!empty($row->updated_at)) {
            $ts = strtotime($row->updated_at);
            if ($ts !== false) $updatedYmdHis = date('YmdHis', $ts);
        }

        $data->rows[$i]['id']   = $row->id;
        $data->rows[$i]['cell'] = [
            $row->sp_detail_id,
            $row->kode_ref,
            $row->mchgs,
            '',
            $row->container,
            $row->code_service,
            $row->item_service,
            $row->qty,
            $row->unit_price,
            $row->price,
            $row->currency,
            $row->prep_coll,
            $row->receive_from,
            $row->remark,
            $row->agent,
            $row->g,
            $row->ppn,
            $row->invoice_db,
			$row->ship_co,
            $updatedYmdHis,
            $row->created_by,
            $row->created_at,
            $row->updated_by,
            $row->updated_at,
            $row->payment_status, // ← hasil JOIN x_finance.status
        ];
        $i++;
    }

    echo json_encode($data);
}

	
	function manage_income()
	{
		$this->access_control->require_feature('stuffing_income', 'delete', true);
		$status = $this->afg_m_income->manage_income($_POST);
		echo json_encode($status);
	}
		
public function get_from_agent()
{
    $post = $this->input->post();
    $page = isset($post['page']) ? (int)$post['page'] : 1;
    $rows = isset($post['rows']) ? (int)$post['rows'] : 10;

    // satu panggilan ke model: count + data (JOIN x_finance)
    $result = $this->afg_m_income->get_from_agent_paged($post);

    $total_count = (int)$result['total'];
    $total_pages = $total_count > 0 ? (int)ceil($total_count / max(1, $rows)) : 0;
    if ($page > $total_pages) $page = $total_pages;

    $agentFallback = $post['agent_code'] ?? '';

    $data = new stdClass();
    $data->page    = $page;
    $data->total   = $total_pages;
    $data->records = $total_count;
    $data->rows    = [];

    $i = 0;
    foreach ($result['rows'] as $row) {
        // fallback agent jika kosong → pakai agent_code dari POST
        $agent = $row->agent !== '' ? $row->agent : $agentFallback;

        $data->rows[$i]['id']   = $row->id;
        $data->rows[$i]['cell'] = [
            $row->sp_detail_id,
            $row->kode_ref,
            $row->item_service,
            $row->container,
            $row->currency,
            $row->qty,
            $row->unit_price,
            $row->price,         // tetap pakai field price dari DB (sesuai versi lama)
            $agent,              // sudah ada fallback
            $row->invoice_db,
            $row->dn_no,
            $row->g,
            $row->created_by,
            $row->created_at,
            $row->updated_by,
            $row->updated_at,
            $row->payment_status // hasil LEFT JOIN x_finance.status
        ];
        $i++;
    }

    echo json_encode($data);
}

	function manage_from_agent()
	{
		$value = $this->afg_m_income->manage_from_agent($_POST);
		echo json_encode($value);
	}
	
	function tarik_data_insentiv_agent()
	{
		$data = $this->afg_m_income->tarik_data_insentiv_agent($_POST);
		echo json_encode($data);
	}
	
	function manage_header_income()
	{
		$id = $this->input->post('id_sd');
		$kode_ref = $this->input->post('kode_ref');
		$data=$this->afg_m_income->update_header_income($_POST);
		$status = $this->afg_m_income->manage_header_income($_POST);
		echo json_encode(array('status'=>'1'));
	}

	function get_from_coloader()
	{
		$spd = $this->input->post('spd');
      $kode_ref = $this->input->post('kode_ref');
      $qty = $this->input->post('qty');
      $trk_doc = $this->input->post('trk_doc');
		
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'desc';
      
      $start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start'] = $start;
		$q = $this->afg_m_income->get_from_coloader($_POST,false);
		$query = $this->afg_m_income->get_from_coloader($_POST,true);
		
		$hitung = count($q);
	 
		if( $hitung > 0 ) {
		   $total_hal = ceil($hitung/$batas);
		} else {
		   $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 	
	 	$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;

		foreach($query as $row) {
			$qtynya = $row->qty;
			
				if($qtynya =='') {
				$qtynya = $qty;
				}else If($row->item_service == 'TAX 1%') {
				$qtynya = 0.01;
				}else If($row->item_service == 'TAX 10%') {
				$qtynya = 0.1;
				}else{
				$qtynya = $row->qty;
				}
				//print_r ($qtynya);
				//die();
				$mchgs = $row->mchgs;
				
				if($qtynya < $mchgs) {
					$kali = floatval($mchgs) * floatval($row->unit_price);
					} else {
					$kali = floatval($qtynya) * floatval($row->unit_price);
				}

				if($kali==0) {
					$kali='';	
				}
			
		    	$data->rows[$i]['id']=$row->id;
		    	$data->rows[$i]['cell']=array(
		    		$kode_ref,
		    		$row->mchgs,
		    		$row->container,
		    		$row->code_service,
					$row->item_service,
		    		$row->currency, 
		    		$qtynya,
		    		$row->unit_price,
					$kali,
		    		$row->invoice_db,
		    		$row->g,
		    		$row->ppn,
		    		$row->sp_detail_id,
					$row->created_by,
					$row->created_at,
					$row->updated_by,
					$row->updated_at,
		   	);
		   	$i++;
		}
		echo json_encode($data);
	}

	function manage_from_coloader()
	{
		$value = $this->afg_m_income->manage_from_coloader($_POST);
		echo json_encode($value);
		//print_r($this->dbx->last_query());
	}

	function clear_agent_coloader()
	{
		$kode_ref = $this->input->post('kode_ref');
		$status = $this->afg_m_income->clear_agent_coloader($kode_ref);
		echo json_encode(array('status'=>$status,'mode'=>'clear table'));
	}
	
	function update_agent_coloader()
	{
		$kode_ref = $this->input->post('kode_ref');
		$qty = $this->input->post('qty');
		$status = $this->afg_m_income->update_agent_coloader($_POST);
		echo json_encode(array('status'=>$status,'mode'=>'update table'));
	}
	

	private function income_is_jakarta()
	{
		return strcasecmp(trim((string)$this->session->userdata('region_id')), 'Jakarta') === 0;
	}

	private function income_quotation_filters($method = 'get')
	{
		$method = strtolower((string)$method);
		$read = function($key) use ($method) {
			return $method === 'post'
				? $this->input->post($key, true)
				: $this->input->get($key, true);
		};

		return [
			'region_id'   => trim((string)$this->session->userdata('region_id')),
			'shipper_id'  => $read('shipper_id'),
			'shipper'     => $read('shipper'),
			'destination' => $read('destination'),
			'sales_id'    => $read('sales_id'),
			'sales_name'  => $read('sales_name'),
			'valid_date'  => $read('valid_date'),
		];
	}

	private function income_load_crm_lifecycle_models()
	{
		$this->load->model('api/crm/M_quotations', 'income_crm_quotations');
		$this->load->model('api/crm/M_opportunities', 'income_crm_opportunities');
	}

	private function map_quotation_items_to_income(
		array $header,
		$source = 'CRM',
		$kurs = 0
	) {
		$source = strtoupper(trim((string)$source));
		$containerDefault = trim((string)($header['container_size'] ?? ''));
		$kurs = (float)$kurs;

		$mapped = [];

		foreach ((array)($header['items'] ?? []) as $item) {
			if (!is_array($item)) {
				continue;
			}

			$basis = strtoupper(trim((string)($item['pricing_basis'] ?? 'FIXED')));

			$includeInTotal = isset($item['include_in_total'])
				? (int)$item['include_in_total']
				: 1;

			if ($source === 'CRM') {
				if ($includeInTotal === 0) {
					continue;
				}

				if (in_array($basis, ['COLLECT', 'EXCLUDED', 'INFO'], true)) {
					continue;
				}
			}

			/*
			 * Currency quotation.
			 */
			$currency = 'RP.';

			if ($source === 'CRM') {
				$currency = strtoupper(trim((string)(
					$item['currency']
					?? $header['currency']
					?? 'IDR'
				)));

				if (in_array($currency, ['IDR', 'RP', 'RP.', 'RUPIAH', ''], true)) {
					$currency = 'RP.';
				}
			}

			/*
			 * Nilai asli quotation.
			 */
			$qty = (float)($item['qty'] ?? 0);

			$unitPrice = (float)(
				$item['rate']
				?? $item['unit_price']
				?? 0
			);

			$price = (float)(
				$item['amount']
				?? 0
			);

			$detail = trim((string)($item['charge_detail'] ?? ''));

			if ($source === 'CRM' && $basis !== '' && $basis !== 'FIXED') {
				$basisNote = 'Pricing: ' . str_replace('_', ' ', $basis);

				$detail = $detail !== ''
					? ($detail . ' | ' . $basisNote)
					: $basisNote;
			}

			/*
			 * =====================================================
			 * CONVERT USD -> IDR
			 * =====================================================
			 *
			 * Hanya dilakukan bila:
			 * - source CRM
			 * - currency item USD
			 * - kurs Income diisi > 0
			 *
			 * Contoh:
			 * USD 100 x kurs 16.500
			 * => unit_price = Rp 1.650.000
			 *
			 * amount USD juga dikalikan kurs.
			 */
			if (
				$source === 'CRM'
				&& $currency === 'USD'
				&& $kurs > 0
			) {
				$originalUnitPrice = $unitPrice;
				$originalPrice = $price;

				$unitPrice = $originalUnitPrice * $kurs;
				$price = $originalPrice * $kurs;

				$currency = 'RP.';

				$kursNote = 'Converted from USD @ '
					. number_format($kurs, 0, '.', ',');

				$detail = $detail !== ''
					? ($detail . ' | ' . $kursNote)
					: $kursNote;
			}

			$mapped[] = [
				'container'    => trim((string)(
					$item['container'] ?? $containerDefault
				)),

				'code_service' => trim((string)(
					$item['item_code']
					?? $item['charge_code']
					?? ''
				)),

				'item_service' => trim((string)(
					$item['item_name']
					?? $item['description']
					?? ''
				)),

				'qty'        => $qty,
				'unit_price' => $unitPrice,
				'price'      => $price,
				'currency'   => $currency,
				'remark'     => $detail,
			];
		}

		return $mapped;
	}

	private function income_json_response(array $payload, $http = 200)
	{
		return $this->output
			->set_status_header((int)$http)
			->set_content_type('application/json; charset=utf-8')
			->set_output(json_encode($payload));
	}

	private function cleanup_new_quotation_income(array $importResult)
	{
		if (!empty($importResult['already_loaded'])) return;
		$ids = array_values(array_filter(array_map('intval', (array)($importResult['inserted_ids'] ?? []))));
		if (!$ids) return;

		$cleanup = $this->afg_m_income->delete_quotation_income_ids($ids);
		if (empty($cleanup['ok'])) {
			log_message('error', 'Quotation lifecycle cleanup income failed: ' . json_encode($cleanup));
		}
	}

	private function restore_crm_after_accept_failure($quoteId, $previousQuoteStatus, $opportunityId, $previousStage)
	{
		try {
			if ((int)$opportunityId > 0 && trim((string)$previousStage) !== '') {
				$this->income_crm_opportunities->changeStage(
					(int)$opportunityId,
					(string)$previousStage,
					(int)$this->session->userdata('user_id')
				);
			}
		} catch (Throwable $e) {
			log_message('error', 'Failed restoring opportunity after quotation lifecycle error: ' . $e->getMessage());
		}

		try {
			if ((int)$quoteId > 0) {
				$this->income_crm_quotations->update((int)$quoteId, [
					'status' => (string)$previousQuoteStatus
				]);
			}
		} catch (Throwable $e) {
			log_message('error', 'Failed restoring quotation after lifecycle error: ' . $e->getMessage());
		}
	}

	function get_list_quotation()
	{
		$filters = $this->income_quotation_filters('get');

		// Jakarta tetap Jasaweb/ERPNext, tetapi sekarang helper menerima context
		// jobsheet sehingga dropdown sudah terfilter SEBELUM user memilih quote.
		if ($this->income_is_jakarta()) {
			return get_daftar_quotation($filters);
		}

		$response = get_daftar_quotation_crm($filters);
		return $this->income_json_response($response, 200);
	}

	function get_list_quotation_detail()
	{
		$quotNo = trim((string)$this->input->get('quot_no', true));

		if ($quotNo === '') {
			return $this->income_json_response(['error'=>'Quotation No. kosong'], 422);
		}

		if ($this->income_is_jakarta()) {
			// Helper lama membaca quot_no dari $_GET. Kontrak detail tetap dipertahankan.
			$response_quot = get_daftar_quotation_detail();
		} else {
			$response_quot = get_daftar_quotation_detail_crm(
				$quotNo,
				$this->income_quotation_filters('get')
			);
		}

		if (isset($response_quot['error_code'])) {
			return $this->income_json_response(
				['error'=>$response_quot['message']],
				(int)$response_quot['error_code']
			);
		}

		return $this->income_json_response($response_quot, 200);
	}

	function insert_quotation_detail()
	{
		$this->access_control->require_feature('stuffing_income', 'create', true);

		/*
		 * Jakarta tetap flow legacy Jasaweb.
		 * Non-Jakarta memakai lifecycle CRM server-side: browser TIDAK menjadi
		 * source of truth harga; quotation di-fetch ulang oleh backend.
		 */
		if (!$this->income_is_jakarta()) {
			return $this->accept_crm_quotation_to_income();
		}

		$data = json_decode((string)$this->input->post('jsonData'), true);
		if (!is_array($data) || empty($data['data']['items']) || !is_array($data['data']['items'])) {
			return $this->income_json_response(
				['status'=>0,'message'=>'No quotation items found.'],
				422
			);
		}

		$header = $data['data'];
		$mapped = $this->map_quotation_items_to_income($header, 'JASAWEB');

		$result = $this->afg_m_income->import_quotation_items([
			'sp_detail_id' => $this->input->post('sp_id'),
			'kode_ref'     => $this->input->post('kode_ref'),
			'quote_no'     => $this->input->post('quot_no'),
		], $mapped);

		return $this->income_json_response(
			$result,
			!empty($result['status']) ? 200 : 422
		);
	}

	private function accept_crm_quotation_to_income()
	{
		$region = trim((string)$this->session->userdata('region_id'));
		$spDetailId = (int)$this->input->post('sp_id');
		$kodeRef = trim((string)$this->input->post('kode_ref', true));
		$quoteNo = trim((string)$this->input->post('quot_no', true));
		
		$kurs = $this->normalize_income_kurs(
			$this->input->post('kurs', true)
		);

		if ($region === '' || $spDetailId <= 0 || $kodeRef === '' || $quoteNo === '') {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Konteks quotation/jobsheet tidak lengkap.'
			], 422);
		}

		if (!$this->afg_m_income->quotation_link_table_ready()) {
			return $this->income_json_response([
				'status'=>0,
				'error'=>'QUOTATION_LINK_MIGRATION_REQUIRED',
				'message'=>'Tabel inf_income_quotation_link belum tersedia. Jalankan migration quotation lifecycle terlebih dahulu.'
			], 500);
		}

		$active = $this->afg_m_income->get_active_quotation_link($spDetailId, $kodeRef, $region);
		if ($active) {
			if (strcasecmp((string)$active['quote_no'], $quoteNo) === 0) {
				return $this->income_json_response([
					'status'=>1,
					'already_loaded'=>true,
					'active'=>true,
					'message'=>'Quotation ini sudah aktif pada Income.',
					'data'=>[
						'quote_no'=>$active['quote_no'],
						'link_id'=>(int)$active['id'],
					],
				], 200);
			}

			return $this->income_json_response([
				'status'=>0,
				'error'=>'ANOTHER_QUOTATION_ALREADY_ACTIVE',
				'message'=>'Jobsheet ini sudah memakai quotation ' . $active['quote_no'] . '. Batalkan quotation aktif terlebih dahulu.'
			], 409);
		}

		$this->income_load_crm_lifecycle_models();

		$filters = $this->income_quotation_filters('post');
		$result = $this->income_crm_quotations->get_for_income_by_no($quoteNo, $filters);

		if (!$result || empty($result['quote'])) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Quotation CRM tidak ditemukan atau sudah tidak sesuai dengan jobsheet.'
			], 404);
		}

		$q = $result['quote'];
		$q['items'] = (array)($result['items'] ?? []);

		$quoteId = (int)($q['quote_id'] ?? 0);
		$opportunityId = (int)($q['opportunity_id'] ?? 0);
		$leadId = (int)($q['lead_id'] ?? 0);
		$sourceType = strtoupper(trim((string)($q['source_type'] ?? '')));
		$previousQuoteStatus = trim((string)($q['status'] ?? ''));
		$previousStage = trim((string)($q['opportunity_stage'] ?? ''));

		if ($quoteId <= 0 || $opportunityId <= 0) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Quotation tidak memiliki relasi Opportunity yang valid.'
			], 422);
		}

		$mapped = $this->map_quotation_items_to_income(
			$q,
			'CRM',
			$kurs
		);
		if (!$mapped) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Quotation tidak memiliki item billable untuk dimasukkan ke Income.'
			], 422);
		}

		$import = $this->afg_m_income->import_quotation_items([
			'sp_detail_id'=>$spDetailId,
			'kode_ref'=>$kodeRef,
			'quote_no'=>$quoteNo,
		], $mapped);

		if (empty($import['status'])) {
			return $this->income_json_response($import, 422);
		}

		try {
			$this->income_crm_quotations->update($quoteId, ['status'=>'ACCEPTED']);
		} catch (Throwable $e) {
			$this->cleanup_new_quotation_income($import);
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Income sudah diproses tetapi status quotation gagal diubah: ' . $e->getMessage()
			], 500);
		}

		$stageResult = $this->income_crm_opportunities->changeStage(
			$opportunityId,
			'Rate Accepted',
			(int)$this->session->userdata('user_id')
		);

		if (empty($stageResult['status'])) {
			$this->restore_crm_after_accept_failure(
				$quoteId,
				$previousQuoteStatus,
				$opportunityId,
				$previousStage
			);
			$this->cleanup_new_quotation_income($import);

			return $this->income_json_response([
				'status'=>0,
				'message'=>'Gagal mengubah Opportunity ke Rate Accepted: ' . ($stageResult['message'] ?? 'Unknown error')
			], 500);
		}

		$link = $this->afg_m_income->create_quotation_link([
			'quote_no'=>$quoteNo,
			'quote_id'=>$quoteId,
			'opportunity_id'=>$opportunityId,
			'lead_id'=>$leadId,
			'source_type'=>$sourceType,
			'sp_detail_id'=>$spDetailId,
			'kode_ref'=>$kodeRef,
			'region_id'=>$region,
			'previous_quote_status'=>$previousQuoteStatus,
			'previous_opportunity_stage'=>$previousStage,
			'accepted_quote_status'=>'ACCEPTED',
			'accepted_opportunity_stage'=>'Rate Accepted',
			'income_ids'=>$import['income_ids'] ?? [],
			'accepted_by_id'=>(int)$this->session->userdata('user_id'),
			'accepted_by'=>(string)$this->session->userdata('fullname'),
		]);

		if (empty($link['status'])) {
			$this->restore_crm_after_accept_failure(
				$quoteId,
				$previousQuoteStatus,
				$opportunityId,
				$previousStage
			);
			$this->cleanup_new_quotation_income($import);

			return $this->income_json_response([
				'status'=>0,
				'message'=>'Quotation/Opportunity sudah diubah tetapi linkage Income gagal disimpan. Perubahan sudah di-revert.'
			], 500);
		}

		return $this->income_json_response([
			'status'=>1,
			'already_loaded'=>!empty($import['already_loaded']),
			'inserted'=>(int)($import['inserted'] ?? 0),
			'message'=>'Quotation ' . $quoteNo . ' digunakan. Income diperbarui, quotation menjadi ACCEPTED, dan Opportunity menjadi Rate Accepted.',
			'data'=>[
				'quote_no'=>$quoteNo,
				'quote_id'=>$quoteId,
				'opportunity_id'=>$opportunityId,
				'lead_id'=>$leadId,
				'source_type'=>$sourceType,
				'quotation_status'=>'ACCEPTED',
				'opportunity_stage'=>'Rate Accepted',
				'link_id'=>(int)($link['data']['id'] ?? 0),
			],
		], 200);
	}

	public function quotation_link_state()
	{
		$spDetailId = (int)$this->input->get('sp_id', true);
		$kodeRef = trim((string)$this->input->get('kode_ref', true));
		$region = trim((string)$this->session->userdata('region_id'));

		if ($this->income_is_jakarta()) {
			return $this->income_json_response([
				'status'=>1,
				'source'=>'JASAWEB',
				'active'=>false
			], 200);
		}

		if (!$this->afg_m_income->quotation_link_table_ready()) {
			return $this->income_json_response([
				'status'=>0,
				'active'=>false,
				'error'=>'QUOTATION_LINK_MIGRATION_REQUIRED'
			], 200);
		}

		$link = $this->afg_m_income->get_active_quotation_link($spDetailId, $kodeRef, $region);
		if (!$link) {
			return $this->income_json_response([
				'status'=>1,
				'source'=>'CRM',
				'active'=>false
			], 200);
		}

		return $this->income_json_response([
			'status'=>1,
			'source'=>'CRM',
			'active'=>true,
			'data'=>[
				'id'=>(int)$link['id'],
				'quote_no'=>(string)$link['quote_no'],
				'quote_id'=>(int)$link['quote_id'],
				'opportunity_id'=>(int)$link['opportunity_id'],
				'source_type'=>(string)$link['source_type'],
				'lead_id'=>(int)$link['lead_id'],
				'quotation_status'=>(string)$link['accepted_quote_status'],
				'opportunity_stage'=>(string)$link['accepted_opportunity_stage'],
				'accepted_at'=>$link['accepted_at'],
				'accepted_by'=>$link['accepted_by'],
			]
		], 200);
	}

	public function cancel_quotation_import()
	{
		$this->access_control->require_feature('stuffing_income', 'delete', true);

		if ($this->income_is_jakarta()) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Cancel lifecycle quotation CRM hanya berlaku untuk region non-Jakarta.'
			], 403);
		}

		$spDetailId = (int)$this->input->post('sp_id');
		$kodeRef = trim((string)$this->input->post('kode_ref', true));
		$reason = trim((string)$this->input->post('reason', true));
		$region = trim((string)$this->session->userdata('region_id'));

		$link = $this->afg_m_income->get_active_quotation_link($spDetailId, $kodeRef, $region);
		if (!$link) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Tidak ada quotation aktif pada Income ini.'
			], 404);
		}

		$incomeIds = $this->afg_m_income->quotation_link_income_ids($link);
		if (!$incomeIds) {
			return $this->income_json_response([
				'status'=>0,
				'message'=>'Link quotation ditemukan tetapi row Income sumber tidak ditemukan.'
			], 409);
		}

		$this->income_load_crm_lifecycle_models();

		$quoteId = (int)$link['quote_id'];
		$opportunityId = (int)$link['opportunity_id'];
		$previousQuoteStatus = trim((string)$link['previous_quote_status']);
		$previousStage = trim((string)$link['previous_opportunity_stage']);
		$acceptedQuoteStatus = trim((string)$link['accepted_quote_status']) ?: 'ACCEPTED';
		$acceptedStage = trim((string)$link['accepted_opportunity_stage']) ?: 'Rate Accepted';

		try {
			$this->income_crm_quotations->update($quoteId, ['status'=>$previousQuoteStatus]);

			if ($opportunityId > 0 && $previousStage !== '') {
				$restoreStage = $this->income_crm_opportunities->changeStage(
					$opportunityId,
					$previousStage,
					(int)$this->session->userdata('user_id')
				);
				if (empty($restoreStage['status'])) {
					throw new RuntimeException($restoreStage['message'] ?? 'Gagal restore Opportunity');
				}
			}
		} catch (Throwable $e) {
			// Pastikan state tetap accepted jika restore hanya berhasil sebagian.
			try {
				$this->income_crm_quotations->update($quoteId, ['status'=>$acceptedQuoteStatus]);
				if ($opportunityId > 0) {
					$this->income_crm_opportunities->changeStage(
						$opportunityId,
						$acceptedStage,
						(int)$this->session->userdata('user_id')
					);
				}
			} catch (Throwable $ignored) {}

			return $this->income_json_response([
				'status'=>0,
				'message'=>'Gagal mengembalikan status CRM: ' . $e->getMessage()
			], 500);
		}

		$deleted = $this->afg_m_income->delete_quotation_income_ids($incomeIds);
		if (empty($deleted['ok'])) {
			// Delete/unbill gagal (mis. PAID): quotation dan opportunity jangan dibiarkan reverted.
			try {
				$this->income_crm_quotations->update($quoteId, ['status'=>$acceptedQuoteStatus]);
				if ($opportunityId > 0) {
					$this->income_crm_opportunities->changeStage(
						$opportunityId,
						$acceptedStage,
						(int)$this->session->userdata('user_id')
					);
				}
			} catch (Throwable $ignored) {}

			$this->afg_m_income->mark_quotation_link_error(
				(int)$link['id'],
				(string)($deleted['error'] ?? 'INCOME_CANCEL_FAILED')
			);

			return $this->income_json_response([
				'status'=>0,
				'error'=>$deleted['error'] ?? 'INCOME_CANCEL_FAILED',
				'message'=>($deleted['error'] ?? '') === 'PAID_INCOME_DELETE_NOT_ALLOWED'
					? 'Quotation tidak dapat dibatalkan karena Income sudah dibayar.'
					: 'Gagal membatalkan Income quotation.',
				'detail'=>$deleted
			], (int)($deleted['http'] ?? 409));
		}

		$marked = $this->afg_m_income->mark_quotation_link_cancelled((int)$link['id'], $reason);

		return $this->income_json_response([
			'status'=>1,
			'message'=>'Quotation dibatalkan dari Income. Status quotation dan Opportunity dikembalikan ke kondisi sebelumnya.',
			'data'=>[
				'quote_no'=>$link['quote_no'],
				'quotation_status'=>$previousQuoteStatus,
				'opportunity_stage'=>$previousStage,
				'deleted_income_ids'=>$deleted['deleted_ids'] ?? [],
				'auto_unbilled'=>!empty($deleted['auto_unbilled']),
				'paid_override_delete'=>!empty($deleted['paid_override_delete']),
				'link_cancelled'=>(bool)$marked,
			],
		], 200);
	}


    /**
     * Revenue Order ERP baru hanya berlaku untuk region NON-Jakarta.
     * Guard ini wajib ada di backend agar endpoint tidak bisa dibypass
     * dengan memanggil URL secara langsung.
     */
    private function require_non_jakarta_revenue_order_region()
    {
        $region = trim((string)$this->session->userdata('region_id'));

        if ($region === '') {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(422)
                ->set_output(json_encode([
                    'status'  => false,
                    'message' => 'Region session tidak ditemukan.'
                ]));
            return false;
        }

        if (strcasecmp($region, 'Jakarta') === 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode([
                    'status'  => false,
                    'message' => 'Revenue Order ERP baru hanya untuk region non-Jakarta. Jakarta tetap menggunakan flow posting existing.'
                ]));
            return false;
        }

        return $region;
    }

    /**
     * New ERP Revenue Order post.
     * Normal income groups strictly by kode_ref.
     * KHUSUS NON-JAKARTA.
     */
    public function post_revenue_order()
    {
        $this->access_control->require_feature('stuffing_income', 'edit', true);

        if ($this->require_non_jakarta_revenue_order_region() === false) {
            return;
        }

        $kode_ref = trim((string)$this->input->post('kode_ref'));
        $sp_detail_id = (int)$this->input->post('sp_detail_id');
        $kurs = (float)str_replace([',',' '], '', (string)$this->input->post('kurs'));
        $extra = [
            'carrier'      => trim((string)$this->input->post('carrier')),
            'sales_id'     => trim((string)$this->input->post('sales_id')),
            'shipper_code' => trim((string)$this->input->post('shipper_code')),
        ];

        $result = $this->afg_m_income->sync_revenue_order($kode_ref, $sp_detail_id, $kurs, $extra);
        $http = !empty($result['status']) ? 200 : ((int)($result['http'] ?? 422) ?: 422);

        return $this->output->set_content_type('application/json')
            ->set_status_header($http)
            ->set_output(json_encode($result));
    }

    /**
     * Global Income groups strictly by x_glbincome.job_no.
     */
    public function post_global_revenue_order()
    {
        $this->access_control->require_feature('stuffing_global_income', 'edit', true);

        if ($this->require_non_jakarta_revenue_order_region() === false) {
            return;
        }

        $job_no = trim((string)$this->input->post('job_no'));
        $result = $this->afg_m_income->sync_global_revenue_order($job_no);
        $http = !empty($result['status']) ? 200 : ((int)($result['http'] ?? 422) ?: 422);

        return $this->output->set_content_type('application/json')
            ->set_status_header($http)
            ->set_output(json_encode($result));
    }

}

?>