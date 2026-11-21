	

/*

//function form_BILL_OF_LOADING()
/*
function get_stuffing_table(rowsp_id)
{
	var sp_id = $('#tabel_stuffing_plan').jqGrid('getCell',rowsp_id,'sp_id');
	$('#spId').val(sp_id); 
	getStuffingPlan('pilih');
}


function openBLFORM(Num)
{	
		$.ajax
		({
			type:"POST",
			url:"<?php echo site_url('afg_c_stuffing_plan/getBILLOFLOADING'); ?>/"+Num,
			data:{ kode_ref : $('#txtRefno2').val(),},
			success:function(data)
			{
				var data = $.parseJSON(data);			
				$('#textfield').val(data.job_no);
				$('#textfield2').val(data.shipper);
				$('#textfield3').val(data.job_no);
				$('#textfield4').val(data.shipper);
				$('#textfield5').val(data.job_no);
				$('#textfield6').val(data.shipper);
				$('#textfield7').val(data.job_no);
				$('#textfield8').val(data.shipper);
				$('#textfield9').val(data.job_no);
				$('#textfield10').val(data.shipper);
				$('#textfield11').val(data.job_no);
				$('#textfield12').val(data.shipper);
				$('#textfield13').val(data.job_no);
				$('#textfield14').val(data.shipper);
				$('#textfield15').val(data.job_no);
				$('#textfield16').val(data.shipper);
				$('#textfield17').val(data.job_no);
				$('#textfield18').val(data.shipper);
				$('#textfield19').val(data.job_no);
				$('#textfield20').val(data.shipper);
				$('#textfield21').val(data.job_no);
				$('#textfield22').val(data.shipper);
				$('#textfield23').val(data.job_no);
				$('#textfield24').val(data.shipper);
				$('#textfield25').val(data.job_no);
				$('#textfield26').val(data.shipper);
				$('#textfield27').val(data.job_no);
				$('#textfield28').val(data.shipper);
				$('#textfield29').val(data.job_no);
				$('#textfield30').val(data.shipper);
				$('#textfield31').val(data.job_no);
				$('#textfield32').val(data.shipper);
				$('#textfield33').val(data.job_no);
				$('#textfield34').val(data.shipper);
				$('#textfield35').val(data.job_no);
				$('#textfield36').val(data.shipper);
				$('#textfield37').val(data.job_no);
				$('#textfield38').val(data.shipper);
				$('#textfield39').val(data.job_no);
				$('#textfield40').val(data.shipper);
				form_BILL_OF_LOADING();
			},
			error:function(XMLHttpRequest)
			{
				alert("===============ERRORR===================\n\nEMPTY CACHE DATA STUFFING\n\Please refresh your browser\n\n========================================");
			}

		})			
}

function form_BILL_OF_LOADING()
{
	$( "#FORM_DISPLAY_BL" ).show();
	$( "#FORM_DISPLAY_BL" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 570,
		width: 750,
		modal: true,
		buttons: 
		{
			"CLOSE": function() 
			{
				$( this ).dialog( "close" );
				CancelInstanAccsess()
			}
		},
		close: function() 
		{	
				CancelInstanAccsess()
		}
	});
}

/*
function  tampilDetail(tipe,id,isPlain) 
{

}

function Confirm_Stuff(Num)
{
	$( "#Confirm-Stuff" ).show();
	$( "#Confirm-Stuff" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 150,
		width: 400,
		modal: true,
		buttons: 
		{
			"EDIT or DEL DATA": function() 
			{
				$( this ).dialog( "close" );
				$(".ui-jqgrid-titlebar-close",$("#tabel_sp_detail")[0].grid.cDiv).click();
			},
			"TO BL BUTTON": function() 
			{
				$( this ).dialog( "close" );
				to_BL(Num);
			},
		},
	});
}

function to_BL(Num)
{
	$("#btnBL_16").html("<a title=\"Instan Access Button\">Instan Access Button</a>");
	<?php 
		for ($i = 1; $i < 16; $i++) { echo "document.getElementById('btnBL-$i').setAttribute('href','javascript:openSEARCBLBUTTON(".$i.")');"."document.getElementById('btnBL-$i').setAttribute('style','color:#000; background-color: #DADADA; -webkit-box-shadow: 0 6px 2px -6px #333; -moz-box-shadow: 0 6px 2px -6px #333; box-shadow: 0 6px 2px -6px #333;');"; } 
	?>
	var btnBL_1 = document.getElementById('btnBL-1');
	btnBL_1.href = "javascript:openBLFORM()";
//	btnBL_1.href = "javascript:openBLFORM("+Num+")";
	
}
*/
