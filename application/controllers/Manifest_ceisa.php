<?php
class Manifest_ceisa extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->library('excel');
		$this->load->model('manifest_model');
	}
	
	function get_file_ciesa() {
		$this->load->helper('directory');
		$this->load->helper('file');
		$files = directory_map(APPPATH.'third_party/file/xls_ceisa');
		if(empty($files)) :
			echo json_encode(array('status'=>0));
			return;
		else :
			if(count($files)!=2):
				delete_files(APPPATH.'third_party/file/xls_ceisa');
				echo json_encode(array('status'=>0));
				return;
			else :
				sort($files);
				$string = read_file(APPPATH.'third_party/file/xls_ceisa/0000.txt');
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
		$path = APPPATH.'third_party/file/xls_ceisa/';
		if (isset($_GET['file'])) {
            
			$import_xls_file = $_GET['file'];
            $inputFileName = $path . $import_xls_file;
            
			try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
				$excelReader = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
			
			// Read Sheet BL
			$sheetBL = $excelReader->getSheetByName('BL');
			$dataBL = [];
			$rowsBL = $sheetBL->toArray(null, true, true, true);
			array_shift($rowsBL); // Remove header

			foreach ($rowsBL as $row) {
				if (!empty($row['A'])) { // Assuming 'A' is 'Id Bl'
					$dataBL[$row['A']] = $row; // Store by Id Bl as key
				}
			}

			// Read Sheet BL HS
			$sheetBLHS = $excelReader->getSheetByName('BL HS');
			$dataBLHS = [];
			$rowsBLHS = $sheetBLHS->toArray(null, true, true, true);
			array_shift($rowsBLHS); // Remove header

			foreach ($rowsBLHS as $row) {
				if (!empty($row['B'])) {
					$dataBLHS[$row['B']][] = $row; // Store multiple rows by Id Bl
				}
			}

			// Read Sheet BL PETIKEMAS
			$sheetBLPetikemas = $excelReader->getSheetByName('BL PETIKEMAS TERANGKUT');
			$dataBLPetikemas = [];
			$rowsBLPetikemas = $sheetBLPetikemas->toArray(null, true, true, true);
			array_shift($rowsBLPetikemas); // Remove header

			foreach ($rowsBLPetikemas as $row) {
				if (!empty($row['E'])) {
					$dataBLPetikemas[$row['E']][] = $row; // Store multiple rows by Id Bl
				}
			}
			
			$combinedData = [];

			foreach ($dataBL as $idBl => $blRow) {
				$combinedData[$idBl] = [
					'BL' => $blRow,
					'BL_HS' => isset($dataBLHS[$idBl]) ? $dataBLHS[$idBl] : [],
					'BL_PETIKEMAS' => isset($dataBLPetikemas[$idBl]) ? $dataBLPetikemas[$idBl] : [],
				];
			}
			
			// Extract NoPos values as keys for sorting
			$noPosValues = [];
			foreach ($combinedData as $idBl => $details) {
				$noPosValues[$idBl] = $details['BL']['E']; // Assuming E is NoPos
			}

			// Sort $combinedData by $noPosValues (by NoPos)
			asort($noPosValues);

			// Rebuild $combinedData in sorted order
			$sortedCombinedData = [];
			foreach ($noPosValues as $idBl => $noPos) {
				$sortedCombinedData[$idBl] = $combinedData[$idBl];
			}

			// Now use $sortedCombinedData in your foreach loop
			foreach ($sortedCombinedData as $idBl => $details) {
				$NoPos[] = $details['BL']['E'];
				$NoBL[] = $details['BL']['H'];
				$tglBL[] = $details['BL']['I'];
				$Mother_Vesel[] = $details['BL']['V'];
				$Pelabuhan_Asal[] = $details['BL']['W'];
				$Sebelumnya[] = $details['BL']['Y'];
				$Bongkar[] = $details['BL']['X'];
				$Akhir[] = $details['BL']['Z'];
				$Shipper[] = $details['BL']['K'];
				$Shipper_Adderess[] = $details['BL']['L'];
				$Consignee[] = $details['BL']['M'];
				$Consignee_Adderess[] = $details['BL']['N'];
				$Notify[] = $details['BL']['O'];
				$Notify_Adderess[] = $details['BL']['P'];
				$Mark[] = $details['BL']['J'];
				$Bruto[] = $details['BL']['Q'];
				$Volume[] = $details['BL']['R'];
				$Jumlah_Kemasan[] = $details['BL']['U'];
				$Satuan[] = $details['BL']['T'];
				$MasterBL[] = $details['BL']['F'];

				$descGoodsText = [];
				$hsCodeText = [];

				foreach ($details['BL_HS'] as $blHsRow) {
					$descGoodsText[] = $blHsRow['D'];
					$hsCodeText[] = $blHsRow['C'];
				}

				$Desc_Goods[] = implode(" | ", $descGoodsText);
				$Hs[] = implode(" | ", $hsCodeText);

				foreach ($details['BL_PETIKEMAS'] as $blPetikemasRow) {
					$Count_Qty[] = $blPetikemasRow['I'];
					$No_Count[] = $blPetikemasRow['D'];
					$Size[] = $blPetikemasRow['J'];
					$Type[] = 'FT';
					$Seal[] = $blPetikemasRow['K'];
				}
			}

		
			for($i=0;$i<count($NoPos);$i++) {
			
				$all[$i] = array(
					'NoPos'=>$NoPos,
					'NoBL'=>$NoBL,
					'TglBL'=>$tglBL,
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
					'HS_Code'=>$Hs,
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
											$row['HS_Code'][$i],
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
		$path = APPPATH.'third_party/file/xls_ceisa/';
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
		
			$objPHPExcel->setActiveSheetIndexByName('NVOCC');
			$MasterEntry = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			$objPHPExcel->setActiveSheetIndexByName('MASTER');
			$Header = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			$objPHPExcel->setActiveSheetIndexByName('BL');
			$Detil = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
			unset($Detil[1]);
			foreach($Detil as $row) {
				$NoPos[] = $row['E'];
				$NoBL[]= $row['H'];
				$Mother_Vesel[]=$row['V'];
				$Pelabuhan_Asal[]=$row['W'];
				$Sebelumnya[]=$row['Y'];
				$Bongkar[]=$row['X'];
				$Akhir[]=$row['Z'];
				$Shipper[]=$row['K'];
				$Shipper_Adderess[]=$row['L'];
				$Consignee[]=$row['M'];
				$Consignee_Adderess[]=$row['N'];
				$Notify[]=$row['O'];
				$Notify_Adderess[]=$row['P'];
				$Mark[]=$row['J'];
				$Bruto[]=$row['Q'];
				$Volume[]=$row['R'];
				$Jumlah_Kemasan[]=$row['U'];
				$Satuan[]=$row['T'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('BL PETIKEMAS TERANGKUT');
			$Kontainer = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Kontainer[1]);
			foreach($Kontainer as $row) {
				$Count_Qty[]= $row['F'];
				$No_Count[]= $row['D'];
				$Size[]= $row['E'];
				$Type[]= $row['G'];
				$Seal[]= $row['H'];
			}
		
			$objPHPExcel->setActiveSheetIndexByName('BL HS');
			$Barang = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
		
			unset($Barang[1]);
			foreach($Barang as $row) {
				$Desc_Goods[]= $row['D'];
			}
		
			$data = array(
				'header'=> array(
					'noMB' => $Header[2]['C'],
					'tgl_master' => $Header[2]['D'],
					'manifest' => $MasterEntry[2]['B'],
					'kapal' => $MasterEntry[2]['H'],
					'noVoy' => $MasterEntry[2]['I'],
					'tglTiba' => $MasterEntry[2]['K'],
					'jml_pos' => $Header[2]['F'],
					'group' => $Header[2]['E'],
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
				case '9':
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
			
			$uploads_dir = APPPATH.'third_party/file/xls_ceisa/';
			$tmp_name = $_FILES["nama_file"]["tmp_name"];
        	$name = md5(time().$_FILES["nama_file"]["name"]).'.xlsx';
        	
			/*new*/
			$this->load->helper('file');
			delete_files(APPPATH.'third_party/file/xls_ceisa');
			
        	if(move_uploaded_file($tmp_name, "$uploads_dir/$name")){
				$text = $name . ';' . $this->session->userdata('fullname') . ';' . date('d-m-Y') . ';' . date('H:i:s');
				write_file(APPPATH.'third_party/file/xls_ceisa/0000.txt', $text);
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
	
	
}

