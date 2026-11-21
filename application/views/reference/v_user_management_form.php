
   <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<div id="msg-user"></div>
<div style="min-height: 100px;">
<table>
	<tr>
		<td width="100"><b>Nama User</b></td>
		<td width="1%">:</td>
		<td><input type="text" id="fullnameForm"  name="fullnameForm" style="width: 200px; margin-bottom: 10px"></td>
	</tr>
	<tr>
		<td><b>Username  </b></td>
		<td width="1%">:</td>
		<td><input type="text" id="usernameForm"  name="usernameForm" style="width: 200px; margin-bottom: 10px"></td>
	</tr>
	<tr>
		<td><b>Password </b></td>
		<td width="1%">:</td>
		<td><input type="password" id="passwordForm" name="passwordForm" style="width: 200px; margin-bottom: 10px"></td>
	</tr>
	<tr>
		<td><b>Email </b></td>
		<td width="1%">:</td>
		<td><input type="text" id="emailForm" name="emailForm" style="width: 200px; margin-bottom: 10px"></td>
	</tr>
	<?php 
	$user_level = $this->session->userdata('level_id');
	if($user_level == 0 ) { 
		$disabled = "";
		$disabledreg = "";
	} elseif($user_level == 1 ) { 
		$disabled = "";
		$disabledreg = 'disabled="disabled"';
	} else {
		$disabled = 'disabled="disabled"';
		$disabledreg = 'disabled="disabled"';
	}
	?>
	<tr><td><b>Unit Level : </b></td><td>:</td>
		<td colspan="2">
			<?php
				if($user_level == 0 ) {
					$level = $this->dbx->get('x_level');
				}
				else {
					$level = $this->dbx->get_where('x_level',array('id >' => 1 ));
				}
				$data['option_level']['']='';
				foreach ($level->result() as $row)
				{	
					$data['option_level'][$row->id] = $row->nama;
				}
				echo form_dropdown('user_levelForm', $data['option_level'], '', 'id="user_levelForm" style="width: 200px" '.$disabled.'');	
			?>
			<input type="hidden" id="userIdForm" name="userId" readonly="readonly">
			<input type="hidden" id="operForm" name="oper" readonly="readonly">
		</td>
	</tr>
	<?php 
		$level_sesion = $this->session->userdata('level_id');
		if( $level_sesion=='0' || $level_sesion== '1' && $mode == 'add') {
			echo '
			<tr>
				<td colspan="3" class="text-end pt-2">
					<button id="btnMenu" class="btn btn-primary btn-sm" onclick="manage_menu()">
						<i class="fa fa-bars"></i> MENU
					</button>

					<button id="btnAccess" class="btn btn-success btn-sm ms-2" onclick="manage_access_user()">
						<i class="fa fa-user-shield"></i> HAK AKSES
					</button>
				</td>

			</tr>';
		}
	?>
	<!--
	<?php  if($user_level == 0 ) {  ?>
	<tr> <!--
		<td id="list_unit" colspan="4"><br /><b>Unit : </b><br /> 
				<?php /*
					$query = $this->unit_model->get_data();
					foreach($query as $row){
	 					if($row->country_id != '--') {
	 					$data = array(
    						'name'        => 'user_unit[]',
    						'id'          => 'user_unit_'.$row->country_id,
    						'value'       => $row->country_id,
    						'class'		  => 'list_unit'
    						);
							echo 
							'<div title="'.$row->country_name.'" style="float: left; width: 50px; padding-top: 3px; padding-right: 8px;">'.
									form_checkbox($data).' '.$row->country_id.'
							</div>';
	 					}
	 				}  */
				?>
		</td>
	</tr>
	<?php } ?>
	-->
	<tr><td colspan="4" height="30"></td></tr>
</table> 
<hr>
	<input type="button" id="btnSaveUser" value="SAVE" style="float: right; width: 80; height: 30" onclick="save_admin()">
	<input type="button" id="btnCancelUser" value="CANCEL" style="float: right; width: 80; height: 30" onclick="close_form()">
</div>


<script type="text/javascript">
$(document).ready(function(){
	$( '#userIdForm' ).val('<?php echo $user_id ?>');
	$( '#operForm' ).val('<?php echo $mode ?>')
	$("input[id='btnMenu']").attr('hidden',true);
	$("input[id='btnAccess']").attr('hidden', true);
	if($( '#userIdForm' ).val() != '')
	{
		var rowid = '<?php echo $user_id ?>';
		$( '#fullnameForm' ).val($("#user-admin").jqGrid('getCell',rowid,'fullname'));
		$( '#usernameForm' ).val($("#user-admin").jqGrid('getCell',rowid,'username'));
		$( '#passwordForm' ).val($("#user-admin").jqGrid('getCell',rowid,'password_encript'));
		$( '#emailForm' ).val($("#user-admin").jqGrid('getCell',rowid,'email'));
		$( '#user_levelForm' ).val($("#user-admin").jqGrid('getCell',rowid,'level'));
		$( '#dropdownregionForm' ).val($("#user-admin").jqGrid('getCell',rowid,'region_id'));
		$("input[id='btnMenu']").attr('hidden',false);
		$("input[id='btnAccess']").attr('hidden', false);
	}
		
	<?php 
	$checked = $this->unit_model->get_checked($user_id);
	if( $checked != '')
	{
		$checked_unit = $checked->user_unit;		
		$id_unit=explode('+',$checked_unit);   	 	
		foreach ($id_unit as $a) {
			echo '$("#user_unit_'.$a.'").attr("checked",true);';
    	}	
	}
	else 
	{
			
	}	
	?>
	
	
});

function close_form(){
	$( '#Form-Input-User' ).fadeOut('slow', function() 
	{
		$( '#Form-Input-User' ).dialog('close');
	});
}

function save_admin(){
	if($( '#fullnameForm' ).val() == '')
	{
		var message = '<div id="content-msg">Fullname must not Empty Value</div>';
		show_msg(message);
		$( '#fullnameForm' ).focus();
		return false;
	}
	else if($( '#usernameForm' ).val() == '')
	{
		var message = '<div id="content-msg">Username must not Empty Value</div>';
		show_msg(message);
		$( '#usernameForm' ).focus();
		return false;
	}
	else if($( '#passwordForm' ).val() == '')
	{
		var message = '<div id="content-msg">Password must not Empty Value</div>';
		show_msg(message);
		$( '#passwordForm' ).focus();
		return false;
	}
	else if($( '#emailForm' ).val() == '')
	{
		var message = '<div id="content-msg">Email must not Empty Value</div>';
		show_msg(message);
		$( '#emailForm' ).focus();
		return false;
	}
	else if($( '#dropdownregionForm' ).val() == '')
	{
		var message = '<div id="content-msg">Region must not Empty Value</div>';
		show_msg(message);
		$( '#dropdownregionForm' ).focus();
		return false;
	}
	else if($( '#user_levelForm' ).val() == '')
	{
		var message = '<div id="content-msg">Unit Level must not Empty Value</div>';
		show_msg(message);
		$( '#user_levelForm' ).focus();
		return false;
	}
	else
	{
		var mode = $( '#operForm' ).val();
		var selectedUserUnit = new Array();
		$("input[name='user_unit[]']:checked").each(function() {selectedUserUnit.push($(this).val());});
		var adminData =
		{
			oper 			: $( '#operForm' ).val(),
			id 		   : $( '#userIdForm' ).val(),
			fullname		: $( '#fullnameForm' ).val(),
			username 	: $( '#usernameForm' ).val(),
			password 	: $( '#passwordForm' ).val(),
			email 		: $( '#emailForm' ).val(),
			region_id 	: $( '#dropdownregionForm' ).val(),
			level 		: $( '#user_levelForm' ).val(),
			user_unit 	: selectedUserUnit,
		}
		$.ajax({
			url : "<?php echo site_url('reference/c_user_management/manage_user_admin')?>",
			type : 'POST',
			data : adminData,
			success: function(data)
			{
				if(mode == 'add')
				{ 
					success_saving('add')
					
				}
				if(mode == 'edit')
				{  
					success_saving('edit')
				}
			},
			error:function()
			{
				var message = '<div id="content-msg">Data can\'t insert</div>';
				show_msg(message);
				return false;
			} 
		})
	}
}

function success_saving(mode){
	if(mode=='add')
	{
		$( "#Insert-Form" ).html('Success insert new user admin') 
	}
	if(mode=='edit')
	{
		$( "#Insert-Form" ).html('Success edit data user admin')
	}
	
	$( "#Insert-Form" ).dialog(
	{
		autoOpen: true,
		resizable: false,
		height: 'auto',
		width: 320,
		modal: true,
		title:"Success Message",
		buttons:
		{
			"OK": function() 
			{
				$( this ).dialog( "close" );
				$( '#Form-Input-User' ).fadeOut('slow', function() 
				{
					$( '#Form-Input-User' ).dialog('close');
				});
			},
		},
		close: function() 
		{
			$("#user-admin").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		}
	});
}

function show_msg(char){
	$('#msg-user').html(char);
   $('#msg-user').fadeIn('slow').delay(2000).fadeOut('slow');
}

function manage_menu(){
	$("#Menu-User").fadeIn("slow", function(){
		$(this)
		.css('font-size','11px')
		.dialog({
			modal: true,
			resizable: false,
			width: 790,
			height: 500,
			title: 'Management Menu User '+$("input[id='fullnameForm']").val(),
			buttons: {
				"SAVE": function(){
					update_menu_user();
				},
				"CANCEL": function(){
					$("#Menu-User").fadeOut("slow", function(){
						$(this).dialog('close')
					})
				}
			}
		})
	});
	$("#Menu-User").html('<img src="<?php echo base_url("public/images/loader.gif")?>"/>');
	$.ajax({
		url : "<?php echo site_url('reference/c_user_management/get_menu')?>",
		type : 'POST',
		data : { user_id : $("input[id='userIdForm']").val(),},
		success: function(response)
		{
			var data = $.parseJSON(response);
			$("#Menu-User").html(data.Menu_User.join(""));
		}
	})
}

function update_menu_user(){
	var UserMenu = new Array();
	$("input[name='user_menu[]']:checked").each(function() {UserMenu.push($(this).val());});
	var DataMenu =
	{
		user_id : $("input[id='userIdForm']").val(),
		user_menu : UserMenu,
	}
	$.ajax({
		url : '<?php echo site_url("reference/c_user_management/update_menu_user")?>',
		type : 'POST',
		data : DataMenu,
		success: function(response)
		{
			var data = $.parseJSON(response);
			$("#Menu-User").fadeOut("slow", function(){
				$(this).dialog('close');
				show_msg('<div id="content-msg">'+data.message+'</div>')
			})	
		}
	})
}

function manage_access_user()
{
    var userId = $("input[id='userIdForm']").val();
    var fullname = $("input[id='fullnameForm']").val();

    if (userId == '') {
        show_msg('<div id="content-msg">Pilih user terlebih dahulu</div>');
        return false;
    }

    $("#Access-User").fadeIn("slow", function(){
        $(this)
        .css('font-size', '11px')
        .dialog({
            modal: true,
            resizable: true,
            width: 900,
            height: 600,
            title: 'Hak Akses User - ' + fullname,
            buttons: {
                "SAVE": function(){
                    update_access_user();
                },
                "CANCEL": function(){
                    $("#Access-User").fadeOut("slow", function(){
                        $(this).dialog('close');
                    });
                }
            }
        });
    });

    $("#Access-User").html('<center><img src="<?php echo base_url("public/images/loader.gif")?>"/></center>');

    $.ajax({
        url  : "<?php echo site_url('reference/c_user_management/get_access_user')?>",
        type : "POST",
        data : { user_id: userId },
        dataType: "json",
        success: function(res)
        {
            if (!res.status) {
                $("#Access-User").html('<div style="color:red;">' + res.message + '</div>');
                return;
            }

            render_access_user(res);
        },
        error: function(xhr)
        {
            $("#Access-User").html('<div style="color:red;">Gagal mengambil data hak akses.</div>');
        }
    });
}

function render_access_user(res)
{
    var html = '';

    html += '<style>';
    html += '.access-table{width:100%;border-collapse:collapse;font-size:11px;}';
    html += '.access-table th{background:#00245E;color:#fff;padding:6px;border:1px solid #ccc;text-align:center;}';
    html += '.access-table td{padding:5px;border:1px solid #ccc;}';
    html += '.access-section-title{background:#FFCA33;color:#00245E;font-weight:bold;padding:7px;margin-top:10px;}';
    html += '.access-check{text-align:center;}';
    html += '</style>';

    html += '<input type="hidden" id="access_user_id" value="' + res.user_id + '">';

    html += '<div class="access-section-title">MENU ACCESS</div>';
    html += '<table class="access-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th width="220">Menu</th>';
    html += '<th>View</th>';
    html += '<th>Create</th>';
    html += '<th>Edit</th>';
    html += '<th>Delete</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';

    $.each(res.menus, function(i, row){
        html += '<tr>';
        html += '<td>' + row.label + '<input type="hidden" name="menu_key[]" value="' + row.key + '"></td>';
        html += checkboxCell('menu', row.key, 'view', row.can_view);
        html += checkboxCell('menu', row.key, 'create', row.can_create);
        html += checkboxCell('menu', row.key, 'edit', row.can_edit);
        html += checkboxCell('menu', row.key, 'delete', row.can_delete);
        html += '</tr>';
    });

    html += '</tbody>';
    html += '</table>';

    html += '<div class="access-section-title">STUFFING PLAN FEATURE ACCESS</div>';
    html += '<table class="access-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th width="220">Feature</th>';
    html += '<th>View</th>';
    html += '<th>Create</th>';
    html += '<th>Edit</th>';
    html += '<th>Delete</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';

    $.each(res.features, function(i, row){
        html += '<tr>';
        html += '<td>' + row.label + '<input type="hidden" name="feature_key[]" value="' + row.key + '"></td>';
        html += checkboxCell('feature', row.key, 'view', row.can_view);
        html += checkboxCell('feature', row.key, 'create', row.can_create);
        html += checkboxCell('feature', row.key, 'edit', row.can_edit);
        html += checkboxCell('feature', row.key, 'delete', row.can_delete);
        html += '</tr>';
    });
	html += '</tbody>';
    html += '</table>';
	
	html += '<div class="access-section-title">JOB CODE ACCESS</div>';
	html += '<div style="padding:7px;background:#fffdf5;border:1px solid #ddd;margin-bottom:6px;">';
	html += '<b>Catatan:</b> Jika tidak ada job code yang dicentang, user boleh akses semua job code. ';
	html += 'Jika ada yang dicentang, user hanya boleh akses job code tersebut, plus job yang prepared oleh dirinya sendiri.';
	html += '</div>';

	html += '<table class="access-table">';
	html += '<thead>';
	html += '<tr>';
	html += '<th width="220">Job Code</th>';
	html += '<th>Allowed</th>';
	html += '</tr>';
	html += '</thead>';
	html += '<tbody>';

	$.each(res.job_codes, function(i, row){
		var checked = parseInt(row.checked) === 1 ? 'checked="checked"' : '';

		html += '<tr>';
		html += '<td>' + row.label + '</td>';
		html += '<td class="access-check">';
		html += '<input type="checkbox" class="job-code-checkbox" data-code="' + row.key + '" ' + checked + '>';
		html += '</td>';
		html += '</tr>';
	});

    html += '</tbody>';
    html += '</table>';

    $("#Access-User").html(html);
}

function checkboxCell(type, key, action, checked)
{
    var name = type + '_' + key + '_' + action;
    var isChecked = parseInt(checked) === 1 ? 'checked="checked"' : '';

    return '<td class="access-check">' +
           '<input type="checkbox" class="access-checkbox" data-type="' + type + '" data-key="' + key + '" data-action="' + action + '" ' + isChecked + '>' +
           '</td>';
}

function update_access_user()
{
    var userId = $("#access_user_id").val();

    if (!userId) {
        show_msg('<div id="content-msg">User ID kosong.</div>');
        return false;
    }

    var menuAccess = [];
    var featureAccess = [];

    var menuMap = {};
    var featureMap = {};

    /*
     * Ambil semua checkbox menu dan feature.
     *
     * Checkbox harus punya attribute:
     * data-type   = menu / feature
     * data-key    = menu_key / feature_key
     * data-action = view / create / edit / delete
     */
    $(".access-checkbox").each(function(){
        var type    = $(this).data("type");
        var key     = $(this).data("key");
        var action  = $(this).data("action");
        var checked = $(this).is(":checked") ? 1 : 0;

        if (!type || !key || !action) {
            return;
        }

        if (type === "menu") {
            if (!menuMap[key]) {
                menuMap[key] = {
                    menu_key: key,
                    can_view: 0,
                    can_create: 0,
                    can_edit: 0,
                    can_delete: 0
                };
            }

            if (action === "view") {
                menuMap[key].can_view = checked;
            } else if (action === "create") {
                menuMap[key].can_create = checked;
            } else if (action === "edit") {
                menuMap[key].can_edit = checked;
            } else if (action === "delete") {
                menuMap[key].can_delete = checked;
            }
        }

        if (type === "feature") {
            if (!featureMap[key]) {
                featureMap[key] = {
                    feature_key: key,
                    can_view: 0,
                    can_create: 0,
                    can_edit: 0,
                    can_delete: 0
                };
            }

            if (action === "view") {
                featureMap[key].can_view = checked;
            } else if (action === "create") {
                featureMap[key].can_create = checked;
            } else if (action === "edit") {
                featureMap[key].can_edit = checked;
            } else if (action === "delete") {
                featureMap[key].can_delete = checked;
            }
        }
    });

    $.each(menuMap, function(key, row){
        menuAccess.push(row);
    });

    $.each(featureMap, function(key, row){
        featureAccess.push(row);
    });

    /*
     * Ambil job code yang dicentang.
     *
     * Checkbox harus punya:
     * class="job-code-checkbox"
     * data-code="100"
     */
    var jobCodes = [];

    $(".job-code-checkbox:checked").each(function(){
        var code = $(this).data("code");

        if (code !== undefined && code !== null && code !== "") {
            jobCodes.push(String(code));
        }
    });

    /*
     * Catatan:
     * Jika jobCodes kosong, backend akan delete semua row mst_user_job_access.
     * Artinya user default boleh semua job/type_of_trans.
     */

    var payload = {
        user_id: userId,
        menu_access: menuAccess,
        feature_access: featureAccess,
        job_codes: jobCodes
    };

    $.ajax({
        url: "<?php echo site_url('reference/c_user_management/update_access_user')?>",
        type: "POST",
        data: payload,
        dataType: "json",
        beforeSend: function()
        {
            $("#Access-User").parent().find(".ui-dialog-buttonpane button:contains('SAVE')")
                .prop("disabled", true)
                .text("SAVING...");
        },
        success: function(res)
        {
            if (res.status) {
                $("#Access-User").fadeOut("slow", function(){
                    $(this).dialog("close");

                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            text: res.message || "Hak akses user berhasil disimpan.",
                            confirmButtonText: "OK"
                        });
                    } else if (typeof swal !== "undefined") {
                        swal({
                            icon: "success",
                            title: "Berhasil",
                            text: res.message || "Hak akses user berhasil disimpan.",
                            button: "OK"
                        });
                    } else {
                        show_msg('<div id="content-msg">' + (res.message || "Hak akses user berhasil disimpan.") + '</div>');
                    }
                });
            } else {
                var message = res.message || "Gagal menyimpan hak akses.";

                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "warning",
                        title: "Gagal",
                        text: message,
                        confirmButtonText: "Mengerti"
                    });
                } else if (typeof swal !== "undefined") {
                    swal({
                        icon: "warning",
                        title: "Gagal",
                        text: message,
                        button: "Mengerti"
                    });
                } else {
                    show_msg('<div id="content-msg">' + message + '</div>');
                }
            }
        },
        error: function(xhr)
        {
            var message = "Gagal menyimpan hak akses.";

            if (xhr.status === 403) {
                message = "Anda tidak memiliki akses untuk menyimpan hak akses.";
            }

            try {
                var res = JSON.parse(xhr.responseText);
                if (res.message) {
                    message = res.message;
                }
            } catch (e) {}

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: message,
                    confirmButtonText: "Mengerti"
                });
            } else if (typeof swal !== "undefined") {
                swal({
                    icon: "error",
                    title: "Error",
                    text: message,
                    button: "Mengerti"
                });
            } else {
                show_msg('<div id="content-msg">' + message + '</div>');
            }
        },
        complete: function()
        {
            $("#Access-User").parent().find(".ui-dialog-buttonpane button:contains('SAVING...')")
                .prop("disabled", false)
                .text("SAVE");
        }
    });
}

</script>








