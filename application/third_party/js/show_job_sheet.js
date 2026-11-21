function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

$(function () {
	
	
	var grid=$('#transaction_stuffing_plan_detail'), i;
	
	var id = grid.getGridParam('selrow');
	var num = grid.getCell(id,'num');
	
	console.log({id,num})
	
	/*
	var kurs  = $('#txtKurs').val();
	if (kurs=="") {
		show_messages('Kurs can not empty value');
		return false;
	}
	
	$("#Form-Job-Sheet").css('background-image', 'url("'+window.location.origin+'/public/images/bg-form.png")');
	$("#Form-Job-Sheet").html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
	$("#Form-Job-Sheet").dialog({
		resizable: false,
		top:100,
		height: 285,
		width: 600,
		modal: true,
		title: "JOB SHEET ",
		close: function() 
		{	
			enabled_top_button();
			remove_detail();
			$(this).html('<img src="'+window.location.origin+'/public/images/loader.gif"/>');
			$("#tabel_sp_detail").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			$("#tabel_sp_detail_export").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
	
	$.ajax({
		type: "POST", 
		url : window.location.origin+'/afg_c_job_sheet/open_form_jobsheet',
		data:{ 
			id:id,
			kode_ref:code 
		},
		success:function(XMLHttpRequest)
		{
			$('#Form-Job-Sheet').html(XMLHttpRequest);
		}
	})
	*/
})