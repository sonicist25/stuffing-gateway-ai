<div id="proses-import"></div>
<input type="hidden" style="width: 50" id="kode_ref" name="kode_ref" >
<input type="hidden" style="width: 50" id="no_bl" name="no_bl" >
<input type="hidden" style="width: 50px; position: absolute; top: 10px;" id="id-form" name="id-form" value="<?php echo $id ?>">
<input type="hidden" style="width: 50" id="proses-form" name="proses-form" value="<?php echo $proses ?>">
<input type="hidden" style="width: 50" id="seller" name="seller" value="<?php echo $seller_import ?>">
<input type="hidden" style="width: 50" id="id_invoice" name="id_invoice">
<input type="hidden" style="width: 50" id="operan" name="operan">
<div id="main-invoice-editor">
	<div class="form_bck" style="float: left; width:340px; margin-top: 10px;">
		<table>
            <tr>
					<td width="100" height="30">Jenis</td>
					<td width="20" align="center">:</td>
					<td>
					<?php
					$oper = $this->input->post("oper");
					$inv = substr($this->input->post("inv_no"),0,4);
					
					if($oper=="add") {
						$selected = 0;
					}
					else {
						if($inv=="INFP") {
							$selected = 3;
						}
						else if($inv=="INR/") {
							$selected = 2;
						}
						else if($inv=="PRIV") {
							$selected = 4;
						}
						else if($inv=="PRFP") {
							$selected = 5;
						}
						else {
							$selected = 1;
						}
					}
					//echo $oper;
					?>
					<?= form_dropdown("opt_jenis",
						array("0"=>"",
							"5"=>"PROFORMA FAKTUR PAJAK",
							"4"=>"PROFORMA INVOICE",
						),$selected,"id =\"opt_jenis\" style=\"min-width: 200px\" onchange=\"get_nomer_invoice()\"") ?></td>
            </tr>
				<tr>
                  <td height="30">Group Invoice</td>
                  <td align="center">:</td>
                  <td><?= form_dropdown("group_invoice",$option,$this->input->post('grup'),"id=\"group_invoice\" style=\"width: 200px;\""); ?></td>
            </tr>			
				<tr>
                  <td height="30">Invoice No</td>
                  <td align="center">:</td>
                  <td><?= form_input(array("id"=>"invoice_no","name"=>"invoice_no","readonly"=>"readonly","style"=>"width: 200")) ?></td>
            </tr>			
				<tr>
                  <td height="30">Inv Date</td>
                  <td align="center">:</td>
                  <td><?= form_input(array("id"=>"invoice_date","name"=>"invoice_date","style"=>"width: 200")) ?></td>
        		</tr>
				<tr id="awb_type_row" style="display:none;">
                  <td height="30">MAWB/HAWB</td>
                  <td align="center">:</td>
                  <td>
					<div style="float: left; width: 80px; ">
					<?php echo form_radio(array('id'=>'awb_type_mawb', 'name'=>'awb_type', 'class'=>'awb_type', 'value'=>'mawb', 'checked'=>true)). ' MAWB'; ?>
					</div>
					<div style="float: left; width: 80px; ">
					<?php echo form_radio(array('id'=>'awb_type_hawb', 'name'=>'awb_type', 'class'=>'awb_type', 'value'=>'hawb')). ' HAWB'; ?>
					</div>
                  </td>
        		</tr>
				<tr>
				<td colspan="2">PPH 23</td>
				<td>
				<div style="float: left; width: 80px; ">
				<?php
				echo form_radio(array('id'=>'pph23', 'name'=>'pph23', 'class'=>'pph23', 'value'=>'yes')). 'Potong';
				?>
				</div>
				<div style="float: left; width: 80px; ">
				<?php
				echo form_radio(array('id'=>'pph-23', 'name'=>'pph23', 'class'=>'pph23', 'value'=>'no')). 'Tidak';
				?>
				</div>
				</td>
				</tr>
        		<tr><td colspan="3"></td></tr>
       </table>
	</div>   
	   
	<div class="form_bck" style=" float: right; margin-top: 10px; margin-left: 10px; width:340px">
			<table width="98%">
				<tr>	
                <td width="80">Kepada</td>
                <td width="20">:</td>
                <td><?= form_input(array("id"=>"shipper_id","name"=>"shipper_id","style"=>"width: 230px; margin-bottom: 5px","readonly"=>"readonly")) ?></td>
            </tr>
				<tr>
					<td colspan="2"></td>
               <td><?= form_input(array("id"=>"shipper_name","name"=>"shipper_name","style"=>"width: 230px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
            </tr>
				<tr>
					<td colspan="2"></td>
               <td><?= form_textarea(array("id"=>"shipper_address","name"=>"shipper_address","style"=>"min-height: 45px; max-height: 45px; max-width: 230px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
            </tr>
				<tr>
					<td id="containerSeal" colspan="2">CONTAINER/SEAL</td>

               <td>
					<?= form_textarea(array(
							"id" => "si_exportir",
							"name" => "si_exportir",
							"style" => "max-width: 230px; height:80px",
							"readonly" => "readonly"
					)) ?>
					</td>

            </tr>
      </table>
	</div>
</div>
<div style="clear: both; height: 10px;"></div>
<table id="tabel_tiga"></table>
<div id="page_tiga" style="font-size: 11px;"></div>
<hr>
<div style="float: right; font-size: 11px; margin-bottom: 10px">
	<input type="button" value="PRINT" id="btnPrint" style="width: 70; height: 30" onclick="print_invoice()"> 
	<input type="button" value="SAVE" id="btnSave" style="width: 70; height: 30" onclick="save_invoice()">
	<!--
	<input type="button" value="DELETE" id="btnDelete" style="width: 70; height: 30" disabled="disabled" onclick="delete_invoice()">
	-->
	<input type="button" value="CANCEL" id="btnCancel" style="width: 70; height: 30" onclick="out()">
	<input type="button" value="REFRESH" style="width: 70; height: 30; font-size: 11px;" onclick="refresh_table()">
</div>

<?= form_input(array("type"=>"hidden","id"=>"chache_sp_name","value"=>$shipper_name)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_sp_add","value"=>$shipper_address)) ?>

<script type="text/javascript" >
$(document).ready(function () 
{
	$( "input[name='invoice_date']" ).datepicker({dateFormat : "dd/mm/yy"});
	var oper = '<?= $this->input->post("oper") ?>';
	var region_id = '<?php echo $this->session->userdata("region_id") ?>';
	var rowid = '<?php echo $id ?>';
	var inv_akhir = '<?= $this->input->post("inv_no") ?>';
	
	if(inv_akhir > 0){
		$.strPad = function(i,l,s) {
			var o = i.toString();
			if (!s) { s = '0'; }
				while (o.length < l) {
					o = s + o;
				}
			return o;
		}
		var numInv = eval(inv_akhir);
		var numInvAkhir = $.strPad( numInv , 5, '0');
	}
	var year = new Date().getFullYear().toString().substring(2, 4);
	
	if(oper=='add'){
	
		
		$( "#btnPrint" ).attr('hidden',true);
		$( "#shipper_name" ).val($('input[id="KET_KOSTUMER"]').val());
		$( "#shipper_address" ).val($('textarea[id="ALAMAT_KOSTUMER"]').val());
		$( '#tabel_tiga' ).jqGrid("clearGridData", true);
		
	} else {
		
		var shp_nm = $("#chache_sp_name").val();
		var shp_add = $("#chache_sp_add").val();
		
		if (shp_nm == "") {
			shp_nm = $('input[id="KET_KOSTUMER"]').val();
		}
		
		if (shp_add == "") {
			shp_add = $('textarea[id="ALAMAT_KOSTUMER"]').val();
		}
		
		$( "#invoice_no" ).val(inv_akhir);
		$( "#btnPrint" ).attr('hidden',false);
		$( "#shipper_name" ).val(shp_nm);
		$( "#shipper_address" ).val(shp_add);
		$( "select[name='opt_jenis']").attr("disable",true);
	}
	
	$( "#si_exportir" ).val(`<?= $si_exportir ?>`);
	$( "#invoice_date" ).val("<?php echo $this->input->post('date') ?>");
	$( "#shipper_id" ).val($('input[id="KOSTUMER"]').val());
	$( "input[name='kode_ref']" ).val("<?php echo $this->input->post('job_no') ?>");
	$( "#group_invoice" ).val("<?php echo $this->input->post('group') ?>");

	// Job 200 (AF export) / 600 (AF import) = mode Air Freight: tampilkan
	// pilihan MAWB/HAWB (default MAWB) di editor. Job lain: sembunyikan.
	var kodeRefForAwb = $( "input[name='kode_ref']" ).val();
	var jobCodeForAwb = kodeRefForAwb ? kodeRefForAwb.substr(4,3) : '';
	if (jobCodeForAwb == '200' || jobCodeForAwb == '600') {
		$("#awb_type_row").show();
	} else {
		$("#awb_type_row").hide();
		$("input[name='awb_type'][value='mawb']").prop('checked', true);
	}
	
	if (jobCodeForAwb == '300' || jobCodeForAwb == '600' || jobCodeForAwb == '400') {
    $("#containerSeal").text("SUPPLIER");
	} else {
		$("#containerSeal").text("NOMOR SI");
	}

	get_table_tiga();

});

function get_nomer_invoice()
{
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_proforma_invoice/get_nomer_invoice'); ?>",
		data: 
		{
			oper: 'add',
			jenis: $("select[name='opt_jenis']").val(),
			job_no: $("input[id='NOMER_JOB']").val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if (data.num_inv==false) {
				$( "#invoice_no" ).val("");
			} else {
				if(data.num_inv > 0){
					$.strPad = function(i,l,s) {
						var o = i.toString();
						if (!s) { s = '0'; }
						while (o.length < l) {
							o = s + o;
						}
						return o;
					}
					var numInv = eval(data.num_inv);
					var numInvAkhir = $.strPad( numInv , 5, '0');
				}
				if (data.jenis =="5") {
					ino = "PRFP/";
				}
				else if (data.jenis =="4") {
					ino = "PRIV/";
				}
				var year = $("#NOMER_JOB").val().substr(7,2);
				$( "#invoice_no" ).val(ino+year+numInvAkhir);
				
			}
		}
	})
}

function out()
{
	$( "#form-invoice" ).fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function get_table_tiga()
{
	var kode_ref = $( "input[name='kode_ref']" ).val();	
	var year = new Date().getFullYear().toString().substring(2, 4);
	var no_invoice = $('#invoice_no').val().replace('IN/'+year,'');
	
	var grid = $("#tabel_tiga"), i;
	grid.jqGrid({
		url:"<?= base_url('finance/c_proforma_invoice/get_table_tiga')?>", 
		mtype : "post", 
		datatype: "json", 
		loadonce: true, 
		postData: { id : $("#id-form").val() },
		colNames:['No Invoice','Item','Qty','Currency','price','Remark','PREP/COL'],
    	colModel:
		[
			{name:'no_invoice',index:'no_invoice', editable: true, hidden:true, editrules:{edithidden:true}},
			{name:'item',index:'item', editable: true, width:200, 
				edittype:"select",
				editoptions:{dataUrl: '../afg_c_dropdown/dropdownItemService'}
			},
			{name:'qty',index:'qty', width:80, editable: true, align: 'center',
    			formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces:4, defaultValue: '' }
			},
			{name:'currency',index:'currency',editable: true, width:70, align: 'center',
				edittype:"select",formatter:'select', editoptions:{value:" : ;USD:USD;RP.:RP."}
			},
			{name:'price',index:'price',editable: true, width:80, align: 'right',
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }
			},
			{name:'remarks',index:'remarks',editable: true, width:130, 
				edittype:"textarea",
				editoptions:{
					cols:"40", rows:"1",
					dataInit: function (el) 
					{ 
						$(el).css('max-width','125px'); 
					}
				}
			},
			{name:'pre_col',index:'pre_col', align: 'center', width:80, editable: true, 
				edittype:"select",  
				editoptions:{
					value:" : ;COLLECT:COLLECT;PREPAID:PREPAID"
				}
			}		
      ],
      sortname: "id",
		sortorder: "asc",
      shrinkToFit:false,
		multiselect:true,
		loadonce : false,
		rowNum:20,
		width: 755,
		height: 200,
		rowList:[20,40,60],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:false,
		editurl:"c_proforma_invoice/manage_data",
		pager:'#page_tiga',
		cellEdit:true,
		cellsubmit:'clientArray',
		
		beforeSaveCell: function(rowid, cellname, value, iRow, iCol) {
			save_tbl_tiga(rowid,cellname,value)
    	},
		
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#tabel_tiga")[0].grid.cDiv).hide();
    	},
    	
    	beforeSelectRow: function(rowid, e) {
        var cbsdis = $("tr#"+rowid+".jqgrow > td > input.cbox:disabled", grid[0]);
        if (cbsdis.length === 0) {
            return true; 
        		} else {
            return false; 
        }
    	},		
    	
		onSelectRow: function(rowid, status){
			var selected = $("tr.jqgrow > td > input.cbox:checked", grid[0]);		
			selected[rowid] = status;
			if(status)
			{ 
				var cbs = $("tr.jqgrow > td > input.cbox", grid[0]);  			
    			//cbs.attr("disabled", true);  
    			//selected.attr("disabled", false); 
    			on_edit(true)    			 			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false);   
    			on_edit(true)     			
        	}
		}
	})
	grid.jqGrid('navGrid','#page_tiga',{view:false, edit:false, add:false, del:true, refresh: false ,<?php echo 'search: false' ?>},
		{ }, 
   	{ },
     	{
     		height: "auto", 
     		width: "auto",
     		serializeDelData: function (postdata) { 			
    			return {
    				id: postdata.id, 
    				oper: postdata.oper, 
    				table : 'x_proforma_item',
    			};
			},
     		afterSubmit: function(){
    		grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    			return [true];
			}
     	},
      { },
     	{ }
	) 
	
	<?php if($_POST['oper']=='edit'){ ?>
	
	grid.navButtonAdd('#page_tiga',{ 
			caption:'', 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_invoice, 
			position: "last", 
			title:"Insert new record", 
			cursor: "pointer"
	})
	
	grid.navButtonAdd('#page_tiga',{ 
			caption:'', 
			buttonicon:"ui-icon-disk", 
			onClickButton: tarik_data_invoice, 
			position: "last", 
			title:"Tarik Data Invoice", 
			cursor: "pointer"
	})
	
	<?php } ?> 
	
}

function insert_data_invoice()
{
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_proforma_invoice/manage_data_table'); ?>",
		data: 
		{
			oper: 'add',
			id : '',
			table: 'x_proforma_item',
			kepada: $("input[name='shipper_id']").val(),
			jenis: $("select[name='opt_jenis']").val(),
			kode_ref: $("input[id='NOMER_JOB']").val(),
			number : '<?php echo $id ?>',
			destination : encodeURIComponent("<?php echo $destination; ?>")
		},
		success:function(response)
		{
			$("#tabel_tiga").jqGrid("setGridParam",  
			{
				datatype: 'json',
				sortname: "id",
      		sortorder: "asc",
      	}).trigger("reloadGrid");
    		return [true];
		}
	})
}

function tarik_data_invoice()
{
	var year = new Date().getFullYear().toString().substring(2, 4);
	if($("select[name='opt_jenis']").val() =='' || $("select[name='invoice_date']").val()==''){
		show_messages('Jenis Invoice & Inv Date tidak boleh kosong');
		return false;
	}
	else
	{
		var DataTarik = 
		{
			table: 'x_proforma',
			kepada: $("input[name='shipper_id']").val(),
			invoice_db: $("select[name='opt_jenis']").val(),
			jenis: "INVOICE",
			kode_ref: $( "input[name='kode_ref']" ).val(),
			number : '<?php echo $id ?>',
			group : $('#group_invoice').val(),
			destination : encodeURIComponent("<?php echo $destination; ?>")
		}
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('finance/c_proforma_invoice/tarik_data_invoice'); ?>",
			data: DataTarik,
			success:function(response)
			{
				$("#tabel_tiga").jqGrid("setGridParam",  
				{
						datatype: 'json',
						sortname: "id",
      				sortorder: "asc",
      		}).trigger("reloadGrid");
    			return [true];
			}
		})
	}
}

function reload_tiga(grid, result)
{
  	$("#table_tiga").trigger("reloadGrid"); 
}

function save_invoice(){
	
	if($("select[id='opt_jenis']").val()==''){
		$("select[id='opt_jenis']").focus();
		show_messages('Please complete the required data, Jenis Can\'t  EMPTY VALUE');
		return false;
	} 
	else if ($( "input[id='invoice_date']" ).val()==''){
		$( "input[id='invoice_date']" ).focus();
		show_messages('Please complete the required data, Jenis & Inv Date Can\'t EMPTY VALUE');
		return false;
	}
	else
	{
		var year = new Date().getFullYear().toString().substring(2, 4);
		var date = $("input[name='invoice_date']").val();
		var form_data =
		{	
			table  		: 'x_proforma',
			oper	 		: '<?php echo $_POST["oper"] ?>',			
			id		 		: $("input[id='id-form']").val(),
			job_no 		: $("input[name='kode_ref']").val(),
			//jenis	 		: $("select[name='opt_jenis']").val(),
			jenis	 		: "INVOICE",
			kepada 		: 'SHIPPER',
			kepada_kode	: $("input[name='shipper_id']").val(),
			group  		: $('#group_invoice').val(),
			number 		: $("input[name='invoice_no']").val(),
			date   		: date.substr(6,4)+date.substr(3,2)+date.substr(0,2),
			shipper_name: $("input[name='shipper_name']").val(),
			shipper_address : $("textarea[name='shipper_address']").val(),
			pph23		: $("input[name='pph23']:checked").val(),
			region_id 	: '<?php echo $this->session->userdata("region_id") ?>',
		};
		
		$.ajax({
			url : "<?php echo site_url('finance/c_proforma_invoice/manage_data')?>",
			type : 'POST',
			data : form_data,
			success: function(response)
			{
				var data = $.parseJSON(response);
				if (data.status==3) {
					show_messages("Saving Data Error, Duplicate entry INVOICE NUMBER");
				}
				$( "#table_dua" ).jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				$( "#table_empat" ).jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				$( "#form-invoice" ).dialog( "close" );
				$('#btnNew').attr('disabled',true);			
			},
			error:function(XMLHttpRequest)
			{
				
			}
		});
	}
}

function refresh_table(){
	$("#tabel_tiga").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function delete_invoice(){
	$("#tabel_tiga").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
}

function print_invoice(){
	var year = new Date().getFullYear().toString().substring(2, 4);
	$( "#out-invoice" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#out-invoice" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 640,
		width: 990,
		modal: true,
		title: "INVOICE "+$("input[id='NOMER_JOB']").val(),
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		}
	});
	var dataPost = 
	{ 
				jenis				:	$("select[name='opt_jenis']").val(),
				id					:  '<?php echo $id ?>',
				kode_ref 		: 	$("input[id='NOMER_JOB']").val(),
				no_invoice 		: 	$("input[id='invoice_no']").val(),
				invoice_date 	: 	$("input[id='invoice_date']").val().replace(/\//g,'-'),
				kepada			:  $("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val(),
				si_shipper		:  $("input[id='si_exportir']").val(),
				seller		:  $("input[id='seller']").val(),
				pph23   		:  $("input[name='pph23']:checked").val(),
				awb_type		:  $("input[name='awb_type']:checked").val() || 'mawb'
	}
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_proforma_invoice/print_invoice')?>/"+dataPost['kode_ref']+"/"+dataPost['id']+"/"+dataPost['pph23'],
			data:  dataPost,
			success: function(data)
			{
				$('#out-invoice').html('<iframe src="<?php echo base_url("finance/c_proforma_invoice/print_invoice") ?>/'+dataPost['kode_ref']+'/'+dataPost['id']+'/'+dataPost['pph23']+'" width="960px" height="600"></iframe>');
			},
			error:function(XMLHttpRequest)
			{

				$('#out-invoice').html(XMLHttpRequest.responseText)
			}
	})
}

function save_tbl_tiga(rowid,cellname,value){
	if(rowid.substr(0,1)== 'j'){
		var mode = 'add';
	}
	else
	{
		var mode = 'edit';	
	}
	var year = new Date().getFullYear().toString().substring(2, 4);
	var grid = $("#tabel_tiga"), i; 	
	DataTblTiga = 
	{
		oper : mode,
		id : rowid,
		table: 'x_proforma_item',
		field : cellname,
		value	: value,
	}
	
	$.ajax
	({
		type:"POST",
		url:"<?php echo site_url('finance/c_proforma_invoice/manage_data_table'); ?>",
		data: DataTblTiga,
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status=='1'){
				$("#tabel_tiga").jqGrid("setGridParam", 
				{
					datatype: 'json',
					sortname: "id",
      			sortorder: "asc",
      		}).trigger("reloadGrid");
    			return [true];
			}
		}
	})
}

</script>

<?php 



?>