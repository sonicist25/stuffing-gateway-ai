<?php
class Rate_guidance_model extends CI_Model
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
	
	function get_rate_guidance_destination($data,$limit){
		$region = $this->session->userdata('region_id');
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where('pricerateguidance_destination',array('rate_id'=>$data['rate_id']));
		return $q->result();
	}
	
	function manage_data_destination($data){
		$oper = $data['oper'];
		$id	= $data['id']; 
		unset($data['oper']);
		unset($data['id']);
		if($oper=='add') {
			$data['rate']=str_replace(' ','',$data['rate']);
			$this->dbx->insert('pricerateguidance_destination',$data);	
		} elseif($oper=='edit'){
			$data['rate']=str_replace(' ','',$data['rate']);
			$this->dbx->update('pricerateguidance_destination',$data,array('id'=>$id));
		} else {
			$this->dbx->delete('pricerateguidance_destination',array('id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper);
	}
	
	
	function get_rate_guidance($data,$limit){
		$region = $this->session->userdata('region_id');
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where('pricerateguidance',array('region_id'=>$region));
		return $q->result();
	}
	
	function manage_data($data){
		$oper = $data['oper'];
		$id	= $data['id']; 
		unset($data['oper']);
		unset($data['id']);
		if($oper=='add') {
			$data['name']=strtoupper($data['name']);
			$data['user']=$this->session->userdata('user_id');
			$data['region_id']=$this->session->userdata('region_id');
			$this->dbx->insert('pricerateguidance',$data);	
		} elseif($oper=='edit'){
			$data['name']=strtoupper($data['name']);
			$data['user']=$this->session->userdata('user_id');
			$data['region_id']=$this->session->userdata('region_id');
			$this->dbx->update('pricerateguidance',$data,array('id'=>$id));
		} else {
			$this->dbx->delete('pricerateguidance',array('id'=>$id));
		}
		return array('status'=>$this->dbx->affected_rows(),'mode'=>$oper);
	}
	
	function rate_parent($id){
		$query = $this->dbx->get_where('pricerateguidance',array('id' => $id));
		return $query->row_array();		
	}
	
	function edit($id){
		$query = $this->dbx->get_where('pricerateguidance_destination',array('rate_id' => $id));
		return $query->result();		
	}
	
	function getListCitiesId()
	{
		$this->dbx->select (' city_id,city_name',FALSE);
		return $this->dbx->get('mstCities');
	}
}


