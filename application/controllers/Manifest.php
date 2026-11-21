<?php
class Manifest extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->library('excel');
		$this->load->model('manifest_model');
		$this->fungsi->dbx = $this->manifest_model->dbx;
		$this->dbx = $this->manifest_model->dbx;
	}
	
	function get_file() {
		$this->load->helper('directory');
		$this->load->helper('file');
		$files = directory_map(APPPATH.'third_party/file/xls');
		if(empty($files)) :
			echo json_encode(array('status'=>0));
			return;
		else :
			if(count($files)!=2):
				delete_files(APPPATH.'third_party/file/xls');
				echo json_encode(array('status'=>0));
				return;
			else :
				sort($files);
				$string = read_file(APPPATH.'third_party/file/xls/0000.txt');
				$text = explode(';',$string);
				$file = $text[0];
				$name = $text[1];
				$date = $text[2];
				$time = $text[3];
				//$this->get_nomer_master($file);
			endif;
		endif;
		
		echo json_encode(
			array(
				'status'=>1,
				'file'=>$file,
				'name'=>$name,
				'date'=>$date,
				'time'=>$time,
			)
		);
	}
	
	function get_data_detail() 
	{
		//$path = realpath($_SERVER["DOCUMENT_ROOT"])."/public/file/xls/";
		$path = APPPATH.'third_party/file/xls/';
		if (isset($_GET['file'])) {
            
			$import_xls_file = $_GET['file'];
            $inputFileName = $path . $import_xls_file;
            
			try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
		
			$objPHPExcel->setActiveSheetIndexByName('Detil');
			$Detil = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
			unset($Detil[1]);
			foreach($Detil as $row) {
				$NoPos[] = $row['E'];
				$NoBL[]= $row['J'];
				$Mother_Vesel[]=$row['L'];
				$TglHBL[]= $row['K'];
				$Pelabuhan_Asal[]=$row['X'];
				$Sebelumnya[]=$row['Y'];
				$Bongkar[]=$row['Z'];
				$Akhir[]=$row['AA'];
				$Shipper[]=$row['R'];
				$Shipper_Adderess[]=$row['S'];
				$Consignee[]=$row['N'];
				$Consignee_Adderess[]=$row['O'];
				$Notify[]=$row['U'];
				$Notify_Adderess[]=$row['V'];
				$Mark[]=$row['AD'];
				$Bruto[]=$row['AF'];
				$Volume[]=$row['AG'];
				$Jumlah_Kemasan[]=$row['AB'];
				$Satuan[]=$row['AC'];
				$MasterBL[]=$row['H'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('Kontainer');
			$Kontainer = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Kontainer[1]);
			foreach($Kontainer as $row) {
				$Count_Qty[]= $row['F'];
				$No_Count[]= $row['D'];
				$Size[]= $row['E'];
				$Type[]= $row['G'];
				$Seal[]= $row['H'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('Barang');
			$Barang = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Barang[1]);
		
			foreach($Barang as $row ) {
				$Desc_Goods[] = array('id'=>$row['B'],'desc'=>$row['E']);
			}
			
			foreach ($Desc_Goods as $entry ) {
			
				foreach ($entry as $key => $value) {
					$result[$entry['id']][] = $value;
				}
			
				foreach ($result as $value) {
					$results[$entry['id']] = array_unique($value);
				}
			
				foreach ($results as $value) {
					unset($value[0]);
					$resultss[$entry['id']] = $value;
				}
			
			}
		
		
			foreach ($resultss as $key => $value) {
			
				$val = array_merge($value);
				$valu = implode(',',$val);
				$desc[] = str_replace(',',new_line(),$valu);
			
			}
		
			////print_r($desc); die();
		
			for($i=0;$i<count($NoPos);$i++) {
			
				$all[$i] = array(
					'NoPos'=>$NoPos,
					'NoBL'=>$NoBL,
					'TglBL'=>$TglHBL,
					'Mother_Vesel'=>$Mother_Vesel,
					'Pelabuhan_Asal'=>$Pelabuhan_Asal,
					'Sebelumnya'=>$Sebelumnya,
					'Bongkar'=>$Bongkar,
					'Akhir'=>$Akhir,
					'Shipper'=>$Shipper,
					'Shipper_Adderess'=>$Shipper_Adderess,
					'Consignee'=>$Consignee,
					'Consignee_Adderess'=>$Consignee_Adderess,
					'Notify'=>$Notify,
					'Notify_Adderess'=>$Notify_Adderess,
					'Mark'=>$Mark,
					'Bruto'=>$Bruto,
					'Volume'=>$Volume,
					'Jumlah_Kemasan'=>$Jumlah_Kemasan,
					'Satuan'=>$Satuan,
					'Count_Qty'=>$Count_Qty,
					'No_Count'=>$No_Count,
					'Size'=>$Size,
					'Type'=>$Type,
					'Seal'=>$Seal,
					'Desc_Goods'=>$desc,
					'MasterBL'=>$MasterBL,
				);
			}
		
			$data = new stdClass();
			$i=0;
			
			foreach($all as $row) {
				$data->rows[$i]['id']= $i;
				$data->rows[$i]['cell']=array(
		    								$row['NoPos'][$i],
		    								$row['NoBL'][$i],
		    								$row['Mother_Vesel'][$i],
											$row['TglBL'][$i],
											$row['Pelabuhan_Asal'][$i],
											$row['Sebelumnya'][$i],
											$row['Bongkar'][$i],
											$row['Akhir'][$i],
											$row['Shipper'][$i],
											$row['Shipper_Adderess'][$i],
											$row['Consignee'][$i],
											$row['Consignee_Adderess'][$i],
											$row['Notify_Adderess'][$i],
											$row['Notify_Adderess'][$i],
											$row['Mark'][$i],
											$row['Bruto'][$i],
											$row['Volume'][$i],
											$row['Jumlah_Kemasan'][$i],
											$row['Satuan'][$i],
											$row['Count_Qty'][$i],
											$row['No_Count'][$i],
											$row['Size'][$i],
											$row['Type'][$i],
											$row['Seal'][$i],
											$row['Desc_Goods'][$i],
											'',
											$row['MasterBL'][$i],
										);
					$i++;
			}
			
			$data->page = 1;
			$data->total = 1;
			$data->records = count($all);
			
			echo json_encode($data);
			
		} else {
			echo json_encode(array());
		}
	}
	       
	function get_nomer_master() 
	{
		//$path = realpath($_SERVER["DOCUMENT_ROOT"])."/public/file/xls/";
		$path = APPPATH.'third_party/file/xls/';
		if (isset($_GET['file'])) {
			
            $import_xls_file = $_GET['file'];
            
            $inputFileName = $path . $import_xls_file;
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
		
			$objPHPExcel->setActiveSheetIndexByName('Master Entry');
			$MasterEntry = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			$objPHPExcel->setActiveSheetIndexByName('Header');
			$Header = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			$objPHPExcel->setActiveSheetIndexByName('Detil');
			$Detil = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
			unset($Detil[1]);
			foreach($Detil as $row) {
				$NoPos[] = $row['E'];
				$NoBL[]= $row['J'];
				$Mother_Vesel[]=$row['L'];
				$Pelabuhan_Asal[]=$row['X'];
				$Sebelumnya[]=$row['Y'];
				$Bongkar[]=$row['Z'];
				$Akhir[]=$row['AA'];
				$Shipper[]=$row['R'];
				$Shipper_Adderess[]=$row['S'];
				$Consignee[]=$row['N'];
				$Consignee_Adderess[]=$row['O'];
				$Notify[]=$row['U'];
				$Notify_Adderess[]=$row['V'];
				$Mark[]=$row['AD'];
				$Bruto[]=$row['AF'];
				$Volume[]=$row['AG'];
				$Jumlah_Kemasan[]=$row['AB'];
				$Satuan[]=$row['AC'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('Kontainer');
			$Kontainer = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Kontainer[1]);
			foreach($Kontainer as $row) {
				$Count_Qty[]= $row['F'];
				$No_Count[]= $row['D'];
				$Size[]= $row['E'];
				$Type[]= $row['G'];
				$Seal[]= $row['H'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('Barang');
			$Barang = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Barang[1]);
			foreach($Barang as $row) {
				$Desc_Goods[]= $row['E'];
			}
		
			$data = array(
				'header'=> array(
					'noMB' => $MasterEntry[2]['D'],
					'manifest' => $Header[2]['D'],
					'kapal' => $Header[2]['K'],
					'noVoy' => $Header[2]['Q'],
					'tglTiba' => $Header[2]['Z'],
					'jamTiba' => $Header[2]['AA']
				),
				'detail'=>array(
					'NoPos'=>$NoPos,
					'NoBL'=>$NoBL,
					'Mother_Vesel'=>$Mother_Vesel,
					'Pelabuhan_Asal'=>$Pelabuhan_Asal,
					'Sebelumnya'=>$Sebelumnya,
					'Bongkar'=>$Bongkar,
					'Akhir'=>$Akhir,
					'Shipper'=>$Shipper,
					'Shipper_Adderess'=>$Shipper_Adderess,
					'Consignee'=>$Consignee,
					'Consignee_Adderess'=>$Consignee_Adderess,
					'Notify'=>$Notify,
					'Notify_Adderess'=>$Notify_Adderess,
					'Mark'=>$Mark,
					'Bruto'=>$Bruto,
					'Volume'=>$Volume,
					'Jumlah_Kemasan'=>$Jumlah_Kemasan,
					'Satuan'=>$Satuan,
					'Count_Qty'=>$Count_Qty,
					'No_Count'=>$No_Count,
					'Size'=>$Size,
					'Type'=>$Type,
					'Seal'=>$Seal,
					'Desc_Goods'=>$Desc_Goods,
				) 
			);
			echo json_encode($data);
		} else {
			echo json_encode(array());
		}
	}
	
	function manage_file() 
	{
		$value = $this->manifest_model->manage_file($_POST);
		echo json_encode($value);
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
		} 
		elseif(empty($_FILES['nama_file']['tmp_name']) || $_FILES['nama_file']['tmp_name'] == 'none')  
		{
			$error = 'No file was uploaded..';
		} 
		else 
		{
			$msg .= " File Name: " . $_FILES['nama_file']['name'] . ", ";
			$msg .= " File Size: " . @filesize($_FILES['nama_file']['tmp_name']);
			
			//$uploads_dir = $_SERVER["DOCUMENT_ROOT"]."/public/file/xls";
			$uploads_dir = APPPATH.'third_party/file/xls/';
			$tmp_name = $_FILES["nama_file"]["tmp_name"];
        	$name = md5(time().$_FILES["nama_file"]["name"]).'.xls';
        	
			/*new*/
			$this->load->helper('file');
			delete_files(APPPATH.'third_party/file/xls');
			
        	if(move_uploaded_file($tmp_name, "$uploads_dir/$name")){
				$text = $name . ';' . $this->session->userdata('fullname') . ';' . date('d-m-Y') . ';' . date('H:i:s');
				write_file(APPPATH.'third_party/file/xls/0000.txt', $text);
			}
			
		}		
		echo json_encode(array('error'=>$error,'msg'=>$msg));
	}

	function index()
	{
		$data['title'] = 'Manifes Bea Cukai';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'manifest';				
		$this->load->view('afg_template', $data);
	}
	
	function get_data_job()
	{
		$value = $this->manifest_model->get_data_job();
		echo json_encode($value);
	}
	
	public function get_warehouse_by_job() {
	  $kode_ref = $_GET['kode_ref'];
	  $query = $this->dbx->get_where('trnstuffingplan', ['kode_ref' => $kode_ref]);
	  $result = $query->row();
	  echo json_encode(['warehouse' => $result ? $result->warehouse : null]);
	}
	
}

