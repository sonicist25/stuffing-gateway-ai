<?php defined('BASEPATH') OR exit('No direct script access allowed');

define ('TBL_ACC_PAY','finance_accounts_payable');
define ('TBL_ACC_RCV','finance_accounts_receivable');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Accounts extends CI_Controller{
	var $title = '';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/accounts_model'));
	}
	
	function get_table_receivable() {
		
		$hal = isset($_POST['page'])?$_POST['page']:''; 
		$batas = isset($_POST['rows'])?$_POST['rows']:'';
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
		$search = isset($_POST['_search'])?$_POST['_search']:'false';
		
	 	$start = floatval($batas)* floatval($hal) - floatval($batas);
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;

		$q = $this->accounts_model->get_data_table($_POST,$limit=false);
		$query = $this->accounts_model->get_data_table($_POST,$limit=true);
		
		$hitung = count($q);	 
		if( $hitung > 0 ) : $total_hal = ceil($hitung/$batas);
		else : $total_hal = 0;
		endif;	 
		if ($hal > $total_hal) $hal=$total_hal;	 
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;	

		$i=0;
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
				$row->tanggal_byr,
				$row->type,
				$row->id_fin,
				$row->nomor,
				$row->pay_to,
				$row->no_job,
				$row->currency,
				$row->ttl_rp,
				$row->total_tagihan,
				$row->jumlah_usd,
				$row->jumlah_rp,
				$row->saldo_usd,
				$row->saldo_rp,
				$row->pelunasan,
				$row->nomor,
			);
			$i++;
		}
		echo json_encode($data);
	}
	
	function get_list_all() {
		$this->accounts_model->get_list_all();
	}
	
	function payable() {
		$data['title'] = $this->title;
		$data['table_title'] ='AP';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/accounts';
		$data['script'] = 'accounts/accounts_payable';
		$this->load->view('afg_template', $data);
	} 
	
	function receivable() {
		$data['title'] = $this->title;
		$data['table_title'] ='AR';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/accounts';
		$data['script'] = 'accounts/accounts_receivable';
		$this->load->view('afg_template', $data);
	}
	
	function update_data() {
		$data = $this->accounts_model->get_invoice();
		
		echo json_encode(array('status'=>1));
	}
	
	function get_table() {
		
		$hal = isset($_GET['page'])?$_GET['page']:''; 
		$batas = isset($_GET['rows'])?$_GET['rows']:'';
		$sidx = isset($_GET['sidx'])?$_GET['sidx']:'';
		$sord = isset($_GET['sord'])?$_GET['sord']:'';
		$search = isset($_GET['_search'])?$_GET['_search']:'false';
		
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_GET['start']=$start;

		$q = $this->accounts_model->get_data_table_ap($_GET,$limit=false);
		$query = $this->accounts_model->get_data_table_ap($_GET,$limit=true);
		
		$hitung = count($q);	 
		if( $hitung > 0 ) : $total_hal = ceil($hitung/$batas);
		else : $total_hal = 0;
		endif;	 
		if ($hal > $total_hal) $hal=$total_hal;	 
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;	

		$i=0;
		foreach($query as $row) {
		    $data->rows[$i]['id']=$row->id;
		    $data->rows[$i]['cell']=array(
				$row->tanggal_byr,
				$row->type,
				$row->id_fin,
				$row->nomor_usd,
				$row->pay_to,
				$row->currency,
				$row->total_tagihan,
				$row->total_bayar,
				$row->saldo_usd,
				$row->saldo_rp,
				$row->pelunasan,
				$row->nomor_usd,
				$row->kode_ref,
				$row->item,
			);
			$i++;
		}
		echo json_encode($data);
	}
	
	function manage_data() {
		$q = $this->accounts_model->manage_data($_POST);
		echo json_encode($q);
	}
	
	function manage_data_pay() {
		$q = $this->accounts_model->manage_data_pay($_POST);
		//print_r($this->dbx->last_query());
		//die();
		echo json_encode($q);
	}
	
	function update_row_shipper() {
		$customer = $this->accounts_model->get_name($_POST['kpd'],$_POST['code'],$_POST['inv']);
		//print_r($this->dbx->last_query());
		//die();
		echo json_encode($customer);
	}
	
	function update_payable() {
		$customer = $this->accounts_model->get_name_payable($_POST['kepada']);
		//print_r($this->dbx->last_query());
		//die();
		echo json_encode($customer);
	}

function download_excel($id,$sell)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Define headers and their styles
    $headers = [
        'A2' => 'No',
        'B2' => 'Nomor Jurnal',
        'C2' => 'Tanggal Bayar',
        'D2' => 'Customer',
        'E2' => 'Invoice No.',
        'F2' => 'Nomor Job',
        'G2' => 'Cur',
        'H2' => 'Nominal Inv.',
        'I2' => 'Total Inv',
        'J2' => 'Pembayaran USD',
        'K2' => 'Pembayaran IDR',
        'L2' => 'Overdue USD',
        'M2' => 'Overdue IDR',
        'N2' => 'Paid'
    ];

    // Title Style
    $titleStyle = [
        'font' => [
            'bold' => true,
            'size' => 16,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    // Header Style
    $headerStyle = [
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4F81BD'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'border' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    // Set Title
    $sheet->setCellValue('A1', 'Laporan Data Account Receivable');
    $sheet->mergeCells('A1:N1'); // Merge cells for title
    $sheet->getStyle('A1')->applyFromArray($titleStyle);
    
    // Set headers
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
        $sheet->getStyle($cell)->applyFromArray($headerStyle);
    }

    // Define data row style
    $rowStyle = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'border' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'C0C0C0'],
            ],
        ],
    ];

    // Define number formats for currency columns
    $currencyFormat = '#,##0.00';

    // Define right alignment for currency columns
    $rightAlignment = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT,
        ],
    ];
	if($sell == 'no'){
	$ids=rawurldecode($id);
	$array = json_decode($ids, true);
	$idsString = array_keys($array);
	$str_arr= implode(',', $idsString);
	}else{
    // Decode the ID and JSON data
    $ids = rawurldecode($id);
    // Convert keys to a comma-separated string
    $str_arr = $ids;
	}
	//print_r($ids);
	//die();
    // Fetch data from the model
    $q = $this->accounts_model->get_list_all($str_arr);
	//print_r($this->dbx->last_query());
	//die();
    $k = 3; // Starting row index for data (row 3 as row 2 is header)
    $total_rp = 0;
    $total_usd = 0;

    // Populate the spreadsheet with data
    foreach ($q as $rows) {
        $sheet->setCellValue('A'.$k, $k - 2); // Numbering
        $sheet->setCellValue('B'.$k, ""); // Empty cell for 'Nomor Jurnal'
        $sheet->setCellValue('C'.$k, $rows->tanggal_byr);
        $sheet->setCellValue('D'.$k, $rows->pay_to);
        $sheet->setCellValue('E'.$k, $rows->no_inv);
        $sheet->setCellValue('F'.$k, $rows->no_job);
        $sheet->setCellValue('G'.$k, $rows->currency);

        // Set values with currency formatting and right alignment
        $sheet->setCellValue('H'.$k, $rows->ttl_rp);
        $sheet->getStyle('H'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('H'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('I'.$k, $rows->total_tagihan);
        $sheet->getStyle('I'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('I'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('J'.$k, $rows->jumlah_usd);
        $sheet->getStyle('J'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('J'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('K'.$k, $rows->jumlah_rp);
        $sheet->getStyle('K'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('K'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('L'.$k, $rows->saldo_usd);
        $sheet->getStyle('L'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('L'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('M'.$k, $rows->saldo_rp);
        $sheet->getStyle('M'.$k)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('M'.$k)->applyFromArray($rightAlignment);

        $sheet->setCellValue('N'.$k, $rows->pelunasan);

        // Apply data row style
        $sheet->getStyle('A'.$k.':N'.$k)->applyFromArray($rowStyle);

        // Calculate totals
		$total_tagihan += floatval($rows->total_tagihan);
        $total_rp += floatval($rows->jumlah_rp);
        $total_usd += floatval($rows->jumlah_usd);
        $total_saldo_usd += floatval($rows->saldo_usd);
        $total_saldo_rp += floatval($rows->saldo_rp);

        $k++;
    }

     // Add totals in footer
    $footerRow = $k; // Footer row index
    $sheet->setCellValue('A'.$footerRow, 'Total');
    $sheet->mergeCells('A'.$footerRow.':H'.$footerRow);
	$sheet->getStyle('A'.$footerRow)->applyFromArray($titleStyle);

    $sheet->setCellValue('I'.$footerRow, $total_tagihan);
    $sheet->getStyle('I'.$footerRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('I'.$footerRow)->applyFromArray($rightAlignment);

    $sheet->setCellValue('J'.$footerRow, $total_usd);
    $sheet->getStyle('J'.$footerRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('J'.$footerRow)->applyFromArray($rightAlignment);

    $sheet->setCellValue('K'.$footerRow, $total_rp);
    $sheet->getStyle('K'.$footerRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('K'.$footerRow)->applyFromArray($rightAlignment);

    $sheet->setCellValue('L'.$footerRow, $total_saldo_usd);
    $sheet->getStyle('L'.$footerRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('L'.$footerRow)->applyFromArray($rightAlignment);

    $sheet->setCellValue('M'.$footerRow, $total_saldo_rp);
    $sheet->getStyle('M'.$footerRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('M'.$footerRow)->applyFromArray($rightAlignment);

    $sheet->getStyle('A'.$footerRow.':N'.$footerRow)->applyFromArray([
        'font' => [
            'bold' => true,
        ],
        'border' => [
            'top' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    // Set the title for the sheet
    $sheet->setTitle("Laporan Data Account Receivable");

    // Set column widths for better readability
    foreach (range('A', 'N') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Clear output buffer and prepare headers for download
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="AR.xlsx"');
    header('Cache-Control: max-age=0');

    // Save and output the file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}


}
