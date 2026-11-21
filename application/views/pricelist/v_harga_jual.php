<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>
<style type="text/css">
	.cs {
		margin: 100px 0 0 100px; 
		padding: 20px; 
		border: 2px double #999;
		width: 410px
	}
</style>
<!--
		<table width="100%" id="form-harga">
			<tr><td align="left" colspan="2"><b>CARI HARGA JUAL</b></td></tr>
			<tr>
				<td>Costumer</td>
				<td>
					<?php
					$agent = $this->port_destination_model->getListDestination()->result();
					$data['option_destination'][0]='- Destination -';
					foreach ($agent as $row)
					{	
						$data['option_destination'][$row->portdestination_name]=$row->portdestination_name;
					}		
					echo form_dropdown('dropdownDestination',$data['option_destination'],'','id="dropdownDestination"');
				?>
				</td>
			</tr>
			<tr>
				<td>Destination</td>
				<td>
				<?php
					$agent = $this->port_destination_model->getListDestination()->result();
					$data['option_destination'][0]='- Destination -';
					foreach ($agent as $row)
					{	
						$data['option_destination'][$row->portdestination_name]=$row->portdestination_name;
					}		
					echo form_dropdown('dropdownDestination',$data['option_destination'],'','id="dropdownDestination"');
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><input type="radio" value=""> OF <?php echo  nbs(10) ?><input type="radio" value=""> MISC</td>
			</tr>
			<tr>
				<td></td>
				<td align="right"><input type="button" value=" CETAK " style="width: 80; height: 40" id="btnCetak"></td>
			</tr>
		</table>
-->

<div id="FORM-HARGA"></div>
<div id="PRINT"></div>

<script type="text/javascript">
$(document).ready(function () 
{
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/harga_jual/open_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#FORM-HARGA" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 'auto',
					width: 350,
					modal: false,
					title :"CARI HARGA JUAL",
					close: function() {	}
			});
			$( "#FORM-HARGA" ).html(XMLHttpRequest)
		}
	})	
});

function cetak_harga_jual()
{
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('pricelist/harga_jual/cetak_form')?>",
		data: { },
		success:function(XMLHttpRequest)
		{
			$( "#PRINT" ).dialog(
				{
					autoOpen: true,
					resizable: false,
					height: 700,
					width: 990,
					modal: false,
					title :"HARGA JUAL",
					close: function() {	}
			});
			$( "#PRINT" ).html('<iframe src="<?php echo base_url("pricelist/harga_jual/cetak_form") ?>" width=" 960px" height="660"></iframe>')
		}
	})
}

function form_close()
{
	$( "#FORM-HARGA" ).dialog('close');
}
</script>