<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Odbc_model extends CI_Model {	
	public function __construct() {
    parent::__construct();
		$db = odbc_db(array(
    		'dsn'=>$this->session->userdata('odbc_dsn'),
    		'driver'=>$this->session->userdata('odbc_driver'),
    		'hostname'=>'',
    		'user'=>'',
    		'password'=>'admin',
    	));
    	$this->mdb = $this->load->database($db,TRUE);		
	}
	
	function get_detail($no) {
		$q = $this->mdb->query("SELECT nopos,nobl,mot_vessel,tglbl,pasalcd,pmuatcd,pbongkarcd,pakhircd,snm,sna,cnm,cna,nnm,nna,mark,bruto FROM tbldetail WHERE nodaftar = '" . $no . "'")->result_object();  			
		if($q) : return $q[0]; else : return ''; endif;			
	}
	
	function get_query($data) {
		return $this->mdb->query($data);
	}
	
	function get_cont_seal($no) {
		$q = $this->mdb->query("SELECT nocountener,seal,tipe,ukuran FROM tblcontainer WHERE nodaftar = '" . $no . "'")->result_object();  			
		if($q) : return $q[0]; else : return ''; endif;
	}
	
	function get_data_header($val) {
		$q = $this->mdb->query("SELECT * FROM tblheader WHERE no_master = '".$val."'")->result_object();
		if($q) : return $q[0]; else : return ''; endif;
	}
	
	function get_nmbl() {
		$q = $this->mdb->query('SELECT no_master FROM tblheader')->result_array();
		foreach($q as $val) {
			$data[$val['no_master']] = $val['no_master'];
		}
		unset($data[' ']);
		unset($data['-']);
		sort($data);
		return $data;

	}
	
	function get_tables() {
		$data = $this->mdb->liat_tabel();
		return $data;
	}
  
	function get_fields($tbl) {
		$data = $this->mdb->query('SELECT * FROM ' . $tbl )->list_fields();
		return $data;
	}
	
	function get_num_rows($q) {
  		return $q->num_fields();
	}
	
	function check_db() {
		return array(
			'mysql_db'=>$this->dbx->database,
			'odbc_db'=>$this->mdb->database
		);
	}
  
  /*
  
  function inject_file($header,$detail) {
  		$this->dbx->insert(TRN_STF_PLN,$header);
  		foreach($detail as $row) {
			$this->dbx->insert(TRN_STF_PLN_DET,$row);
		}
  		return $this->dbx->affected_rows();
  }
  
  function get_num_rows($q) {
  		return $q->num_fields();
  }
  
  function get_query($data) {
		return $this->odbc->query($data);  			
  }
  
  function get_where($data) {
		return $this->odbc->query("SELECT ".$data[0][0]." FROM ".$data[1]." WHERE ".$data[2]." = '".$data[3]."'")->result_object();  			
  }
  
  function get_header_import($data) {
  		return $this->odbc->query("SELECT * FROM tblheader WHERE no_master = '".$data['no_master']."'")->result_object();
  }
  
  function get_table_column($data) {
  		$q = $this->odbc->query("SELECT * FROM ".$data['table']);
  		$n = $q->num_fields();
		for ($i=1; $i<=$n; $i++) 
      {
     		$colm[] = $q->list_fields();
      }
      
      foreach($q->result_object() as $row) {
      	$rows[] = $row;
      }
      
      return array('field'=>$n,'colm'=>$colm,'rows'=>$rows);
	}
  
	
	
	function show_tables($tbl) {
		if($tbl=='odbc') : $tables=$this->odbc->liat_table(); 
		else : $tables=$this->dbx->list_tables();
		endif;
		return array(
			'tables'=>$tables,
		);
	}
   
 	
 	function get_tables() {
		$data = $this->odbc->liat_tabel();
		$dt[''] = '';
		foreach($data as $val) {
			$dt[$val]=strtoupper($val);
		}
		return $dt;
	}
   
	function get_nomer_master() {
		$data = $this->odbc->query('SELECT no_master from TBLHEADER');
		//print_r($data); die();
		foreach($data as $val) {
			$dt[$val] = strtoupper($val);
		}
		return $dt;
		
		/*
		////print_r($data);die();
		//return $data;
		for($i=0; $i < count($data); $i++) {
			if($data[$i]['no_master']==' ' || $data[$i]['no_master']=='-') unset($data[$i]); 
		}
		$dt[''] = '';
		foreach($data as $val) {
			$dt[$val['no_master']]=$val['no_master'];
		}
		return $dt;
		
	}
	
	
	function odbc_str($str) {
		$out="";
		for($a=0; $a<strlen($str); $a++) {
			if($str[$a]=="'") {
				$out.="''";
			} else if($str[$a]!=chr(10)) {
					$out.=$str[$a];
			}
		}
		return $out;
	}
	*/
}