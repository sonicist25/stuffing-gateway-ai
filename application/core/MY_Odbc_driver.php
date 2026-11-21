<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once(BASEPATH.'database/DB_driver.php');
require_once(BASEPATH.'database/DB_result.php');
require_once(BASEPATH.'database/drivers/odbc/odbc_driver.php');
require_once(BASEPATH.'database/drivers/odbc/odbc_result.php');

class MY_Odbc_driver extends CI_DB_odbc_driver {
	
	public function liat_tabel() {
		$tbl = odbc_tables($this->conn_id);
		while (odbc_fetch_row($tbl))
		{
			if (odbc_result($tbl,"TABLE_TYPE")=="TABLE") 
    		{
        			$tables[] = odbc_result($tbl,"TABLE_NAME");
    		}
		}
		return $tables;
	}
	
}