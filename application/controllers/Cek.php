<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cek extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url'));
		$this->load->model(array('m_bill_of_ladding'));
	}

	function index()
	{
		$id = 17;
		$data = $this->m_bill_of_ladding->get_data_agent($id);
		if($data->num_rows() > 0) {
			$row = $data->row_array();
			$agent_remark = $row['agent_company_remark'];
			$agent = $row['agent_company_name'].' '.$row['agent_company_address'];
			$special_instruction = '';		
		}
		echo 
		'Remarks : '.$agent_remark. br(2).
		'Agent :'.$agent. br();
	}
	
	function uniq()
	{
		//$q1 = $this->dbx->query("select * from 'refportdestination_1' order_by 'portdestination_code' asc");

		$this->dbx->select('*');
		$this->dbx->from('refportdestination_1');
		$this->dbx->LIKE('portdestination_code','A');
		$this->dbx->order_by('portdestination_code', 'asc');
		$q1 = $this->dbx->get();

		$i= 1;
		foreach($q1->result() as $row) {
			echo $i.' '.$row->portdestination_code. br();
			$i++;			
		}
		
	//	$this->dbx->query("ALTER TABLE `refportdestination_1` ADD UNIQUE( `portdestination_code`);");
	}
	
	function date_java(){
		$this->load->view('cek');
	}
}




