<?php
class Afg_m_table_stuffing extends CI_Model
{	
	var $table='mstcities';
	
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
   
   function get_data_detail_by_sp_id($des,$agent,$data)
   {
   	$this->dbx->select('trnstuffingplandetail.shipper_print,trnstuffingplandetail.bl_meas');
   	$this->dbx->join('trnstuffingplan', 'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id', 'inner');
   	$q=$this->dbx->get_where('trnstuffingplandetail',
   		array(
   			'trnstuffingplan.destination'=>$des,
   			'trnstuffingplan.agen_penagihan'=>$agent,
   			'trnstuffingplan.stuffing_date >='=>$data['w'],
   			'trnstuffingplan.stuffing_date <='=>$data['n'],
   			'trnstuffingplan.region_id'=>$this->session->userdata("region_id"),
   		)
   	);
   	return $q->result_array();
   }
   
   function get_data_sp_id($des,$agent,$data)
   {
   	$this->dbx->select('sp_id');
   	$this->dbx->order_by('destination','ASC');
   	$q=$this->dbx->get_where('trnstuffingplan',
   		array(
   			'destination'=>$des,
   			'agen_penagihan'=>$agent,
   			'stuffing_date >='=>$data['w'],
   			'stuffing_date <='=>$data['n'],
   			'region_id'=>$this->session->userdata("region_id"),
   		)
   	);
   	return $q->result_array();
   }
   
   function get_data_test($data)
   {
   	$this->dbx->select('destination,sp_id,kode_ref,agen_penagihan');
   	$this->dbx->like('kode_ref','-100');
   	$this->dbx->order_by('destination','ASC');
   	$q=$this->dbx->get_where('trnstuffingplan',
   		array(
   			'stuffing_date >='=>$data['w'],
   			'stuffing_date <='=>$data['n'],
   			'region_id'=>$this->session->userdata("region_id"),
   		)
   	);
   	return $q;
   }
   
   function get_data_test_detail($id) {
   	$this->dbx->select('shipper,bl_meas');
   	$this->dbx->order_by('sp_id','ASC');
   	$q=$this->dbx->get_where('trnstuffingplandetail',
   				array(
   					'sp_id'=>$id,
   				)
   	);
   	return $q->result();
   }
   
   
   function get_data($data)
   {
   	$this->dbx->select('destination,sp_id,kode_ref');
   	$this->dbx->like('kode_ref','-100');
   	$this->dbx->order_by('destination','ASC');
   	$q=$this->dbx->get_where('trnstuffingplan',
   		array(
   			'stuffing_date >='=>$data['w'],
   			'stuffing_date <='=>$data['n'],
   			'region_id'=>$this->session->userdata("region_id"),
   		)
   	);
   	if($q->num_rows()>0) 
   	{
   		foreach ($q->result() as $row)
   		{
   			$this->dbx->select('sp_id,sp_detail_id,shipper,bl_meas,agent_code');
   			$this->dbx->order_by('sp_id','ASC');
   			$qq[]=$this->dbx->get_where('trnstuffingplandetail',
   				array(
   					'sp_id'=>$row->sp_id,
   				)
   			)->result();
   		}
   		
   		return array($q->result(),$qq);
   		
   	}
   	else 
   	{
   		return;
   	}
   }
   
   function get_data_detail($id) {
   	$this->dbx->select('sp_id,shipper,bl_meas,agent_code');
   	$this->dbx->order_by('sp_id','ASC');
   	$q=$this->dbx->get_where('trnstuffingplandetail',
   				array(
   					'sp_id'=>$id,
   				)
   	);
   	if($q->num_rows()>0) 
   	{
   		return $q->result();
   	}
   	else 
   	{
   		return;
   	}
   }
     
}