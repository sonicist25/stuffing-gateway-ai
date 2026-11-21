<?php
class C_agenda_invoice extends CI_Controller{
	
    var $title ='Agenda Invoice';
	
    public function __construct(){
        parent::__construct();
        $this->fungsi->restrict();
        $this->load->model(array('finance/m_agenda_invoice','finance/m_finance_proses'));
    }
	
    public function index()
    {
        $this->fungsi->auth(47,'Agenda Invoice');
        $data['title'] = $this->title;
        $data['h1_title'] ='INVOICE TO SHIPPER';
        $data['table_title'] ='INVOICE';
        $data['menu_navigation'] ='navigation';
        $data['main_view'] ='finance/v_agenda_invoice';
        $this->load->view('afg_template', $data);
    }
	
    public function select_value()
    {
        $value = $this->input->post('value');
        if($value == 'AGENT' && $value != '') {
            $q = $this->m_finance_proses->getListAgent()->result();
            $data['option'][' ']=' ';
            foreach ($q as $row)
            {	
                $data['option'][$row->agent_company_code]=$row->agent_company_code;
            }	
        } else if($value == 'COLOADER' && $value != '') {
            $q = $this->m_finance_proses->getListShippco()->result();
            $data['option'][' ']=' ';
            foreach ($q as $row)
            {	
                $data['option'][$row->shipping_company_code]=$row->shipping_company_code;
            }
        } else if($value == 'SHIPPER' && $value != '') {
            $q = $this->m_finance_proses->getListShipper()->result();
            $data['option'][' ']=' ';
            foreach ($q as $row)
            {	
                $data['option'][$row->shipper_id]=$row->shipper_name;
            }
        } else if($value == 'CONSIGNEE' && $value != '') {
            $q = $this->m_finance_proses->getListShipper()->result();
            $data['option'][' ']=' ';
            foreach ($q as $row)
            {	
                $data['option'][$row->shipper_id]=$row->shipper_name;
            }
        } else {
            $data['option'][' ']=' ';
        }
		
        $dropdown = form_dropdown('kode_form',$data['option'],'','id="kode_form" style="width: 300px;"');
        echo json_encode(array("dropdown" => $dropdown ));		
    }
		
    public function display_table_satu()
    {
        $hal   = $this->input->post('page') ?: 1;
        $batas = $this->input->post('rows') ?: 10;
        $sidx  = $this->input->post('sidx') ?: 'date';
        $sord  = $this->input->post('sord') ?: 'asc';

        $start = $batas * $hal - $batas;
        $start = ($start<0)?0:$start;

        // Kumpulkan semua parameter untuk model
        $params = $this->input->post(); // _search, searchField, jenis, kepada, kode, from_date, to_date, dll.
        $params['start'] = $start;
        $params['rows']  = $batas;
        $params['sidx']  = $sidx;
        $params['sord']  = $sord;
		// simpan filter ke session untuk keperluan export
		$this->session->set_userdata('agenda_invoice_filter', $params);
        // Hitung total record
        $hitung = $this->m_agenda_invoice->count_filtered($params);

        if( $hitung > 0 ) {
            $total_hal = ceil($hitung/$batas);
        } else {
            $total_hal = 0;
        }
        if ($hal > $total_hal) $hal = $total_hal;

        // Ambil data untuk page ini
        $query = $this->m_agenda_invoice->get_paginated($params);

        $data            = new stdClass();	 
        $data->page      = $hal;
        $data->total     = $total_hal;
        $data->records   = $hitung;

        $i = 0;
        foreach($query as $row) {
            $data->rows[$i]['id']  = $row->id;
            $data->rows[$i]['cell']= array(
                $row->job_no,
                $row->number,
                $this->fungsi->jin_date_str($row->date),
                $this->get_data_item($row->id)->bl_no,
                $this->m_agenda_invoice->get_name($row->kepada_kode,$row->kepada,$row->number),
                $row->tgl_bayar,
                $this->get_total($row->id,'USD'),
                $this->get_total($row->id,'RP.'),
            );
            $i++;
        }
        echo json_encode($data);
    }
	
    public function print_data_to_pdf()
    {
         // Ambil filter terakhir dari session (di-set di display_table_satu)
		$params = $this->session->userdata('agenda_invoice_filter');
		if (!is_array($params)) {
			$params = [];
		}

		// Data full untuk export (tanpa paginasi)
		$data = $this->m_agenda_invoice->get_all_for_export($params);

        $this->load->library('FPDF_MC_Table');
        $pdf = new FPDF_MC_Table();	

        // Periode pakai from_date/to_date langsung dari form
        $tgl_awal  = isset($params['from_date']) ? $params['from_date'] : '';
        $tgl_akhir = isset($params['to_date']) ? $params['to_date'] : '';

        $pdf->setDataHeader(array(
            'dari'   => $tgl_awal,
            'sampai' => $tgl_akhir,
            'report' => 'agenda_invoice'
        ));
		
        $pdf->AddPage('L','Legal');
		
        $pdf->SetFont('Arial','',10);
        $pdf->SetWidths(array(10,40,25,20,15,35,50,30,30,20,62));
        $pdf->SetAligns(array('C','L','C','C','C','L','L','R','R','L','L'));

        $i        = 1;
        $printed  = []; // cegah duplikasi id

        foreach($data as $row ) {

            if (isset($printed[$row['id']])) {
                continue;
            }
            $printed[$row['id']] = true;

            $pdf->Row(array(
                $i,
                $row['job_no'],
                $row['number'],
                $this->fungsi->jin_date_str($row['date']),
                $this->get_data_item($row['id'])->currency,
                $this->get_data_item($row['id'])->bl_no,
                $this->m_agenda_invoice->get_name($row['kepada_kode'],$row['kepada'],$row['number']),
                $this->fungsi->pecah((float)$this->get_total($row['id'],$cur='RP.'),','),
                number_format((float)$this->get_total($row['id'],$cur='USD'),2,'.',','),
                $row['tgl_bayar'],
                '',
            ));
            $i++;
        }
		
        $pdf->Output('','I');
    }
	
    public function print_data_to_excel()
    {
        // Ambil filter terakhir dari session
		$params = $this->session->userdata('agenda_invoice_filter');
		if (!is_array($params)) {
			$params = [];
		}

		$data = $this->m_agenda_invoice->get_all_for_export($params);
			
		$page = 'agenda_invoice';
			
		$tgl_awal  = isset($params['from_date']) ? $params['from_date'] : '';
		$tgl_akhir = isset($params['to_date']) ? $params['to_date'] : '';
		$per       = $tgl_awal . ' s/d ' . $tgl_akhir;
		
        $tgl_awal  = isset($params['from_date']) ? $params['from_date'] : '';
        $tgl_akhir = isset($params['to_date']) ? $params['to_date'] : '';
        $per       = $tgl_awal . ' s/d ' . $tgl_akhir;
		
        header("Cache-Control: no-cache, no-store, must-revalidate");  
        header("Content-Type: application/vnd.ms-excel");  
        header("Content-Disposition: attachment; filename=".$page.".xls");		

        echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css');
        echo '<table summary="" width="100%" align="center">
        <tr><td class="space"></td><td class="judul" colspan="11">AGENDA INVOICE & CN</td></tr>
        <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$per.'</td></tr>
        <tr>
            <td class="space"></td>
            <td align="center" width="30" class="header" rowspan="2">NO</td>
            <td align="center" width="120" class="header" rowspan="2">JOB NO</td>
            <td align="center" width="170" class="header" rowspan="2">NO.REF</td>
            <td align="center" width="90" class="header" rowspan="2">TGL REF</td>
            <td align="center" width="90" class="header" rowspan="2">CURR</td>
            <td align="center" width="120" class="header" rowspan="2">BL NO.</td>
            <td align="center" width="120" class="header" rowspan="2">RECEIVED FROM</td>
            <td align="center" width="240" class="header" colspan="2">AMOUNT</td>
            <td align="center" width="120" class="header" rowspan="2">PAID</td>
            <td align="center" width="120" class="header" rowspan="2">REMARKS</td>
        </tr>
        <tr>
            <td class="space"></td>
            <td class="header" align="center">IDR</td>
            <td class="header" align="center">USD</td>
        </tr>
        <tr><td style="border:none; height: 5px" colspan="11"></td></tr>';

        $i       = 1;
        $printed = []; // cegah duplikasi id

        foreach($data as $row ) {

            if (isset($printed[$row['id']])) {
                continue;
            }
            $printed[$row['id']] = true;

            $idr = $this->get_total($row['id'],'RP.');
            $usd = $this->get_total($row['id'],'USD');

            if($idr==0 || $idr==="") {
                $idr = '';
            } else {
                $idr = $this->fungsi->pecah($idr,',');
            }
			
            if($usd==0 || $usd==="") {
                $usd = '';
            } else {
                $usd = $this->fungsi->pecah($usd,',');
            }

            echo '	
            <tr>
                <td class="space"></td>
                <td align="left">'.$i.'</td>
                <td align="left">'.$row['job_no'].'</td>
                <td align="left">'.$row['number'].'</td>
                <td align="left">'.$this->fungsi->jin_date_str($row['date']).'</td>
                <td align="left">'.$this->get_data_item($row['id'])->currency.'</td>
                <td align="left">'.$this->get_data_item($row['id'])->bl_no.'</td>
                <td align="left">'.$this->m_agenda_invoice->get_name($row['kepada_kode'],$row['kepada'],$row['number']).'</td>
                <td align="right">'.$idr.'</td>
                <td align="right">'.$usd.'</td>
                <td align="left">'.$row['tgl_bayar'].'</td>
                <td align="center"></td>
            </tr>';
            $i++;
        }

        echo '</table>';
        exit();
    }
	
    // === helper sama seperti versi lama ===

    public function get_data_item($id) {
        $data = $this->m_agenda_invoice->get_data_item($id);
        if($data->num_rows() > 0) {
            return $data->row();
        } else {
            $data = new stdClass();	
            $data->bl_no   = '';
            $data->currency='';
            return $data;
        }
    }
	
    public function get_total($id,$cur) {
        $data = $this->m_agenda_invoice->get_total($id,$cur);
        return $data;
    }

    // optional: test template
    public function test(){
        $per = '12345';
        echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css');
        echo '<table summary="" width="100%" align="center">
        <tr><td class="space"></td><td class="judul" colspan="11">AGENDA INVOICE & CN</td></tr>
        <tr><td class="space"></td><td class="title" colspan="11">Periode : '.$per.'</td></tr>
        <tr>
            <td class="space"></td>
            <td align="center" width="30" class="header" rowspan="2">NO</td>
            <td align="center" width="120" class="header" rowspan="2">JOB NO</td>
            <td align="center" width="170" class="header" rowspan="2">NO.REF</td>
            <td align="center" width="90" class="header" rowspan="2">TGL REF</td>
            <td align="center" width="90" class="header" rowspan="2">CURR</td>
            <td align="center" width="120" class="header" rowspan="2">BL NO.</td>
            <td align="center" width="120" class="header" rowspan="2">RECEIVED FROM</td>
            <td align="center" width="240" class="header" colspan="2">AMOUNT</td>
            <td align="center" width="120" class="header" rowspan="2">PAID</td>
            <td align="center" width="120" class="header" rowspan="2">REMARKS</td>
        </tr>
        <tr>
            <td class="space"></td>
            <td class="header" align="center">IDR</td>
            <td class="header" align="center">USD</td>
        </tr>
        <tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
        echo '</table>';
    }
}
