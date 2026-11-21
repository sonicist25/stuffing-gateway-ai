if(!window.location.origin) {
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}

$(function() {
	$(this).ajaxStart(function(){
		NProgress.configure({ showSpinner: true });
		NProgress.start();
		$('#nprogress .bar').css({'background':'#ff00d9'});
		$('#nprogress .peg').css({'box-shadow':'0 0 5px #00f, 0 0 5px #00f'});
		$('#nprogress .spinner-icon').css({'border-top-color': '#00f', 'border-left-color': '#ff00d9'});
	}).ajaxStop(function(){
		NProgress.done();
	});
	if (restrict!='') {
		show_messages('You are not authorize to access '+ restrict +' system')
 	}
});

toObject=function(arr,v) {
	var data = {};
	if (v=='name') for (var i = 0; i < arr.length; ++i) data[arr[i].id] = arr[i].name;
	if (v=='code') for (var i = 0; i < arr.length; ++i) data[arr[i].id] = arr[i].code;
	if (v=='address') for (var i = 0; i < arr.length; ++i) data[arr[i].id] = arr[i].address;
	return data;
}

serialize = function(obj, prefix) {
  	var str = [], p;
  	for(p in obj) {
    	if (obj.hasOwnProperty(p)) {
			var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
			str.push((v !== null && typeof v === "object") ?
        	serialize(v, k) :
        	encodeURIComponent(k) + "=" + encodeURIComponent(v));
    	}
  	}
  	return str.join("&");
}

not_empty=function(value, colname) {
	if (value == ""){
		return [false, colname+" CAN'T BE EMPTY VALUE"];
   	$('#'+colname).addClass('error')
	} else if (value != "") {
		return [true,""];
   }
}

show_messages=function(chr){
	$('#message_error').fadeIn('slow', function() {
	 		$('#message_error').html('<div class="error-user">'+chr+'</div>')
 	}).delay(3000).fadeOut('slow');
}

active_select=function(input,char)
{
	var id = input['id'];
	if(char=='in'){
		$('#'+id+'').css({'border':'1px solid #f044ff','font-weight':'510','background-color':'#ffffff','cursor':'pointer'});
	} else {
		$('#'+id+'').css({'border':'1px solid #999999','font-weight':'500','background-color':'#eeeeee'});
	}
}

select_db = function () {
  var payload = {
    form: "DB-CHANGE",
    db: $("select#combo_tahun").val(),
    _: Date.now()
  };

  $.ajax({
    url: app+"get_data",
    type: "POST",
    dataType: "json",
    cache: false,
    data: payload,
    success: function (d) {
      if (d && d.status == 1) {
        setTimeout(function () { location.reload(true); }, 400);
      } else {
        show_messages("Unable to load databases " + payload.db + ", Please contact system Administrator");
      }
    },
    error: function (xhr, textStatus) {
      console.error(xhr.status, xhr.responseText);
      show_messages("DB change failed: " + textStatus);
    }
  });
};


select_region=function() {
	var get = {};
	get.form="REGION";
	get.region_id=$('select[name="region"]').val();
	$.ajax({
		type: "GET",
		url : app+'get_data',
		data: get,
		success:function(data)
		{
			var data = $.parseJSON(data);
			window.open(window.location.href,'_self',1,true);
		}	
	})
}

process_logout=function() {
	$.get(pub+'logout','',
		function(d,s,e) {
			window.open(window.location.href,'_self',1,true);
	},'json')
}

format_number=function(input,int){
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf('.') > -1){
    		num = num.split('.');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > int){
				show_messages('Hanya dapat memasukan '+int+' digit desimal');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} 
    		input.value = num[0]+'.'+num[1];
    	} 
    	else
    	{ 
    		input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1').split('').reverse().join('').replace(/^[\ ]/,'') 
    	};
    }
    else
    { 
		show_messages('Only numeric allowed / Hanya diperbolehkan memasukan angka');
    	input.value = input.value.substring(0,input.value.length-1); 
    }
}

format_uang=function(input){
    var num = input.value.replace(/\ /g,'');
    if(!isNaN(num)){
    	if(num.indexOf(',') > -1){
    		num = num.split(',');
    		num[0] = num[0].toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'');
    		if(num[1].length > int){
				show_messages('Hanya dapat memasukan 2 digit desimal');
    			num[1] = num[1].substring(0,num[1].length-1);
    		} input.value = num[0]+','+num[1];
    	} else{ input.value = num.toString().split('').reverse().join('').replace(/(?=\d*\,?)(\d{3})/g,'$1 ').split('').reverse().join('').replace(/^[\ ]/,'') };
    }
    else
    { 
		show_messages('Only numeric allowed / Hanya diperbolehkan memasukan angka');
    	input.value = input.value.substring(0,input.value.length-1); 
    }
}

load_file_head=function(type,link){
	if(type=='css'){
		var fileref = document.createElement('link');
		fileref.setAttribute('href',link);
		fileref.setAttribute('rel','stylesheet');
		fileref.setAttribute('type','text/css');
	}
	if(type=='js'){
		var fileref = document.createElement('script');
		fileref.setAttribute('src',link);
		fileref.setAttribute('type','text/javascript');
	}
	document.getElementsByTagName('head')[0].appendChild(fileref);	
}

remove_file_head=function(type,link){
	var targetelem = (type=='js')?'script':(type=='css')?'link':'none'	
	var targetattr = (type=='js')?'script':(type=='css')?'href':'none'
	var all = document.getElementsByTagName(targetelem)	
	for(var i=all.length; 1>0;i--){
		if((all[i]&&all[i].getAttribute(targetattr))!=null&&all[i].getAttribute(targetattr).indexOf(link)!=-1){
			all[i].parentNode.removeChild(all[i])
		}
	}
}

change_password = function () {
	var form = $('#form-stuffing');
	form.dialog({ 
		autoOpen: true, resizable: false, height: 'auto', width: 400, position: ['center', 80], modal: true, title: 'USER : ' + $('font[id="usr"]').html(),
		close: function () {
			form.html('');
		},
		open: function () {
			// MODIFIKASI: Old Password dihilangkan. 
			// Layout disesuaikan agar hanya meminta New Password dan Confirm Password.
			form.html('<div id="form-password" style="padding:10px; height:auto; border:1px solid #eee">' +
				'<div style="float:left;width:130px; line-height:25px;">NEW PASSWORD</div>' +
				'<div style="float:left;width:220px; margin-bottom:10px;"><input type="password" id="new_pswd" class="password" style="width:100%" /></div>' +
				'<div style="clear:both;"></div>' +
				'<div style="float:left;width:130px; line-height:25px;">RE ENTER PASS</div>' +
				'<div style="float:left;width:220px; margin-bottom:10px;"><input type="password" id="con_pswd" class="password" style="width:100%" /></div>' +
				'<div style="clear:both;"></div>' +
				'<div style="float:right;width:90px;margin-bottom:10px">' +
				'<input type="submit" value="RUBAH" id="btn_submit" onclick="update_password()" style="width:100%;height:30px; cursor:pointer;" />' +
				'</div>' +
				'</div>'
			);
		}
	});
}

update_password = function () {
    var hasError = false;

    // Cek apakah ada field yang kosong
    $('.password').map(function () {
        if ($(this).val() == '') {
            hasError = true;
            $(this).parent()
                .effect('shake', { times: 3 }, 50)
                .find('.password')
                .addClass('error');
        } else {
            $(this).parent().find('.password').removeClass('error');
        }
    });

    if (hasError) {
        return;
    } else {
        var myGET = {};
        $('.password').each(function () {
            myGET[$(this).attr("id")] = $(this).val();
        });

        // Cek apakah password baru dan konfirmasi cocok
        if (myGET['new_pswd'] != myGET['con_pswd']) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Password does not match'
            });
            $('#new_pswd,#con_pswd')
                .parent()
                .effect('shake', { times: 3 }, 50)
                .find('.password')
                .addClass('error');
            return;
        }

        // Kirim data ke server (tanpa old_pswd)
        $.post(pub + 'change_password', myGET, function (d, s, e) {
            if (d == 1 || d.status == 1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Password changed successfully'
                });
                $('#form-stuffing').dialog('close');
                setTimeout(function () { window.location.reload(); }, 1500);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Failed',
                    text: 'Gagal merubah password, silakan coba lagi.'
                });
            }
        }, 'json');
    }
}


change_date=function(c,spr) {
	if (c!='') {
		dt=c.substr(6,2)+spr+c.substr(4,2)+spr+c.substr(0,4)
	} else {
		dt="";
	}
	return dt;
}

dateFormat=function(c) {
	var change = change_date(c,'/');
	return change;
}

getUser=function(v) {
	if (v==0) return "Administrator";
	else 
	$.post(app+'get_where',{tbl:'ref_usr',fld:'fullname',cond:{user_id:v},row:'fullname'},
	function(d,s,e){
		return d;
	},'json')
}

get_table_actifity=function() {
	var where = {};
	where.region_id = $("select#region").val();
	var myData = {};
	myData.form = 'TABLE';
	myData.table = 'ACT_LOG';
	myData.field = 'id,date,time,user,ip,log';
	var grid=$("#act_log"), i;
	grid.jqGrid({
      url : app+'get_data',
		mtype:"get",
		postData:myData,
		datatype: "json",
  		sortname: "id",
		sortorder: "desc",
   	colNames:['DATE','TIME','USER','IP','ACTIFITY'], 
  		colModel:
   	[
			{name:'date',index:'date', width:30, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['eq','lt','gt'],
					dataInit: function(element) {
        				$(element).datepicker({dateFormat: 'yymmdd'})
      			}
				},
				formatter: dateFormat,
			},
			{name:'time',index:'time', width:20, align:"center",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				}
			},
			{name:'user',index:'user', width:50, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				formatter: get_user,
			},
			{name:'ip',index:'ip', width:50, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
			},
			{name:'log',index:'log', width:150, align:"left",
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				//formatter:'textarea',
			},
		],
		shirnkToFit:true,
  		multiselect:false,
		loadonce : false,
		cellEdit: false,
		rowNum:5,
		width: 830,
		height: "auto",
		rowList:[20,40,60],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
      caption:"Tabel Aktifitas User",
      editurl	:'manage_data',
		pager: '#page-inv',
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();				
    	},
	})
   grid.jqGrid('navGrid','#page-inv', {edit: false, add: false,  del: false, refresh: true},{},{},{},
	{ 
		modal: true, 
		height: "auto", 
		width: 380, 
		closeAfterSearch : true, 
		caption: "Find Actifity ..",
	}, 
	{})
	search_extend(grid);
}

check_ip=function () {
	var ip=$('#ip_addr').val();
	if (ip=='') { show_messages('IP tidak boleh dikosongkan') }
	else {
		$.post(app+'check_ip',{ip:ip},
		function(d,s,e){
			var dt = JSON.stringify(d)
			$('#ip_show').html(dt)
		},'json')
	}
}

add_extend=function(grid) {
	$.extend($.jgrid.add, {
        onInitializeForm: function ($form) {
            $("td.DataTD>.FormElement", $form).keypress(function (e) {
                if (e.which === $.ui.keyCode.ENTER) {
                    $("#sData", $form.next()).trigger("click");
                    return false;
                }
            });
        }
    });
}

edit_extend=function(grid) {
	$.extend($.jgrid.edit, {
        onInitializeForm: function ($form) {
            $("td.DataTD>.FormElement", $form).keypress(function (e) {
                if (e.which === $.ui.keyCode.ENTER) {
                    $("#sData", $form.next()).trigger("click");
                    return false;
                }
            });
        }
    });
}

search_extend=function(grid) {
	$.extend($.jgrid.search, {
		width: "auto",
   	height: "auto",
   	closeAfterSearch : true, 
   	multipleSearch: false,
   	afterRedraw: function (p) {
   		var $form = $(this), formId = this.id, // fbox_list
     		bindKeydown = function () {
     			$form.find("td.data>.input-elm").keydown(function (e) {
        			if (e.which === $.ui.keyCode.ENTER) {
               	$(e.target).change();
            		$("#" + $.jgrid.jqID(formId) + "_search").click();
            	}
         	});
      	},
       	oldOnChange = p.onChange,
     		myOnChange = function (param) {
     			var $input = $form.find("td.data>.input-elm"), events;
            oldOnChange.call(this, param);
          	if ($input.length > 0) {
           		events = $input.data("events");
           		if (events && !events.keydown) {
        				bindKeydown();
           		}
          	}
       	};
      	p.onChange = myOnChange;
      	bindKeydown.call(this);
   	}
	});
}

detail_extend=function(grid,page){
	grid.navGrid(page,{edit:false, add:false, del:true, refresh:true, search:false,},{},{},{
		height:"auto", width:"auto",
     	serializeDelData:function (data) {
     		return {
    			oper:data.oper,
    			table:this.id,
    			myData:{id:data.id}
    		};
		},
     	afterSubmit:function () {
    		grid.jqGrid("setGridParam", {datatype:'json'}).trigger("reloadGrid");
    		return [true];
		}
	},{},{})
	grid.navButtonAdd(page,{ 
		caption:"",
		id:"add_"+grid.selector.replace("#",""),
		buttonicon:"ui-icon-plus", 
		onClickButton:insert_data,
		title:"Insert Data ( Crtl + I )", 
		cursor:"pointer",
		position:"first",
	})
	grid.bind("keyup", "tr", function(e) {
		
		e = e || event;	
		if(e.ctrlKey==true&&e.keyCode==73) {
        $("#add_"+grid.selector.replace('#','')).click()
    	}
    	
    	if(e.shiftKey==true&&e.keyCode==73) {
        var ids = grid.jqGrid('getDataIDs');
        grid.jqGrid('editCell',ids.length,2,true);
    	}
	})
}

if(!window.location.origin){
	window.location.origin=window.location.protocol+'//'+window.location.hostname+(window.location.port+':'+window.location.port)
}