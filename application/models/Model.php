<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model extends CI_Model {	
	
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
   
   function manage_data($data) {
		//return $data;
		$table = $data["table"];
		$oper = explode('-',$data['oper'])[0];
		$opr = $data['oper'];
		if(isset($data['where'])) : $where = $data["where"]; unset($data["where"]); endif;
		unset($data['oper']);
		unset($data['table']);
		
		if($oper=='add') :
			$this->dbx->insert(constant($table),$data['data']);
		elseif($oper=='edit') :
			$this->dbx->update(constant($table),$data['data'],$where);	
		else :
			foreach ($where as $key => $val) {
				$idx[] = $key;
				$ids[] = $val;
			}
			$idx = $idx[0];
			$ids = $ids[0];
			$ids = explode(",",$ids);
			
			for($i=0;$i<=count($ids)-1;$i++) {
				if(isset($data['unlink'])) :
					if(file_exists(IMGPATH.'bl/'.$data['unlink'])) : 
        				unlink(IMGPATH.'bl/'.$data['unlink']);
        			endif;
				endif;
				if($table=='TRN_STF_PLN') :
					$this->dbx->update(constant($table),array('hide'=>1),$where);
				else :
					$this->dbx->delete(constant($table),array($idx=>$ids[$i]));
				endif;
			}
			if(isset($data['table_del'])) :
				$tbld=explode(',',$data['table_del']);	
				for($n=0;$n<=count($tbld)-1;$n++) { 
					$this->dbx->update($tbld[$n],array('hide'=>1),array($id_data=>$idx[$i]));
				}
			endif;	
		endif;
		$this->log_activity($oper,$table,$data);
		return array('table'=>$table,'oper'=>$opr,'rows'=>$this->dbx->affected_rows());
	}
   
	function get_data_table($data,$limit) {
   	$this->dbx->select($data['field']);  	
   	if(isset($data['where']['from']) && !isset($data['where']['to'])) {
   		$data['_search']=true;
   		$data['searchField']='date';
   		$data['searchOper']='ge';
   		$data['searchString']=$data['where']['from'];
   		unset($data['where']['from']);
   	}
   	
   	else if(isset($data['where']['to']) && !isset($data['where']['from'])) {
   		$data['_search']=true;
   		$data['searchField']='date';
   		$data['searchOper']='le';
   		$data['searchString']=$data['where']['to'];
   		unset($data['where']['to']);
   	}
   	
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
		
		if(isset($data['where']['from']) && isset($data['where']['to'])) {
   		$this->dbx->where('date >=',$data['where']['from']);
   		$this->dbx->where('date <=',$data['where']['to']);
   		unset($data['where']['from']);
   		unset($data['where']['to']);
   	}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);	
		}
		$tbl=$data['table'];
		$this->dbx->order_by($data['sidx'], $data['sord']);
		if(isset($data['where'])) {
			$q = $this->dbx->get_where(constant($tbl),$data['where'])->result();
		} else {
			$q = $this->dbx->get(constant($tbl))->result();
		}
		return $q;
	}   
   function get_job_cost($id_sp) {
   	$this->dbx->select('sp_detail_id as id,num');
   	$q=$this->dbx->get_where(TRN_STF_PLN_DET,array('sp_id'=>$id_sp));
   	if($q->num_rows()>0) :
   		foreach($q->result() as $row) {
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_CST_SATU.'.*'
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_CST_SATU.'.sp_detail_id','left');
   			$q_1=$this->dbx->get_where(TRN_CST_SATU,array(TRN_CST_SATU.'.sp_detail_id'=>$row->id,TRN_CST_SATU.'.region_id'=>REGID))->result_array();
   			
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_CST_DUA.'.*'
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_CST_DUA.'.sp_detail_id','left');
   			$q_2=$this->dbx->get_where(TRN_CST_DUA,array(TRN_CST_DUA.'.sp_detail_id'=>$row->id,TRN_CST_DUA.'.region_id'=>REGID))->result_array();
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_CST_TIGA.'.*'
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_CST_TIGA.'.sp_detail_id','left');
   			$q_3=$this->dbx->get_where(TRN_CST_TIGA,array(TRN_CST_TIGA.'.sp_detail_id'=>$row->id,TRN_CST_TIGA.'.region_id'=>REGID))->result_array();
   			
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_CST_EMPAT.'.*'
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_CST_EMPAT.'.sp_detail_id','left');
   			$q_4=$this->dbx->get_where(TRN_CST_EMPAT,array(TRN_CST_EMPAT.'.job_no'=>$row->id,TRN_CST_EMPAT.'.region_id'=>REGID))->result_array();
   		}
   		return array(array(),$q_1,$q_2,$q_3,$q_4);
   	else :
   		return array(array(),array(),array(),array());
   	endif;
   }
   
   function get_job_income($id_sp) {
   	$this->dbx->select('sp_detail_id,num');
   	$q=$this->dbx->get_where(TRN_STF_PLN_DET,array('sp_id'=>$id_sp));
   	if($q->num_rows()>0) :
   		foreach($q->result() as $row) {
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_INC_SATU.'.currency',
   				TRN_INC_SATU.'.unit_price',
   				TRN_INC_SATU.'.qty',
   				TRN_INC_SATU.'.prep_coll',
   				TRN_INC_SATU.'.item_service',
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_INC_SATU.'.sp_detail_id','left');
   			$q_s=$this->dbx->get_where(TRN_INC_SATU,array(TRN_INC_SATU.'.region_id'=>REGID))->result_array();
   			$this->dbx->select(array(
   				TRN_STF_PLN_DET.'.num',
   				TRN_INC_DUA.'.currency',
   				TRN_INC_DUA.'.unit_price',
   				TRN_INC_DUA.'.qty',
   				TRN_INC_DUA.'.prep_coll',
   				TRN_INC_DUA.'.item_service',
   			));
   			$this->dbx->join(TRN_STF_PLN_DET,TRN_STF_PLN_DET.'.sp_detail_id='.TRN_INC_DUA.'.sp_detail_id','left');
   			$q_c=$this->dbx->get_where(TRN_INC_DUA,array(TRN_INC_DUA.'.region_id'=>REGID))->result_array();
   		}
   		return array_merge($q_s,$q_c);
   	endif;
   }   
   
   function get_job_sheet($data) {
		$fld=array(
			TRN_STF_PLN.'.sp_id as id_sp',
			TRN_STF_PLN.'.kode_ref',
			TRN_STF_PLN.'.carrier',
			TRN_STF_PLN.'.warehouse',
			TRN_STF_PLN.'.type_cont_qty',
			TRN_STF_PLN.'.type_cont_size',
			TRN_STF_PLN.'.cont_seal_no',
			TRN_STF_PLN.'.kurs',
			TRN_STF_PLN.'.agen_penagihan as agent_penagihan',
			TRN_STF_PLN_DET.'.sp_detail_id as id_sd',
			TRN_STF_PLN_DET.'.num',
			TRN_STF_PLN_DET.'.shipper_print',
			TRN_STF_PLN_DET.'.shipper',
			TRN_STF_PLN_DET.'.consignee',
			TRN_STF_PLN_DET.'.consignee_add as consignee_note',
			TRN_STF_PLN_DET.'.notify',
			TRN_STF_PLN_DET.'.notify_add as notify_note',
			TRN_STF_PLN_DET.'.sales',
			TRN_STF_PLN_DET.'.destination',
			TRN_STF_PLN_DET.'.hb_l',
			TRN_STF_PLN_DET.'.desc_print',			
			TRN_BOF_LAD.'.trucking_co',
			TRN_BOF_LAD.'.last_vessel',
			TRN_BOF_LAD.'.last_vessel_etd',
			TRN_BOF_LAD.'.last_vessel_eta',
			TRN_BOF_LAD.'.last_vessel_etd_date',
			TRN_BOF_LAD.'.last_vessel_eta_date',
			TRN_BOF_LAD.'.feeder_vessel',
			TRN_BOF_LAD.'.feeder_vessel_etd',
			TRN_BOF_LAD.'.feeder_vessel_eta',
			TRN_BOF_LAD.'.feeder_vessel_etd_date',
			TRN_BOF_LAD.'.feeder_vessel_eta_date',
			TRN_BOF_LAD.'.last_snd_vessel',
			TRN_BOF_LAD.'.last_snd_vessel_etd',
			TRN_BOF_LAD.'.last_snd_vessel_eta',
			TRN_BOF_LAD.'.last_snd_vessel_etd_date',
			TRN_BOF_LAD.'.last_snd_vessel_eta_date',
			TRN_BOF_LAD.'.snd_vessel',
			TRN_BOF_LAD.'.snd_vessel_etd',
			TRN_BOF_LAD.'.snd_vessel_eta',
			TRN_BOF_LAD.'.snd_vessel_etd_date',
			TRN_BOF_LAD.'.snd_vessel_eta_date',
		);
		$this->dbx->select($fld);
		$this->dbx->join(TRN_STF_PLN,TRN_BOF_LAD.'.sp_id='.TRN_STF_PLN.'.sp_id','left');
		$this->dbx->join(TRN_STF_PLN_DET,TRN_BOF_LAD.'.detail_id='.TRN_STF_PLN_DET.'.sp_detail_id','left');
		
		$this->dbx->order_by(TRN_STF_PLN_DET.'.num','asc');
		//$q=$this->dbx->get_where(TRN_BOF_LAD,array(TRN_BOF_LAD.'.id_sp'=>$data['data']['id_sp'],TRN_STF_PLN.'.id'=>$data['data']['id_sp'],TRN_STF_PLN_DET.'.id_sp'=>$data['data']['id_sp'],TRN_STF_PLN_DET.'.hide'=>0));
		
		$q=$this->dbx->get_where(TRN_BOF_LAD,array(
			TRN_BOF_LAD.'.sp_id'=>$data['data']['id_sp'],
			TRN_BOF_LAD.'.region_id'=>$data['data']['region_id'],
			TRN_STF_PLN.'.sp_id'=>$data['data']['id_sp'],
			TRN_STF_PLN_DET.'.sp_id'=>$data['data']['id_sp'],
		));
		
		return $q->result_array();
	}
   
   function cek_bill($data) {
		$q = $this->dbx->get_where(TRN_BOF_LAD,array('sp_id'=>$data['id_sp']));
		if($q->num_rows()==0 ) :
			$this->dbx->select('sp_detail_id as id');
			$d=$this->dbx->get_where(TRN_STF_PLN_DET,array('sp_id'=>$data['id_sp']));
			if($d->num_rows()>=1) :
				foreach($d->result() as $row) {
					//$job_no=$this->get_where(array('table'=>TRN_STF_PLN,'fld'=>'kode_ref','cond'=>array('sp_id'=>$data['id_sp'])))->result()[0]->kode_ref;
					$this->dbx->insert(TRN_BOF_LAD,array('sp_id'=>$data['id_sp'],'detail_id'=>$row->id,'region_id'=>REGID));
				}
			endif;
		elseif ($data['form']=='BILL OF LADING') :
			$q = $this->dbx->get_where(TRN_BOF_LAD,array('id_sd'=>$data['id_sd'])); 
			if($q->num_rows()==0 ) :
				$this->dbx->insert(TRN_BOF_LAD,array('id_sp'=>$data['id_sp'],'id_sd'=>$data['id_sd']));
			endif;
		endif;
	}
	
	function get_where($data) {
		$this->dbx->select($data['fld']);
		if(isset($data['sord'])) : 
			$this->dbx->order_by($data['sidx'], $data['sord']);
		endif;
		return $this->dbx->get_where($data['table'],$data['cond']);
	}
	
	function get_data_prepared() {
   	$this->dbx->select(REF_USR.'.user_id as id,'.REF_USR.'.fullname,'.TRN_STF_PLN.'.prepared');
   	$this->dbx->join(TRN_STF_PLN,TRN_STF_PLN.'.prepared='.REF_USR.'.user_id','left');
   	$this->dbx->order_by(REF_USR.'.fullname','asc');
   	$q = $this->dbx->get_where(REF_USR,array(TRN_STF_PLN.'.region_id'=>REGID));
   	return $q;
   }
	
	function get_data_sales() {
   	$this->dbx->select(REF_USR.'.user_id as id,'.REF_USR.'.fullname');
   	$this->dbx->join(REF_USR,REF_USR.'.user_id='.REF_SLS.'.user_id','left');
   	$this->dbx->order_by(REF_USR.'.fullname','asc');
		$q = $this->dbx->get_where(REF_SLS,array(REF_USR.'.region_id'=>REGID));
		return $q;
	}
	
	function insert_data($data) {
		$this->dbx->insert($data['table'],$data['data']);
		//$q=$this->dbx->get_where($data['table'],array('id_sd'=>$data['data']['id_sd']));
		$q=$this->dbx->get_where($data['table'],$data['get']);
		return $q;
	}
	
	function log_activity($opr,$tbl,$data){
		$log = $this->array_to_string($opr,$tbl,$data);
		$this->dbx->insert(ACT_LOG,array(
			'date'=>date('Ymd'),
			'time'=>date('H:i:s'),
			'user'=>$this->session->userdata('user_id'),
			'ip'=>$this->input->ip_address(),
			'region_id'=>$this->session->userdata('region_id'),
			'log'=>$log,
		));
	}
	
	function array_to_string($opr,$tbl,$data) {
		$arr = json_encode($data);
		$arr = str_replace(',', $this->newline(), $arr);
		$arr = str_replace('"', "", $arr);
		$arr = str_replace('{', "", $arr);
		$arr = str_replace('}', "", $arr);
		$tring = '<strong>'.strtoupper($opr).'</strong> TABLE <strong>'.strtoupper($tbl).'</strong> DATA ='.
			$this->newline().strtoupper($arr);
		return $tring;
	}
	
	function newline(){
		return "\n ";
	}
	function update_user($id,$oldpassword,$password) {
   	
		$this->dbx->where('user_id',$id);
		$this->dbx->where('password',md5($oldpassword));
		$this->dbx->update('mstadmin',$password);
		if($this->dbx->affected_rows() > 0){
			return 1;
		} else {
			return 0;
		}
	}
}