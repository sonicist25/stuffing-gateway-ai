<?php
defined('BASEPATH') OR exit('No direct script access allowed');
#[\AllowDynamicProperties]
class MY_Loader extends CI_Loader {
	
	public function database( $params = '', $return = false, $query_builder = null ) {
		
		$ci =& get_instance( );
		
		if ( $return === false && $query_builder === null && isset( $ci->db ) && is_object( $ci->db ) && !empty( $ci->db->conn_id) ) {
    		return false;
		}

		require_once( BASEPATH . 'database/DB.php' );
      
		$db =& DB( $params, $query_builder );
           
		$libraryName = $db->dbdriver.'_driver';
		$className   = ucfirst(strtolower($libraryName));
		$className   = config_item('subclass_prefix').$className;
		
		$file = APPPATH . 'core/' . $className . '.php';
 
 		if ( file_exists( $file ) === true && is_file( $file ) === true ) {
            require_once( $file );
			$dbo = new $className( get_object_vars( $db ) );
            $db = & $dbo;
		}
 
 		if ( $return === true ) {
            return $db;
		}
        
		$ci->db = '';
    	$ci->db = $db;
    	
    	////print_r(array($file,$this));
    	//exit;

		return $this;
	}
    

	/*
	public function dbutil($db = NULL, $return = FALSE)
	{
		$CI =& get_instance();

		if ( ! is_object($db) OR ! ($db instanceof CI_DB))
		{
			class_exists('CI_DB', FALSE) OR $this->database();
			$db =& $CI->db;
		}
		
		$myclass = config_item('subclass_prefix').'db_'.$db->dbdriver;
		require_once(APPPATH.'libraries/'.$myclass);
		
		$class = $myclass;

		if ($return === TRUE)
		{
			return new $class($db);
		}

		$CI->dbutil = new $class($db);
		return $this;
	}
	*/

}
