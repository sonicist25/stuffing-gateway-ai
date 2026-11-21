<?php
class Account_model extends CI_Model
{
  public $dbx;
  public $dby;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dby = $this->load->database('reference', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dby = $this->load->database('reference', TRUE);
        }
    }
	
	function getDatarefAccount()
	{
		$q = $this->dbx->query("SELECT * FROM `ref_account` ORDER BY `ref_account`.`id` DESC");
		return $q;
	}
	
	function insert_account($tax_id,$account_name,$account_address,$blok,$nomor,$rt,$rw,$kecamatan,$kelurahan,$kabupaten,$propinsi,$kodepos,$account_phone,$account_email,$account_cp)
	{
		$this->dbx->insert('ref_account', array('tax_id'=>$tax_id,'account_name'=>$account_name,'account_address'=>$account_address,'blok'=>$blok,'nomor'=>$nomor,'rt'=>$rt,'rw'=>$rw,'kecamatan'=>$kecamatan,'kelurahan'=>$kelurahan,'kabupaten'=>$kabupaten,'propinsi'=>$propinsi,'kodepos'=>$kodepos,'account_phone'=>$account_phone,'account_email'=>$account_email,'account_cp'=>$account_cp,'region_id'=>$this->session->userdata('region_id'), 'created_by'=>$this->session->userdata('fullname'),'created_at'=>date('Y-m-d H:i:s')));
		if($this->dbx->affected_rows() > 0) {
			return '1';			
		} else {
			return '0';	
		}
	}
	
	function update_account($tax_id,$account_name,$account_address,$blok,$nomor,$rt,$rw,$kecamatan,$kelurahan,$kabupaten,$propinsi,$kodepos,$account_phone,$account_email,$account_cp, $id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->update('ref_account', array('tax_id'=>$tax_id,'account_name'=>$account_name,'account_address'=>$account_address,'blok'=>$blok,'nomor'=>$nomor,'rt'=>$rt,'rw'=>$rw,'kecamatan'=>$kecamatan,'kelurahan'=>$kelurahan,'kabupaten'=>$kabupaten,'propinsi'=>$propinsi,'kodepos'=>$kodepos,'account_phone'=>$account_phone,'account_email'=>$account_email,'account_cp'=>$account_cp,'region_id'=>$this->session->userdata('region_id'), 'created_by'=>$this->session->userdata('fullname'),'created_at'=>date('Y-m-d H:i:s')));
		if($this->dbx->affected_rows() > 0) {
			return '1';			
		} else {
			return '0';	
		}
	}
	
	function delete_account($id)
	{
		$this->dbx->where('id',$id);
		$this->dbx->delete('ref_account');
	}
	
}	
