<?php

class Unit_model extends CI_Model {

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
	
	function get_checked($id){
		$this->dbx->select('user_id,user_unit');
		$query = $this->dbx->get_where('mstadmin', array('user_id'=>$id));
		if($query->num_rows > 0 ) {
			$q = $query->row();
			return $q;
		} else {
			return "";
		}
	}
	
	function get_checked_unit($unit){
		$this->dbx->select('*');
		$this->dbx->from('mstcountries');
		$this->dbx->like('country_id',"+".$unit."+");
		return $this->dbx->get();
	}
	
	function get_checked_menu($menu){
		
	}

	function get_data(){
		$query = $this->dbx->get('mstcountries');
		return $query->result();
	}
	
	function get_countries($id_user){
		$this->dbx->select('*');
		$this->dbx->from('mstcountries');
		$this->dbx->join('mstadmin','mstadmin.user_id=mstcountries.id_user', 'left');
		$this->dbx->where('id_user', $id_user);
		$this->dbx->or_where('activ', '');
		return $this->dbx->get();
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
	}//end of simpan

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
	}//end of simpan

	function delete($id) {
		$this->dbx->delete('refagentcompany', array('agent_company_id' => $id));		
	}//end of simpan
	
	function getListCountry() {
		$this->dbx->select ('agent_company_id,agent_company_code', FALSE);
		return $this->dbx->get('refagentcompany');
	}

}