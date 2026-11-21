<?php
class Mfg_login extends CI_Model
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
	
	function check_user($username, $password)	
	{
		
		$sql = "select * from mstadmin where username = ? and password = ?"; 		
		$query = $this->dbx->query($sql,array($username, $password));
		
		if ($query->num_rows() > 0)		
		{
			return TRUE;		
		}		
		else		
		{			
			return FALSE;		
		}	
	}	
	function getInfoAdmin($username)	
	{
		$this->dbx->select('*');	
		$this->dbx->from('mstadmin');	
		$this->dbx->where('username', $username);	
		return $this->dbx->get()->row();
	}
}
?>