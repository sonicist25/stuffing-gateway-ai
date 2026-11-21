<div style="float: left; width: 250px;">
	<div style="float: left; margin-top: 20px; margin-left: 20px">
		<input type="file" title="Upload File" enctype="multipart/form-data" id="nama_file" name="nama_file" onchange="get_file()">
	</div>
	<div style="clear: both;"></div>
	<div style="margin-left: 20px; margin-bottom: 30px; margin-top: 10px;">
		<center>
		<div id="sql-satu"></div>
		</center>
		<center>	
		<div id="sql-dua"></div>
		</center>
	</div>
	<div style="clear: both; height: 5px"></div>
	<div style="float: left; width: 300px; margin-top: 20px; margin-left: 20px;" id="form-input">
		<div style="float: left; width: 50px">UID</div><div style="float: left; width: 150px"><?= form_input(array("id"=>"uid","name"=>"uid","style"=>"width: 150px")) ?></div>
		<div style="clear: both; height: 5px"></div>	
		<div style="float: left; width: 50px">PWD</div><div style="float: left; width: 150px"><?= form_input(array("id"=>"pwd","name"=>"pwd","style"=>"width: 150px")) ?></div>
		<div style="float: left; width: 200px;"><?= form_submit(array("id"=>"btnSubmit","name"=>"btnSubmit","value"=>"OK", "onclick"=>"get_db()","style"=>"float: right; width: 80px; margin-top: 10px; margin-bottom: 10px;height: 30px")) ?></div>
	</div>
</div>
<div style="float: left; width: 250px; margin-top: 20px; min-height: 150px;">
	<div id="tbl_opt"></div>
</div>

<?php echo  form_input(array('id'=>'table_change','name'=>'table_change','type'=>'hidden')) ?>	
<?php echo  form_input(array('id'=>'db_type','name'=>'db_type','type'=>'hidden')) ?>	
<?php echo  form_input(array('id'=>'tbl_vfp','name'=>'tbl_vfp','type'=>'hidden')) ?>	

<script src="<?php echo base_url('public/file/ajaxfileupload.js') ?>" type="text/javascript"></script>

<script type="text/javascript">
function tabel_column()
{
	table_change()
	$('#rn_cl').attr('hidden',false);
	$('#rp_cl').attr('hidden',false);
	
	var db_type = $('#db_type').val();
	if (db_type=="mdb") 
	{
		table = $('#table').val();
	}
	else 
	{
		table = $('#tbl_vfp').val();
	}
	
	$.ajax({
		type: "POST", 
		url : window.location.protocol+'/databases/table_column',
		data: { 
			table: table,
			tbl : $('#table_change').val(),
			db_type : db_type,
	   },
		success:function(response)
		{
			var data = $.parseJSON(response);
			var myData = JSON.stringify(data.Rows);
			//$("#form-data").html(data.Rows);
			$("input#btnConvert").attr("hidden",false)
			var myData = data.Rows;
         var myModels = [];
				for (i=0;i<=data.Models.length-1;i++) 
				{
    				myModels.push({name: data.Models[i], index:data.Models[i], align:'left', width:100, editable: true});
				}
				"use strict";
				lastSel = -1,
            grid = $('#table-sql-'+data.tbl);              
        		grid.jqGrid({
       				datatype: "local",
       			   //colNames: myNames,	
       				data: myData,
                	colModel: myModels,
                	editurl: window.location.protocol+'/databases/manage_data',
                	sortname: "name",
                	ignoreCase: true,
                	height: 450,
                	width: 910,
                	viewrecords: true,
                	rownumbers: true,
                	//rowNum:1000,
                	shrinkToFit: false,
                	sortorder: "asc",
                	multiselect:true,
                	pager: '#page-sql-'+data.tbl,
                	caption:"TABLE "+data.table.toUpperCase(),
                	loadComplete: function(data) 
                	{
                		$("#cb_"+grid[0].id).hide();
                		//$(".ui-jqgrid-titlebar-close", $("#table-sql-"+data.tbl)[0].grid.cDiv).hide();
                	},
                	jsonReader : { 
        					root: "rows", 
        					page: "page", 
        					total: "total", 
        					records: "records", 
        					repeatitems: true, 
        					cell: "cell", 
        					id: "id",
        					userdata: "userdata", 
        					subgrid: {root:"rows", repeatitems: true, cell:"cell" } 
    					},
            });
            grid.jqGrid("navGrid",'#page-sql-'+data.tbl, { edit: true, add: true, search: true, refresh: true },
            	{
						height: "auto", 
						width: "auto", 
						closeAfterEdit: true, 
						reloadAfterEdit : true, 
						modal: true, 
						editCaption: "Edit Data "+data.table, 
						recreateForm: true,
						viewPagerButtons: false,
						onInitializeForm: function(formId) {
							$(".FormGrid select[name='opt_job_no']").attr('disabled',true)
						},
						beforeShowForm: function(frm) {
							var dlgDiv = $("#editmod" + grid[0].id);
               		//dlgDiv[0].style.top = Math.round($(window).height()/3.5) + "px";
               		dlgDiv[0].style.left = Math.round($(window).width()/2.5) + "px";
						},
						beforeSubmit: function (post) {
    						post['table'] = data.table;
    						return [true];
						},
						afterSubmit: function () {
    						//$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    						return [true];
						},
					},
           		{
           			height: "auto", 
						width: "auto", 
						closeAfterAdd: true, 
						reloadAfterAdd : true, 
						modal: true, 
						addCaption: "Add Data "+data.table,
           			recreateForm: true,
						beforeShowForm: function(frm) {
							var dlgDiv = $("#editmod" + grid[0].id);
               		//dlgDiv[0].style.top = Math.round($(window).height()/3.5) + "px";
               		dlgDiv[0].style.left = Math.round($(window).width()/2.5) + "px";	
						},
						beforeSubmit: function (post) {
    						post['table'] = data.table;
    						return [true];
						},
						afterSubmit: function () {
    						//$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    						return [true];
						},
           		},
            	{
            		height: "auto", 
            		width: "auto",
            		delCaption: "Delete Data "+data.table,
     					serializeDelData: function (postdata) {
     						return {
    							id: postdata.id, 
    							oper: postdata.oper, 
    							table : data.table,
    						};
						},
     					afterSubmit: function () {
    						//$("#table-do-impor").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    						return [true];
						},
     				},
            	{
            	
            	},
            	{
            	
            	}
            );
            
             //for(var i=0;i<=data.Rows.length;i++)
             //grid.jqGrid('addRowData',i + 1, data.Rows[i]['ID']);
             
		}
	})
}


function table_change(){
	$("input#btnConvert").attr("hidden",true)	
	var change = $("#table_change").val();
	if (change=="satu") {
		$("#table_change").val("dua");
		$('#sql-dua').show();
		$('#sql-dua').html("<table id=\"table-sql-dua\"><div id=\"page-sql-dua\"></div></table>");
		return;
	} else {
		$("#table_change").val("satu");
		$('#sql-satu').show();
		$('#sql-satu').html("<table id=\"table-sql-satu\"><div id=\"page-sql-satu\"></div></table>");
		return;
	}	
}

function get_db()
{
	var input, file;
   input = document.getElementById('nama_file');
   file = input.files[0];
   ext = file.name.split('.').pop().toLowerCase();
   
   //alert(file.name+" "+ext);
   //return false;
   
	$.ajaxFileUpload
		({
			url:'<?php echo base_url("databases/ajaxFileUpload") ?>',
			secureuri:false,
			fileElementId:'nama_file',
			type: 'POST',
			dataType: 'json',
			data: { 
				ext : ext,
				uid : $("#uid").val(),
				pwd : $("#pwd").val(),
			},
			success: function (data, status)
			{
				if(typeof(data.error) != 'undefined')
				{
					if(data.error != '')
					{
						return false;
					}
					else 
					{
						if (ext=="mdb") {
							connect_db();
						}
						else 
						{
							$('#tbl_vfp').val(file.name);
							$('#db_type').val(ext);
							tabel_column();
						}
					}
				}
			},
			error: function (data, status, e)
			{
				show_messages(e);
			}
		})
}

function connect_db() {
	$.ajax({
		type: "POST", 
		url : "<?php echo base_url('databases/connect_db')?>",
		data: {
			ajax : 1,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if (data.db=="mdb") {
				$('div#form-input').attr('hidden',true);
				$("#tbl_opt").html(data.dropdown);
				$("#db_type").val(data.db)
			}
			else 
			{
				$("#db_type").val(data.db)
				tabel_column();
			}
		}
	})
}

function get_file()
{
	var file = $("nama_file").val();
	if (file!="") {
		$('div#form-input').attr('hidden',false);
	}
}

$(document).ready(function ()
{
	$('div#form-input').attr('hidden',true);
})

</script>

<?php
/*
$info = new SplFileInfo('/..//./../../'.__FILE__);
var_dump($info->getRealPath());

$info = new SplFileInfo('/tmp');
var_dump($info->getRealPath());
*/
?>