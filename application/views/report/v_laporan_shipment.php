<div id="form-output-pdf"></div>
<style type="text/css">
table {
    padding: 10px;
}
td {
    /* Bisa ditambahkan styling khusus td jika perlu */
}
</style>

<div id="ref-wrapper" class="outset-border rate-guidance" style="width: 360px;"> 
    <div id="content-table-container" class="clearfix">
        <table>
            <tr class="tr-heading">
                <td width="150">Laporan LR Job perbulan</td>
                <td>
                <?php
                $bln = array(
                    '01'=>'January',
                    '02'=>'Febuary',
                    '03'=>'Maret',
                    '04'=>'April',
                    '05'=>'Mei',
                    '06'=>'Juni',
                    '07'=>'Juli',
                    '08'=>'Agustus',
                    '09'=>'September',
                    '10'=>'Oktober',
                    '11'=>'November',
                    '12'=>'Desember',
                );
                
                $data['option_bln'][''] = 'Semua';
                foreach ($bln as $key => $value) {
                    $data['option_bln'][$key] = $value;
                }   
                echo form_dropdown('bulan', $data['option_bln'], '', 'id="bulan" style="margin-right: 5px"');
                
                $thn = array(
                    date('y', strtotime('-1 year')) => date('Y', strtotime('-1 year')),
                    date('y') => date('Y'),
                    date('y', strtotime('+1 year')) => date('Y', strtotime('+1 year')),
                );
                
                $data['option_thn'][''] = '';
                foreach ($thn as $key => $value) {
                    $data['option_thn'][$key] = $value;
                }
                
                $tahun = substr($this->session->userdata('db'), 2, 4);
                echo form_dropdown('tahun', $data['option_thn'], $tahun, 'id="tahun" disabled="disabled" class="label label-default"');
                ?>
                </td>
            </tr>
        </table>
        <br>
        <br>
        <hr>
    </div>
   <div id="topbutton-report">
    <a href="javascript:void(0)" id="btnPDF" onclick="get_output('pdf')" style="margin-right: 55px">TO PDF</a>
    <a href="javascript:void(0)" id="btnEXCEL" onclick="get_output('excel')" style="margin-right: 55px">TO EXCEL</a>
    <a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
  </div>
</div>

<div id="messages"></div>

<script type="text/javascript">
function close_form() {
    $( "#form-report" ).dialog('close');
}

// FUNGSI BARU: Tidak pakai AJAX lagi, langsung rakit URL dan buka
function get_output(mode) {
    var bulan = $('#bulan').val();
    var tahun = $('#tahun').val();

    // Rakit URL dengan parameter GET
    var target_url = "<?php echo site_url('report/laporan_shipment/to_'); ?>" + mode + "?bulan=" + bulan + "&tahun=" + tahun;

    if (mode === 'pdf') {
        $("#form-output-pdf").dialog({
            autoOpen: true,
            resizable: false,
            height: 650,
            width: 990,
            modal: true,
            title :"Laporan Shipment"
        });
        
        // Tampilkan PDF di dalam iframe menggunakan URL yang sudah dirakit
        $('#form-output-pdf').html('<center><iframe src="' + target_url + '" width="960px" height="600" style="border:none;"></iframe></center>');
    } else {
        // Mode Excel: Buka di tab baru agar file langsung terdownload
        window.open(target_url, "_blank");
    }
}

function messages(char) {
    $('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">' + char + '</div>');
    $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}
</script>
