$(function () {
	$('#list_menu').live('change',function () {
		var list_menu = $(this).attr('checked')
		if ($(this).attr('checked')=='checked') {
			$('input[name="user_menu[]"]').attr('checked','checked')
		} else {
			$('input[name="user_menu[]"]').attr('checked',false)
		}
	})
	
	var where = {};
	where.region_id = REGID;
	var myData = {};
	myData.form = 'TABLE';
	myData.table = 'REF_USR';
	myData.field = 'user_id as id,username,password,fullname,email,level,active';
	myData.where = where;
	var grid = $("#REF_USR"), i;	
	grid.jqGrid({
      url : app+'get_data',
		mtype:"get",
		postData:myData,
		datatype: "json", 
		ajaxSelectOptions: {type: "GET"},
		colNames:
		[
			'USERNAME',
			'PASSWORD',
			'FULLNAME',
			'EMAIL',
			'LEVEL',
			'ACTIVE',
			'',
		],
    	colModel:
		[
			{name:'username',index:'username', width:20, 
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				editable: true,
				editoptions:{
						size:"40",
						maxlength:"10",
						//dataInit:function (el) {$(el).css({'text-transform':'uppercase'})}
				},
				editrules:{ custom:true, custom_func: not_empty },
			},
			{name:'password',index:'password', width:15, 
				hidden:true, 
				editable: true,
				//edittype:"password", 
				editrules:{
					edithidden:true
				},
				editoptions:{
					dataInit:function (el) {$(el).css({'width':'254px'})}
				},
				//editrules:{ custom:true, custom_func: not_empty },
			},
			{name:'fullname',index:'fullname', width:25, 
				editable: true, 
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				editrules:{ custom:true, custom_func: not_empty },
			},
			{name:'email',index:'email', width:40, 
				editable: true, 
				editoptions:{size:"40"},
				stype:"text",
				searchoptions:{ 
					sopt:['cn','eq'],
				},
				editrules:{ custom:true, custom_func: not_empty },
			},
			{name:'level',index:'level', width:20,
				editable: true, 
				edittype:"select",
				editrules:{custom:true, custom_func: not_empty}, 
				editoptions:{
					value: ldata,
					dataInit: function(element) {
        				$(element)
        				.css({ "text-align": "left","max-width":"120px","text-transform":"uppercase"})
      			}
				},
				stype:"select",
				searchoptions: {
					searchhidden: true,
					sopt:['eq'],
					//dataUrl: app+'get_data?form=LIST&name=level&key=id&val=nama&opt=true',
					buildSelect:function(data){
               	var obj = $.parseJSON(data);
               	return obj;
          		}
          	},
				formatter:get_level,
			},
			{name:'active',index:'active', width:20, align:"center", 
				search: false,
				editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:{value:"YES:YES;NO:NO", width: 30,},
			},
			{name:'button',index:'button', width:20, 
				hidden:true, 
				editable: true, 
				edittype:"button",
				editrules:{
					edithidden:true
				},
				editoptions:{
					dataInit:function (el) {$(el).css({'width':'100px','height':'25px'})},
					dataEvents: 
					[{  
                  type: 'click',
               	fn: function(e) {
               		var form = $('#form-stuffing');
               		form.dialog({ autoOpen:true, resizable:false, height: 'auto', width: 720, position :['center',20], modal: true, title :'SELECT MENU USER',
								close: function(){
									form.html('');
								},
								open: function() {
									var id = grid.getGridParam('selrow');
									$.get(app+'get_menu',{id:id},function(d,s,e) {
										form.html('<div style="width:700px;font-size:0.8em">'+
											'<div style="float: left">'+
												'<div style="margin-bottom:20px"><h1>MASTER</h1>'+d.mst.join("")+'</div>'+
												'<div><h1>PRICE LIST</h1>'+d.prc.join("")+'</div>'+
											'</div>'+											
											'<div style="float:left;margin-left:20px;margin-bottom:20px;"><h1>REFERENCE</h1>'+d.ref.join("")+'</div>'+
											'<div style="float: left;margin-left:20px;">'+
												'<div style="margin-bottom:20px;"><h1>FINANCE</h1>'+d.fnc.join("")+'</div>'+
												'<div><h1>REPORT</h1>'+d.rpt.join("")+'</div>'+
											'</div>'+
											'<div style="float:left;margin-left:20px;margin-bottom:20px;"><h1>TRANSACTION</h1>'+d.trn.join("")+'</div>'+
											'<div style="float:left;margin-bottom:20px;"><input type="checkbox" id="list_menu" />Check All</div>'+
											'<div style="float:left;margin-bottom:20px;"><input type="submit" onclick="update_menu('+id+')" value="UPDATE" style="width:80px;height:25px" /></div>'+
										'</div>')
									},'json');
								}
							})
						}
            	}]
				},
			},
      ],
      sortname: "username",
		sortorder: "asc",
		multiselect:true,
		loadonce : false,
		cellEdit: false,
		rowNum:15,
		width: 890,
		height: 'auto',
		rowList:[15,30,45],
		viewrecords: true,
		rownumbers: true,
		gridview: true,
		modal: true,
		caption:"USER " + REGID.toUpperCase() + " DATA TABLE",
		editurl:app+'manage_data',
		cellsubmit:"clientArray",
		pager:'#pager',
				
		loadComplete: function() {
			$("#cb_"+grid[0].id).hide();
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
     		}
        	else
        	{
        		var cbs = $("tr.jqgrow > td > input.cbox:disabled", grid[0]);  		
    			//cbs.attr("disabled", false);       			
        	}
		},
		
	})
	grid.navGrid('#pager',{ edit: true, add: true, del: true,},{
		height: "auto", 
		width: "auto",  
		closeAfterEdit: true, 
		reloadAfterEdit : true, 
		modal: true,
		viewPagerButtons: false,
		editCaption: "Edit Data User",
		beforeShowForm:function (id) {
			$('#button').show().val('SELECT MENU')
		},
		beforeSubmit: function(post,frmId) {
			console.log(post)
			var table = this.id;
  			var key = {};
  			var where = {};
  			for(p in post) {
				if (post.hasOwnProperty(p)) {
					key[p]=post[p].toUpperCase();
					where.user_id = key[table+'_id']
					delete key[table+'_id'];
					delete post[p];
					delete post['button'];
  				}
  			}
  			post.data=key;
			post.table=table;
			post.where=where;
			return [true];
		},
		afterSubmit: function () {
			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			return [true];
		}
	},{
		height: "auto", 
		width: "auto", 
		closeAfterAdd: true, 
		reloadAfterAdd : true, 
		modal: true, 
		addCaption: "Insert Data User",
		beforeShowForm:function () {
			$('#button').hide();
			
		},
 		beforeSubmit: function(post,frmId) {
  			var table = this.id;
  			var key = {};
  			key.region_id = REGID;
  			for(p in post) {
				if (post.hasOwnProperty(p)) {
					key[p]=post[p];
					if (p=='password') {key[p]=CryptoJS.MD5(key[p])}
					delete key[table+'_id'];
					delete post[p];
					delete post['button'];
					post[table+'_id']='_empty';
  				}
  			}
  			post.data=key;
			post.table=table;
			return [true];
		},
 		afterSubmit: function () {
 			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	},{
 		autoOpen: true,
		modal:true,
		height: "auto", 
		width: "auto",
		serializeDelData: function (post) {
			var data = {};
     		var where = {};
     		var table = this.id;
     		where.user_id = post.id
     		data.table = table;
     		data.oper = post.oper;
     		data.where = where;
     		return data;
		},
  		afterSubmit: function () {
 			grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
    		return [true];
		}
	},{
		modal: true, 
		height: "auto", 
		width: "auto", 
		closeAfterSearch : true, 
		multipleSearch: false, 
		caption: "Search Data User"
	},{ })
	add_extend(grid);
	edit_extend(grid);
	search_extend(grid);
})


update_menu=function(id){
	var menu = new Array();
	$("input[name='user_menu[]']:checked").each(function() {menu.push($(this).attr('id'))});
	$.post(app+'update_menu_user',
	{id:id,menu:menu},
	function(d,s,e) {
		$('#form-stuffing').dialog('close')
		show_messages(d.msg)
	},'json')
}
