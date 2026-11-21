<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Laporan_shipment extends CI_Controller
{
    var $title = 'Laporan Shipment';
    
    public function __construct(){
        parent::__construct();
        $this->fungsi->restrict();
        $this->load->model('report/m_laporan_shipment');
        $this->fungsi->dbx = $this->m_laporan_shipment->dbx;
    }

    function index(){
        $this->fungsi->auth(55, $this->title);
        $data['title'] = $this->title;
        $data['menu_navigation'] = 'navigation';
        $data['main_view'] = 'report/template-report';
        $this->load->view('afg_template', $data);
    }
    
    function open_form() {  
        $this->load->view('report/v_laporan_shipment');
    }
    
    // get_proses() dihapus karena alur baru langsung melempar $_GET dari View ke to_excel / to_pdf

    function to_excel() {   
        // Ambil dari GET, jika kosong ambil dari POST (fleksibel)
        $bulan = $this->input->get('bulan') ? $this->input->get('bulan') : $this->input->post('bulan'); 
        $tahun = $this->input->get('tahun') ? $this->input->get('tahun') : $this->input->post('tahun');
        
        $data = $this->m_laporan_shipment->get_report_data($bulan);
        
        if(empty($data)) {
            die("<center><h3>Data tidak ditemukan untuk periode ini.</h3></center>");
        }

        $periode = $this->fungsi->bulan3($bulan).' '.$tahun;
        
        header("Cache-Control: no-cache, no-store, must-revalidate");  
        header("Content-Type: application/vnd.ms-excel");  
        header("Content-Disposition: attachment; filename=laporan_shipment_$periode.xls");      
        
        echo link_tag('public/theme/css/report.css', 'stylesheet', 'text/css').
        '<table summary="" width="100%" align="center">
        <tr><td class="space"></td><td class="judul" colspan="10">LAPORAN RL PER JOB PERIODE : '.$periode.'</td></tr>
        <tr>
            <td class="space"></td>
            <td align="center" width="40" class="header">NO</td>
            <td align="center" width="120" class="header">JOB NO #</td>
            <td align="center" width="80" class="header">ETD/ETA</td>
            <td align="center" width="180" class="header">DESTINATION</td>
            <td align="center" width="100" class="header">KURS</td>
            <td align="center" width="100" class="header">TYPE</td>
            <td align="center" width="120" class="header">INCOME USD</td>
            <td align="center" width="120" class="header">INCOME IDR</td>
            <td align="center" width="120" class="header">TOTAL INCOME</td>
            <td align="center" width="120" class="header">COST USD</td>
            <td align="center" width="120" class="header">COST IDR</td>
            <td align="center" width="120" class="header">TOTAL COST</td>
            <td align="center" width="120" class="header">PROFIT/LOSE</td>
        </tr>
        <tr><td style="border:none; height: 5px" colspan="11"></td></tr>';
        
        $i = 1; 
        
        // Variabel Penampung Total
        $tot_income_rp = 0; $tot_income_usd = 0; $tot_income_all = 0;
        $tot_cost_rp = 0; $tot_cost_usd = 0; $tot_cost_all = 0;
        $tot_profit = 0;
        foreach($data as $row) {
            $imp = substr($row['kode_ref'], 4, 3);
            $dest_cond = array('300','301','302','400','402','600');
            
            $destination = in_array($imp, $dest_cond) ? $row['origin'] : $row['destination'];

            // Gunakan langsung nilai dari database, persis seperti kode asli Anda
            $total_income = $row['total_income']; 
            $total_cost   = $row['total_cost'];     
            $profit_loss  = $row['total_profit'];
            $revenue_usd  = $row['income_usd'];

            echo '<tr>
                <td class="space"></td>
                <td align="center">'.$i.'</td>
                <td>'.$row['kode_ref'].'</td>
                <td>'.$this->fungsi->jin_date_str($row['etd']).'</td>
                <td>'.$destination.'</td>
                <td>'.$this->fungsi->pecah($row['kurs'],',').'</td>
                <td align ="center">'.strtoupper($row['type'])." / ".$row['type_cont_size'].'</td>
                <td align ="right">'.$this->fungsi->pecah($revenue_usd,',').'</td>
                <td align ="right">'.$this->fungsi->pecah($row['income_rp'],',').'</td>
                <td align ="right">'.$this->fungsi->pecah($total_income,',').'</td>
                <td align ="right">'.$this->fungsi->pecah($row['cost_usd'],',').'</td>
                <td align ="right">'.$this->fungsi->pecah($row['cost_rp'],',').'</td>
                <td align ="right">'.$this->fungsi->pecah($total_cost,',').'</td>
                <td align ="right">'.$this->fungsi->pecah($profit_loss,',').'</td>
            </tr>';
            
            // Akumulasi Total Grand
            $tot_income_rp += $row['income_rp'];
            $tot_income_usd += $row['income_usd'];
            $tot_income_all += $total_income;
            $tot_cost_rp += $row['cost_rp'];
            $tot_cost_usd += $row['cost_usd'];
            $tot_cost_all += $total_cost;
            $tot_profit += $profit_loss;
            
            $i++;   
        }
        
        echo '<tr><td style="border:none; height: 5px" colspan="11"></td></tr>
            <tr>
                <td class="space"></td>
                <td align="center" width="460" class="header" colspan="6">TOTAL</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_income_usd).'</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_income_rp).'</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_income_all).'</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_cost_usd).'</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_cost_rp).'</td>
                <td align="right" width="80" class="header">'.$this->fungsi->pecah($tot_cost_all).'</td>
                <td align="right" width="120" class="header">'.$this->fungsi->pecah($tot_profit).'</td>
            </tr>
        </table>';
        exit();
    }
    
    function to_pdf() {
        // Lakukan hal yang sama persis untuk to_pdf
        $bulan = $this->input->get('bulan') ? $this->input->get('bulan') : $this->input->post('bulan'); 
        $tahun = $this->input->get('tahun') ? $this->input->get('tahun') : $this->input->post('tahun');

        $data = $this->m_laporan_shipment->get_report_data($bulan);

        if(empty($data)) {
            die("<center><h3>Data tidak ditemukan untuk periode ini.</h3></center>");
        }

        $this->load->library('FPDF_MC_Table');
        $pdf = new FPDF_MC_Table();     
        
        $nama_bulan = $this->fungsi->bulan2($bulan);
        $pdf->setDataHeader(array('report'=>'14','periode'=>$nama_bulan.' '.$tahun));
        
        $pdf->AddPage('L','A4');    
        $pdf->AliasNbPages();       
        $pdf->SetFont('Arial','',8);
        $pdf->SetWidths(array(10,27,20,30,15,15,15,30,25,15,30,25,24));
        $pdf->SetAligns(array('C','L','L','L','C','C','R','R','R','R','R','R','R'));
        
        $i = 1; 
        
        // Penampung Total
        $tot_income_rp = 0; $tot_income_usd = 0; $tot_income_all = 0;
        $tot_cost_rp = 0; $tot_cost_usd = 0; $tot_cost_all = 0;
        $tot_profit = 0;

        foreach($data as $row) {
            $imp = substr($row['kode_ref'], 4, 3);
            $dest_cond = array('300','301','302','400','402','600');
            
            $destination = in_array($imp, $dest_cond) ? $row['origin'] : $row['destination'];
            
            // Variabel $kurs tetap dibiarkan untuk mencegah error jika sewaktu-waktu nilainya kosong di database
            $kurs = ($row['kurs'] > 0) ? $row['kurs'] : 1; 

            // Gunakan nilai total langsung dari database (seperti perbaikan di Excel)
            $total_income = $row['total_income']; 
            $total_cost   = $row['total_cost'];     
            $profit_loss  = $row['total_profit'];

            $pdf->Row(array(
                $i,
                $row['kode_ref'],
                $this->fungsi->jin_date_str($row['etd']),
                substr($destination??'',0,12),
                $this->fungsi->pecah($row['kurs'], ','),
                strtoupper($row['type']),
                $this->fungsi->pecah($row['income_usd'], ','),
                $this->fungsi->pecah($row['income_rp'], ','),
                $this->fungsi->pecah($total_income, ','),
                $this->fungsi->pecah($row['cost_usd'], ','),
                $this->fungsi->pecah($row['cost_rp'], ','),
                $this->fungsi->pecah($total_cost, ','),
                $this->fungsi->pecah($profit_loss, ',') 
            ));
            
            // Akumulasi Total Grand
            $tot_income_rp += $row['income_rp'];
            $tot_income_usd += $row['income_usd'];
            $tot_income_all += $total_income;
            $tot_cost_rp += $row['cost_rp'];
            $tot_cost_usd += $row['cost_usd'];
            $tot_cost_all += $total_cost;
            $tot_profit += $profit_loss;

            $i++;
        }
        
        $pdf->Ln(1);
        $pdf->SetFillColor(230,230,230);
        $pdf->SetFont('Arial','B',7);
        
        // Lebar Cell pertama (117) adalah gabungan kolom (NO s/d TYPE) = 10+27+20+30+15+15
        $pdf->Cell(117, 5, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell(25, 5, $this->fungsi->pecah($tot_income_usd), 1, 0, 'R', true);
        $pdf->Cell(20, 5, $this->fungsi->pecah($tot_income_rp), 1, 0, 'R', true);
        $pdf->Cell(25, 5, $this->fungsi->pecah($tot_income_all), 1, 0, 'R', true);
        $pdf->Cell(25, 5, $this->fungsi->pecah($tot_cost_usd), 1, 0, 'R', true);
        $pdf->Cell(20, 5, $this->fungsi->pecah($tot_cost_rp), 1, 0, 'R', true);
        $pdf->Cell(25, 5, $this->fungsi->pecah($tot_cost_all), 1, 0, 'R', true);
        $pdf->Cell(24, 5, $this->fungsi->pecah($tot_profit), 1, 1, 'R', true);
        
        $pdf->Output('laporan_shipment_'.$nama_bulan.'_'.$tahun.'.pdf', 'I');
    }
}