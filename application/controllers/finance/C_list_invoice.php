<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
class C_list_invoice extends CI_Controller{
	
	var $title ='List Invoice';

	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/m_list_invoice','finance/m_inv_to_shipper','finance/m_agenda_invoice'));
		$this->dbx = $this->m_list_invoice->dbx;
	}
	
	function index(){
		$data['title'] = $this->title;
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/v_list_invoice';
		$data['kepada'] ='SHIPPER';
		$this->load->view('afg_template', $data);
	}
	
	function get_table_inv()
	{
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
	 	$sord = isset($_POST['sord'])?$_POST['sord']:'';
		$search = isset($_GET['_search'])?$_GET['_search']:'false';
	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$_POST['start']=$start;
	 	
	 	$q = $this->m_list_invoice->get_table_inv($_POST,$limit=false);
		//echo '<pre>',print_r($this->dbx->last_query()),'</pre>';		
		$query = $this->m_list_invoice->get_table_inv($_POST,$limit=true);	
		//print_r($this->dbx->last_query());
		//die();
		$hitung = count($q);
		//echo '<pre>',print_r($hitung),'</pre>';
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}
	 
		if ($hal > $total_hal) $hal=$total_hal;
	 
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($query as $row) {
			$customer = $this->m_agenda_invoice->get_name($row->kepada_kode,$row->kepada,$row->number);	
			$now = new DateTime();  // Tanggal saat ini
			$dateFromDB = DateTime::createFromFormat('Ymd', $row->date);  // Konversi dari YYYYMMDD
			$interval = $now->diff($dateFromDB);
			$aging =$interval->days;
			if ($aging > 60 && $row->status == 'unpaid'){
				$status = 'overdue';
			}else{
				$status = $row->status;
			}
			if( $row->status == 'unpaid'){
			$umur = $aging." days";	
			} else {
			$umur = "";	
			}
			if( $row->currency == ''){
			$curr = "IDR";	
			}else if( $row->currency == 'USD' && $row->status == 'paid'){
			$curr = "IDR";	
			}else{
			$curr = "USD";	
			}
		   $data->rows[$i]['id']=$row->id;
			$data->rows[$i]['cell']=array(
												$row->number,
												$row->group,
												$this->fungsi->jin_date_str($row->date),
												$row->job_no,
												$row->kepada,
												$row->kepada_kode,
												$customer,
												$row->kurs,
												$status,
												$umur,
												$curr,
												$row->grand_total,
												$row->fin_proses,
												$row->nilai_ppn,
												$row->tarif_ppn,
												
											);
		    $i++;
		}
		echo json_encode($data);
	}
	
	function manage_data(){
		$this->dbx->where('id', $_POST['id']);
		$this->dbx->where('region_id',  $this->session->userdata('region_id'));
		$body = array("invoice_no" => $this->dbx->get('x_finance')->result()[0]->number, "delete" => true);
		$response = curl_jasaweb("gateway_container.v1.sales.unpost_invoice", json_encode($body));
		if($response['code'] == 500){
		foreach ($response['response'] as $obj){
			////print_r($obj);
			//die();
			if(strpos($obj,"doesn't exists") !== false)
			{
			$status = $this->m_list_invoice->manage_data($_POST);
			$update = $this->m_list_invoice->update_data($_POST);
			echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
			}else{
			return $this->output->set_status_header(500)
				->set_content_type('application/json')
				->set_output(json_encode($response['response']));
			}
		}
		}else{
			$status = $this->m_list_invoice->manage_data($_POST);
			$update = $this->m_list_invoice->update_data($_POST);
			//print_r($_POST));
			//die();
			echo json_encode(array('status'=>$status,'oper'=>$_POST['oper']));
		}
	}
function download_csv($no_faktur,$id)
{	
    // Cek login dulu
    if (!$this->session->userdata('username')) {
        redirect('login');
        return;
    }

    // Lepas session lock (biar request lain nggak ke-lock)
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // --- HEADER SPREADSHEET (punya kamu) ---
    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1','FK');
    $sheet->setCellValue('B1','KD_JENIS_TRANSAKSI');
    $sheet->setCellValue('C1','FG_PENGGANTI');
    $sheet->setCellValue('D1','NOMOR_FAKTUR');
    $sheet->setCellValue('E1','MASA_PAJAK');
    $sheet->setCellValue('F1','TAHUN_PAJAK');
    $sheet->setCellValue('G1','TANGGAL_FAKTUR');
    $sheet->setCellValue('H1','NPWP');
    $sheet->setCellValue('I1','NAMA');
    $sheet->setCellValue('J1','ALAMAT_LENGKAP');
    $sheet->setCellValue('K1','JUMLAH_DPP');
    $sheet->setCellValue('L1','JUMLAH_PPN');
    $sheet->setCellValue('M1','JUMLAH_PPNBM');
    $sheet->setCellValue('N1','ID_KETERANGAN_TAMBAHAN');
    $sheet->setCellValue('O1','FG_UANG_MUKA');
    $sheet->setCellValue('P1','UANG_MUKA_DPP');
    $sheet->setCellValue('Q1','UANG_MUKA_PPN');
    $sheet->setCellValue('R1','UANG_MUKA_PNPBM');
    $sheet->setCellValue('S1','REFERENSI');
    $sheet->setCellValue('T1','');
    $sheet->setCellValue('U1','');

    $sheet->setCellValue('A2','LT');
    $sheet->setCellValue('B2','NPWP');
    $sheet->setCellValue('C2','NAMA');
    $sheet->setCellValue('D2','JALAN');
    $sheet->setCellValue('E2','BLOK');
    $sheet->setCellValue('F2','NOMOR');
    $sheet->setCellValue('G2','RT');
    $sheet->setCellValue('H2','RW');
    $sheet->setCellValue('I2','KECAMATAN');
    $sheet->setCellValue('J2','KELURAHAN');
    $sheet->setCellValue('K2','KABUPATEN');
    $sheet->setCellValue('L2','PROPINSI');
    $sheet->setCellValue('M2','KODE_POS');
    $sheet->setCellValue('N2','NOMOR_TELEPON');

    $sheet->setCellValue('A3','OF');
    $sheet->setCellValue('B3','KODE_OBJEK');
    $sheet->setCellValue('C3','NAMA');
    $sheet->setCellValue('D3','HARGA_SATUAN');
    $sheet->setCellValue('E3','JUMLAH_BARANG');
    $sheet->setCellValue('F3','HARGA_TOTAL');
    $sheet->setCellValue('G3','DISKON');
    $sheet->setCellValue('H3','DPP');
    $sheet->setCellValue('I3','PPN');
    $sheet->setCellValue('J3','TARIF_PPNBM');
    $sheet->setCellValue('K3','PPNBM');

    // ==============================
    //  PENGAMBILAN DATA (DIUBAH)
    // ==============================

    $ids_raw = rawurldecode($id);             // "42619,71815,71168,71425"
    $id_list = preg_split("/\,/", $ids_raw);  // array nilai string

    $all_rows = array();

    foreach ($id_list as $one_id) {
        $one_id = trim($one_id);
        if ($one_id === '' || !is_numeric($one_id)) continue;

        // Panggil model dengan SATU ID saja
        $rows = $this->m_inv_to_shipper->get_data_pajak(array((int)$one_id));
        if (is_array($rows) && count($rows) > 0) {
            $all_rows = array_merge($all_rows, $rows);
        }
    }

    $q = $all_rows;

    if (empty($q)) {
        echo "Tidak ada data untuk di-export.";
        exit;
    }

    // ==============================
    //  LANJUTKAN LOGIC KAMU SEPERTI SEMULA
    // ==============================

    $n = 4;

    foreach($q as $row )
    {
        // kalau ini berat, boleh dimatikan dulu
        $this->m_list_invoice->update_finance($row->id);

        $jenis = '';
        $jumlah_karakter = strlen($no_faktur);

        if($row->ppn=='10%' || $row->ppn=='11%' || $row->ppn=='12%')
            $jenis='04';
        else
            $jenis='05';

        $sheet->setCellValue('A'.$n,'FK');
        $sheet->setCellValue('A'.($n+1),'FAPR');
        $sheet->setCellValue('B'.$n,$jenis);
        $sheet->setCellValue('B'.($n+1),'PT GATEWAY CONTAINER LINE');
        $sheet->setCellValue('C'.$n,0);
        $sheet->setCellValue('C'.($n+1),'JL OTISTA III NO 18 RT 001 RW 001, JAKARTA TIMUR');

        if ($jumlah_karakter == 11){
            $sheet->setCellValue('D'.$n,'00'.$no_faktur++);
        } else if ($jumlah_karakter==12){
            $sheet->setCellValue('D'.$n,'0'.$no_faktur++);	
        }

        $sheet->setCellValue('D'.($n+1),'Hesty Rosmawaty');
        $sheet->setCellValue('E'.$n,date("n", strtotime($row->date)));
        $sheet->setCellValue('E'.($n+1),'JAKARTA TIMUR');
        $sheet->setCellValue('F'.$n,substr($row->date, 0, 4));
        $sheet->setCellValue('G'.$n,date("d/m/Y", strtotime($row->date)));
        $sheet->setCellValue('H'.$n,sprintf("%'015d",$row->npwp));
        $sheet->setCellValue('I'.$n,$row->nama);
        $sheet->setCellValue('J'.$n,$row->alamat);
        $sheet->setCellValue('M'.$n,0);
        $sheet->setCellValue('N'.$n,'');
        $sheet->setCellValue('O'.$n,0);
        $sheet->setCellValue('P'.$n,0);
        $sheet->setCellValue('Q'.$n,0);
        $sheet->setCellValue('R'.$n,0);
        $sheet->setCellValue('S'.$n,$row->number);

        // --- HITUNG DPP & PPN DETAIL (OF) ---
		$total       = 0.0; // total DPP (untuk kolom K)
		$total_pajak = 0.0; // total PPN detail (nanti di-floor untuk kolom L)
		$k           = $n + 2;

		$q1 = $this->m_inv_to_shipper->get_data_pajak_array($row->number);

		foreach ($q1 as $rows)
		{
			$dpp = round($rows->qty * $rows->unit_price); // DPP dasar dari database (dibulatkan ke rupiah)

			$sheet->setCellValue('A'.$k, 'OF');
			$sheet->setCellValue('B'.$k, $rows->code);
			$sheet->setCellValue('C'.$k, $rows->item);
			$sheet->setCellValue('D'.$k, $rows->unit_price);
			$sheet->setCellValue('E'.$k, $rows->qty);
			$sheet->setCellValue('F'.$k, $dpp);
			$sheet->setCellValue('G'.$k, 0);

			$ppn_raw      = 0.0;      // nilai PPN sebelum dibulatkan ke 1 desimal
			$dpp_for_ppn  = $dpp;     // DPP yang dipakai untuk hitung PPN (bisa berbeda untuk 12%)

			// CABANG BERDASARKAN TARIF PPN
			if ($row->ppn == '10%' || $row->ppn == '1%') {

				// 10% atau 1% normal
				$sheet->setCellValue('H'.$k, $dpp);

				if ($row->ppn == '10%') {
					$ppn_raw = $dpp * 0.10;
				} else { // '1%'
					$ppn_raw = $dpp * 0.01;
				}

				$total += $dpp;
			}
			else if ($row->ppn == '11%') {

				// 11% normal
				$sheet->setCellValue('H'.$k, $dpp);
				$ppn_raw = $dpp * 0.11;
				$total  += $dpp;
			}
			else if ($row->ppn == '12%') {

				// Khusus 12%: tetap pertahankan pola lama kamu
				// DPP (kolom H) = 11/12 dari nilai awal
				// PPN = 11% dari nilai awal (sehingga efektif ≈ 12% dari DPP)
				$dpp_for_ppn = round($dpp * 11 / 12);
				$sheet->setCellValue('H'.$k, $dpp_for_ppn);

				$ppn_raw = $dpp * 11 / 100;  // sama seperti rumus lama
				$total  += $dpp_for_ppn;
			}
			else if ($row->ppn == '1.1%' || $row->ppn == '1.2%') {

				// Kasus 1.1% / 1.2% → sering muncul .5 (contoh 170.5)
				$sheet->setCellValue('H'.$k, $dpp);

				// Ambil angka 1.1 / 1.2 dari string
				$tarif = 0.011;
				$ppn_raw = $dpp * $tarif;

				$total += $dpp;
			}
			else {

				// fallback kalau ada nilai ppn lain / kosong
				$sheet->setCellValue('H'.$k, $dpp);
				$ppn_raw = 0.0;
				$total  += $dpp;
			}

			// Samakan gaya seperti PAJAK.csv:
			// PPN per baris disimpan dengan 1 angka desimal (bisa .0 atau .5)
			$ppn = round($ppn_raw);

			// Akumulasi total PPN (masih float dengan desimal)
			$total_pajak += $ppn;

			// Simpan ke kolom I (PPN)
			$sheet->setCellValue('I'.$k, round($ppn));
			$sheet->setCellValue('J'.$k, 0);
			$sheet->setCellValue('K'.$k, 0);

			$k++;
		}

		// --- SET HEADER JUMLAH_DPP & JUMLAH_PPN DI BARIS FK ---

		// K = JUMLAH_DPP (jumlah DPP yang dipakai untuk hitung PPN)
		$sheet->setCellValue('K'.$n, round($total));

		// L = JUMLAH_PPN
		// di file PAJAK*.csv: total PPN = penjumlahan detail (boleh punya .5) lalu dibulatkan ke bawah
		$sheet->setCellValue('L'.$n, round($total_pajak));

		// baris berikutnya untuk faktur selanjutnya
		$n = $k;
    }

    $writer   = new Csv($spreadsheet);
    $filename = 'eFaktur';

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="'.$filename.'.csv"');
    header('Cache-Control: max-age=0');
	
    $writer->save('php://output');
    exit;
}

public function generate_xml($no_faktur, $id)
{
    // 1. Helper & cek login
    $this->load->helper(['file', 'download']);

    if (!$this->session->userdata('username')) {
        redirect('login');
        return;
    }

    // 2. Lepas session lock supaya request lain tidak ikut nunggu
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // 3. Pecah list ID dari URL (contoh: "42619,71815,71168")
    $ids_raw = rawurldecode($id);
    $id_list = preg_split("/,/", $ids_raw);

    // 4. Ambil HEADER per invoice, 1 query per ID (AMAN)
    $header_rows = [];

    foreach ($id_list as $one_id) {
        $one_id = trim($one_id);
        if ($one_id === '' || !is_numeric($one_id)) {
            continue;
        }

        // Panggil model dengan SATU ID
        $rows = $this->m_inv_to_shipper->get_data_pajak([(int)$one_id]);

        if (is_array($rows) && count($rows) > 0) {
            // get_data_pajak join ke item, jadi bisa banyak baris per invoice,
            // kita cuma perlu satu row sebagai header invoice ini
            $header_rows[] = $rows[0];
        }
    }

    if (empty($header_rows)) {
        show_error('Tidak ada data untuk di-export XML.');
        return;
    }

    // 5. Buat struktur XML (sama seperti punyamu)
    $xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="utf-8"?><TaxInvoiceBulk xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>'
    );

    $xml->addAttribute(
        'xsi:noNamespaceSchemaLocation',
        'TaxInvoice.xsd',
        'http://www.w3.org/2001/XMLSchema-instance'
    );

    // TIN perusahaan
    $xml->addChild('TIN', '0029326105001000');

    // ListOfTaxInvoice
    $listOfTaxInvoice = $xml->addChild('ListOfTaxInvoice');

    // 6. Loop per invoice header
    foreach ($header_rows as $row) {

        // Update status finance kalau memang harus di sini
        $this->m_list_invoice->update_finance($row->id);

        // TrxCode
        $jenis = ($row->ppn == '12%' || $row->ppn == '11%') ? '04' : '05';

        // Tanggal faktur
        $tanggal_invoice = date("Y-m-d", strtotime($row->date));

        // Detail barang/jasa untuk invoice ini
        $q1 = $this->m_inv_to_shipper->get_data_pajak_array($row->number);

        // TaxInvoice node
        $taxInvoice = $listOfTaxInvoice->addChild('TaxInvoice');
        $taxInvoice->addChild('TaxInvoiceDate', $tanggal_invoice);
        $taxInvoice->addChild('TaxInvoiceOpt', 'Normal');
        $taxInvoice->addChild('TrxCode', $jenis);
        $taxInvoice->addChild('AddInfo', '');
        $taxInvoice->addChild('CustomDoc', '');
        $taxInvoice->addChild('CustomDocMonthYear', '');
        $taxInvoice->addChild('RefDesc', $row->number);
        $taxInvoice->addChild('FacilityStamp', '');
        $taxInvoice->addChild('SellerIDTKU', '0029326105001000000000');

        // Buyer info
        $taxInvoice->addChild('BuyerTin', $row->npwp);
        $taxInvoice->addChild('BuyerDocument', 'TIN');
        $taxInvoice->addChild('BuyerCountry', 'IND');
        $taxInvoice->addChild('BuyerDocumentNumber', '');
        $taxInvoice->addChild('BuyerName', $row->nama);
        $taxInvoice->addChild('BuyerAdress', '');
        $taxInvoice->addChild('BuyerEmail', '');
        $taxInvoice->addChild('BuyerIDTKU', $row->npwp . '000000');

        // ListOfGoodService
        $listOfGoodService = $taxInvoice->addChild('ListOfGoodService');

        if ($q1) {
            foreach ($q1 as $item) {

                // Format qty (kalau nanti butuh, sekarang kamu pakai Qty=1)
                $formatted_qty = number_format($item->qty, 2, '.', '');
                $unit          = 'UM.0030';

                // Hitung DPP & PPN untuk nilai lain
                $dpp_nilai_lain = 0;
                $VATRate        = 0;
                $PPN            = 0;

                if ($row->ppn == '1.1%' || $row->ppn == '1.2%') {
                    $dpp_nilai_lain = $item->price;
                    $VATRate        = 12;
                    // formula sesuai logic kamu sebelumnya
                    $PPN = floatval($item->price) * (11/12) * 0.1 * 0.12;
                } elseif ($row->ppn == '11%' || $row->ppn == '12%') {
                    $dpp_nilai_lain = floatval($item->price) * (11/12);
                    $VATRate        = 12;
                    $PPN            = floatval($item->price) * (11/12) * 0.12;
                }

                // Node GoodService
                $goodService = $listOfGoodService->addChild('GoodService');
                $goodService->addChild('Opt', 'B');
                $goodService->addChild('Code', '060000');
                $goodService->addChild('Name', htmlspecialchars($item->item, ENT_XML1));
                $goodService->addChild('Unit', $unit);
                $goodService->addChild('Price', $item->price);
                $goodService->addChild('Qty', 1); // atau pakai $formatted_qty kalau mau
                $goodService->addChild('TotalDiscount', 0);
                $goodService->addChild('TaxBase', $item->price);
                $goodService->addChild('OtherTaxBase', round($dpp_nilai_lain));
                $goodService->addChild('VATRate', $VATRate);
                $goodService->addChild('VAT', round($PPN));
                $goodService->addChild('STLGRate', 0);
                $goodService->addChild('STLG', 0);
            }
        }
    }

    // 7. Format & download XML
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    $xmlString = $dom->saveXML();

    force_download('TaxInvoice_' . $no_faktur . '.xml', $xmlString);
}


/*
Lembar ke-2:Untuk Penjual BKP/Pemberi JKP
		Sebagai Bukti Pajak Keluaran
*/}