
<style type="text/css">
#txtVessel, #txtVoyage, #txtCarrier, #txtMotherBL,#txtContSealNumber,#txtAirLine {text-transform: uppercase;}
.Blue { color: #33F;}
.left_1{float: left; width: 160px; margin-bottom: 5px}
.left_2{float: left; width: 10px; margin-bottom: 5px}
.left_3{float: left; width: 50px; margin-bottom: 5px}
#main_2 {
	float: left;		
	padding-top: 15px; 
	padding-left: 20px;	
	margin: 10px 0 10px 18px;	
}
</style>



<div id="form-do-impor" class="main-do-impor" style="margin:50px 10px 0 10px;">
	<div id="tab-2" class="the-tab">
		<table id="table"></table>
		<div id="pager"></div>
	</div>
</div>


<script type="text/javascript">


$(document).ready(function () 
{
	var grid = $("#table"), i;	
	$("#table").jqGrid(
	{ 
		url:"<?php echo base_url('afg_c_ppjk/getData_ppjk')?>",
		mtype : "post",
		datatype: "json",
		postData: { searchField:'ppjk_id', searchOper:'eq', searchString: 'N' }, 
		colNames:
		[
			'No.', 
			'Kode Ref',
			'Type of Trans',
			'Shipper',
			'Consignee',
			'AWB/BL',
			'HAWB/HBL',
			'ETD',
			'ATD',
			'ETA Port of Discharge',
			'ATA Port of Discharge',
			'AJU',
			'Pre-Alert Received',
			'Draft PIB',
			'PIB to Bank',
			'PIB from Bank',
			'Actual EDI Transfer',
			'RED Line',
			'Inspection Date',
			'SPPB',
			'Actual Delivery/Handover',
		],
    	colModel:
		[
			{name:'ppjk_id',index:'ppjk_id', align:"center",width:20, editable: false, hidden:true,
				editoptions:{size:"40",maxlength:"50"}
			},				
			{name:'kode_ref',index:'kode_ref', align:"left",width:100, editable: true,  hidden:false, 
				editable: true, 
				edittype:"select", 
				editoptions:{
					dataUrl: window.location.origin+'/afg_c_dropdown/dd_kode_ref',
					dataInit : function (e) { 
						$(e).css({'text-transform':'uppercase','min-width':'165px','max-width':'165px'}) 
					},
					dataEvents: [
                  {  type: 'change',
                  	fn: function(e) {
                       	dropdown_kode_ref(this.value)
                      }
                   }
                ]
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'type_of_trans',index:'type_of_trans', align:"left", width:100, editable: true,  hidden:false, 
			   editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'shipper',index:'shipper', align:"left", width:125, editable: true,  hidden:false, 
				editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'consignee',index:'consignee', align:"left", width:125, editable: true,  hidden:false, 
				   editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'awb',index:'awb', align:"left",width:100, editable: true,  hidden:false, 
				   editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'hb_l',index:'hb_l', align:"left",width:80, editable: true, 
				   editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'etd',index:'etd', align:"left",width:60, editable: true, 
			   editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase"})
      			}
				},
				editrules:{
					edithidden:true,
				},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'atd',index:'atd', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'eta_port',index:'eta_port', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'ata_port',index:'ata_port', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'aju',index:'aju', align:"center",width:60, editable: true, 
				editoptions:{size:"40",maxlength:"50"}
			},				
			{name:'pre_alert',index:'pre_alert', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'draft_pib',index:'draft_pib', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'pib_to_bank',index:'pib_to_bank', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'pib_from_bank',index:'pib_from_bank', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'actual_edi',index:'actual_edi', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'red_lane',index:'red_lane', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'inspection',index:'inspection', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'sppb',index:'sppb', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
			{name:'actual_delivery',index:'actual_delivery', align:"center",width:60, editable: true, 
				editable: true,
				editoptions: {
      			maxlengh: 10,
      			dataInit: function(e) {
        				$(e).datepicker({dateFormat: 'dd/mm/yy'})
        				$(e).css({'text-transform':'uppercase','min-width':'895px','max-width':'895px'}) 
      			}
    			},
    			stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],  
				}
			},				
		],
		sortname:"ppjk_id",
		sortorder:"desc",
  		multiselect:true,
		shrinkToFit: false,
		loadonce : true,
		cellEdit: false,
		rowNum:500,
		height : ($(window).height() - 250),
        width : ($(window).width() - 400),
        scroll : true, 
		rowList:[500,1000,1500,2000,2500],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		autoencode: true,
		modal: true,
		caption:"PPJK Data Table",
		editurl:'afg_c_ppjk/manage_data',
		cellsubmit:'clientArray',
		pager:'#pager',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
			$(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
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
    			cbs.attr("disabled", true);  
    			selected.attr("disabled", false);   			
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			cbs.attr("disabled", false);       			
        	}
		},
		
	}).navGrid('#pager',{ edit: true, add: true, del: true,},
			{
				height: "auto", 
				width: "auto",  
				closeAfterEdit: true, 
				reloadAfterEdit : true, 
				modal: true, 
				editCaption: "Edit Data PPJK", 
				recreateForm: true,
				viewPagerButtons: false,
				onInitializeForm: function(formId) {
					$(".FormGrid select[name='opt_job_no']").attr('disabled',true)
				},
				beforeShowForm: function(frm) {
					$("#opt_job").attr('disabled',true);	
					var dlgDiv = $("#editmod" + grid[0].id);
					dlgDiv[0].style.top = 30 + "px";
               dlgDiv[0].style.left = Math.round($(window).width()/6.5) + "px";
					var rowid = grid.getGridParam('selrow');
					var job = grid.jqGrid('getCell',rowid,'job_no');
					$("tr#tr_job_no > td.DataTD").html("<select id=\"job_no\" style=\"min-width: 895px\" disabled=\"disabled\"><option value="+job+">"+job+"</option></select>")
					
					var con = grid.jqGrid('getCell', rowid,'consignee');
					var con_add = grid.jqGrid('getCell', rowid,'con');
					$("textarea[name=\"consignee\"]").val(con+con_add)
				},
				beforeSubmit: function (postData) {
					postData['ppjk_id']=$("select#ppjk_id").val();
    				return [true];
				},
				afterSubmit: function () {
    				$("#table").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
			},
   		{
   			height: "auto", 
   			width: "auto", 
   			closeAfterAdd: true, 
   			reloadAfterAdd : true, 
   			modal: true, 
   			addCaption: "Insert Data PPJK", 
				recreateForm: true,
				viewPagerButtons: true,
				beforeShowForm: function(frm) {
					var dlgDiv = $("#editmod" + grid[0].id);
               dlgDiv[0].style.top = 30 + "px";
               dlgDiv[0].style.left = Math.round($(window).width()/6.5) + "px";	
				},
				afterSubmit: function () {
    				$("#table").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
   		},
     		{
     			height: "auto", width: "auto",
     			serializeDelData: function (postdata) {
     				return {
    					id: postdata.id, 
    					oper: postdata.oper, 
    					kode_ref : $('input[id="job_no_del"]').val(),
    				};
				},
     			afterSubmit: function () {
    				$("#table").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
     		},
      	{
      		modal: true, 
      		height: "auto", 
      		width: "auto", 
      		closeAfterSearch : true, 
      		multipleSearch: false
      	},
     		{ 
     			afterSubmit: function () {
    				$("#table").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    				return [true];
				},
     		}
    );
});

function dropdown_kode_ref(kode_ref)
{
	$.ajax
	({
		type:"POST",
		url:window.location.origin+'/afg_c_ppjk/dropdown_kode',
		data: { kode_ref : kode_ref },
		success:function(response)
		{
			var data = $.parseJSON(response);				
			$('#shipper').val(data.shipper_print);
			$('#consignee').val(data.consignee);
			$('#awb').val(data.mother_bl);
			$('#hb_l').val(data.hb_l);
			$('#etd').val(data.etd_jkt);
			$('#type_of_trans').val(data.type_of_trans);
		}
	})
}
</script>