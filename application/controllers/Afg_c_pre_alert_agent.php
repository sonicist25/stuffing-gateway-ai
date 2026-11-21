<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Afg_c_pre_alert_agent extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->fungsi->restrict();

        $this->load->model(array(
            'M_pre_alert_agent',
            'm_bill_of_ladding',
            'm_print_out_bl',
            'afg_m_stuffing_plan',
            'finance/m_finance_proses',
            'finance/m_inv_to_shipper',
        ));

        $this->dbx = $this->M_pre_alert_agent->dbx;
        $this->fungsi->dbx = $this->dbx;
    }

    public function open_form()
    {
        $job_no = trim((string)$this->input->post('job_no', true));
        if ($job_no === '') return $this->plainError('Job number kosong', 400);

        $header = $this->M_pre_alert_agent->get_header($job_no);
        if (!$header) return $this->plainError('Stuffing plan tidak ditemukan / region tidak sesuai', 404);

        $details = $this->M_pre_alert_agent->get_house_bls($job_no, $header->sp_id);
        $agents  = $this->M_pre_alert_agent->get_agents($job_no, $header->agen_penagihan);

        $this->load->view('pre_alert/v_pre_alert_agent', array(
            'header'  => $header,
            'details' => $details,
            'agents'  => $agents,
        ));
    }

    public function document_summary()
    {
        $job_no     = trim((string)$this->input->post('job_no', true));
        $agent_code = trim((string)$this->input->post('agent_code', true));

        $header = $this->M_pre_alert_agent->get_header($job_no);
        if (!$header || $agent_code === '') {
            return $this->json(array('status' => false, 'message' => 'Job/agent invalid'));
        }

        $details = $this->M_pre_alert_agent->get_house_bls($job_no, $header->sp_id);
        $bundle  = $this->M_pre_alert_agent->get_finance_bundle($job_no, $agent_code);

        $dn = 0; $cn = 0;
        foreach ($bundle['posted'] as $row) {
            if (strtoupper($row->jenis) === 'DEBIT NOTE') $dn++;
            if (strtoupper($row->jenis) === 'CREDIT NOTE') $cn++;
        }

        return $this->json(array(
            'status'         => true,
            'hbl_count'      => count($details),
            'dn_count'       => $dn,
            'cn_count'       => $cn,
            'unposted_count' => count($bundle['unposted']),
        ));
    }

    public function download()
    {
        $job_no      = trim((string)$this->input->post('job_no', true));
        $agent_code  = trim((string)$this->input->post('agent_code', true));
        $instruction = trim((string)$this->input->post('instruction', true));

        if ($job_no === '' || $agent_code === '') {
            return $this->plainError('Job number dan agent wajib diisi', 400);
        }

        $header = $this->M_pre_alert_agent->get_header($job_no);
        if (!$header) return $this->plainError('Stuffing plan tidak ditemukan / region tidak sesuai', 404);

        $details = $this->M_pre_alert_agent->get_house_bls($job_no, $header->sp_id);
        if (!$details) return $this->plainError('Tidak ada House BL position pada job ini', 422);

        foreach ($details as $row) {
            if (trim((string)$row->hb_l) === '') {
                return $this->plainError('Pre Alert dibatalkan: position '.$row->num.' / '.$row->kode_ref.' belum memiliki House BL Number.', 422);
            }
        }

        $agent = $this->M_pre_alert_agent->get_agent($agent_code);
        if (!$agent) return $this->plainError('Agent tidak ditemukan pada region aktif', 422);

        $bundle = $this->M_pre_alert_agent->get_finance_bundle($job_no, $agent_code);

        if (!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', APPPATH.'libraries/font/');
        $this->load->library('FPDF_MC_Table');
        $this->load->library('House_bl_renderer');
        $this->load->library('Finance_note_renderer');

        $pdf = new FPDF_MC_Table();
        $pdf->AddFont('Calibri', '', 'Calibri.php');
        $pdf->AddFont('Calibri', 'B', 'Calibribold.php');
        $pdf->AddFont('DejaVu', '', 'dejavusans.php');
        $pdf->AliasNbPages();

        // 1) Pre Alert cover / job summary
        $this->renderCover($pdf, $header, $details, $agent, $instruction, $bundle);

        // 2) House BLs, in position order, using each saved x_form_bl template.
        foreach ($details as $row) {
            $this->house_bl_renderer->render(
                $pdf,
                (int)$row->sp_detail_id,
                (string)$row->hb_l,
                (int)$row->sp_id,
                'TRUE'
            );
        }

        // 3) All DN first, then 4) all CN (Posted and Unposted).
        $financeContext = $this->financeContext($header, $agent);
        foreach (array('DEBIT NOTE', 'CREDIT NOTE') as $kind) {
            foreach ($bundle['posted'] as $doc) {
                if (strtoupper((string)$doc->jenis) !== $kind) continue;
                try {
                    $this->finance_note_renderer->render($pdf, $doc, $financeContext);
                } catch (\Throwable $e) {
                    log_message('error', '[PRE_ALERT_FINANCE_RENDER_FAIL] job='.$job_no
                        .' agent='.$agent_code
                        .' finance_id='.(int)($doc->id ?? 0)
                        .' doc='.(string)($doc->number ?? '')
                        .' error='.$e->getMessage());
                    return $this->plainError(
                        'Pre Alert dibatalkan: detail DN/CN '.(string)($doc->number ?? '')
                        .' gagal dicetak. Periksa data Finance Process dan log server.',
                        422
                    );
                }
            }
        }

        // Build binary first. Audit is written only if PDF creation succeeded.
        $binary = $pdf->Output('S');
        if ($binary === false || $binary === null || $binary === '') {
            return $this->plainError('Gagal membentuk PDF Pre Alert', 500);
        }

        $dnCount = 0; $cnCount = 0;
        foreach ($bundle['posted'] as $doc) {
            if (strtoupper((string)$doc->jenis) === 'DEBIT NOTE') $dnCount++;
            if (strtoupper((string)$doc->jenis) === 'CREDIT NOTE') $cnCount++;
        }

        $filename = 'PRE-ALERT_'.$this->safeFile($job_no).'_'.$this->safeFile($agent_code).'_'.date('Ymd_His').'.pdf';

        if (!$this->M_pre_alert_agent->log_download(
            $job_no,
            $agent_code,
            $instruction,
            count($details),
            $dnCount,
            $cnCount,
            $filename
        )) {
            // Audit is required. Surface the actual DB error so deployment issues can be fixed precisely.
            $dbError = $this->M_pre_alert_agent->dbx->error();
            $dbCode  = isset($dbError['code']) ? (string)$dbError['code'] : '';
            $dbMsg   = isset($dbError['message']) ? trim((string)$dbError['message']) : '';
            log_message('error', 'PRE_ALERT_DOWNLOAD audit failed. job='.$job_no.' agent='.$agent_code.' db_code='.$dbCode.' db_message='.$dbMsg);

            $detail = $dbMsg !== '' ? ' MySQL '.$dbCode.': '.$dbMsg : '';
            return $this->plainError('PDF berhasil dibuat tetapi audit x_timeline_audit gagal disimpan. Download dibatalkan.'.$detail, 500);
        }

        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($binary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $binary;
        exit;
    }

    private function renderCover($pdf, $header, $details, $agent, $instruction, $bundle)
    {
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage('P', 'A4');

        $logo = APPPATH.'third_party/img/logo.png';
        if (file_exists($logo)) $pdf->Image($logo, 10, 10, 38);

        $pdf->SetTextColor(0, 36, 94);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(55, 12);
        $pdf->Cell(145, 7, 'PRE ALERT TO AGENT', 0, 1, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(55, 20);
        $pdf->Cell(145, 5, 'PT. GATEWAY CONTAINER LINE', 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetDrawColor(0, 36, 94);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, 31, 200, 31);

        $pairs = array(
            array('Job No.', $header->kode_ref, 'Agent', $agent->agent_company_code.' - '.$agent->agent_company_name),
            array('Master BL', $header->mother_bl, 'Total Pos', count($details)),
            array('Vessel / Voyage', trim($header->vessel.' / '.$header->voyage, ' /'), 'POL', $header->port_loading),
            array('ETD / ETA', trim($header->etd_jkt.' / '.$header->eta, ' /'), 'POD', $header->destination),
            array('Container / Seal', $header->cont_seal_no, 'Container Type', trim($header->type_cont_qty.' '.$header->type_cont_size)),
        );

        $y = 36;
        foreach ($pairs as $p) {
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY(10,$y); $pdf->Cell(28,5,$p[0],0,0,'L');
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(38,$y); $pdf->Cell(63,5,(string)$p[1],0,0,'L');
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY(105,$y); $pdf->Cell(25,5,$p[2],0,0,'L');
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(130,$y); $pdf->Cell(70,5,(string)$p[3],0,1,'L');
            $y += 6;
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetTextColor(0,36,94);
        $pdf->Cell(190,6,'HOUSE BL SUMMARY',0,1,'L');
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(0,36,94);
        $pdf->SetFont('Arial','B',6.5);

       $w = array(
                8,   // POS
                27,  // DESTINATION
                35,  // HOUSE BL
                24,  // HS CODE
                22,  // PARTY
                39,  // SHIPPER
                13,  // TERM
                22   // RELEASE MODE
            );
        foreach (array('POS','DESTINATION','HOUSE BL','HS CODE','PARTY','SHIPPER','TERM','RELEASE MODE') as $i=>$h) {
            $pdf->Cell($w[$i],6,$h,1,0,'C',true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',6.2);

        foreach ($details as $r) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage('P','A4');
                $pdf->SetFillColor(0,36,94); $pdf->SetTextColor(255,255,255); $pdf->SetFont('Arial','B',6.5);
                foreach (array('POS','DESTINATION','HOUSE BL','HS CODE','PARTY','SHIPPER','TERM','RELEASE MODE') as $i=>$h) $pdf->Cell($w[$i],6,$h,1,0,'C',true);
                $pdf->Ln(); $pdf->SetTextColor(0,0,0); $pdf->SetFont('Arial','',6.2);
            }
               // ==========================
                // PARTY
                // ==========================
                $party = trim(
                    (string)$r->no_pack_qty . ' ' .
                    (string)$r->no_pack_unit
                );

                // ==========================
                // SHIPPER
                // ==========================
                $shipper = isset($r->shipper_print)
                    ? trim((string)$r->shipper_print)
                    : '';

                // ==========================
                // RELEASE MODE
                // ==========================
                $expressRelease = isset($r->express_release)
                    ? strtoupper(trim((string)$r->express_release))
                    : '';

                $releaseMode = in_array(
                    $expressRelease,
                    array('TRUE', '1', 'YES', 'Y'),
                    true
                )
                    ? 'EXPRESS'
                    : 'ORIGINAL';

                // ==========================
                // DATA ROW
                // ==========================
                $vals = array(
                    $r->num,          // POS
                    $r->destination,  // DESTINATION
                    $r->hb_l,         // HOUSE BL
                    $r->hs_code,      // HS CODE
                    $party,           // PARTY
                    $shipper,         // SHIPPER
                    $r->incoterms,    // TERM
                    $releaseMode      // RELEASE MODE
                );

                foreach ($vals as $i => $v) {

                    // panjang text disesuaikan per kolom
                    switch ($i) {

                        case 0: // POS
                            $maxChar = 3;
                            break;

                        case 1: // DESTINATION
                            $maxChar = 22;
                            break;

                        case 2: // HOUSE BL
                            $maxChar = 27;
                            break;

                        case 3: // HS CODE
                            $maxChar = 18;
                            break;

                        case 4: // PARTY
                            $maxChar = 15;
                            break;

                        case 5: // SHIPPER
                            $maxChar = 28;
                            break;

                        case 6: // TERM
                            $maxChar = 10;
                            break;

                        case 7: // RELEASE MODE
                            $maxChar = 15;
                            break;

                        default:
                            $maxChar = 30;
                    }

                    $text = $this->fitText(
                        (string)$v,
                        $maxChar
                    );

                    $align = ($i === 0 || $i === 3 || $i === 6 || $i === 7)
                        ? 'C'
                        : 'L';

                    $pdf->Cell(
                        $w[$i],
                        6,
                        $text,
                        1,
                        0,
                        $align
                    );
                }

                $pdf->Ln();
        }

        $dn = 0; $cn = 0;
        foreach ($bundle['posted'] as $doc) {
            if (strtoupper((string)$doc->jenis) === 'DEBIT NOTE') $dn++;
            if (strtoupper((string)$doc->jenis) === 'CREDIT NOTE') $cn++;
        }

        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(190,5,'DOCUMENT SUMMARY',0,1,'L');
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(190,5,'House BL: '.count($details).'   |   Debit Note: '.$dn.'   |   Credit Note: '.$cn,0,1,'L');
        if (count($bundle['unposted']) > 0) {
            $pdf->SetTextColor(160,80,0);
            $pdf->Cell(190,5,'Excluded Unposted DN/CN: '.count($bundle['unposted']),0,1,'L');
            $pdf->SetTextColor(0,0,0);
        }

        if ($instruction !== '') {
            $pdf->Ln(3);
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(190,5,'INSTRUCTION / MESSAGE TO AGENT',0,1,'L');
            $pdf->SetFont('Arial','',8);
            $pdf->MultiCell(190,4,$instruction,1,'L');
        }
    }

    private function financeContext($header, $agent)
    {
        $to = trim((string)$agent->agent_company_name);
        $address = trim((string)$agent->agent_company_address);
        if ($address !== '') $to .= ($to !== '' ? "\n" : '').$address;
        if ($to === '') $to = (string)$agent->agent_company_code;

        return array(
            'to_name'     => $to,
            'vessel'      => trim((string)$header->vessel.' '.(string)$header->voyage),
            'etd'         => $this->financeDate((string)$header->etd_jkt),
            'eta'         => $this->financeDate((string)$header->eta),
            'origin'      => (string)$header->port_loading,
            'destination' => (string)$header->destination,
            'manifest'    => (string)$header->kode_ref,
            'container'   => (string)$header->cont_seal_no,
            'mbl'         => (string)$header->mother_bl,
        );
    }

    /** Format tanggal service DN/CN seperti Finance Process: dd-mm-yyyy. */
    private function financeDate($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') return '';

        foreach (array('!Ymd', '!Y-m-d', '!d-m-Y', '!d/m/Y') as $format) {
            $d = \DateTime::createFromFormat($format, $raw);
            if ($d instanceof \DateTime && $d->format(substr($format, 1)) === $raw) {
                return $d->format('d-m-Y');
            }
        }
        return $raw;
    }

    private function fitText($text, $max)
    {
        $text = preg_replace('/\s+/', ' ', trim((string)$text));
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $max) {
            return mb_substr($text, 0, $max - 3, 'UTF-8').'...';
        }
        if (strlen($text) > $max) return substr($text, 0, $max - 3).'...';
        return $text;
    }

    private function safeFile($text)
    {
        $text = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim((string)$text));
        return trim($text, '-_');
    }

    private function json($data)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    private function plainError($message, $status = 400)
    {
        $this->output->set_status_header((int)$status);
        $this->output->set_content_type('text/plain', 'utf-8');
        $this->output->set_output((string)$message);
    }
}
