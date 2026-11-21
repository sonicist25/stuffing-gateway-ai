<script type="text/javascript">
$(document).ready(function (){
	$("#txtDate").datepicker({dateFormat : "dd/mm/yy",beforeShowDay: function(date) {
    return [date.getDay() === 0 || date.getDay() === 6 ,''];
}});
	
$('a[href^="#"]').on('click', function(event) {

    var target = $(this.getAttribute('href'));

    if( target.length ) {
        event.preventDefault();
        $('html, body').stop().animate({
            scrollTop: target.offset().top
        }, 100000);
    }

});

});


function get_data() {
	$("#txtLoad").ajaxStart(function(){
		$("#txtValue").html("");
		$(this).fadeIn();
		$(this).html('<img src="'+window.location.origin+'/public/images/loader.gif">');
	}).ajaxStop(function(){
		$(this).fadeOut();
	})
			
	$.post(window.location.origin+'/afg_c_table_stuffing/get_data' ,  {'date':$("#txtDate").val()},
	function(response) {
		var data = $.parseJSON(response);
		$("#txtInterval").html(data.interval);
		
		for (i=0; i<data.value.length; i++) {
				var v = data.value[i].detail 
				var z = '';
				var d = '';
				for (n=0;n<v.length;n++) {
					z += "<tr><td>"+v[n].shipper_print+"</td><td class='right'>"+v[n].bl_meas+"</td></tr>";
					d += v[n].bl_meas+" ";
				}
				
				var k = d.split(" ");
				
				for(var u=0; u<k.length; u++) { 
					k[u] = +k[u]; 
				}
				
				//var k = [2.2529,4.3045,6.5023,8.7000];
				
				sum = k.reduce(function(a, b) { return a + b; }, 0);
				
				
				var newElement = document.createElement('div');
				newElement.className = "stuffing";
				newElement.innerHTML = "<table width='98%'>"+
				"<tr><td class='header des'>"+data.value[i].destination+"</td><td class='header right'>"+data.value[i].agent+"</td></tr>"+z+
				"<tr><td class='total right'>Grand Total</td><td class='total right'>"+sum.toFixed(4)+"</td></tr>"+
				"</table>";			
				var element = document.getElementById("txtValue");
				element.appendChild(newElement);
	
			}
	})
    
}

</script>

<style type="text/css">
.stuffing {
	/*border: 1px solid #999;*/
	float: left;
	margin-right: 10px;
	margin-bottom: 25px;
	width: 980px;
}
table td{
	border: 1px solid #999;
	padding-left: 5px;
	font-size: 11px;
	height: 18px;
	vertical-align: middle;
}
.header {
	background-color: #cdf;
	height: 30px;
}

.des {
	font-size: 14px;
	font-style: italic;
	font-weight: bold;
	width: 500px;
}

.right {
	text-align: right;
	padding-right: 5px;
}

.total {
	background-color: #ddd;
	height: 20px;
	font-weight: bold;
	font-size: 10px;
}
.a {
  background: #ddd;
  color: #444;
  font-family: sans-serif;
  text-align: center;
  text-decoration: none;
  padding: 20px;
  display: block;
}
</style>
<a href="#bottom" id="top">Click to scroll down</a>

<?php
echo "<div style='width:110; margin-top: 20; margin-right:100; float: right'>".
			form_input(array('id'=>'txtDate','value'=>'Pilih tanggal stuffing','style'=>'width: 100; text-align: center;','onchange'=>'get_data()','onblur'=>'this.value=\'Input Date\'')).
	  "</div>
		<div id='txtInterval' style='margin-top: 20; font-style : italic; font-weight: bold'></div>
		<div style='clear: both; height: 20'></div>
		<div id='txtValue'></div>
		<div id='txtLoad'></div>";	
?>

<a href="#top" id="bottom">Click to scroll up</a>
