<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cetak_bl extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('m_bill_of_ladding'));
        $this->load->library('session');
        // jika ada helper atau library lain yang diperlukan, load di sini
        // $this->load->helper('string');
    }

    // Endpoint: /cetak_bl?spd=...&bl=...&cetak=...&code=...
    public function index() {
        // ambil parameter dari query string
        $spd   = $this->input->get('spd');
        $cetak = $this->input->get('cetak');
        $code  = $this->input->get('code');

        // ambil data dari model
        $respon = $this->m_bill_of_ladding->collect_data_draft($spd);
        if (empty($respon) || !isset($respon[0])) {
            // jika tidak ada data, hentikan dengan pesan sederhana (atau bisa redirect/404)
            show_error('Data not found', 404);
            return;
        }
        $data = $respon[0];
		$bl_no =  $data->house_bl;
        // load library PDF
        $this->load->library('FPDF_MC_Table');
        $pdf = new FPDF_MC_Table();
        $pdf->AliasNbPages();

        // pilih template halaman
        if ($code == 1 && $cetak === 'FALSE') {
            $pdf->AddPage('P','polos');
        } else {
            $pdf->AddPage('P','A4');
            $pdf->SetFont('Arial','',8);
        }

        // fungsi konversi encoding untuk teks yang akan ditulis ke FPDF
        // diletakkan di dalam method agar bisa dipanggil
        $convert_fn = function($text) {
            if ($text === null) return '';
            // Hapus karakter invisible
            $text = preg_replace('/[\x{200E}\x{200F}\x{202C}\x{FEFF}]/u', '', $text);
            // Konversi ke ISO-8859-1 dengan translit dan ignore
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
            if ($converted === false) return $text;
            return $converted;
        };

        // mulai menulis konten sesuai logic asli
        if ($code == 1) {
            if ($cetak === 'TRUE') {
                // background / header image
                $pdf->Image(APPPATH.'third_party/img/gateway_new.jpg',5,5,+200);

                // watermark jika library FPDF_MC_Table menyediakan method Watermark
                // jika tidak tersedia, method ini harus ada di class FPDF_MC_Table
                if (method_exists($pdf, 'Watermark')) {
                    $pdf->Watermark('DRAFT');
                }

                $pdf->SetFont('Arial','',8);

                // Note
                $pdf->SetXY(10, 22);
                // shipper
				$pdf->SetXY(10,22);
				$pdf->MultiCell(80,3,strtoupper($data->shipper_name),0,'L',false);
				$pdf->SetXY(10,25);
				$pdf->MultiCell(80,3,strtoupper($data->shipper_address),0,'L',false);

                // BL No (tampilkan BL number)
                $pdf->SetXY(152, 23);
                $pdf->SetFont('Arial','B',10);
                $pdf->write(0, str_replace('%20',' ',$bl_no));

                // Consignee
                $pdf->SetFont('Arial','',8);
                $pdf->SetXY(10, 48);
                $pdf->MultiCell(90,3,strtoupper($convert_fn($data->consignee ?? '')),0,'L');

                // Notify
                $pdf->SetXY(10, 74);
                $pdf->MultiCell(90,3, strtoupper($convert_fn($data->notify ?? '')),0,'L');

                // POL & POL duplicate
                $pol_text = $data->pol_bl ?? $data->origin_city ?? '';
                $pdf->SetXY(10, 103);
                $pdf->write(0, $pol_text);
                $pdf->SetXY(57, 103);
                $pdf->write(0, $pol_text);

                // Vessel
                $pdf->SetXY(10, 113);
                $pdf->write(0, $data->vessel ?? '');

                // POD & duplicate
                $pod_text = $data->pod_bl ?? $data->destination_city ?? '';
                $pdf->SetXY(106, 113);
                $pdf->write(0, $pod_text);
                $pdf->SetXY(154, 113);
                $pdf->write(0, $pod_text);

                // For delivery / consignee for delivery
                $pdf->SetXY(113, 74);
                $pdf->MultiCell(90,3, $data->for_delivery ?? '', 0, 'L');

                // Marking & Description handling
                $marking_text = $data->marking ?? '';
                $description_of_goods_text = $data->description_of_goods ?? '';

                // Hitung baris yang dibutuhkan untuk multicell (menggunakan helper di bawah)
                $attc_marks = $this->getMulticellLineCount($pdf, 39, $marking_text);
                $attc_discription = $this->getMulticellLineCount($pdf, 60, $description_of_goods_text);

                if ($attc_marks > 18 || $attc_discription > 15) {
                    $pdf->SetXY(12, 157);
                    // pastikan font tersedia
                    $pdf->SetFont('Verdana', 'B', 10);
                    $pdf->MultiCell(160, 5, 'MARKING AND DESCRIPTION ARE ATTACHED IN THE NEXT PAGE', 0, 'L');
                } else {
                    $pdf->SetFont('Arial','',8);
                    $pdf->SetXY(10, 128);
                    $pdf->MultiCell(39, 3, $marking_text, 0, 'L', false);
                    $pdf->SetXY(80, 133);
                    $pdf->MultiCell(60,3,strtoupper($description_of_goods_text),0,'L');
                }

                // Container / Seal / Type
                $pdf->SetFont('Arial','',8);
                $pdf->SetXY(10, 182);
                $pdf->write(0, 'CONTAINER/SEAL/TYPE');
                $pdf->SetXY(10, 184);
                $pdf->MultiCell(45, 3, $data->container_no ?? '');

                // Quantity & package type
                $pdf->SetXY(56, 130);
                $pdf->MultiCell(20,3, ($data->quantity ?? '') . ' ' . ($data->package_type ?? ''));

                // Quantity in words (menggunakan fungsi helper $this->fungsi->num_to_words jika tersedia)
                $qty_words = '';
                if (isset($this->fungsi) && method_exists($this->fungsi, 'num_to_words')) {
                    $qty_words = $this->fungsi->num_to_words($data->quantity ?? 0,'',0,'');
                }
                $pdf->SetXY(56, 194);
                $pdf->MultiCell(120,3, ($data->quantity ?? '') . ' ( ' . $qty_words . ' ) ' . ($data->package_type ?? '') . ' ONLY');

                // EXPRESS RELEASE badge
                if (($data->number_of_bl ?? '') === "EXPRESS RELEASE") {
                    $pdf->SetXY(99, 187);
                    $pdf->SetDrawColor(255, 0, 0);
                    $pdf->SetTextColor(255, 0, 0);
                    $pdf->SetFont('Arial', 'B', 15);
                    $text = 'EXPRESS RELEASE';
                    $w = $pdf->GetStringWidth($text) + 10;
                    $h = 10;
                    $pdf->Cell($w, $h, $text, 1, 0, 'C');
                    $pdf->SetTextColor(0, 0, 0);
                }

                // Shipped on board block
                if (isset($data->shipped_on_board) && intval($data->shipped_on_board) == 1) {
					$pdf->SetFont('Arial','',8);
                    $pdf->SetXY(130, 165);
                    $pdf->write(0, 'SHIPPED ON BOARD');
                    $from_text = $data->pol_bl ?? $data->origin_city ?? '';
                    $pdf->SetXY(130, 168);
                    $pdf->write(0, 'FROM ' . $from_text);
                    $pdf->SetXY(130, 171);
                    $pdf->write(0, 'ON ' . ($data->expect_etd ?? ''));
                    $pdf->SetXY(130, 174);
                    $pdf->write(0, 'BY ' . ($data->vessel ?? ''));
                }

                // GW / NW / MEAS
				$pdf->SetFont('Arial','',8);
                $pdf->SetXY(158, 135);
                $pdf->write(0, 'GW :');
                $pdf->SetXY(177, 134);
                $pdf->Cell(13,2, $data->weight ?? '', 0, 0, 'R', false, '');
                $pdf->SetXY(188, 135);
                $pdf->write(0, ' KGS');

                if (!empty($data->netto)) {
                    $pdf->SetXY(158, 140);
                    $pdf->write(0,'NW : ');
                    $pdf->SetXY(177, 139);
                    $pdf->Cell(13,2, $data->netto ?? '', 0, 0, 'R', false, '');
                    $pdf->SetXY(188, 140);
                    $pdf->write(0, ' KGS');

                    $pdf->SetXY(158, 145);
                    $pdf->write(0,'MEAS : ');
                    $pdf->SetXY(177, 144);
                    $pdf->Cell(13,2, $data->volume ?? '', 0, 0, 'R', false, '');
                    $pdf->SetXY(188, 145);
                    $pdf->write(0, ' M');
                    $pdf->SetXY(191, 144);
                    $pdf->SetFont('Arial','',6);
                    $pdf->write(0, ' 3');
                } else {
                    $pdf->SetXY(158, 140);
                    $pdf->write(0,'MEAS : ');
                    $pdf->SetXY(177, 139);
                    $pdf->Cell(13,2, $data->volume ?? '', 0, 0, 'R', false, '');
                    $pdf->SetXY(188, 140);
                    $pdf->write(0, ' M');
                    $pdf->SetXY(191, 139);
                    $pdf->SetFont('Arial','',6);
                    $pdf->write(0, ' 3');
                }

                // Freight label
                $pdf->SetFont('Arial','',8);
                $pdf->SetXY(22, 209);
                $pdf->write(0, 'FREIGHT');

                // Type of Movement label and value (centered under label)
                $x_label = 17;    // Posisi X label
                $y_label = 251;   // Posisi Y label
                $label = "Type of Movement :";
                $pdf->SetFont('Arial','',8);
                $pdf->SetXY($x_label, $y_label);
                $pdf->Write(0, $label);

                $value = $data->movement_type ?? '';
                $label_width = $pdf->GetStringWidth($label);
                $value_width = $pdf->GetStringWidth($value);
                $x_value = $x_label + ($label_width - $value_width) / 2;
                $y_value = $y_label + 4;
                $pdf->SetXY($x_value, $y_value);
                $pdf->Write(0, $value);

                // Freight prepaid/collect placement
                if (($data->freight ?? '') === "PREPAID") {
                    $pdf->SetXY(60, 209);
                    $pdf->write(0, $data->freight);
                } else {
                    $pdf->SetXY(88, 209);
                    $pdf->write(0, $data->freight ?? '');
                }

                // Payable at
                $pdf->SetXY(54, 251);
                $pdf->write(0,'Payable at');
                $pdf->SetXY(55, 255);
                $pdf->write(0, $data->payable ?? '');

                // Number of BL display
                if (($data->number_of_bl ?? '') === "3 ORIGINAL") {
                    $pdf->SetXY(113, 209);
                    $pdf->MultiCell(30,3,'3(THREE) ORIGINAL',0,'','');
                }

                // Region and ETD
                $region = $this->session->userdata('region_id') ?? '';
                $pdf->SetXY(140, 248);
                $pdf->Write(0, strtoupper($region . ', ' . ($data->expect_etd ?? '')));

                // Company / signature block
                $pdf->SetXY(125, 267);
                $pdf->write(0,'PT GATEWAY CONTAINER LINE');
                if (!empty($data->as_agent)) {
                    $pdf->SetXY(125, 269);
                    $pdf->MultiCell(60,3,strtoupper($data->as_agent),0,'L');
                }

                // Output PDF (nama file random + draft)
                $pdf->Output(rand().' Draft ' . ($data->house_bl ?? '') . '.pdf','I');
                return;
            } // end if cetak TRUE

            // jika code==1 tapi cetak bukan TRUE, tetap output minimal
            $pdf->Output('I', 'BL_'.$bl_no.'.pdf');
            return;
        } // end if code==1

        // jika code != 1, fallback output
        $pdf->Output('I', 'BL_'.$bl_no.'.pdf');
        return;
    }

    /**
     * Helper untuk menghitung berapa baris yang akan dipakai oleh MultiCell
     * $pdf : instance FPDF
     * $w   : lebar cell dalam mm
     * $txt : teks yang akan diukur
     *
     * Mengembalikan jumlah baris yang diperlukan.
     */
    /**
 * Hitung jumlah baris yang akan dipakai oleh MultiCell tanpa mengakses properti protected FPDF.
 *
 * @param FPDF $pdf  Instance FPDF/FPDF_MC_Table (pastikan font sudah diset sebelum memanggil)
 * @param float $w   Lebar cell dalam mm
 * @param string $txt Teks yang akan diukur
 * @return int Jumlah baris yang diperlukan
 */
private function getMulticellLineCount($pdf, $w, $txt) {
    if ($txt === null || $txt === '') return 0;

    // Pastikan font sudah diset (caller harus memanggil $pdf->SetFont(...) sebelumnya)
    // Konversi CRLF ke LF
    $s = str_replace("\r", '', $txt);
    $lines = explode("\n", $s);

    $totalLines = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $totalLines += 1;
            continue;
        }

        // Pecah menjadi kata-kata
        $words = preg_split('/\s+/', $line);
        $current = '';
        foreach ($words as $i => $word) {
            $test = $current === '' ? $word : ($current . ' ' . $word);
            // GetStringWidth mengembalikan lebar dalam unit user (mm jika unit mm)
            $testWidth = $pdf->GetStringWidth($test);
            // Jika lebar melebihi cell width, maka wrap: kata baru di baris berikutnya
            if ($testWidth > $w) {
                if ($current === '') {
                    // kata sendiri lebih panjang dari lebar cell -> pecah per karakter
                    $charLine = '';
                    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($chars as $ch) {
                        $testChar = $charLine . $ch;
                        if ($pdf->GetStringWidth($testChar) > $w) {
                            $totalLines++;
                            $charLine = $ch;
                        } else {
                            $charLine = $testChar;
                        }
                    }
                    // setelah memproses kata panjang, set current kosong (karena sudah di baris baru)
                    $current = '';
                } else {
                    // pindah baris, kata menjadi awal baris baru
                    $totalLines++;
                    $current = $word;
                    // jika kata itu sendiri masih lebih panjang dari lebar, pecah per karakter
                    if ($pdf->GetStringWidth($current) > $w) {
                        $charLine = '';
                        $chars = preg_split('//u', $current, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($chars as $ch) {
                            $testChar = $charLine . $ch;
                            if ($pdf->GetStringWidth($testChar) > $w) {
                                $totalLines++;
                                $charLine = $ch;
                            } else {
                                $charLine = $testChar;
                            }
                        }
                        $current = '';
                    }
                }
            } else {
                // masih muat di baris saat ini
                $current = $test;
            }
            // jika ini kata terakhir, hitung baris yang tersisa
            if ($i === count($words) - 1 && $current !== '') {
                $totalLines++;
            }
        }
    }

    return max(1, (int)$totalLines);
}
}