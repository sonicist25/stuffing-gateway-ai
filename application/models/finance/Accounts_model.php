<?php
class Accounts_model extends CI_Model
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
	
	function get_total($id,$cur) {
		$q = $this->dbx->get_where('x_finance_item',array('number'=>$id,'currency'=>$cur));
		if($q->num_rows() > 0) {
			foreach ($q->result() as $row){
                $total[] = $row-> price;
				//$total[] = $row->qty * $row-> price;
			}
			return array_sum($total);
		} else {
			return "";
		}
	}
	
	function get_finance($id) {
		$this->dbx->order_by('number','asc');
		$data = $this->dbx->get_where('x_finance',array('id'=>$id),1,false);
		if(empty($data)):
			return '';
		else :
			$hasil = $data->result_object();
			if(empty($hasil)) :
				return '';
			else :
				return $hasil[0];
			endif;
		endif;
	}
	
	function get_finance_item($id) {
		$this->dbx->order_by('number','asc');
		$data = $this->dbx->get_where('x_finance_item',array('number'=>$id),1,false);
		if(empty($data)):
			return '';
		else :
			$hasil = $data->result_object();
			if(empty($hasil)) :
				return '';
			else :
				return $hasil[0];
			endif;
		endif;
	}
	
	function get_invoice() {
		
		$this->dbx->select('id_fin');
		$id_fin = $this->dbx->get_where(TBL_ACC_RCV,
			array(
				'region_id'=>$this->session->userdata('region_id'),
				'hide'=>0
			)
		);
		
		$this->dbx->select('id, date as tanggal, number as no_inv,job_no as no_job, kepada_kode as pay_to');
		$this->dbx->like('date',$this->session->userdata('db').$_GET['whr']['tanggal']);
		$q = $this->dbx->get_where('x_finance',
					array(
					'region_id'=>$this->session->userdata('region_id'),
					
				)
		);
		
		if($id_fin->num_rows() == 0 ) :
			$hasil = $q->result_array();
			if(!empty($hasil)) :
				foreach ($hasil as $row) {
					$rows['id_fin'] = $row['id'];
					$rows['tanggal'] = $row['tanggal'];
					$rows['editable'] = 0;
					$rows['region_id'] = $this->session->userdata('region_id');
					$this->dbx->insert(TBL_ACC_RCV,$rows);
				}
			endif;
		
		else :
			
			
			foreach($q->result_array() as $row ) {
				$id_a [] = $row['id'];
			}
			
			foreach($id_fin->result_array() as $row ) {
				$id_b [] = $row['id_fin'];
			}
			
			$id_u = array_diff($id_b,$id_a);
			
			foreach($id_u as $upd) {
				$this->dbx->update(TBL_ACC_RCV,array('hide'=>1),array('id_fin'=>$upd));
			}
			
			$id_up = array_diff($id_a,$id_b);
			
			foreach($id_up as $id) {
				$rows['id_fin'] = $id;
				$rows['tanggal'] = $this->dbx->get_where('x_finance',array('id'=>$id))->row('date');
				$rows['editable'] = 0;
				$rows['region_id'] = $this->session->userdata('region_id'); 
				$this->dbx->insert(TBL_ACC_RCV,$rows);
			}

		endif;
		
		/*
		$this->dbx->like('tanggal',$this->session->userdata('db').$_GET['whr']['tanggal']);
		$this->dbx->where('editable',0);
		$this->dbx->delete(TBL_ACC_RCV);
		
		
		$this->dbx->select('id, date as tanggal, number as no_inv,job_no as no_job, kepada_kode as pay_to');
		$this->dbx->like('date',$this->session->userdata('db').$_GET['whr']['tanggal']);
		$q = $this->dbx->get_where('x_finance',
			array(
				'region_id'=>$this->session->userdata('region_id'),
			)
		)->result_array();
			
		if(!empty($q)) :
			foreach ($q as $row) {
				$row['no_bl'] = $this->get_data_item($row['id']);
				$row['ttl_usd'] = $this->get_total($row['id'],'USD');
				$row['ttl_rp'] = $this->get_total($row['id'],'RP.');
				unset($row['id']);
				$row['editable'] = 0;
				$this->dbx->insert(TBL_ACC_RCV,$row);
			}
		endif;
		*/
	}
	
	function get_list_inv() {
		$this->dbx->select('number as code');
		$q = $this->dbx->get_where('x_finance',array('region_id'=>$this->session->userdata('region_id')))->result_array();
		return $q;
	}
	
	function get_list_all($ids) {
	$this->dbx->select('*');
		$this->dbx->from('finance_accounts_receivable');
		$this->dbx->where( 'id IN ('.$ids.')',NULL, false);
		$query = $this->dbx->get()->result();
		if (count($query) > 0)  {
			return $query;
		} else {
			return null;	
		}
	}
	
	function manage_data($data) {
		$table = 'TBL_ACC_RCV';
		$oper = $data['oper'];
		
		if($oper=='add') :
			$data['tanggal_byr'] =  date("Y-m-d");
			$data['id_fin'] =  date("Y-m-d");
			$data['region_id'] = $this->session->userdata('region_id');
			unset($data['table']);
			unset($data['oper']);
			unset($data['bulan']);
			$this->dbx->insert(constant($table),$data);
		elseif($oper=='edit') :
			$id=$data['id'];
			$nmr_inv = $data['mydata']['nomor'];
			$date = new DateTime($data['mydata']['tanggal_byr']);
			$formattedDate = $date->format('Ymd');
			//print_r($nmr_inv);
			//die();
			$this->dbx->update(constant($table),$data['mydata'],array('id'=>$id));
			if ($data['mydata']['pelunasan'] == 'Yes') {
				$this->dbx->set('x_finance.status', 'paid');
				$this->dbx->set('x_finance.tgl_bayar', $formattedDate);
				$this->dbx->where_in('x_finance.number', $nmr_inv);
				$this->dbx->update('x_finance');
			} else {
				$this->dbx->set('x_finance.status', 'unpaid');
				$this->dbx->set('x_finance.tgl_bayar', '');
				$this->dbx->where_in('x_finance.number', $nmr_inv);
				$this->dbx->update('x_finance');
				}
			return true;
		else :
			$idx = explode(",",$data['id']);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete(constant($table),array("id"=>$idx[$i]));
			if ($this->dbx->affected_rows() > 0) {
				$this->dbx->set('x_finance.status', 'unpaid');
				$this->dbx->set('x_finance.tgl_bayar', '');
				$this->dbx->where_in('x_finance.id', $idx[$i]);
				$this->dbx->update('x_finance');
			}
			}		
		endif;
		
		return array('table'=>$table,'oper'=>$oper,'status'=>$this->dbx->affected_rows());
	}
	
	function manage_data_pay($data) {
		$table = $data['table'];
		$oper = $data['oper'];
		
		if($oper=='add') :
			$data['tanggal'] =  date("Y-m-d");
			$data['region_id'] = $this->session->userdata('region_id');
			unset($data['table']);
			unset($data['oper']);
			unset($data['bulan']);
			$this->dbx->insert(constant($table),$data);
		elseif($oper=='edit') :
			$id=$data['id'];
			//print_r($nmr_inv);
			//die();
			$this->dbx->update(constant($table),$data['mydata'],array('id'=>$id));
			if ($data['mydata']['pelunasan'] == 'Yes') {
				$this->update_status($data['mydata']['kode_ref'],$data['mydata']['item']);
			} else {
				return false;
				}
			return true;
		else :
			$idx = explode(",",$data['id']);
			for($i=0;$i<=count($idx)-1;$i++) {
				$this->dbx->delete(constant($table),array("id"=>$idx[$i]));
			}		
		endif;
		
		return array('table'=>$table,'oper'=>$oper,'status'=>$this->dbx->affected_rows());
	}
	
	function getAllJob() {
		$this->dbx->select('kode_ref');
		return $this->dbx->get_where(TRN_STF_PLN_DET,array('region_id'=>$this->session->userdata('region_id')));
	}

	function get_data_table($data,$limit) {
   		
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
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);	
		}
		
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		$tbl='TBL_ACC_RCV';
		$q = $this->dbx->get_where(constant($tbl),array('hide'=>0,'region_id'=>$this->session->userdata('region_id')))->result();
		return $q;
	}
	
	function get_data_table_ap($data,$limit) {
   		
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
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);	
		}
		
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		$tbl='TBL_ACC_PAY';
		$q = $this->dbx->get_where(constant($tbl),array('hide'=>0,'region_id'=>$this->session->userdata('region_id')))->result();
		return $q;
	}
	
	function get_name($kpd,$code,$number)
	{
		if($kpd=="AGENT") {
			$sa = $this->dbx->get_where("refagentcompany",array("agent_company_code"=>$code));
			if($sa->num_rows() > 0 ) {
				return $sa->row()->agent_company_name;
			} else {
				return "";
			}
		}
		if($kpd=="WAREHOUSE") {
			$sc = $this->dbx->get_where("refwarehouse",array("warehouse_code"=>$code));
			if($sc->num_rows() > 0 ) {
				return $sc->row()->warehouse_name;
			} else {
				return "";
			}
		}
		if($kpd=="COLOADER") {
			$sc = $this->dbx->get_where("refshippingcompany",array("shipping_company_code"=>$code));
			if($sc->num_rows() > 0 ) {
				return $sc->row()->shipping_company_name;
			} else {
				return "";
			}
		}
		if($kpd=="SHIPPER") {
			$ss = $this->dbx->get_where("x_invoice",array("number"=>$number,"region_id"=>$this->session->userdata('region_id')));
			if($ss->num_rows() > 0 ) {
				return $ss->row()->shipper_name;
			} 
			else {
				return "";
			}
		}
		if($kpd=="CONSIGNEE") {
			$scn = $this->dbx->get_where("refconsigneeimport",array("consignee_import_code"=>$code));
			if($scn->num_rows() > 0 ) {
				return $code;
			} else {
				return "";
			}
		}
		if($kpd=="") {
			return $code;
		}
	}
	function get_name_payable($kpd)
	{
			$sa = $this->dbx->get_where("refvendor",array("vendor_code"=>$kpd));
			if($sa->num_rows() > 0 ) {
				return $sa->row()->vendor_name;
			} else {
				return "";
			}
	}
	
	function update_status($ids, $tables) {
        // Explode the IDs and tables into arrays
        $id_array = explode(',', $ids);
        $table_array = explode(',', $tables);

        // Ensure both arrays have the same length
        if (count($id_array) != count($table_array)) {
            throw new Exception("Mismatched number of IDs and tables");
        }

        // Iterate through each table and ID pair
        foreach ($table_array as $index => $table) {
            $id = $id_array[$index];

            // Update the 'status' column to 'paid' where 'id' matches
            $this->dbx->where_in('id', $id);
            $this->dbx->update($table, ['status' => 'paid']);
        }
    }
}
