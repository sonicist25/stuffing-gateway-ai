<?php
class Rebate_coloader_model extends CI_Model
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
	
	function table_detail()
	{
		$query = $this->dbx->get('x_rebate_detail');
		return $query;
	}
	
	function table_detail_where($ref_no)
	{
		$query = $this->dbx->get_where('x_rebate_detail',array('ref_no'=>$ref_no));
		return $query;
	}
	
	function table_detail_where_order($ref_no, $sidx, $sord)
	{
		$this->dbx->order_by($sidx, $sord);
		$query = $this->dbx->get_where('x_rebate_detail',array('ref_no'=>$ref_no));
		return $query;
	}
	
	function table_browse()
	{
		$query = $this->dbx->get('x_rebate');
		return $query;
	}
	
	function lastRebate()
	{
		$sql = "SELECT * FROM `x_rebate` where id !='0'  order by id desc limit 1"; 		
		$query = $this->dbx->query($sql);	
		if($query->num_rows()>0) 
		{		
			return $query->row();
		}
		else 
		{
			$query = new stdClass();	
			$query->id = '';	
			$query->ref_no = '';	
			$query->date = '';	
			$query->code_shipco = '';	
			return $query;
		}
	}
	
	function pilihRebate($id)
	{
		$sql = "SELECT * FROM `x_rebate` where id = ? and id !='0'"; 		
		$query = $this->dbx->query($sql,array($id));	
		return $query->row();
	}
	
	function nextRebate($id)
	{
		$sql = "SELECT * FROM `x_rebate` where id > ? order by id asc limit 1"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `x_rebate` where id = ? order by id asc limit 1"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
	
	function prevRebate($id)
	{
		$sql = "SELECT * FROM `x_rebate` where id < ? order by id desc limit 1"; 		
		$query = $this->dbx->query($sql,array($id));	
		if($query->num_rows() > 0 ) {
			return $query->row();
		} else {
			$sql = "SELECT * FROM `x_rebate` where id = ? order by id desc limit 1"; 		
			$query = $this->dbx->query($sql,array($id));
			return $query->row();
		}
	}
	
	function add_data($data)
	{
		$this->dbx->insert('x_rebate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data($data,$id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->update('x_rebate',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data($id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->delete('x_rebate');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function add_data_detail($data)
	{
		$this->dbx->insert('x_rebate_detail',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update_data_detail($data,$id)
	{
		$this->dbx->where('id_detail',$id);
		$this->dbx->update('x_rebate_detail',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete_data_detail($id)
	{
		$this->dbx->where('id_detail',$id);
		$this->dbx->delete('x_rebate_detail');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
}


