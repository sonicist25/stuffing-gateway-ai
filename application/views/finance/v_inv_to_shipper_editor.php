<?php
$paid_revision_allowed_user_ids = array(0, 107);
$can_paid_invoice_revision = in_array(
    (int)$this->session->userdata('user_id'),
    $paid_revision_allowed_user_ids,
    true
);
?>
<style type="text/css">
#btnPrint,#btnSave,#btnCancel,#btnPrinteInv,#btnPrinteInvKredit,#btnPost,#btnRevision,#btnPrintGabungan 
{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#000000;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
	text-align: center;
}
#btnPrinteInvKredit:hover,#btnPrinteInv:hover,#btnPrint:hover,#btnSave:hover,#btnCancel:hover,#btnPost:hover,#btnRevision:hover,#btnPrintGabungan:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}
</style>
<div id="proses-import"></div>
<input type="hidden" id="kode_ref" name="kode_ref" >
<input type="hidden" id="no_bl" name="no_bl" >
<input type="hidden" id="id-form" name="id-form" value="<?php echo $id ?>">
<input type="hidden" id="id_invoice" name="id_invoice">
<input type="hidden" id="operan" name="operan">
<div id="main-invoice-editor">
	<div class="form_bck" style="float: left; width:340px; margin-top: 10px; min-height: 140px;">
		<table>
            <tr>
					<td style="width: 150px; height: 30px">Jenis</td>
					<td style="width: 20px; height: 30px" align="center">:</td>
					<td>
					<?php
					$oper = $this->input->post("oper");
					$inv = explode('/', $this->input->post("inv_no"))[0];
					
					if($oper=="add") {
						$selected = 0;
					} else {
						if($inv=="INFP" || $inv=="ICFP" || $inv=="SUBFP" ||$inv=="SMGFP" || $inv=="MINFP" ||$inv=="BINFP" || $inv=="MKSINFP") {
							$selected = 3;
						}
						else if($inv=="INR" || $inv=="CINR" || $inv=="SINR" || $inv=="SRINR" || $inv=="MINR" || $inv=="BINR" || $inv=="MKSINR") {
							$selected = 2;
						}
						else {
							$selected = 1;
						}
					}
					echo form_dropdown("opt_jenis",
							array("0"=>"",
								"1"=>"INVOICE",
								"2"=>"INVOICE REIMBURSEMENT",
								"3"=>"INVOICE FAKTUR PAJAK",
							),$selected,"id =\"opt_jenis\" style=\"min-width: 220px\"") 
						?>
					</td>
            </tr>
				<tr>
                  <td style="width: 150px; height: 50px">Group Invoice</td>
                  <td align="center">:</td>
                  <td><?= form_dropdown("group_invoice",$option,$this->input->post('grup'),"id=\"group_invoice\" style=\"width: 220px;\""); ?></td>
            </tr>			
			<tr>
                  <td height="30">Invoice No</td>
                  <td align="center">:</td>
                  <td><?= form_input(array("id"=>"invoice_no","name"=>"invoice_no","style"=>"width: 215px","disabled"=>"disabled")) ?></td>
            </tr>			
			<tr>
                  <td height="50">Inv Date</td>
                  <td align="center">:</td>
                  <td><?= form_input(array("id"=>"invoice_date","name"=>"invoice_date","style"=>"width: 215px")) ?></td>
        	</tr>
			<tr>
                  <td height="30">Inv Status</td>
                  <td align="center">:</td>
                  <td><?= form_input(array("id"=>"invoice_status","name"=>"invoice_status","style"=>"width: 215px","disabled"=>"disabled")) ?></td>
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
        		<tr><td colspan="3"></td></tr>
       </table>
	</div>
	     
	<div class="form_bck" style="float: left; margin-top: 10px; margin-left: 5px; width:340px; min-height: 140px;">
		<table width="98%">
				<tr>	
                <td style="width: 120px; height: 30px">Kepada</td>
                <td width="20"></td>
                <td><?= form_input(array("id"=>"shipper_name","name"=>"shipper_name","style"=>"width: 200px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
            </tr>
				<tr>
					<td colspan="2"></td>
               <td><?= form_textarea(array("id"=>"shipper_address","name"=>"shipper_address","style"=>"min-height: 57px; max-height: 57px; max-width: 200px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
            </tr>
				<tr>
					<td colspan="2">Incoterms</td>
               <td><?= form_input(array("id"=>"incoterms","name"=>"incoterms","style"=>"width: 200px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
				</tr>
			   <tr>
					<td colspan="2">SI Exportir</td>
               <td><?= form_textarea(array("id"=>"si_exportir","name"=>"si_exportir","style"=>"width: 200px; max-height: 53px","readonly"=>"readonly")) ?></td>
            </tr>
      </table>
	</div>
	<div class="form_bck" style="float: right; margin-top: 10px; margin-left: 5px; width:340px; min-height: 140px;">
		<table width="98%">
				<tr>
				<td colspan="2">Nama NPWP</td>
				<td><?= form_input(array("id"=>"select_npwp","name"=>"select_npwp","style"=>"width: 200px; margin-bottom: 5px;")) ?></td>
				</tr>
				<tr>
					<td colspan="2">Nomor NPWP</td>
               <td><?= form_input(array("id"=>"npwp","name"=>"npwp","style"=>"width: 200px; margin-bottom: 5px","readonly"=>"readonly")) ?></td>
			   </tr>
			   <tr>
					<td colspan="2"></td>
               <td><?= form_textarea(array("id"=>"alamat_npwp","name"=>"alamat_npwp","style"=>"min-height: 109px; max-height: 109px; max-width: 200px; margin-bottom: 5px; text-transform: uppercase")) ?></td>
			</tr>
			<tr>
			<td colspan="2"></td>
               <td><?= form_input(array("id"=>"idnpwp","name"=>"idnpwp","style"=>"width: 200px","hidden"=>true)) ?></td>
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
			<tr>
      </table>
	</div>
</div>
<div style="clear: both; height: 10px;"></div>
<table id="tabel_tiga"></table>
<div id="page_tiga" style="font-size: 11px;"></div>
<hr>
<div style="font-size: 11px; margin-bottom: 10px;">
	<input type="button" value="e-INV COPY" id="btnPrinteInvKredit" style="width: 100px; height: 30px; float: right;" onclick="print_e_inv_kredit()"> 
	<input type="button" value="e-INV ORI" id="btnPrinteInv" style="width: 100px; height: 30px; float: right;" onclick="print_e_inv()"> 
	<input type="button" value="PRINT" id="btnPrint" style="width: 100px; height: 30px; float: right;" onclick="print_invoice()"> 
	<?php
	// Invoice gabungan (FP + non-tax, nomor = nomor FP) hanya untuk invoice Faktur Pajak.
	// INWHM ikut ditampilkan karena GCL WAREHOUSE memakai prefix yang sama untuk FP;
	// server tetap memvalidasi lewat jenis_code.
	if ($this->input->post("oper") === "edit" && ($selected == 3 || $inv == "INWHM")):
	?>
	<input type="button" value="PDF GABUNGAN" id="btnPrintGabungan" style="width: 130px; height: 30px; float: right;" onclick="print_invoice_gabungan()" title="Cetak 1 invoice berisi item kena pajak (invoice ini) + invoice non-tax di job yang sama, dengan nomor invoice ini">
	<?php endif; ?>
	<input type="button" value="SAVE" id="btnSave" style="width: 100px; height: 30px; float: right;" onclick="save_and_generate()">
	<!--<input type="button" value="PROFORMA" id="btnCancel" style="width: 100px; height: 30px; float: right;" onclick="cetak_proforma()"> -->
	<input type="button" value="POST INVOICE" id="btnPost" style="width: 120px; height: 30px; float: left;" onclick="get_proses_post()">
	<?php if ($this->input->post("oper") === "edit" && $can_paid_invoice_revision): ?>
	<input type="button" value="REVISI PAID" id="btnRevision" style="width: 120px; height: 30px; float: left; margin-left:5px;" onclick="open_paid_invoice_revision()">
	<?php endif; ?>
</div>
<div id="form-excel-invoice"></div>
<?= form_input(array("type"=>"hidden","id"=>"chache_sp_name","value"=>$shipper_name)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_sp_add","value"=>$shipper_address)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_nama","value"=>$nama)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_npwp","value"=>$npwp)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_alamat_npwp","value"=>$alamat_npwp)) ?>
<?= form_input(array("type"=>"hidden","id"=>"chache_pph23","value"=>$pph23?? '')) ?>
<script type="text/javascript" >
var fin = '<?= base_url("finance")?>';
var CAN_PAID_INVOICE_REVISION = <?= $can_paid_invoice_revision ? 'true' : 'false' ?>;
$(function () {
	$("input[name='invoice_date']").datepicker({dateFormat : "dd/mm/yy"});
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
	
	var year = $("#NOMER_JOB").val().substr(7,2);
	
	if(oper=='add') {
		
		$("#btnPrint").attr('hidden',true);
		$("#invoice_no").val("Auto Generated on Save"); 
		$("#invoice_no").prop("disabled", true);
		$("#shipper_name").val($('input[id="KET_KOSTUMER"]').val());
		$("#shipper_address").val($('textarea[id="ALAMAT_KOSTUMER"]').val());
		$('#tabel_tiga').jqGrid("clearGridData", true);
		$("#tabel_tiga").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		//console.log($("#shipper_name").val($('input[id="KET_KOSTUMER"]').val()));
		
	} else {
		
		var shp_nm = $("#chache_sp_name").val();
		var shp_add = $("#chache_sp_add").val();
		var nama = $("#chache_nama").val();
		var npwp = $("#chache_npwp").val();
		var alamat_npwp = $("#chache_alamat_npwp").val();
		var pph23 = $("#chache_pph23").val();
		
		if (shp_nm == "") {
			shp_nm = $('input[id="KET_KOSTUMER"]').val();
		}
		
		if (shp_add == "") {
			shp_add = $('textarea[id="ALAMAT_KOSTUMER"]').val();
		}
		
		$("#invoice_no").val(inv_akhir);
		$("#btnPrint").attr('hidden',false);
		$("#shipper_name").val(shp_nm);
		$("#shipper_address").val(shp_add);
		$("#select_npwp").val(nama);
		$("#npwp").val(npwp);
		$("#alamat_npwp").val(alamat_npwp);
		$("select[name='opt_jenis']").attr("disable",true);
		$("input[name='pph23'][value='" + pph23 + "']").prop("checked", true);
	}
	
	$("#si_exportir").val(`<?= $si_exportir ?>`);	
	$("#incoterms").val("<?php echo $this->input->post('incoterms') ?>");	
	$("#invoice_date").val("<?php echo $this->input->post('date') ?>");
	$("#invoice_status").val("<?php echo $this->input->post('status') ?>");
	$("#shipper_id").val($('select[id="KOSTUMER"]').val());
	$("input[name='kode_ref']").val("<?php echo $this->input->post('job_no') ?>");
	$("#group_invoice").val("<?php echo $this->input->post('group') ?>");

	// Job 200 (AF export) / 600 (AF import) = mode Air Freight: tampilkan
	// pilihan MAWB/HAWB (default MAWB) di editor. Job lain: sembunyikan.
	var kodeRefForAwb = $("input[name='kode_ref']").val();
	var jobCodeForAwb = kodeRefForAwb ? kodeRefForAwb.substr(4,3) : '';
	if (jobCodeForAwb == '200' || jobCodeForAwb == '600') {
		$("#awb_type_row").show();
	} else {
		$("#awb_type_row").hide();
		$("input[name='awb_type'][value='mawb']").prop('checked', true);
	}

	get_table_tiga();

});

function get_nomer_invoice() {
	$.ajax ({
		type :"POST",
		url : base_url+"/finance/c_inv_to_shipper/get_nomer_invoice",
		data : {
			oper: 'add',
			jenis: $("select[name='opt_jenis']").val(),
			job_no: $("input[id='NOMER_JOB']").val(),
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if (data.num_inv==false) {
				$("#invoice_no").val("");
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
					var numInvAkhir = $.strPad(numInv , 5, '0');
				}
				
				//var year = new Date().getFullYear().toString().substring(2, 4);
				//console.log(data.jenis);
				var year = $("#NOMER_JOB").val().substr(7,2);
				var job = $("#NOMER_JOB").val().substr(4,1);
				var region_id = '<?php echo $this->session->userdata("region_id") ?>';
				
				if(region_id=="Cikarang") {
					var ino = "INC/"
				}
				else if(region_id=="Surabaya") {
					var ino = "SYINF/"
				}
				else if(region_id=="Semarang") {
					var ino = "SMINF/"
				}
				else if(region_id=="OSLSUB") {
					var ino = "SYOSL/"
				}
				else if(region_id=="OSLSRG") {
					var ino = "SMOSL/"
				}
				else if(region_id=="Medan") {
					var ino = "MINF/"
				}
				else if(region_id=="Bandung") {
					var ino = "BINF/"
				}
				else if(region_id=="Makassar") {
					var ino = "MKSIN/";
				}
				else if(region_id=="GCL WAREHOUSE") {
					var ino = "INWHM/"}	
				else if(region_id=="Winfast") {
					var ino = "INWIN/"
				}
				else if(region_id=="BPLINE") {
					var ino = "INBPL/"
				}
				else {
					var ino = "IN/"
				}
				
				if (data.jenis =="1") {
				if (job=="4"|| job=="9") {
				if(region_id=="Cikarang") {
					ino = "INC/";
				}
				else if(region_id=="Surabaya") {
					ino = "SYINF/";
				}
				else if(region_id=="Semarang") {
					ino = "SMINF/";
				}
				else if(region_id=="Medan") {
					ino = "MINF/";
				}
				else if(region_id=="Bandung") {
					ino = "BINF/";
				}
				else if(region_id=="Makassar") {
					var ino = "MKSIN/";
				}
				else if(region_id=="GCL WAREHOUSE") {
					var ino = "INWHM/";
				}
				else if(region_id=="Winfast") {
					 ino = "INWIN/";
				}
				else if(region_id=="BPLINE") {
					var ino = "INBPL/";
				}
				else{
					
						ino = "INV/"
					}
				}
			if (job=="7") {
				if(region_id=="Cikarang") {
				var ino = "INC/"
				}
				else if(region_id=="Surabaya") {
					var ino = "SYINF/"
				}
				else if(region_id=="Semarang") {
					var ino = "SMINF/"
				}
				else if(region_id=="Medan") {
					var ino = "MINF/"
				}
				else if(region_id=="Bandung") {
					var ino = "BINF/"
				}
				else if(region_id=="Makassar") {
					var ino = "MKSIN/"
				}
				else if(region_id=="Winfast") {
					var ino = "INWIN/"
				}
				else if(region_id=="BPLINE") {
					var ino = "INBPL/"
				}else{
						
						ino = "INF/"	
					}
				}
				else {
						ino = ino;
					}
				}			
				
		else if (data.jenis =="2"){
			if(region_id=="Cikarang") {
				var ino = "CINR/"
				}
			else if(region_id=="Surabaya") {
					var ino = "SINR/"
				}
			else if(region_id=="Semarang") {
					var ino = "SRINR/"
				}
			else if(region_id=="Medan") {
					var ino = "MINR/"
				}
			else if(region_id=="Bandung") {
					var ino = "BINR/"
				}
			else if(region_id=="Makassar") {
					var ino = "MKSINR/"
				}
			else
				{
					ino = "INR/"
				}
			}
		else if (data.jenis =="3") {
					
				if(region_id=="Cikarang") {
					var ino = "ICFP/"
				}
				else if(region_id=="Surabaya") {
					var ino = "SUBFP/"
				}
				else if(region_id=="Semarang") {
					var ino = "SMGFP/"
				}
				else if(region_id=="Medan") {
					var ino = "MINFP/"
				}
				else if(region_id=="Bandung") {
					var ino = "BINFP/"
				}
				else if(region_id=="Makassar") {
					var ino = "MKSINFP/"
				}
				else if(region_id=="GCL WAREHOUSE") {
					var ino = "INWHM/"
					}	
				else{
					     var ino = "INFP/"
					}
					
				}
				$("#invoice_no").val(ino+year+numInvAkhir);
				$("#table_tiga").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				
			}
		}
	})
}

function out()
{
	$("#form-invoice").fadeOut('slow', function() 
	{
		$(this).dialog('close');
	})
}

function get_table_tiga()
{
	var kode_ref = $("input[name='kode_ref']").val();	
	var year = $("#NOMER_JOB").val().substr(7,2);
	var no_invoice = $('#invoice_no').val().replace('IN/'+year,'');
	
	var grid = $("#tabel_tiga"), i;
	grid.jqGrid({
		url : base_url+"/finance/c_inv_to_shipper/get_table_tiga",
		mtype : "post", 
		datatype: "json", 
		loadonce: true, 
		postData: { id : $("#id-form").val() },
		colNames:['No','Code','Item','Qty','Currency','Unit Price','Price','Remarks'],
    	colModel:
		[
			{name:'no_invoice',index:'no_invoice', editable: true, hidden:true, editrules:{edithidden:true}},
			{name:'code_service',index:'code_service', align:"left", width:50, editable: true, hidden: false},
			{name:'item',index:'item', editable: true, width:270, 
				edittype:"select",
				editoptions:{dataUrl: '../afg_c_dropdown/dropdownItemService'}
			},
			{name:'qty',index:'qty', width:130, editable: true, align: 'center',
    			formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces:4, defaultValue: '' }
			},
			{name:'currency',index:'currency',editable: true, width:120, align: 'center',
				edittype:"select",formatter:'select', editoptions:{value:" : ;USD:USD;RP.:RP."}
			},
			{name:'unit_price',index:'unit_price',editable: true, width:145, align: 'right',
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }
			},
			{name:'price',index:'price',editable: true, width:145, align: 'right',
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 0, defaultValue: '' }
			},
			{name:'remarks',index:'remarks',editable: true, width:155, align: 'left'
			},
				
      ],
      sortname: "id",
		sortorder: "asc",
      shrinkToFit:false,
		multiselect:true,
		loadonce : false,
		rowNum:100,
		width: 1110,
		height: 'auto',
		rowList:[-0],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:false,
		editurl:"c_inv_to_shipper/manage_data",
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
    				table : 'x_finance_item',
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
	
	var div = $(".ui-jqgrid-bdiv");
 	var nx = div.length-1;
 	var divx = div[nx];
 	$(divx).css({'min-height':'100px'});
}

function insert_data_invoice() {
	$.ajax({
		type:"POST",
		url : base_url+"/finance/c_inv_to_shipper/manage_data_table",
		data: 
		{
			oper: 'add',
			id : '',
			table: 'x_finance_item',
			kepada: $("input[name='shipper_id']").val(),
			jenis: $("select[name='opt_jenis']").val(),
			kode_ref: $("input[id='NOMER_JOB']").val(),
			number : '<?php echo $id ?>',
			destination : encodeURIComponent("<?php echo $destination; ?>"),
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


function invoice_requires_revision(response)
{
    var msg = '';

    if (response) {
        msg = response.message || response.msg || '';

        if (!msg && response.data) {
            msg = response.data.message || response.data.msg || '';
        }
    }

    return (
        msg === 'PAID_INVOICE_REQUIRES_REVISION'
        || (response && parseInt(response.requires_revision || 0, 10) === 1)
    );
}

function invoice_response_message(response, fallback)
{
    if (!response) return fallback || 'UNKNOWN_ERROR';
    return response.message || response.msg || fallback || 'UNKNOWN_ERROR';
}

function open_paid_invoice_revision(afterSuccess)
{
    if (!CAN_PAID_INVOICE_REVISION) {
        Swal.fire({
            icon: 'error',
            title: 'Akses ditolak',
            text: 'Anda tidak memiliki hak untuk membatalkan status PAID dan membuka revisi invoice.'
        });
        return;
    }

    var finance_id = parseInt($("#id-form").val() || 0, 10);
    var invoice_no = $.trim($("#invoice_no").val() || '');

    if (!finance_id) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak bisa revisi',
            text: 'Finance ID tidak tersedia.'
        });
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Invoice sudah PAID',
        html:
            'Status <b>PAID</b> akan dibatalkan menjadi <b>UNPAID</b> agar invoice dapat direvisi.<br><br>' +
            '<b>Riwayat payment/GL tidak dihapus.</b> Alasan revisi wajib diisi.',
        input: 'textarea',
        inputLabel: 'Alasan revisi / renegosiasi',
        inputPlaceholder: 'Contoh: consignee renegosiasi charge setelah invoice paid',
        showCancelButton: true,
        confirmButtonText: 'Batalkan PAID & Buka Revisi',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: function(value) {
            value = $.trim(value || '');

            if (value.length < 5) {
                Swal.showValidationMessage('Alasan revisi minimal 5 karakter.');
                return false;
            }

            return value;
        }
    }).then(function(result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Membuka revisi...',
            text: 'Membatalkan status PAID secara terkontrol.',
            allowEscapeKey: false,
            allowOutsideClick: false,
            didOpen: function() { Swal.showLoading(); }
        });

        $.ajax({
            type: 'POST',
            url: base_url + '/finance/c_inv_to_shipper/reopen_paid_invoice_revision',
            dataType: 'json',
            data: {
                finance_id: finance_id,
                invoice_no: invoice_no,
                reason: result.value
            },
            success: function(res) {
                if (!res || parseInt(res.status || 0, 10) !== 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal membuka revisi',
                        text: invoice_response_message(res, 'REVISION_OPEN_FAILED')
                    });
                    return;
                }

                $('#invoice_status').val('unpaid');

                Swal.fire({
                    icon: 'success',
                    title: 'Revisi dibuka',
                    text:
                        'Status PAID sudah dibatalkan menjadi UNPAID. ' +
                        'Revision #' + (res.revision_no || '-') + ' siap diproses.'
                }).then(function() {
                    if (typeof afterSuccess === 'function') {
                        afterSuccess();
                    }
                });
            },
            error: function(xhr) {
                var res = xhr.responseJSON || null;
                if (!res && xhr.responseText) {
                    try { res = JSON.parse(xhr.responseText); } catch (e) {}
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal membuka revisi',
                    text: invoice_response_message(res, xhr.responseText || 'REVISION_OPEN_FAILED')
                });
            }
        });
    });
}

// PERBAIKI JUGA TOMBOL TARIK DATA MANUAL AGAR TIDAK ERROR
function tarik_data_invoice()
{
	var kode_ref_val = $("input[name='kode_ref']").val();
	let jenis = $("select[name='opt_jenis']").val();
	if($("select[name='opt_jenis']").val() =='' || $("select[name='invoice_date']").val()==''){
		show_messages('Jenis Invoice & Inv Date tidak boleh kosong');
		return false;
	}
	else
	{
		var DataTarik = 
		{
			table: 'x_finance',
			kepada: $("input[name='shipper_id']").val(),
			invoice_db: "INVOICE",
			jenis: jenis,
			kode_ref: kode_ref_val,
			number : $("input[id='id-form']").val(), // PERBAIKAN: Jangan pakai echo $id
			inv_no : $("input[name='invoice_no']").val(),
			group : $('#group_invoice').val(),
			destination : encodeURIComponent("<?php echo $destination; ?>"),
		}
		$.ajax
		({
			type:"POST",
			url : base_url+"/finance/c_inv_to_shipper/tarik_data_invoice",
			data: DataTarik,
			dataType: 'json', 
			success:function(response)
			{
				/*
				 * PENTING: cek status/revision SEBELUM membaca response.ppn.
				 * Response PAID_INVOICE_REQUIRES_REVISION tidak memiliki field ppn.
				 */
				if (!response || response.status != '1') {
					if (invoice_requires_revision(response)) {
						open_paid_invoice_revision(function() {
							tarik_data_invoice();
						});
						return;
					}

					Swal.fire({
						icon: 'error',
						title: 'Tarik Data gagal',
						text: invoice_response_message(response, 'INVOICE_REBUILD_FAILED')
					});
					return;
				}

				if (response.ppn && response.ppn !== 'ok') {
					Swal.fire({
						icon: 'warning',
						title: 'Perhatian!',
						text: response.ppn,
						confirmButtonText: 'Mengerti'
					});
					return;
				}

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
	else if($("select[id='opt_jenis']").val()=='3' && $("#select_npwp").val() == '' ){
		$("select[id='opt_jenis']").focus();
		Swal.fire({
		  title: "Nama wajib pajak belum diisi!",
		  icon: "error",
		  draggable: true
		});
		return false;
	}
	else if ($("input[id='invoice_date']").val()==''){
		$("input[id='invoice_date']").focus();
		show_messages('Please complete the required data, Jenis & Inv Date Can\'t EMPTY VALUE');
		return false;
	}
	else
	{
		var year = $("#NOMER_JOB").val().substr(7,2);
		var date = $("input[name='invoice_date']").val();
		var form_data =
		{	
			table  		: 'x_finance',
			oper	 	: '<?php echo $_POST["oper"] ?>',			
			id		 	: $("input[id='id-form']").val(),
			job_no 		: $("input[name='kode_ref']").val(),
			incoterms	: $('#incoterms').val(),
			jenis	 	: "INVOICE",
			kepada 		: 'SHIPPER',
			kepada_kode	: $("input[name='shipper_id']").val(),
			group  		: $('#group_invoice').val(),
			number 		: $("input[name='invoice_no']").val(),
			date   		: date.substr(6,4)+date.substr(3,2)+date.substr(0,2),
			shipper_name: $("input[name='shipper_name']").val(),
			shipper_address : $("textarea[name='shipper_address']").val(),
			se_exportir : $("textarea[id='si_exportir']").val(),
			nama		: $("#select_npwp").val(),
			npwp		: $("#npwp").val(),
			alamat		: $("#alamat_npwp").val(),
			pph23		: $("input[name='pph23']:checked").val(),
			region_id 	: '<?php echo $this->session->userdata("region_id") ?>',
		};
		
		$.ajax({
			url : base_url+"/finance/c_inv_to_shipper/manage_data",
			type : 'POST',
			data : form_data,
			success: function(response)
			{
				var data = $.parseJSON(response);
				if (data.status==3) {
					show_messages("Error Saving Data, Duplicate INVOICE NUMBER");
				}
				$("#table_satu").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				$("#table_empat").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				$("#table_tiga").jqGrid('setGridParam',{ datatype: "json",}).trigger("reloadGrid");
				$("#form-invoice").dialog( "close");
				//tarik_data_invoice();
				$('#btnSave').attr('disabled',true);			
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
	var kode_ref_val = $("input[name='kode_ref']").val(); // Ambil dari input hidden lokal
	$("#out-invoice").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#out-invoice").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 960,
		modal: true,
		position:['center',0],
		title: "INVOICE " + kode_ref_val,
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		},
		open : function () {
			var data = {};
			// PERBAIKAN: Ambil ID dari input hidden yang terupdate via AJAX
			data.id = $("input[id='id-form']").val(); 
			data.jenis = $("select[name='opt_jenis']").val();
			data.invoice_date = $("input[id='invoice_date']").val().replace(/\//g,'-');
			data.shipper = $("input[id='shipper_name']").val();
			data.kepada = $("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val();
			data.si_shipper = $("textarea[id='si_exportir']").val();
			// PERBAIKAN: Ambil Job No dari variabel yang aman
			data.kode_ref = kode_ref_val; 
			data.region_id = '<?php echo $this->session->userdata("region_id") ?>'; // Lebih aman dari session
			data.no_invoice = $("input[id='invoice_no']").val();
			data.kepada_pajak    =   $("#select_npwp").val()+'\n'+$("#alamat_npwp").val();
			data.pos    =  $("input[id='shipper_name']").val().indexOf('GIVAUDAN');
			data.pph23   =  $("input[name='pph23']:checked").val();
			data.awb_type = $("input[name='awb_type']:checked").val() || 'mawb';
			var url=serialize(data);
			$('#out-invoice').html('<object data="'+fin+'/c_inv_to_shipper/invoice?'+url+'" width="960px" height="600px" type="application/pdf">');
		}
	});
}

/*
 * =====================================================================
 * INVOICE GABUNGAN (FP + non-tax dalam 1 PDF, nomor = nomor FP)
 * =====================================================================
 * Hanya cetak. Invoice di sistem tetap terpisah, faktur pajak/XML Coretax
 * tetap dari invoice FP saja.
 */
function escape_html_text(t)
{
	return $('<div>').text(t == null ? '' : String(t)).html();
}

function print_invoice_gabungan()
{
	var finance_id = parseInt($("input[id='id-form']").val() || 0, 10);

	if (!finance_id) {
		Swal.fire({ icon: 'warning', title: 'Simpan invoice dulu', text: 'Finance ID tidak tersedia.' });
		return;
	}

	$.ajax({
		type: 'POST',
		url: base_url + '/finance/c_inv_to_shipper/get_combined_invoice_candidates',
		dataType: 'json',
		data: { finance_id: finance_id },
		success: function(res) {
			if (!res || parseInt(res.status || 0, 10) !== 1) {
				Swal.fire({ icon: 'error', title: 'Tidak bisa digabung', text: invoice_response_message(res, 'COMBINE_FAILED') });
				return;
			}

			if (!res.candidates || !res.candidates.length) {
				Swal.fire({
					icon: 'info',
					title: 'Tidak ada invoice non-tax',
					text: 'Belum ada invoice non-tax (IN/INR) lain di job ini untuk digabung dengan ' + res.main.number + '.'
				});
				return;
			}

			var html = '<div style="text-align:left; font-size:13px">' +
				'<p style="margin:0 0 8px">Nomor yang dicetak: <b>' + escape_html_text(res.main.number) + '</b></p>' +
				'<p style="margin:0 0 6px">Gabungkan dengan invoice non-tax:</p>';

			$.each(res.candidates, function(i, c) {
				var total = Number(c.grand_total || 0).toLocaleString('en-US');
				html += '<label style="display:block; margin:4px 0; cursor:pointer">' +
					'<input type="checkbox" class="gab-inv" value="' + parseInt(c.id, 10) + '" checked> ' +
					'<b>' + escape_html_text(c.number) + '</b>' +
					(c.group && c.group.toUpperCase() !== 'NULL' ? ' &middot; grup ' + escape_html_text(c.group) : '') +
					' &middot; ' + escape_html_text(c.date) +
					' &middot; ' + total +
					' &middot; ' + escape_html_text(c.status) +
					'</label>';
			});

			html += '<hr style="margin:10px 0">' +
				'<label style="display:block; cursor:pointer">' +
				'<input type="checkbox" id="gab-show-ref" checked> Tampilkan nomor invoice non-tax sebagai referensi kecil' +
				'</label></div>';

			Swal.fire({
				title: 'Print Invoice Gabungan',
				html: html,
				showCancelButton: true,
				confirmButtonText: 'Cetak',
				cancelButtonText: 'Batal',
				focusConfirm: false,
				preConfirm: function() {
					var popup = Swal.getPopup();
					var ids = [];
					$(popup).find('input.gab-inv:checked').each(function() {
						ids.push($(this).val());
					});
					if (!ids.length) {
						Swal.showValidationMessage('Pilih minimal 1 invoice non-tax.');
						return false;
					}
					return {
						ids: ids,
						show_ref: $(popup).find('#gab-show-ref').is(':checked') ? '1' : '0'
					};
				}
			}).then(function(result) {
				if (!result.isConfirmed) return;
				open_invoice_gabungan_pdf(finance_id, result.value.ids, result.value.show_ref);
			});
		},
		error: function(xhr) {
			var res = xhr.responseJSON || null;
			if (!res && xhr.responseText) {
				try { res = JSON.parse(xhr.responseText); } catch (e) {}
			}
			var msg = invoice_response_message(res, 'COMBINE_FAILED');
			if (msg === 'NOT_FAKTUR_PAJAK') {
				msg = 'Invoice gabungan hanya bisa dicetak dari invoice Faktur Pajak (INFP).';
			}
			Swal.fire({ icon: 'error', title: 'Tidak bisa digabung', text: msg });
		}
	});
}

function open_invoice_gabungan_pdf(finance_id, ids, show_ref)
{
	var kode_ref_val = $("input[name='kode_ref']").val();
	$("#out-invoice").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#out-invoice").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 960,
		modal: true,
		position:['center',0],
		title: "INVOICE GABUNGAN " + $("input[id='invoice_no']").val(),
		buttons : {  },
		close: function()
		{
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		},
		open : function () {
			var data = {};
			data.id           = finance_id;
			data['with']      = ids.join(',');
			data.show_ref     = show_ref;
			data.invoice_date = $("input[id='invoice_date']").val().replace(/\//g,'-');
			data.shipper      = $("input[id='shipper_name']").val();
			data.kepada       = $("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val();
			data.kepada_pajak = $.trim($("#select_npwp").val()) === '' ? '' : $("#select_npwp").val()+'\n'+$("#alamat_npwp").val();
			data.si_shipper   = $("textarea[id='si_exportir']").val();
			data.pph23        = $("input[name='pph23']:checked").val();
			data.awb_type     = $("input[name='awb_type']:checked").val() || 'mawb';
			data.kode_ref     = kode_ref_val;
			var url = serialize(data);
			$('#out-invoice').html('<object data="'+fin+'/c_inv_to_shipper/invoice_gabungan?'+url+'" width="960px" height="600px" type="application/pdf">');
		}
	});
}

function print_e_inv(){
	var kode_ref_val = $("input[name='kode_ref']").val();
	$("#out-invoice").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#out-invoice").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 960,
		modal: true,
		position:['center',0],
		title: "INVOICE " + kode_ref_val,
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		},
		open : function () {
			var data = {};
			data.id = $("input[id='id-form']").val(); // PERBAIKAN
			data.jenis = $("select[name='opt_jenis']").val();
			data.invoice_date = $("input[id='invoice_date']").val().replace(/\//g,'-');
			data.shipper = $("input[id='shipper_name']").val();
			data.kepada = $("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val();
			data.si_shipper = $("textarea[id='si_exportir']").val();
			data.kode_ref = kode_ref_val; // PERBAIKAN
			data.region_id = '<?php echo $this->session->userdata("region_id") ?>';
			data.no_invoice = $("input[id='invoice_no']").val();
			data.kepada_pajak    =   $("#select_npwp").val()+'\n'+$("#alamat_npwp").val();
			data.pos    =  $("input[id='shipper_name']").val().indexOf('GIVAUDAN');
			data.pph23   =  $("input[name='pph23']:checked").val();
			data.awb_type = $("input[name='awb_type']:checked").val() || 'mawb';
			var url=serialize(data);
			$('#out-invoice').html('<object data="'+fin+'/c_inv_to_shipper/e_invoice?'+url+'" width="960px" height="600px" type="application/pdf">');
		}
	});
}

function print_e_inv_kredit(){
	var kode_ref_val = $("input[name='kode_ref']").val();
	$("#out-invoice").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#out-invoice").dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 960,
		modal: true,
		position:['center',0],
		title: "INVOICE " + kode_ref_val,
		buttons : {  },
		close: function()  
		{	 
			$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
		},
		open : function () {
			var data = {};
			data.id = $("input[id='id-form']").val(); // PERBAIKAN
			data.jenis = $("select[name='opt_jenis']").val();
			data.invoice_date = $("input[id='invoice_date']").val().replace(/\//g,'-');
			data.shipper = $("input[id='shipper_name']").val();
			data.kepada = $("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val();
			data.si_shipper = $("textarea[id='si_exportir']").val();
			data.kode_ref = kode_ref_val; // PERBAIKAN
			data.region_id = '<?php echo $this->session->userdata("region_id") ?>';
			data.no_invoice = $("input[id='invoice_no']").val();
			data.kepada_pajak    =  $("#select_npwp").val()+'\n'+$("#alamat_npwp").val();
			data.pos    =  $("input[id='shipper_name']").val().indexOf('GIVAUDAN');
			data.pph23   =  $("input[name='pph23']:checked").val();
			data.awb_type = $("input[name='awb_type']:checked").val() || 'mawb';
			var url=serialize(data);
			$('#out-invoice').html('<object data="'+fin+'/c_inv_to_shipper/e_invoice_kredit?'+url+'" width="960px" height="600px" type="application/pdf">');
		}
	});
}


function save_tbl_tiga(rowid,cellname,value){
	if(rowid.substr(0,1)== 'j'){
		var mode = 'add';
	}
	else
	{
		var mode = 'edit';	
	}
	var year = $("#NOMER_JOB").val().substr(7,2);
	var grid = $("#tabel_tiga"), i; 	
	DataTblTiga = 
	{
		oper 	: mode,
		id 	 	: rowid,
		table	: 'x_finance_item',
		field 	: cellname,
		value	: value,
	}
	
	$.ajax
	({
		type:"POST",
		url : base_url+"/finance/c_inv_to_shipper/manage_data_table",
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

function invoice_post_success_info(response)
{
    if (!response || typeof response !== 'object') {
        return null;
    }

    /*
     * =====================================================
     * RESPONSE NORMAL BACKEND GCL / NON-JAKARTA
     * =====================================================
     *
     * Support:
     * status: 1
     * status: "1"
     * status: true
     */
    if (
        response.status === 1 ||
        response.status === '1' ||
        response.status === true
    ) {
        return {
            success: true,
            message:
                typeof response.message === 'string'
                    ? response.message
                    : 'Invoice berhasil diposting ke ERP.',
            erp_invoice_no:
                response.erp_invoice_no ||
                response.invoice_no ||
                ''
        };
    }

    /*
     * =====================================================
     * RESPONSE LEGACY JAKARTA / JASAWEB
     * =====================================================
     *
     * Response seperti:
     *
     * {
     *   "message": {
     *       "Sales Invoice": {
     *           "name": "INFP/2631126-1",
     *           "docstatus": 1,
     *           ...
     *       }
     *   },
     *   "exc": [...]
     * }
     *
     * Field "exc" JANGAN dianggap gagal karena pada ERP lama
     * bisa berisi debug SQL walaupun invoice sudah Submitted.
     */
    var salesInvoice = null;

    if (
        response.message &&
        typeof response.message === 'object' &&
        response.message['Sales Invoice']
    ) {
        salesInvoice = response.message['Sales Invoice'];
    }

    if (
        salesInvoice &&
        salesInvoice.name &&
        parseInt(salesInvoice.docstatus, 10) === 1
    ) {
        return {
            success: true,
            message: 'Invoice berhasil diposting ke ERP.',
            erp_invoice_no: salesInvoice.name
        };
    }

    return null;
}


function invoice_post_error_message(data, fallback)
{
    fallback = fallback || 'Terjadi kesalahan saat posting invoice.';

    if (!data) {
        return fallback;
    }

    /*
     * message string biasa
     */
    if (
        typeof data.message === 'string' &&
        $.trim(data.message) !== ''
    ) {
        return data.message;
    }

    /*
     * message berupa object
     */
    if (
        data.message &&
        typeof data.message === 'object'
    ) {
        if (
            typeof data.message.message === 'string' &&
            $.trim(data.message.message) !== ''
        ) {
            return data.message.message;
        }

        if (
            typeof data.message.error === 'string' &&
            $.trim(data.message.error) !== ''
        ) {
            return data.message.error;
        }
    }

    /*
     * Error standar Frappe / ERPNext
     */
    if (data._server_messages) {
        try {
            var serverMessages =
                typeof data._server_messages === 'string'
                    ? JSON.parse(data._server_messages)
                    : data._server_messages;

            if (
                Array.isArray(serverMessages) &&
                serverMessages.length
            ) {
                var first = serverMessages[0];

                if (typeof first === 'string') {
                    try {
                        first = JSON.parse(first);
                    } catch (e) {
                        return first;
                    }
                }

                if (
                    first &&
                    typeof first.message === 'string'
                ) {
                    return first.message;
                }
            }
        } catch (e) {}
    }

    if (
        typeof data.error === 'string' &&
        $.trim(data.error) !== ''
    ) {
        return data.error;
    }

    if (
        typeof data.exception === 'string' &&
        $.trim(data.exception) !== ''
    ) {
        return data.exception;
    }

    /*
     * Jangan sampai kembali menjadi [object Object].
     */
    try {
        var json = JSON.stringify(data);

        if (json && json !== '{}') {
            return json;
        }
    } catch (e) {}

    return fallback;
}


function show_invoice_post_success(info)
{
    var text = info.message || 'Invoice berhasil diposting ke ERP.';

    if (info.erp_invoice_no) {
        text += '\nERP Invoice: ' + info.erp_invoice_no;
    }

    Swal.fire({
        icon: 'success',
        title: 'Terimakasih, invoice sudah berhasil diposting',
        text: text
    });
}


function get_proses_post()
{
    var finance_id = parseInt(
        $("input[id='id-form']").val() || 0,
        10
    );

    var invoice_no = $.trim(
        $("input[id='invoice_no']").val() || ''
    );

    /*
     * TIDAK ADA lagi pembatasan region di frontend.
     *
     * Jakarta / Non-Jakarta ditentukan backend
     * C_inv_to_shipper::post_invoice_to_ar().
     */
    if (!finance_id && !invoice_no) {
        Swal.fire({
            icon: 'warning',
            title: 'Validasi gagal',
            text: 'ID finance / nomor invoice tidak tersedia.'
        });

        return;
    }

    Swal.fire({
        title: 'Memproses Posting...',
        text: 'Mohon tunggu sebentar',
        allowEscapeKey: false,
        allowOutsideClick: false,

        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',

        url:
            "<?php echo site_url(
                'finance/c_inv_to_shipper/post_invoice_to_ar'
            )?>",

        dataType: 'json',

        data: {
            finance_id: finance_id,
            invoice_no: invoice_no
        },

        success: function(response)
        {
            /*
             * ===============================================
             * CEK SUKSES
             * ===============================================
             */
            var successInfo =
                invoice_post_success_info(response);

            if (successInfo) {
                show_invoice_post_success(successInfo);
                return;
            }

            /*
             * HTTP 200 tetapi body menyatakan gagal.
             */
            if (invoice_requires_revision(response)) {
                open_paid_invoice_revision();
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Posting Gagal',

                text: invoice_post_error_message(
                    response,
                    'POST_FAILED'
                )
            });
        },

        error: function(xhr)
        {
            var data = xhr.responseJSON || null;

            if (!data && xhr.responseText) {
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    data = null;
                }
            }

            /*
             * ===============================================
             * PENTING UNTUK JAKARTA
             * ===============================================
             *
             * ERP/Jasaweb kadang mengembalikan HTTP error
             * walaupun Sales Invoice SUDAH berhasil dibuat.
             *
             * Jadi cek isi body lebih dulu sebelum
             * menampilkan "Posting Gagal".
             */
            var successInfo =
                invoice_post_success_info(data);

            if (successInfo) {
                show_invoice_post_success(successInfo);
                return;
            }

            /*
             * Benar-benar gagal.
             */
            Swal.fire({
                icon: 'error',
                title: 'Posting Gagal',

                text: invoice_post_error_message(
                    data,
                    xhr.responseText ||
                    'Error, no connection to server!'
                )
            });
        }
    });
}

on_edit=function () {
	
}
function download_excel() {
	var id= $("input[id='id-form']").val();
	var no_inv = $("input[id='invoice_no']").val();
	$('#form-excel-invoice').html('<div style="margin-bottom: 10px">'+
	'<center>'+
	'<iframe id="frame_invoice" src="'+base_url+'/finance/c_inv_to_shipper/excel/'+id+'/'+no_inv+'" width="310px" height="60"></iframe>'+
	'</center></div>');
}


$(function(){
		var data = "<?php echo site_url('afg_c_dropdown/dropdown_account')?>";
		$("#select_npwp").autocomplete({
			source: data,
			select: function(event, ui){
			event.preventDefault();
            $("#select_npwp").val(ui.item.label);
            $('#idnpwp').val(ui.item.value); 
			var nama = ui.item.label;
			var id = ui.item.value;
			onchange = get_nomor_npwp(id,nama);
			}
		});
	});
	
function get_nomor_npwp(id,nama) {
		
	if (nama == '' ) 
	{
			show_messages('Nama NPWP harus dipilih');
			$('#npwp').val('');	
	}
	else 
	{
		$.ajax
			({
				async: true,
				type : "POST",
				url  : base_url+'/finance/c_inv_to_shipper/npwp',
				data:
				{
					id : id,
					nama : nama,
				},
				success:function(response)
				{
					var data = $.parseJSON(response);
					console.log(data);
					$("#npwp").val(data[0]);
					$("#alamat_npwp").val(data[1]);
				},
				error:function(XMLHttpRequest)
				{
					show_messages("NPWP tidak ditemukan")
				}
			});
	}
}

function cetak_proforma(){
	var year = $("#NOMER_JOB").val().substr(7,2);
	$( "#out-proforma" ).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$( "#out-proforma" ).dialog(
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
				jenis			:	$("select[name='opt_jenis']").val(),
				id				:  '<?php echo $id ?>',
				kode_ref 		: 	$("input[id='NOMER_JOB']").val(),
				no_invoice 		: 	$("input[id='invoice_no']").val(),
				invoice_date 	: 	$("input[id='invoice_date']").val().replace(/\//g,'-'),
				kepada			:  	$("input[id='shipper_name']").val()+'\n'+$("textarea[id='shipper_address']").val(),
				si_shipper		:  	$("textarea[id='si_exportir']").val(),
				kepada_pajak    :   $("#select_npwp").val()+'\n'+$("#alamat_npwp").val(),
	}
	$.ajax
	({
			type: "POST",
			url : "<?php echo base_url('finance/c_proforma_invoice/print_proforma')?>/"+dataPost['kode_ref']+"/"+dataPost['id'],
			data:  dataPost,
			success: function(data)
			{
				$('#out-proforma').html('<iframe src="<?php echo base_url("finance/c_proforma_invoice/print_proforma") ?>/'+dataPost['kode_ref']+'/'+dataPost['id']+'" width="960px" height="600"></iframe>');
			},
			error:function(XMLHttpRequest)
			{

				$('#out-proforma').html(XMLHttpRequest.responseText)
			}
	});
}

function save_and_generate() {
    // 1. Validasi Input
    if ($("select[id='opt_jenis']").val() == '') {
        Swal.fire("Error", "Pilih Jenis Invoice terlebih dahulu!", "error");
        return false;
    }
    if ($("input[id='invoice_date']").val() == '') {
        Swal.fire("Error", "Tanggal Invoice harus diisi!", "error");
        return false;
    }
    
    // Tampilkan Loading
    Swal.fire({
        title: 'Sedang Memproses...',
        html: 'Menyimpan Header & Menarik Data...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    var date = $("input[name='invoice_date']").val();
    var jenis_code = $("select[id='opt_jenis']").val(); // PERBAIKAN: Ambil kode jenis
    
    // 2. Siapkan Data Header
    var form_data = {
        table: 'x_finance',
        oper: '<?php echo $this->input->post("oper") ?>',
        id: $("input[id='id-form']").val(),
        job_no: $("input[name='kode_ref']").val(),
        incoterms: $('#incoterms').val(),
        jenis: "INVOICE",
        kepada: 'SHIPPER',
        kepada_kode: $("input[name='shipper_id']").val(),
        group: $('#group_invoice').val(),
        number: $("input[name='invoice_no']").val(),
        date: date.substr(6, 4) + date.substr(3, 2) + date.substr(0, 2),
        shipper_name: $("input[name='shipper_name']").val(),
        shipper_address: $("textarea[name='shipper_address']").val(),
        se_exportir: $("textarea[id='si_exportir']").val(),
        nama: $("#select_npwp").val(),
        npwp: $("#npwp").val(),
        alamat: $("#alamat_npwp").val(),
        pph23: $("input[name='pph23']:checked").val(),
        region_id: '<?php echo $this->session->userdata("region_id") ?>',
        
        // PERBAIKAN: Kirim jenis_code agar server bisa generate nomor
        jenis_code: jenis_code 
    };

    // 3. Simpan Header ke Database
    $.ajax({
        url: base_url + "/finance/c_inv_to_shipper/manage_data",
        type: 'POST',
        data: form_data,
        success: function(response) {
            var data = $.parseJSON(response);
            
            // Cek jika gagal
            if (data.status == 0 || data.status == 3) {
                if (invoice_requires_revision(data)) {
                    Swal.close();
                    open_paid_invoice_revision(function() {
                        save_and_generate();
                    });
                    return;
                }

                Swal.fire("Gagal", data.msg || data.message || "Data tidak berubah/Gagal Simpan", "error");
                return;
            }

            // ============================================================
            // PERBAIKAN KRUSIAL: UPDATE DATA DARI RESPON SERVER
            // ============================================================
            // Kita wajib mengambil Nomor Baru & ID Baru yang digenerate server
            // agar proses 'tarik_data' selanjutnya tidak salah alamat.
            
            var new_invoice_no = data.new_number; // Pastikan Model PHP return ini
            var new_id_finance = data.new_id;     // Pastikan Model PHP return insert_id()
            
            if(new_invoice_no) {
                // Update tampilan form
                $("input[name='invoice_no']").val(new_invoice_no);
                $("#invoice_no").val(new_invoice_no);
                
                // Update ID hidden (PENTING untuk update selanjutnya)
                if(new_id_finance) {
                   $("input[id='id-form']").val(new_id_finance);
                }
            } else {
                // Fallback jika edit (tidak ada nomor baru)
                new_invoice_no = $("input[name='invoice_no']").val();
                new_id_finance = $("input[id='id-form']").val();
            }

            // 4. JIKA SUKSES SIMPAN HEADER -> LANGSUNG TARIK DATA ITEM
            var DataTarik = {
                table: 'x_finance',
                kepada: $("input[name='shipper_id']").val(),
                invoice_db: "INVOICE",
                jenis: $("select[name='opt_jenis']").val(),
                kode_ref: $("input[name='kode_ref']").val(),
                
                // PERBAIKAN: Gunakan ID & Nomor yang BARU saja didapat dari server
                number: new_id_finance, 
                inv_no: new_invoice_no,
                
                group: $('#group_invoice').val(),
                destination: encodeURIComponent("<?php echo $destination; ?>"),
            };

            $.ajax({
                type: "POST",
                url: base_url + "/finance/c_inv_to_shipper/tarik_data_invoice",
                data: DataTarik,
                dataType: 'json',
                success: function(resTarik) {
                    Swal.close(); 

                    if (resTarik.status == '1') {
                        // Reload Grids
                        $("#tabel_tiga").jqGrid("setGridParam", {
                            datatype: 'json',
                            postData: { id: new_id_finance }, // Update grid agar load ID baru
                            sortname: "id",
                            sortorder: "asc",
                        }).trigger("reloadGrid");

                        $("#table_satu").jqGrid('setGridParam', { datatype: "json" }).trigger("reloadGrid");
                        $("#table_dua").jqGrid('setGridParam', { datatype: "json" }).trigger("reloadGrid");

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Invoice: ' + new_invoice_no + ' Berhasil Dibuat!'
                        });

                        // Ubah tombol jadi Update
                        $('#btnSave').val('UPDATE');
                        $('#btnPrint').attr('hidden', false);
                        $('#btnPrinteInv').attr('hidden', false);

                    } else {
                        if (invoice_requires_revision(resTarik)) {
                            open_paid_invoice_revision(function() {
                                save_and_generate();
                            });
                            return;
                        }

                        Swal.fire(
                            "Warning",
                            invoice_response_message(
                                resTarik,
                                "Invoice tersimpan ("+new_invoice_no+") tapi Data Item kosong/gagal ditarik."
                            ),
                            "warning"
                        );
                    }
                }
            });

        },
        error: function(err) {
            Swal.fire("Error", "Terjadi kesalahan koneksi server", "error");
        }
    });
}


</script>

