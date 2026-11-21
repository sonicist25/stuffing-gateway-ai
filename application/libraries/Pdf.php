<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Pdf
{
    /** @var Dompdf */
    protected $dompdf;

    public function __construct()
    {
        $opt = new Options();
		$opt->set('isRemoteEnabled', true);
		$opt->set('isHtml5ParserEnabled', true);
		$opt->set('defaultMediaType', 'print'); // penting

        // Optional: supaya lebih aman kalau ada asset lokal
        // $opt->set('chroot', FCPATH);

        $this->dompdf = new Dompdf($opt);
    }

    public function loadHtml($html)
    {
        $html = (string)$html;

        // Guard: kalau HTML kosong, Dompdf bisa error internal
        if (trim($html) === '') {
            $html = '<html><body>Empty preview</body></html>';
        }

        $this->dompdf->loadHtml($html, 'UTF-8');
        return $this;
    }

    public function setPaper($paper = 'A4', $orientation = 'portrait')
    {
        $this->dompdf->setPaper($paper, $orientation);
        return $this;
    }

    public function render()
    {
        $this->dompdf->render();
        return $this;
    }

    public function output()
    {
        return $this->dompdf->output();
    }

    public function stream($filename = 'document.pdf', $options = [])
    {
        // Pastikan tidak ada output lain sebelum PDF
        if (ob_get_length()) {
            @ob_end_clean();
        }

        $this->dompdf->stream($filename, $options);
    }
}
