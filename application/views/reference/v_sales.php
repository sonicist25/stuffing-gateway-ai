<?php // echo ! empty($h1_title) ? '<h1>' . $h1_title . '</h1>': ''; ?>

<div id="main-page" style="padding-top: 70px;">
	<center>
		<table id="table" ></table>
	</center>
	<div id="pager"></div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var grid = $("#table");
    $("#table").jqGrid({
        url: "<?php echo base_url('reference/sales/getDatarefsales')?>",
        mtype: "post",
        datatype: "json",
        colNames: [
			'ID','User','Fullname','Management','Percentage','Type','Status','Active','Remarks','Region'
		],
		colModel: [
			{name:'id', index:'id', key:true, hidden:true},        
			{
				name:'user_id',
				index:'user_id',
				hidden:true,          // tidak tampil di grid
				editable:true,
				edittype:"select",
				editoptions:{dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownUser") ?>'},
				editrules:{edithidden:true}   // penting: supaya muncul di form
			},
			{
				name:'fullname',
				index:'fullname',
				width:120,
				editable:false,
				formatter:'text'
			},
			{
				name:'management', index:'management', width:90, align:'center', editable:true,
				edittype:'checkbox', editoptions:{value:"1:0"},
				formatter: function(cellvalue) {
					return cellvalue == 1
						? '<span class="badge bg-success">Management</span>'
						: '<span class="badge bg-primary">Staff</span>';
				}
			},
			{name:'percentage', index:'percentage', width:80, align:'right', formatter:'number', editable:true},
			{
				name:'type', index:'type', width:90, editable:true,
				edittype:"select",
				editoptions:{value:"All:All;Import:Import;Domestik:Domestik;Air Freight:Air Freight"}
			},
			{
				name:'status', index:'status', width:90, editable:true,
				edittype:"select",
				editoptions:{value:"karyawan:karyawan;freelance:freelance"},
				formatter: function(cellvalue) {
					return cellvalue === 'karyawan'
						? '<span class="badge bg-info">Karyawan</span>'
						: '<span class="badge bg-warning">Freelance</span>';
				}
			},
			{
				name:'is_active', index:'is_active', width:70, align:'center', editable:true,
				edittype:'checkbox', editoptions:{value:"1:0"},
				formatter: function(cellvalue) {
					return cellvalue == 1
						? '<span class="badge bg-success">Active</span>'
						: '<span class="badge bg-secondary">Inactive</span>';
				}
			},
			{name:'remark', index:'remark', width:120, hidden:true, editable:true},      
			{
				name:'region_id',
				index:'region_id',
				hidden:true,          // tidak tampil di grid
				editable:false,       // tidak muncul di form
				editoptions:{defaultValue:'<?php echo $this->session->userdata("region_id") ?>'}
			}

		],
        sortname: "id",
        sortorder: "desc",
        multiselect: true,
        rowNum: 16,
        rowList: [10,20,30],
        pager: '#pager',
        viewrecords: true,
        rownumbers: true,
        gridview: true,
        width: 900,
        height: 400,
        caption: "Sales <?php echo $this->session->userdata('region_id') ?> Data Table",
        editurl: ref+'sales/manage_data',
        loadComplete: function() {
            $("#cb_"+grid[0].id).hide();
            $(".ui-jqgrid-titlebar-close",$("#table")[0].grid.cDiv).hide();
        }
    }).navGrid('#pager',
        { edit: true, add: true, del: true, refresh: false, search: false },
        { closeAfterEdit: true, reloadAfterEdit: true },
        { closeAfterAdd: true, reloadAfterAdd: true },
        {},
        {}
    );
});

</script>