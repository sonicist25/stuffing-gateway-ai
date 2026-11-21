<div id="form-output-pdf"></div>
<style type="text/css">
table {
	padding: 10px;
}
td {
	border: none;	
	vertical-align: middle;
	padding: 3px;
}
</style>
<div id="ref-wrapper"  class="outset-border rate-guidance"> 
	<div id="content-table-container" class="clearfix">
  		<table>
    		<tr class="tr-heading">
      		<td width="100px">Periode Job Bulan</td>
      		<!--
        		<td> <input name="" type="text" id="bulan"></td>
        		<td> <input name="" type="text" id="bulan"></td>
        		-->
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
				$data['option_bln']['']= 'Semua';
				foreach ($bln as $key => $value) {
					$data['option_bln'][$key]= $value;
				}	
				echo form_dropdown('bulan',$data['option_bln'],'','id="bulan" style="margin-right: 5px"');
        		
        		$thn = array(
					date('y', strtotime('-1 year')) => date('Y', strtotime('-1 year')),
					date('y')=>date('Y'),
					date('y', strtotime('+1 year')) => date('Y', strtotime('+1 year')),
				);
				$data['option_thn']['']= '';
				foreach ($thn as $key => $value) {
					$data['option_thn'][$key]= $value;
				}
				$tahun = substr($this->session->userdata('db'),2,4);
				//echo $tahun.date('y');
				echo form_dropdown('tahun',$data['option_thn'],$tahun,'id="tahun" disabled="disabled" class="label label-default"');
        		?>
        		</td>
      	</tr>
     </table>
		<hr>
  		<div id="topbutton-report">
			<a href="javascript:void(0)" id="btnPDF" onclick="get_proses('pdf')">TO PDF</a>
  			<a href="javascript:void(0)" id="btnEXCEL" onclick="get_proses('excel')">TO EXEL</a>
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
	var tahun = $('select[id="tahun"]').val();

	
		$.ajax({
			type: "POST", 
			url : "<?php echo site_url('report/listing_invoice/get_proses')?>",
			data: { 
				output : mode,
				bulan: bulan,
				tahun: tahun,
			},
			success:function(response)
			{
				var data = $.parseJSON(response);
				if(data.status > 0 ){
					get_output(mode,bulan)
				} else {
					messages("Query return to empty data, Output can not be send")
				}
				
			}
		})
	
}

function get_output(mode,bulan){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('report/listing_invoice')?>/to_"+mode,
		data: { bulan : bulan },
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
					title :"PRINT OUT TOTAL INVOICE",
					close: function() {	}
				});
				$('#form-output-pdf').html('<center><iframe src="<?php echo base_url("report/listing_invoice/") ?>/to_'+mode+'" width="960px" height="600"></iframe></center>');
			} else {
				window.open("listing_invoice/to_"+mode, "_self",1,true);
			}
		}
	})	
}

function messages(char){
	$('#messages').html('<div style="font-size: 12px; color: #f33; border: 1px solid #f33; padding: 15px">'+char+'</div>');
   $('#messages').fadeIn('slow').delay(2000).fadeOut('slow');
}
</script>





