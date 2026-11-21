<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cetak_bc extends CI_Controller {

    public function __construct() {
        parent::__construct();
         $this->load->model(array('api/m_instant_booking','m_bill_of_ladding','m_print_out_bl','pricelist/booking_model'));
        $this->load->library('session');
    }

    // Endpoint: /cetak_bc?booking_number=XYZ&warehouse=...
    public function index() {
        // include library QR
       //include the libraries
		require_once("pdf/phpqrcode/qrlib.php");

        // ambil parameter dari query string
        $si        = $this->input->get('booking_code');
        $warehouse = $this->input->get('warehouse');

        // load library PDF
        $this->load->library('FPDF_MC_Table');
        $pdf = new FPDF_MC_Table();
        $pdf->AddPage('P','A4');
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(false);

        // ambil data dari model
        $booking = $this->booking_model->find($si);
        $wh      = $this->booking_model->get_warehouse_detail($warehouse);
        $detail  = $this->booking_model->collect_data_booking($si,$si);

        // background template
        $pdf->Image(APPPATH.'third_party/img/BC.jpg',0,0,+210);

        // contoh isi header
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(147, 33);
        $pdf->Write(0, $detail['booking']->house_bl);

        $pdf->SetFont('Arial','',7);
        $pdf->SetXY(60, 42);
        $pdf->Write(0, $detail['booking']->mode);

        $pdf->SetXY(60, 46.5);
        $pdf->Write(0, $detail['booking']->booking_code);

        $pdf->SetXY(60, 51);
        $pdf->Write(0, date("Y/m/d"));

        $pdf->SetXY(60, 55.5);
        $pdf->Write(0, $detail['booking']->closing_date);

        $pdf->SetXY(60, 65);
        $pdf->Write(0, $detail['booking']->si_number);

        // shipper
        $pdf->SetXY(12,77);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->shipper_name),0,'L',false);
        $pdf->SetXY(12,80);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->shipper_address),0,'L',false);

        // consignee
        $pdf->SetXY(110,75);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->consignee_name),0,'L',false);
        $pdf->SetXY(110,78);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->consignee),0,'L',false);

        // notify
        $pdf->SetXY(12,120);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->notify_name),0,'L',false);
        $pdf->SetXY(12,123);
        $pdf->MultiCell(80,3,strtoupper($detail['detail']->notify),0,'L',false);

        // vessel & voyage
        $pdf->SetXY(110, 123);
        $pdf->Write(0, strtoupper($detail['booking']->vessel));
        $pdf->SetXY(155, 123);
        $pdf->Write(0, strtoupper($detail['booking']->voyage));

        // etd & eta
        $pdf->SetXY(110, 132);
        $pdf->Write(0, $detail['booking']->expect_etd);
        $pdf->SetXY(155, 132);
        $pdf->Write(0, $detail['booking']->eta);

        // pol & pod
        $pdf->SetXY(110, 140);
        $pdf->Write(0, $detail['booking']->origin_city);
        $pdf->SetXY(155, 140);
        $pdf->Write(0, $detail['booking']->destination_city);

        // BL, qty, weight, volume
        $pdf->SetXY(12, 149);
        $pdf->Write(0, $detail['booking']->number_of_bl);
        $pdf->SetXY(75, 149);
        $pdf->Write(0, $detail['detail']->quantity." ".$detail['detail']->package_type);
        $pdf->SetXY(110, 149);
        $pdf->Write(0, $detail['detail']->weight." KGs");
        $pdf->SetXY(140, 149);
        $pdf->Write(0, $detail['detail']->netto." KGs");
        $pdf->SetXY(172, 149);
        $pdf->Write(0, $detail['detail']->volume." M3");

        // freight & incoterm
        $pdf->SetXY(12, 157);
        $pdf->Write(0, $detail['booking']->freight);
        $pdf->SetXY(110, 157);
        $pdf->Write(0, $detail['booking']->incoterm);

        // marking & description
        $pdf->SetXY(12, 165);
        $pdf->MultiCell(60, 3, strtoupper($detail['detail']->marking), 0, 'L');
        $pdf->SetXY(110, 165);
        $pdf->MultiCell(60, 3, strtoupper($detail['detail']->description_of_goods), 0, 'L');

        // warehouse
        $pdf->SetXY(15, 220);
        $pdf->Write(0, $wh['warehouse_name']);
        $pdf->SetXY(15, 223);
        $pdf->MultiCell(80,3,$wh['warehouse_address']);
		$pdf->SetXY(115, 220);
		$pdf->write(0,'NPWP : '.$wh['npwp']);
		$pdf->SetXY(115, 222);
		$pdf->multiCell(100,3,'NITKU : '.$wh['nitku']);
		$pdf->SetXY(115, 225);
		$pdf->multiCell(80,3,'ALAMAT : '.$wh['alamat']);
		$pdf->SetXY(11, 271);
	
		$pdf->write(0,'SEA');
		
		$pdf->SetXY(30, 271);
		$pdf->write(0,$detail['booking']->vessel);
		$pdf->SetXY(85, 271);
		$pdf->write(0,$detail['booking']->origin_city);
		if(!empty($detail['booking']->trans_city) && $detail['booking']->trans_city != $detail['booking']->destination_city){
		$pdf->SetXY(126, 271);
		$pdf->write(0,$detail['booking']->trans_city);
		$pdf->SetXY(11, 275);
	
		$pdf->write(0,'SEA');
		$pdf->SetXY(30, 275);
		$pdf->write(0,$detail['booking']->voyage);
		$pdf->SetXY(85, 275);
		$pdf->write(0,$detail['booking']->trans_city);
		$pdf->SetXY(126, 275);
		$pdf->write(0,$detail['booking']->destination_city);
		$pdf->SetXY(157, 271);
		$pdf->write(0,$detail['booking']->expect_etd);
		$pdf->SetXY(177, 275);
		$pdf->write(0,$detail['booking']->eta);
		}else{
		$pdf->SetXY(126, 271);
		$pdf->write(0,$detail['booking']->destination_city);
		$pdf->SetXY(157, 271);
		$pdf->write(0,$detail['booking']->expect_etd);
		$pdf->SetXY(177, 271);
		$pdf->write(0,$detail['booking']->eta);
		}

        // signature
        $pdf->SetXY(11,287);
        //$pdf->Write(0,$this->session->userdata('fullname'));

        // QR code
        $pdf->Image("http://localhost/pdf/qr_generator.php?code=$si", 160, 278, 18, 18, "png");

        // set header agar browser tahu ini PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="BC_'.$si.'.pdf"');

        // output PDF dan hentikan eksekusi
        $pdf->Output('I', "BC_".$si.".pdf");
        exit;
    }
}