<?php
class Afg_m_cost extends CI_Model
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
   
   function update_header_cost($data) 
	{
		if($data['kode_ref']!='') 
		{
			$this->dbx->update('inf_cost',array('kurs'=>$data['kurs'],'rc'=>$data['rc_tdk_proses']),array("kode_ref"=>$data['kode_ref']));
		}
		
		return array("status"=>$this->dbx->affected_rows());
	}
	
	function check_posted_cost($kode_ref,$id_sd){
		$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id_sd));
		if($q->num_rows() == 1) {
			return $q->row('manifest_no');
		} else {
			return 0;	
		}	
	}
   
   function check_data_cost($kode_ref){
		$q = $this->dbx->get_where('inf_cost',array('kode_ref'=>$kode_ref));
		if($q->num_rows() == 1) {
			return $q->row_array();
		} else {
			$this->dbx->insert('inf_cost', 
			array('kode_ref'=>$kode_ref,
			'rc'=>'FALSE',
			'status'=>'Unposted'));
			if($this->dbx->affected_rows() > 0) {
				$q = $this->dbx->get_where('inf_cost',array('kode_ref'=>$kode_ref));
				return $q->row_array();
				} else {
				return 0;	
			}
		}	
	}
	
	function check_data_global_cost($kode_ref){
		$q = $this->dbx->get_where('inf_cost',array('kode_ref'=>$kode_ref));
		if($q->num_rows() == 1) {
			return $q->row_array();
		} else {
			$this->dbx->insert('inf_cost', 
			array('kode_ref'=>$kode_ref,
			'rc'=>'FALSE',
			'status'=>'Unposted'));
			if($this->dbx->affected_rows() > 0) {
				$q = $this->dbx->get_where('inf_cost',array('kode_ref'=>$kode_ref));
				return $q->row_array();
				} else {
				return 0;	
			}
		}	
	}
   
	function clear_refund($kode_ref)
	{
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where ('region_id',$this->session->userdata("region_id"));
		$this->dbx->delete(array('inf_cost_refund_sc','inf_cost_refund_discount'));
		return '1';
	}
	
	function saving_cost($data)
	{
		$data['myData']['sp_detail_id']=$data['myData']['spd'];
		$data['myData']['g']=$data['myData']['Grup'];
		unset($data['myData']['spd']);
		unset($data['myData']['Grup']);
		$data['myData']['updated_by']=$this->session->userdata('fullname');
		$data['myData']['updated_at']=date('Y-m-d H:i:s');
		
		$updated = $this->dbx->update("inf_cost_tbl_satu",
			$data['myData'],
			array('id'=>$data['id'])
		);

		if($updated){
			$this->dbx->update('inf_income',array(    
					'last_update_by'=>$this->session->userdata('fullname'),
					'last_update_at'=>date('Y-m-d H:i:s')
				),array('id_sd'=>$data['myData']['sp_detail_id']));
			return array("status"=>1);
		}else{
			return array("status"=>0);
		}

	}


	function tarik_cost($data)
	{
		$data['shipper_id'] = $this->get_shipper_id($data['shipper_id']);	
		$q = $this->dbx->get_where('inf_cost_tbl_satu', array('kode_ref'=>$data['kode_ref'],'id_tarik '=>'1'));
		if($q->num_rows() > 0 ) 
		{
			$this->dbx->where('kode_ref',$data['kode_ref']);
			$this->dbx->where('id_tarik','1');
			$this->dbx->where('sp_detail_id',$data['spd']);
			$this->dbx->where ('region_id',$this->session->userdata('region_id'));
			$this->dbx->delete('inf_cost_tbl_satu');
		}
		
		$job = substr($data['kode_ref'], 4,3);
		if($job != '300' && $job != '301' &&  $job != '302' && $job != '400' && $job != '402' && $job != '600')
		{
			$this->dbx->join('cost_price_agent_export','cost_price_agent_export.id=cost_price_agent_export_detail.id_data','left');
			$query = $this->dbx->get_where('cost_price_agent_export_detail',array(
				'cost_price_agent_export.agent'=>$data['agent'],
				//'destination'=>$data['destination'],
			));
		}
		else
		{
			return false;
		}
		
		//print_r($query->result());
		
		if($query->num_rows()>0) 
		{
		

			foreach($query->result() as $row)
			{
				$id = $row->id;
			}
			
			$qd = $this->dbx->get_where("cost_price_agent_export_detail",array("id_data"=>$row->id,'destination'=>$data['destination']))->result();
				
				foreach($qd as $key)
				{
					$set = $data['qty'];
					if($row->mchgs >= $set ) 
					{
						$price = $row->mchgs * $row->price;
					}
					else 
					{
						$price = $set * $row->price;
					}
					
					$this->dbx->insert("inf_cost_tbl_satu",
						array(
							'id_tarik'=>1,
							'sp_detail_id'=>$data['spd'],
							"kode_ref"=>$data['kode_ref'],
							"container"=>"",
							"code_service"=>$this->fungsi->get_code_service($key->service),
							"item_service"=>$key->service,
							"qty"=>$set,
							"unit_price"=>$key->hcbm,
							"price"=>$price,
							"currency"=>"USD",
							"remark"=>"",
							"prep_coll"=>$key->prep_coll,
							"payable_to"=>$data['agent'],
							"agent"=>$data['agent'],
							"ship_co"=>"",
							"invoice_db"=>"",
							"ppn"=>"",
							"created_by"=>$this->session->userdata('fullname'),
							"created_at"=>date('Y-m-d H:i:s'),
							"updated_by"=>$this->session->userdata('fullname'),
							"updated_at"=>date('Y-m-d H:i:s')
					));
					
				}
			
		}
		
		return array($q);
	}

	function get_data_payable()
	{	
		if($this->session->userdata('region_id') == 'Jakarta'){
		$searchTerm = $_GET['term'];
		$this->dbx->select('vendor_code as payable');
		$this->dbx->like('vendor_code',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$query = $this->dbx->get('refvendor')->result_array();
		return array_merge($query);
		}else{
		$searchTerm = $_GET['term'];
		$this->dbx->select('shipper_id as payable');
		$this->dbx->like('shipper_id',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q1 = $this->dbx->get('mstshipper')->result_array();
		
		$this->dbx->select('warehouse_code as payable');
		$this->dbx->like('warehouse_code',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q2 = $this->dbx->get('refwarehouse')->result_array();
		
		$this->dbx->select('agent_company_code as payable');
		$this->dbx->like('agent_company_code',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q3 = $this->dbx->get('refagentcompany')->result_array();
		
		$this->dbx->select('shipping_company_code as payable');
		$this->dbx->like('shipping_company_code',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q4 = $this->dbx->get('refshippingcompany')->result_array();
		
		$this->dbx->select('trucking_company_code as payable');
		$this->dbx->like('trucking_company_code',$searchTerm);
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q5 = $this->dbx->get('reftruckingcompany')->result_array();
		
		return array_merge($q1,$q2,$q3,$q4,$q5);
		}
	}
	
	function get_data_payable_global()
	{
		$this->dbx->select('warehouse_code as payable');
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q2 = $this->dbx->get('refwarehouse')->result_array();
		
		$this->dbx->select('agent_company_code as payable');
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q3 = $this->dbx->get('refagentcompany')->result_array();
		
		$this->dbx->select('shipping_company_code as payable');
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q4 = $this->dbx->get('refshippingcompany')->result_array();
		
		$this->dbx->select('trucking_company_code as payable');
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q5 = $this->dbx->get('reftruckingcompany')->result_array();
		
		return array_merge($q2,$q3,$q4,$q5);
	}

	function get_shipper_id($id)
	{
		return $this->dbx->get_where('mstshipper',array('id_shipper'=>$id))->row()->shipper_id;

	}

	function tarik_data_refund($data)
	{	
	
		$data['shipper_id'] = $this->get_shipper_id($data['shipper_id']);
	
		$q = $this->dbx->get_where($data['table'],array('kode_ref'=>$data['kode_ref'],'id_tarik !='=>0));
		if($q->num_rows() > 0 ) {
			$this->dbx->where(array('kode_ref'=>$data['kode_ref'],'region_id'=>$this->session->userdata('region_id'),'id_tarik !='=>0));
			$this->dbx->delete($data['table']);
		}
	
		
		if($data['table']=='inf_cost_refund_discount') {
			$this->dbx->select('nama_service,destination,currency,price,kondisi,container,valid,a_selling.id_selling,a_selling_rate.mchgs,
				a_selling_rate.discount, a_selling_rate.set_m3, a_selling.tanggal,
			');
			$this->dbx->join('a_selling','a_selling.id_selling=a_selling_rate.id_selling','left');
			if($data['type'] == 'FCL' || $data['type'] == 'SF IMPORT FCL' ||  $data['shipment'] == 'FCL') {
						$type ='FCL';
						$query = $this->dbx->get_where('a_selling_rate', array(
						'destination'=>$data['destination'],
						'discount !='=>'',
						//'a_selling.tanggal <='=> $this->fungsi->jin_date_sql($data['valid']),
						'a_selling.valid >='=> $this->fungsi->jin_date_sql($data['valid']),
						'kode_shipper'=>$data['shipper_id'],
						'type'=>$type,	
						'a_selling_rate.container'=>$data['container'],

				));
				}
				else
				{
						if ($data['type'] == 'TRUCKING ONLY' || $data['type'] == 'CUSTOM' || $data['type'] == 'TRUCKING VENDOR' )
						{ $type = 'FCL';}
						else
						{
						 $type ='LCL';
						}
					$this->dbx->from('a_selling_rate')->group_start()->where('destination', $data['destination'])->or_where('destination', '')->group_end();
					$this->dbx->where('discount !=','');
					$this->dbx->where('a_selling.valid >=',$this->fungsi->jin_date_sql($data['valid']));
					$this->dbx->where('kode_shipper',$data['shipper_id']);
					$this->dbx->where('type',$type);
					$query = $this->dbx->get();
				}
			//print_r($this->dbx->last_query());
			//die();
			$code_service = 'SD';
			$code = 'SALES DISCOUNT';
			$rows ='discount';
		} 
		else 
		{	
			$this->dbx->select('nama_service,destination,currency,price,kondisi,container,valid,a_selling.id_selling,a_selling_rate.mchgs,
				a_selling_rate.sales_comission, a_selling.tanggal,
			');
			$this->dbx->join('a_selling','a_selling.id_selling=a_selling_rate.id_selling','left');
			$query = $this->dbx->get_where('a_selling_rate', array(
						'destination'=>$data['destination'],
						'sales_comission !='=>'',
						'valid >='=> $this->fungsi->jin_date_sql($data['valid']),
						'a_selling.tanggal <='=> $this->fungsi->jin_date_sql($data['valid']),
						'kode_shipper'=>$data['shipper_id'],	
					));	
			$code = 'SALES COMMISION';
			$rows = 'sales_comission';
		}
			
		if($query->num_rows() > 0 ) {
				foreach ($query->result() as $row){
					if($data['type'] == 'FCL' || $data['type'] == 'SF IMPORT FCL')
					{
						$set = $data['qty'];
						if($row->mchgs >= $set )
							{       
                        $set = $row->mchgs;
							}
							else 
							{
								$set = $data['qty'];
							}
							
							$data_new=array(
							'id_tarik'=>$row->id_selling,
							'sp_detail_id'=>$data['spd'],
							'kode_ref'=>$data['kode_ref'],
							'container'=>$row->container,
							'code_service'=>$code_service,
							'item_service'=>$code,
							'qty'=>$set,
							'mchgs'=>$row->mchgs,
							'unit_price'=>$row->$rows,
						//	'price'=>'',
							'currency'=>$row->currency,
							'prep_coll'=>$row->kondisi,
							'payable_to'=>'',
							'region_id'=>$this->session->userdata('region_id'),
							'created_by'=>$this->session->userdata('fullname'),
							'created_at'=>date('Y-m-d H:i:s'),
							'updated_by'=>$this->session->userdata('fullname'),
							'updated_at'=>date('Y-m-d H:i:s')
						//	'remark'=>'',
						//	'agent'=>$row->kode_agen,
						//	'ship_co'=>$row->kodeshipco,
						//	'g'=>'',
						//	'ppn'=>'FALSE',
						//	'invoice_db'=>$row->invoice_db,
						//	'cetak_rt_cfm'=>'FALSE',
					);
				}
				else 
				{
                    $set = $data['qty'];
					if($row->price == "0" )
					{
                        $set = $data['qty'];
					} 
					else if($row->set_m3 == 'SET' )
					{
						$set = '1';
					}
					else if ($row->mchgs >= $set)
					{
                        $set = $row->mchgs;
                    }
							$data_new=array(
							'id_tarik'=>$row->id_selling,
							'sp_detail_id'=>$data['spd'],
							'kode_ref'=>$data['kode_ref'],
							'container'=>$row->container,
							'code_service'=>$this->fungsi->get_code_service($code),
							'item_service'=>$code,
							'qty'=>$set,
							'mchgs'=>$row->mchgs,
							'unit_price'=>$row->$rows,
						//	'price'=>'',
							'currency'=>$row->currency,
						   'prep_coll'=>$row->kondisi,
							'payable_to'=>'',
							'region_id'=>$this->session->userdata('region_id'),
							'created_by'=>$this->session->userdata('fullname'),
							'created_at'=>date('Y-m-d H:i:s'),
							'updated_by'=>$this->session->userdata('fullname'),
							'updated_at'=>date('Y-m-d H:i:s')
						//	'remark'=>'',
						//	'agent'=>$row->kode_agen,
						//	'ship_co'=>$row->kodeshipco,
						//	'g'=>'',
						//	'ppn'=>'FALSE',
						//	'invoice_db'=>$row->invoice_db,
						//	'cetak_rt_cfm'=>'FALSE',
					);
				}
				$this->dbx->insert($data['table'],$data_new);
			}
		}
		return $query->result();	
	}
	
	function tampil_semua_cost($data)
	{	$region_id = $this->session->userdata('region_id');
		$q = $this->dbx->query("select * from inf_cost_tbl_satu where kode_ref='$kode_ref' and region_id='$region_id' order by 'id' asc");
		return $q;
	}
	
	function tampil_data_table($data,$limit){
	//	return $this->dbx->get_where($data['table'],array('kode_ref'=>$data['kode_ref']));
	
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where($data['table'],array('kode_ref'=>$data['kode_ref'],'region_id'=>$this->session->userdata('region_id')));
		return $q->result();
	}
	
	function get_cost_pdf($kode_ref){
		$this->dbx->select('*');
	 	$this->dbx->from('inf_cost_tbl_satu');
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}

	function get_cost_pdf_2($kode_ref){
		$this->dbx->select('*');
	 	$this->dbx->from('inf_cost_refund_discount');
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}

	function get_cost_pdf_3($kode_ref){
		$this->dbx->select('*');
	 	$this->dbx->from('inf_cost_refund_sc');
		$this->dbx->where('kode_ref',$kode_ref);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}

	function get_cost_pdf_4($code){
		$kode_ref = substr($code,0,15);
		$this->dbx->select('*');
	 	$this->dbx->from('x_glbcost');
		$this->dbx->where('job_no',$kode_ref);
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		$query = $this->dbx->get();
		if($query->num_rows() > 0 ) {
			return $query;
		} else {
			return null;	
		}
	}

	function get_cost_pdf_5($code){
		$kode_ref = substr($code,0,15);
		$this->dbx->select('bl_meas');
	 	$this->dbx->from('trnstuffingplandetail');
		$this->dbx->where('code',$kode_ref);
		$query = $this->dbx->get()->result_array();
		return $query;
	}
	
	function tampil_table_satu($data,$limit){
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where('inf_cost_tbl_satu',array('kode_ref'=>$data['kode_ref'],'region_id'=>$this->session->userdata('region_id')));
		return $q->result();
	}
	
	function add_tbl_satu($spd, $kode_ref, $kolom, $isi) {
		// 1. Mulai transaksi
		$this->dbx->trans_start();

		// 2. Susun array data dasar (Data wajib)
		$data = array(
			'sp_detail_id' => $spd,
			'kode_ref'     => $kode_ref,
			'region_id'    => $this->session->userdata('region_id'),
			'currency'     => '',
			'prep_coll'    => '',
			'code_service' => '', // Default kosong, akan tertimpa jika $kolom = 'code_service'
			'created_by'   => $this->session->userdata('fullname'),
			'created_at'   => date('Y-m-d H:i:s'),
			'updated_by'   => $this->session->userdata('fullname'),
			'updated_at'   => date('Y-m-d H:i:s')
		);

		// 3. MENCEGAH ERROR SQL: Hanya tambahkan input dinamis jika $kolom tidak kosong
		if (!empty($kolom)) {
			if ($kolom == 'code_service') {
				$isi = 'xxx'; // (Sesuai kode asli Anda)
				$data['item_service'] = $this->fungsi->get_item_service($isi); 
			}
			
			// Masukkan kolom beserta isinya ke array database
			$data[$kolom] = $isi; 
		}

		// 4. Eksekusi Insert
		$this->dbx->insert('inf_cost_tbl_satu', $data);

		// 5. Selesaikan transaksi
		$this->dbx->trans_complete();

		// 6. Evaluasi Status & Catat Log
		if ($this->dbx->trans_status() === FALSE) {
			//catat_log('Gagal menambah data biaya (Cost) untuk Kode Ref: ' . $kode_ref, 'Modul Tagihan & Biaya');
			return 0; // Gagal
		} else {
			//catat_log('Berhasil menambah data biaya (Cost) untuk Kode Ref: ' . $kode_ref, 'Modul Tagihan & Biaya');
			return 1; // Sukses
		}
	}

	function update_tbl_satu($kolom, $isi, $id, $price, $qty) {
		$this->dbx->trans_start();

		// Susun array dasar agar query update lebih rapi
		$update_data = array(
			$kolom       => $isi,
			'price'      => $price,
			'qty'        => $qty,
			"updated_by" => $this->session->userdata('fullname'),
			"updated_at" => date('Y-m-d H:i:s')
		);

		// Tambahkan item_service jika kolom yang diedit adalah code_service
		if($kolom == 'code_service') {
			$update_data['item_service'] = $this->fungsi->get_item_service($isi); 
		} 

		$this->dbx->where('id', $id);
		$this->dbx->update('inf_cost_tbl_satu', $update_data);

		$this->dbx->trans_complete();

		$item_service = $update_data['item_service'] ?? '(unknown)';

		if ($this->dbx->trans_status() === FALSE) {
			catat_log('Gagal mengubah data biaya '.$item_service.' dengan ID: ' . $id, 'Modul Tagihan & Biaya');
			return 0;
		} else {
			catat_log('Berhasil mengubah data biaya '.$item_service.' dengan ID: ' . $id, 'Modul Tagihan & Biaya');
			return 1;
		}

	}

	function delete_tbl_satu($id) {
		// Karena ada perulangan (looping) delete, trans_begin dan try-catch sangat krusial
		$this->dbx->trans_begin();

		try {
			$idx = explode(",", $id);
			$region_id = $this->session->userdata('region_id');

			for($i=0; $i < count($idx); $i++) {
				$this->dbx->delete('inf_cost_tbl_satu', array(
					"id"        => $idx[$i],
					'region_id' => $region_id // Keamanan tambahan sesuai region
				));
			}

			if ($this->dbx->trans_status() === FALSE) {
				$this->dbx->trans_rollback();
				catat_log('Gagal menghapus data biaya (Cost) - Database Error untuk ID: ' . $id, 'Modul Tagihan & Biaya');
				return 0;
			} else {
				$this->dbx->trans_commit();
				catat_log('Berhasil menghapus data biaya (Cost) untuk ID: ' . $id, 'Modul Tagihan & Biaya');
				return 1;
			}
		} catch (Exception $e) {
			$this->dbx->trans_rollback();
			log_message('error', 'Delete tbl_satu failed: ' . $e->getMessage());
			catat_log('Gagal menghapus data biaya (Cost) - System Exception untuk ID: ' . $id, 'Modul Tagihan & Biaya');
			return 0;
		}
	}
	

	
	function get_global_cost($data,$limit){
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where('x_glbcost',array('job_no'=>$data['job_no'],'region_id'=>$this->session->userdata('region_id')));
		return $q->result();
	}
	
	function add_global($data){
		$data['region_id']=$this->session->userdata('region_id');
		$data["created_by"]=$this->session->userdata('fullname');
		$data["created_at"]=date('Y-m-d H:i:s');
		$data["updated_by"]=$this->session->userdata('fullname');
		$data["updated_at"]=date('Y-m-d H:i:s');
		$this->dbx->insert('x_glbcost',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_global($data,$id){
		$data["updated_by"]=$this->session->userdata('fullname');
		$data["updated_at"]=date('Y-m-d H:i:s');
		$this->dbx->where('id',$id);
		$this->dbx->update('x_glbcost',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_global($id){
		$this->dbx->where('id',$id);
		$this->dbx->delete('x_glbcost');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}

	function manage_data_refund($data){
		$oper = $data['oper'];
		$table = $data['table'];
		$id	= $data['id'];
		unset($data['oper']);
		unset($data['table']);
		unset($data['id']);
		
		if(isset($data['kolom'])) {
			$kolom=$data['kolom'];	
		} else {
			$kolom='';
		}
		
		if($oper=='add') {
			$data["created_by"]=$this->session->userdata('fullname');
			$data["created_at"]=date('Y-m-d H:i:s');
			$data["updated_by"]=$this->session->userdata('fullname');
			$data["updated_at"]=date('Y-m-d H:i:s');
			$data['region_id']=$this->session->userdata('region_id');
			$this->dbx->insert($table,$data);
		}
		if($oper=='edit') {
			if($kolom=='code_service') {
				$item_service = $this->fungsi->get_item_service($data['isi']);
				$this->dbx->where('id',$id);
				$this->dbx->update($table,array($kolom=>$data['isi'],'item_service'=>$item_service,'updated_by'=>$this->session->userdata('fullname'),
				'updated_at'=>date('Y-m-d H:i:s')));
			}
			else {
				$this->dbx->where('id',$id);
				$this->dbx->update($table,array($kolom=>$data['isi'],'price'=>$data['price'],'updated_by'=>$this->session->userdata('fullname'),
				'updated_at'=>date('Y-m-d H:i:s')));
			}
		}
		else
		{
			$idx = explode(",",$id);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete($table,array("id"=>$idx[$i],'region_id'=>$this->session->userdata('region_id')));
			}
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper,'kolom'=>$kolom);
	}

}



?>