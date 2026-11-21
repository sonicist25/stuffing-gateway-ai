<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
#[\AllowDynamicProperties]
class Databases extends CI_Controller {
	
	public function __construct() 
	{  
		parent::__construct();
		//$this->fungsi->restrict();
		//$this->load->model(array('m_head_office','m_user'));
   }
   
   var $title = 'DATABASES';
   
   function vfp_db()
   {
   	$dbfname = "C:/GCL/nomor.DBF";
   	$fdbf = fopen($dbfname,'r');
   	
   	$fields = array(); 
    	$buf = fread($fdbf,32); 
    	$header = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8)); 
    	//echo 'Header: '. json_encode($header).'<br/>'; 
    	$headernya[] = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
    	
    	$goon = true; 
    	$unpackString=''; 
    	while ($goon && !feof($fdbf)) 
    	{ // read fields: 
        $buf = fread($fdbf,32); 
        if (substr($buf,0,1)==chr(13)) 
        {
        		$goon=false;
        } // end of field list 
        else 
        { 
           $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18)); 
           //echo 'Field: '. json_encode($field).'<br/>'; 
           $fieldnya[] = unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
           
           $unpackString.="A$field[fieldlen]$field[fieldname]/"; 
           array_push($fields, $field);
         }
      } 
    	fseek($fdbf, $header['FirstRecord']+1); // move back to the start of the first record (after the field definitions) 
    	for ($i=1; $i<=$header['RecordCount']; $i++) 
    	{ 
        $buf = fread($fdbf,$header['RecordLength']); 
        $record=unpack($unpackString,$buf); 
        $recordnya[] = unpack($unpackString,$buf); 
        //echo 'record: '. json_encode($record).'<br/>'; 
        //echo $i.$buf.'<br/>';
        
        
        
     	} //raw record 
     	
     	////print_r(array('header'=>$headernya,'filed'=>$fieldnya,'record'=>$recordnya));
     	
     	$i=0;
     	foreach( $fieldnya as $row )
     	{
     		$c[]=$row['fieldname'];
     		
     		$i++;
     	}
     	
     	$data = new stdClass();
		$data->table = $this->input->post('table');
		$data->tbl = $this->input->post('tbl');
		$data->Models = $c;
		$data->Rows = $recordnya;
		echo json_encode($data);
     	
    	fclose($fdbf);
   	
   	/*
   	include(APPPATH.'/third_party/adodb5/adodb.inc.php'); 
   	
   	$db = ADONewConnection('vfp');
		//$dsn="Driver={Microsoft Visual FoxPro Driver};SourceType=DBF;SourceDB=\\\\othercomputer\data\provider.dbf;Exclusive=No;";
		$dsn="Driver={Microsoft dBase Driver (*.dbf)};SourceType=DBF;SourceDB=C:\GCL\lokasi.dbf;Exclusive=No;";
		$db->Connect($dsn) or die('nope');
		$db->SetFetchMode(ADODB_FETCH_ASSOC);
		$query = "Select * from lokasi where 1 = 'X9999'";
		$rs = $db->Execute($query);
		//print_r($rs);
		/*
		while (!$rs->EOF) {
    		//print_r($rs->fields);
    		$rs->MoveNext();
		}
		
		/*
		$conn = new COM("ADODB.Connection");
		$conn->Open('Provider=VFPOLEDB.1;Data Source="c:\wwwroot\trans\trans.DBC";Collating Sequence=HUNGARY;');
		// SQL statement to build recordset.
		$rs = $conn->Execute("SELECT * FROM courier ORDER BY Name");
		echo "<p>List of couriers:</p><hr>";
		// Display all the values in the records set
		while (!$rs->EOF) { 
    		$fv = $rs->Fields("Name");
    		echo $fv->value."<br>\n";
    		$rs->MoveNext();
		} 
		$rs->Close();
		*/
   }
 
 	function index()
 	{
 		$data = array(
			'title'=> $this->title,
			'menu_navigation'=>'navigation',
			'level'=>$this->session->userdata('level_id'),
			'main_view'=> 'databases/v_databases',
		);											
		$this->load->view('afg_template', $data);
 	}
 	
 	function test()
 	{
 		$data = array(
			'title'=> $this->title,
			'menu_navigation'=>'navigation',
			'level'=>$this->session->userdata('level_id'),
			'main_view'=> 'mssql/test',
		);											
		$this->load->view('templates', $data);
 	}
 	
 	function get_connection()
 	{
 		$file  = realpath($_SERVER["DOCUMENT_ROOT"]).'\public\file\tmp_db\\'.$this->session->userdata('file');
 		$ext	 = $this->session->userdata('ext');
 		$uid   = $this->session->userdata('uid');
 		$pwd   = $this->session->userdata('pwd');
 		
 		switch ($ext){
  			case 'mdb': $dsn = 'DRIVER={Microsoft Access Driver (*.mdb)};DBQ='.$file.''; break;
  			//case 'dbf': $dsn = 'DRIVER={Microsoft dBase Driver (*.dbf)};;SourceType=DBF;SourceDB='.$file.''; break;
  			default:
    		return;
		}
		if(odbc_connect($dsn,$uid,$pwd)) { 
 			return odbc_connect($dsn,$uid,$pwd); 
 		} else { 
 			//print_r(odbc_error()); 
 			return false; 
 		}
 	}
 	
 	function connect_db() 
 	{
 		////print_r(array('con'=>$con,'uid'=>$this->session->userdata('uid'),'pwd'=>$this->session->userdata('pwd'),'ext'=>$this->session->userdata('ext')));

 		$con = $this->get_connection();
 		$ext	 = $this->session->userdata('ext');
 		//if($ext=="mdb") 
 		//{
			$tbl = odbc_tables($con);
			while (odbc_fetch_row($tbl))
			{
				if (odbc_result($tbl,"TABLE_TYPE")=="TABLE") 
    			{
        			$table[] = odbc_result($tbl,"TABLE_NAME");
    			}
			}
			$data['option'][''] = '';
			foreach($table as $row)
			{
				$data['option'][$row] = $row;
			}
			$dropdown =  form_dropdown('table',$data['option'],'','id="table" onclick="table_change()" onchange="tabel_column()"style="max-width: 120px; min-width: 120px"');
			echo json_encode(array("db"=>"mdb","dropdown"=>$dropdown));
		//}
		//else 
		//{
		//	echo json_encode(array("db"=>"dbf","dropdown"=>""));
		//}
	}
 	
 	function table_column()
 	{
 		if($this->input->post('table')=="mbd") {
 			$column=array();
 			$rows=array();
 			$con = $this->get_connection();
 			$query = odbc_exec($con,'select * from `'.$this->input->post('table').'`;');
			
			$field = odbc_num_fields($query);
      	for ($n=1; $n<=$field; $n++) 
      	{
     			$column[] = odbc_field_name($query, $n);
      	}

			$i=0;
			while($row = odbc_fetch_object($query)) {
				$rows[] = $row;
				$i++;
			}
			$headernya = array();
		}
		else 
		{
			//$dbfname = "C:/GCL/nomor.DBF";
			$dbfname = realpath($_SERVER["DOCUMENT_ROOT"]).'\public\file\tmp_db\\'.$this->session->userdata('file');
   		$fdbf = fopen($dbfname,'r');
   	
   		$fields = array(); 
    		$buf = fread($fdbf,32); 
    		$header = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8)); 
    		//echo 'Header: '. json_encode($header).'<br/>'; 
    		$headernya[] = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
    	
    		$goon = true; 
    		$unpackString=''; 
    		while ($goon && !feof($fdbf)) 
    		{ // read fields: 
        		$buf = fread($fdbf,32); 
        		if (substr($buf,0,1)==chr(13)) 
        		{
        			$goon=false;
        		} // end of field list 
        		else 
        		{ 
           		$field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18)); 
           		//echo 'Field: '. json_encode($field).'<br/>'; 
           		$fieldnya[] = unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
           		$unpackString.="A$field[fieldlen]$field[fieldname]/"; 
           		array_push($fields, $field);
         	}
      	} 
    		fseek($fdbf, $header['FirstRecord']+1); 
    		$rows = array();
    		if($header['RecordCount']>0) {
    			for ($i=1; $i<=$header['RecordCount']; $i++) 
    			{ 
        			$buf = fread($fdbf,$header['RecordLength']); 
        			$record=unpack($unpackString,$buf); 
        			$rows[] = unpack($unpackString,$buf); 
      		} 
     		}
     		
     		$i=0;
     		foreach( $fieldnya as $row )
     		{
     			$column[]=$row['fieldname'];
     			$i++;
     		}
		}	
			
		$data = new stdClass();
		$data->Header = $headernya;
		$data->table = $this->input->post('table');
		$data->tbl = $this->input->post('tbl');
		$data->Models = $column;
		$data->Rows = $rows;
		echo json_encode($data);

 	}
 	
 	function get_absolute_path($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
    
   function info()
 	{
 		//print_r(get_extension_funcs("odbc"));
 		//phpinfo();
 	}
 	
 	function lock_sc () {
		$db=$this->dbx->database;
		$this->session->set_userdata(array('username'=>'lock_sc','region_id'=>'Jakarta'));
		if($db==date('Y')) {
			$db=$this->db;
		}
	}
 	
 	function ajaxFileUpload() 
 	{
		$error = "";
		$msg = "";
		$fileElementName = 'nama_file';
		if(!empty($_FILES[$fileElementName]['error']))
		{
			switch($_FILES[$fileElementName]['error'])
			{
				case '1':
					$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
					break;
				case '2':
					$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
					break;
				case '3':
					$error = 'The uploaded file was only partially uploaded';
					break;
				case '4':
					$error = 'No file was uploaded.';
					break;

				case '6':
					$error = 'Missing a temporary folder';
					break;
				case '7':
					$error = 'Failed to write file to disk';
					break;
				case '8':
					$error = 'File upload stopped by extension';
					break;
				case '999':
					default:
					$error = 'No error code avaiable';
			}
		} elseif(empty($_FILES['nama_file']['tmp_name']) || $_FILES['nama_file']['tmp_name'] == 'none') {
			$error = 'No file was uploaded..';
		} else {
				$msg .= " File Name: " . $_FILES['nama_file']['name'] . ", ";
				$msg .= " File Size: " . @filesize($_FILES['nama_file']['tmp_name']);
			
				$uploads_dir = $_SERVER["DOCUMENT_ROOT"]."/public/file/tmp_db";
				$tmp_name = $_FILES["nama_file"]["tmp_name"];
        		//$name = md5(time().$_FILES["nama_file"]["name"]);
        		$name=strtolower($_FILES["nama_file"]["name"]);
        		if(move_uploaded_file($tmp_name, "$uploads_dir/$name")){
					$this->session->set_userdata(array(
						'uid' => $this->input->post('uid'),
						'pwd' => $this->input->post('pwd'),
						'file'=> $name,
						'ext' => strtolower($this->input->post('ext')),
					));
				}
		}		
		echo "{";
		echo				"error: '" . $error . "',\n";
		echo				"msg: '" . $msg . "'\n";
		echo "}";
	}

}

/* end controler */