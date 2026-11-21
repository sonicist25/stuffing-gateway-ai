<?php

class Region_model extends CI_Model {

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
	function get_all_order(){		
		$this->dbx->select('status');
		$this->dbx->from('trnstuffingplan_booking');     
		$this->dbx->where('status','finalize'); 				            
		$this->dbx->where('region_id',$this->session->userdata('region_id')); 				            
		$q =$this->dbx->get();
		return $q->num_rows();
		
	}
	function get_all_quot(){		
		$this->dbx->select('status');
		$this->dbx->from('trnstuffingplan_booking');     
		$this->dbx->where('status','prepared'); 	
        $this->dbx->where('region_id',$this->session->userdata('region_id')); 		
		$q =$this->dbx->get();
		return $q->num_rows();
		
	}
	function get_data(){
		$query = $this->dbx->get('refregion');
		return $query->result();
	}
	
	function edit($id){
		$query = $this->dbx->get_where('refagentcompany',array('agent_company_id' => $id));
		return $query->row_array();		

	}
	
	function update(){
		$id  = $this->input->post('id');
		$code  = $this->input->post('code');
		$name  = $this->input->post('name');
		$city  = $this->input->post('city');
		$address  = $this->input->post('address');
		$remark  = $this->input->post('remark');
			$data = array(
				'agent_company_code'=>$code, 
				'agent_company_name'=>$name,
				'agent_company_city'=>$city,
				'agent_company_address'=>$address,
				'agent_company_remark'=>$remark   
			);
		$this->dbx->where('agent_company_id', $id);	
		$this->dbx->update('refagentcompany',$data);    
	}

	function add(){
		$code  = $this->input->post('code');
		$name  = $this->input->post('name');
		$city  = $this->input->post('city');
		$address  = $this->input->post('address');
		$remark  = $this->input->post('remark');
			$data = array(
				'agent_company_code'=>$code, 
				'agent_company_name'=>$name,
				'agent_company_city'=>$city, 
				'agent_company_address'=>$address, 
				'agent_company_remark'=>$remark
				);
				$this->dbx->insert('refagentcompany',$data);     
	}
	
	function delete($id) {
		$this->dbx->delete('refagentcompany', array('agent_company_id' => $id));		
	}
	
	function getListAgent() {
		$this->dbx->select ('agent_company_id,agent_company_code',FALSE);
		return $this->dbx->get('refagentcompany');
	}

}