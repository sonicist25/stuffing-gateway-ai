<?php class Manifest_model extends CI_Model
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
	
	function update_file_upload($data)
	{
		if($data['oper']=='edit') {
			$nama_file = $this->dbx->get_where('file_upload',array('id'=>$data['id']))->row()->nama_file;
			unlink($_SERVER["DOCUMENT_ROOT"].'/'.$nama_file);
		}
		$this->dbx->where('id',$data['id']);
		$this->dbx->update('y_manifest',array('nama_file'=>$data['nama_file']));	
		////print_r($data);
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
			$this->dbx->insert('y_manifest',$data);
			$this->dbx->select_max('id');
			$data['id'] = $this->dbx->get_where('y_manifest',array('upload_by'=>$this->session->userdata('user_id')))->row()->id;
		}
		elseif($oper=='edit') {
			$this->dbx->update('y_manifest',$data,array('id'=>$data['id']));
		}
		else 
		{
			$nama_file = $this->dbx->get_where('y_manifest',array('id'=>$data['id']))->row()->nama_file;
			$this->dbx->delete('y_manifest',array('id'=>$data['id']));
			$status = $this->dbx->affected_rows();
			if($status > 0 && $nama_file != '' ) {
				unlink($_SERVER["DOCUMENT_ROOT"].'/public/file/mdb/'.$nama_file);
			}
		}
		return array('success'=>$this->dbx->affected_rows(),'oper'=>$oper,'id'=>$data['id']);
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
		return $this->dbx->get('y_manifest')->result();
	}
	function get_data_job()
	{
		$this->dbx->select(array('sp_id','kode_ref'));
		$this->dbx->like('kode_ref','-300');
		$this->dbx->order_by('kode_ref','desc');
		$q2= $this->dbx->get_where('trnstuffingplan',array('region_id'=>$this->session->userdata('region_id')));
		if($q2->num_rows()>0) {
			foreach($q2->result_array() as $row ){
				$i[]= $row;
			}
		}
		return $i;
	}	

}







