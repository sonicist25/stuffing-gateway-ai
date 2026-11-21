<?php
class Afg_m_schedule extends CI_Model
{	
	var $table='trnexport';
	
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
	
	function tampil_connecting($data,$limit) {
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('trnexportconecting',array('exp_id'=>$data['exp_id']))->result();
	}
	
	function manage_data_connecting($data){
		$id = $data['id'];
		$oper = $data['oper'];
		unset($data['id']);
		unset($data['oper']);
		
		if($oper=='add') {
			$data['connecting_eta'] = $this->fungsi->jin_date_sql($data['connecting_eta']);
			$data['connecting_etd'] = $this->fungsi->jin_date_sql($data['connecting_etd']);
			$data['connecting_vesel'] = strtoupper($data['connecting_vesel']);
			$data['connecting_voyage'] = strtoupper($data['connecting_voyage']);
			$this->dbx->insert('trnexportconecting',$data);
		} elseif($oper=='edit') {
			$data['connecting_eta'] = $this->fungsi->jin_date_sql($data['connecting_eta']);
			$data['connecting_etd'] = $this->fungsi->jin_date_sql($data['connecting_etd']);
			$data['connecting_vesel'] = strtoupper($data['connecting_vesel']);
			$data['connecting_voyage'] = strtoupper($data['connecting_voyage']);
			$this->dbx->update('trnexportconecting',array('id'=>$id),$data);
		} else {
			$this->dbx->delete('trnexportconecting',array('id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper);
	}
	
	
	function get_all_schedule($data,$limit){
		$region = $this->session->userdata('region_id');
		
	//	$this->dbx->select('sp_id,kode_ref,closing_date_time,etd_jkt,vessel,voyage,port_loading,transhipment_port,destination,prepared',false);
		
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
      	if($searchField == 'origin_city_id' || $searchField == 'final_destination_city_id') $searchString = $this->get_city_by_name($searchString);
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
	
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		return $this->dbx->get_where('trnexport',array('region_id'=>$region))->result();
	}
	
	function getDataExport($country_id,$start,$limit,$sidx,$sord,$where){
		$this->dbx->select('*');
    	$this->dbx->limit($limit);
    	if($where != NULL)$this->dbx->where($where,NULL,FALSE);
		$this->dbx->where('final_destination_country_id',$country_id);
    	$query = $this->dbx->get($this->table,$limit,$start);
		
		return $query;
	}
	
	function tampil_data_export_region(){
		$this->dbx->select('*');
		$this->dbx->from('trnexport');
	//	$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnexport.sp_id');
	//	$this->dbx->where('trnstuffingplan.region_id',$this->session->userdata('region_id'));
	//	$this->dbx->where('trnexport.isActive','Yes');
		$this->dbx->where('stf_cls >=',date('Ymd'));
		$this->dbx->where('region_id',$this->session->userdata('region_id'));
		return $this->dbx->get();
	}
	
	function tampil_data_export_semua(){
		$this->dbx->select('*');
		$this->dbx->from('trnexport');
		$this->dbx->where('isActive','Yes');
		return $this->dbx->get();
	}
	
	function tampil_data_export($sp_id){
		$this->dbx->select('*');
		$this->dbx->from('trnexport');
		$this->dbx->where('sp_id',$sp_id);
		$this->dbx->where('isActive','Yes');
		return $this->dbx->get();
	}
	
	function add_data_export($data){
		$this->dbx->insert($this->table,$data);
		if($this->dbx->affected_rows() > 0) {
		/*	$this->dbx->insert('trnstuffingplan',array(
				'sp_id'=>$data['sp_id'],
				'region_id'=>$data['region_id'],
				'prepared'=>$data['created'],
				)
			);
		*/
			return '1';
		} else {
			return '0';	
		}
	}
	
	function add_data_export_new($data){
		$this->dbx->insert('trnexport',$data);
		if($this->dbx->affected_rows() > 0) {
		/*	$this->dbx->insert('trnstuffingplan',array(
				'sp_id'=>$data['sp_id'],
				'region_id'=>$data['region_id'],
				'prepared'=>$data['created'],
				)
			);
		*/
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_export($data,$id){
		$this->dbx->where('export_id',$id);
		$this->dbx->update($this->table,$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_export_edit($data,$id){
		$this->dbx->where('sp_id',$id);
		$this->dbx->update($this->table,$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_export($id,$sp_id){
		$this->dbx->delete($this->table, array('export_id' => $id));
		if($this->dbx->affected_rows() > 0) {
		//	$this->dbx->delete('trnstuffingplan', array('sp_id' => $sp_id));
		//	$this->dbx->delete('trnstuffingplandetail', array('sp_id' => $sp_id));
			$this->dbx->delete('trnexportconecting', array('sp_id' => $sp_id));
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_export_where($sp_id){
		$this->dbx->delete($this->table, array('sp_id' => $sp_id));
		if($this->dbx->affected_rows() > 0) {
		//	$this->dbx->delete('trnstuffingplan', array('sp_id' => $sp_id));
		//	$this->dbx->delete('trnstuffingplandetail', array('sp_id' => $sp_id));
			$this->dbx->delete('trnexportconecting', array('sp_id' => $sp_id));
			return '1';
		} else {
			return '0';	
		}
	}
	
	function get_last_id_export(){
		$this->dbx->select_max('export_id');
		$query = $this->dbx->get('trnexport');
		if ( $query->result() == '')
		{
			return 1;
		}  
		else
		{
			return $query->row()->export_id + 1;
		}
	}
	
	function get_last_id_export_new(){
		$this->dbx->select_max('export_id');
		$query = $this->dbx->get('trnexport');
		if ( $query->result() == '')
		{
			return 1;
		}  
		else
		{
			return $query->row()->export_id;
		}
	}
	
	function get_id_stuffing(){
		$this->dbx->select_max('sp_id');
		$query = $this->dbx->get('trnstuffingplan');
		if ( $query->result() == '')
		{
			return 1;
		}  
		else
		{
			return $query->row()->sp_id + 1;
		}
	}
	
	function get_data_request($sp_id){
		$this->dbx->from('trnshipmentrequest');
		$this->dbx->join('trnexport','trnexport.export_id=trnshipmentrequest.export_id');
		$this->dbx->where('trnexport.sp_id',$sp_id);
		$this->dbx->where('trnshipmentrequest.isProcessed','No');
		$query = $this->dbx->get();
		if ( $query->num_rows() > 0)
		{
			return $query->num_rows();
		}
		else
		{
			return 0;
		}
	}

	function cek_expire($sp_id){
		$this->dbx->select('
			trnstuffingplan.etd_jkt,
			trnstuffingplandetail.kode_ref,
			trnstuffingplandetail.isLock,
			trnstuffingplandetail.shipment_request_id,
		',false);
		$this->dbx->join('trnstuffingplan','trnstuffingplan.kode_ref=trnstuffingplandetail.code','left');
		$this->dbx->where('trnstuffingplan.sp_id',$sp_id);
		$this->dbx->where('trnstuffingplandetail.isLock','0');
		$query = $this->dbx->get('trnstuffingplandetail');
		if($query->num_rows() > 0 ) {
			$date = date('Ymd');
			$etd_jkt = $query->row()->etd_jkt;
			if($date > $etd_jkt) {
				foreach($query->result() as $row){
					$this->dbx->where('kode_ref',$row->kode_ref);
					$this->dbx->update('trnstuffingplandetail',array('isLock'=>'1'));
					$this->dbx->where('shipment_request_id',$row->shipment_request_id);
					$this->dbx->update('trnshipmentrequest',array('lock'=>'1'));
				}
			}
		}
		return $query->num_rows();
	}	
	function get_city_by_id($id){
		$this->dbx->select('*');
		$this->dbx->from('mstcities');
		$this->dbx->where('city_id',$id);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_name;
    	} else {
    		return "";
    	}
	}
	
	function get_city_by_name($name){
		$this->dbx->select('*');
		$this->dbx->from('mstcities');
		$this->dbx->where('city_name',$name);
    	$data = $this->dbx->get();
    	if($data->num_rows() > 0) {
    		$row = $data->row();
    		return $row->city_id;
    	} else {
    		return "";
    	}
	}
}
?>