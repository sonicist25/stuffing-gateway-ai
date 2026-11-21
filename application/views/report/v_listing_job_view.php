<div id="form-output-pdf"></div>

<style type="text/css">
  table {
    padding: 10px;
    width: 100%;
    border-collapse: collapse;
  }
  td {
    vertical-align: middle;
    padding: 6px;
  }
  .tr-heading td {
    font-weight: bold;
    background-color: #f0f0f0;
  }
  #topbutton-report {
    margin-top: 15px;
  }
  #topbutton-report a {
    margin-right: 10px;
    padding: 6px 12px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
  }
  #topbutton-report a:hover {
    background-color: #0056b3;
  }
  .report-description {
    margin-top: 20px;
    padding: 10px;
    background-color: #ffffe0;
    border-left: 4px solid #ffc107;
    font-style: italic;
  }
</style>

<div id="ref-wrapper" class="outset-border rate-guidance">
  <div id="content-table-container" class="clearfix">
    <table>
      <tr class="tr-heading">
        <td width="150px">Periode Job Bulan</td>
        <td>
          <?php
            $bln = array(
              '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
            );
            $data['option_bln'][''] = 'Semua';
            foreach ($bln as $key => $value) {
              $data['option_bln'][$key] = $value;
            }
            echo form_dropdown('bulan', $data['option_bln'], '', 'id="bulan" style="margin-right: 5px"');
            $tahun = substr($this->session->userdata('db'), 2, 4);
            echo form_dropdown('tahun', $this->session->userdata('db'), $tahun, 'id="tahun" disabled="disabled" class="label label-default"');
			echo '<input type="hidden" id="tahun_hidden" name="tahun" value="'.$tahun.'">';
          ?>
        </td>
      </tr>
    </table>

    <div class="report-description">
      Laporan ini berisi daftar item pada modul <strong>Income</strong> yang belum ditarik ke <strong>Invoice</strong>,
      serta item yang sudah ditarik ke Invoice namun mengalami perubahan, pembaruan, atau penarikan ulang di modul Income.
    </div>

    <div id="topbutton-report">
      <a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')">TO PDF</a>
      <a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')">TO EXCEL</a>
      <a href="javascript:void(0)" id="btnCLOSE" onclick="close_form()">CLOSE</a>
    </div>
  </div>
</div>
<script type="text/javascript">
function close_form()
{
	$( "#form-report" ).dialog('close');
}

function get_proses(mode){	

	var bulan = $('select[id="bulan"]').val();
	var tahun = $('#tahun_hidden').val();

	
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/listing_job/get_proses')?>",
			data: { 
				output : mode,
				bulan: bulan,
				tahun: tahun,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,bulan,tahun)
				} else {
					messages("Query return to empty data, Output can not be send")
				}
				
			}
		})
	
}

function get_output(mode,bulan,tahun){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/listing_job')?>/to_"+mode,
		data: { bulan : bulan,tahun : tahun },
		success:function(XMLHttpRequest)
		{
			
			if (mode=='pdf') {
				$( "#form-output-pdf" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 650,
					width: 990,
					modal: true,
					title :"PRINT OUT LISTING JOB BLUM DIBUAT INVOICE",
					close: function() {	}
				});
				$('#form-output-pdf').html('<center><iframe src="<?php echo base_url("report/listing_job") ?>/to_'+mode+'/'+tahun+'/'+bulan+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("listing_job/to_"+mode+'/'+tahun+'/'+bulan, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}
</script>





