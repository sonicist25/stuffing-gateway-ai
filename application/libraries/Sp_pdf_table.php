<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sp_Pdf_Table - tambahan kecil di atas FPDF_MC_Table untuk tabel STUFFING PLAN.
 * ---------------------------------------------------------------------------
 * FPDF_MC_Table::Row() memakai SATU font untuk semua kolom. Cetak Stuffing Plan butuh kolom No. PEB / No. NPE
 * dengan huruf lebih kecil (nomor NPE panjang, mis. 603955/PM/KPU.1/2026) dan beberapa nomor PEB dalam satu sel,
 * jadi class ini menambah SpRow(): tiap sel punya font / ukuran / rata / tinggi baris sendiri.
 * Tampilannya sengaja dibuat sama dengan Row() yang sekarang: garis kotak per sel, teks di tengah vertikal,
 * tinggi minimum 10 mm, jarak baris 5 mm. Row() asli TIDAK diubah, jadi cetakan lain tidak terpengaruh.
 *
 * Hanya memakai fitur FPDF standar (SetFont, GetStringWidth, Rect, MultiCell, AddPage), tidak bergantung
 * pada isi FPDF_MC_Table selain bahwa ia turunan FPDF.
 */
class Sp_Pdf_Table extends FPDF_MC_Table
{
    /** callable(Sp_Pdf_Table $pdf) dipanggil setelah pindah halaman, mis. untuk mengulang header tabel (harus mengatur Y sendiri). */
    public $sp_new_page = null;

    /**
     * Ukuran font terbesar (<= $max, >= $min) agar SEMUA teks muat satu baris pada sel selebar $w mm.
     * Dipakai supaya kolom nomor tidak terpotong / terbelah.
     */
    public function SpFit(array $texts, $w, $max, $min, $style = '')
    {
        $family = $this->FontFamily;
        $inner  = $w - 2 * $this->cMargin;
        $fs = (float) $max;
        while ($fs > $min) {
            $this->SetFont($family, $style, $fs);
            $fit = true;
            foreach ($texts as $t) {
                if ($this->GetStringWidth((string) $t) > $inner) { $fit = false; break; }
            }
            if ($fit) { break; }
            $fs -= 0.25;
        }
        return max($fs, (float) $min);
    }

    /**
     * Satu baris tabel. $cells = array(array(
     *     't'  => string | array(string,...)   // isi; array = beberapa baris terpisah (mis. beberapa PEB)
     *     'w'  => lebar mm,        'a' => 'L'|'C'|'R',
     *     'fs' => ukuran font pt (10),  'st' => '' | 'B',  'lh' => tinggi baris mm (5)
     * ), ...)
     * Tinggi baris = yang terbesar dari semua sel (minimal $min_h). Return tinggi baris.
     */
    public function SpRow(array $cells, $min_h = 10.0)
    {
        $family = $this->FontFamily;
        $style0 = $this->FontStyle;
        $size0  = $this->FontSizePt;

        // 1. ukur: jumlah baris tiap sel (setelah pembungkusan teks) dan tinggi baris
        $h = (float) $min_h;
        foreach ($cells as $i => $c) {
            $c += array('t' => '', 'a' => 'L', 'fs' => 10, 'st' => '', 'lh' => 5);
            $this->SetFont($family, $c['st'], $c['fs']);
            $parts = is_array($c['t']) ? $c['t'] : array($c['t']);
            $c['p'] = array();
            $n = 0;
            foreach ($parts as $p) {
                $p  = (string) $p;
                $nb = ($p === '') ? 0 : $this->SpNbLines($c['w'], $p);
                $c['p'][] = array($p, $nb);
                $n += $nb;
            }
            $c['n'] = $n;
            $h = max($h, $n * $c['lh']);
            $cells[$i] = $c;
        }

        // 2. pindah halaman bila tidak muat
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            if (is_callable($this->sp_new_page)) { call_user_func($this->sp_new_page, $this); }
            $this->SetDrawColor(0, 0, 0);
            $this->SetTextColor(70, 70, 70);
            $this->SetFillColor(255, 255, 255);
        }

        // 3. gambar
        $y = $this->GetY();
        $x = $this->lMargin;
        foreach ($cells as $c) {
            $this->Rect($x, $y, $c['w'], $h);
            if ($c['n'] > 0) {
                $this->SetFont($family, $c['st'], $c['fs']);
                $yy = $y + ($h - $c['n'] * $c['lh']) / 2;     // teks di tengah vertikal
                foreach ($c['p'] as $pp) {
                    if ($pp[1] === 0) { continue; }
                    $this->SetXY($x, $yy);
                    $this->MultiCell($c['w'], $c['lh'], $pp[0], 0, $c['a']);
                    $yy += $pp[1] * $c['lh'];
                }
            }
            $x += $c['w'];
        }
        $this->SetFont($family, $style0, $size0);
        $this->SetXY($this->lMargin, $y + $h);
        return $h;
    }

    /** Jumlah baris yang dipakai $txt bila ditulis dengan MultiCell selebar $w pada font aktif (algoritma standar FPDF). */
    protected function SpNbLines($w, $txt)
    {
        $cw   = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s    = str_replace("\r", '', (string) $txt);
        $nb   = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") { $nb--; }
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c === ' ') { $sep = $i; }
            $l += isset($cw[$c]) ? $cw[$c] : 0;
            if ($l > $wmax) {
                if ($sep === -1) { if ($i === $j) { $i++; } }
                else { $i = $sep + 1; }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}
