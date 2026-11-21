    <!DOCTYPE html>
<html>
<head>
<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
</head>
<body>

<h2>Data Shipment</h2>

<table>
  <tr>
    <th>JOB NO</th>
    <th></th>
  </tr>
  <tr>
    <td>SHIPPER</td>
    <td>Maria Anders</td>
  </tr>
  <tr>
    <td>CONSIGNEE</td>
    <td>Francisco Chang</td>
  </tr>
  <tr>
    <td>AGENT</td>
    <td>Roland Mendel</td>
  </tr>
  <tr>
    <td>DESTINATION</td>
    <td>Helen Bennett</td>
    
  </tr>
  <tr>
    <td>HBL NO :</td>
    <td>Yoshi Tannamuri</td>
   </tr>

</table>

</body>
</html>
<form style="width:60%;overflow:auto;position:relative;height:auto;" id="truckData">
    <table id="pcCon" class="EditTable" cellspacing="0" cellpadding="0" border="0">
        <tbody>
            <tr rowpos="6" class="FormData" id="tr_port_of_destination">
                <td class="CaptionTD" colspan="2"><strong>PICKUP CARGO</strong></td>
            </tr>
        </tbody>
    </table>
	<table id="dpolCon" class="EditTable" cellspacing="0" cellpadding="0" border="0">
        <tbody>
            <tr class="FormData" id="tr_port_of_destination">
                <td class="CaptionTD" colspan="2"><strong>Departure from POL</strong></td>
            </tr>
		</tbody>
	</table>
	<table id="atpCon" class="" cellspacing="0" cellpadding="0" border="0">
        <tbody>
            <tr class="FormData" id="tr_port_of_destination">
                <td class="CaptionTD" colspan="2"><strong>Arrival at Transhipment Port</strong></td>
            </tr>
		</tbody>
	</table>
	<button onclick="kirim()" type="button">KIRIM</button>
</form>


<script>
function kirim(){
	var data = JSON.stringify($("#truckData").serializeArray());
	//console.log(data);
  $.ajax({
	type : "POST",
	url : window.location.origin+'/afg_c_trucking/manage_tracking',
	data: $("#truckData").serialize(),
	success : function(data){
		console.log(data)
	}
  })	
}
//Pickup Cargo
var pc = 1;
var dpol = 1;
var atp = 1;
function addPC(){
	pc = pc+1;
	$("#pcCon").append("<tr id='trPC"+pc+"'>" + 
		'<td><input type="text" name="pigo_attr_'+[pc]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
		'<td><input type="text" name="pigo_val_'+[pc]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
		'<button type="button" id="btnAddPC'+pc+'" onclick="addPC()">&plus;</button></td>'+
		"</tr>");
	$("#btnAddPC"+(pc-1)).html("&#8722;");
	$("#btnAddPC"+(pc-1)).attr("onclick", "removePC("+(pc-1)+")");
}

function removePC(val){
	$("#trPC"+val).remove();
}


//Departure from POL
function addDPOL(){
	dpol = dpol+1;
	$("#dpolCon").append("<tr id='trDPOL"+dpol+"'>" + 
		'<td><input type="text" name="dpol_attr_'+[dpol]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
		'<td><input type="text" name="dpol_val_'+[dpol]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
		'<button type="button" id="btnAddDPOL'+dpol+'" onclick="addDPOL()">&plus;</button></td>'+
		"</tr>");
	$("#btnAddDPOL"+(dpol-1)).html("&#8722;");
	$("#btnAddDPOL"+(dpol-1)).attr("onclick", "removeDPOL("+(dpol-1)+")");
}
function removeDPOL(val){
	$("#trDPOL"+val).remove();
}

//Arrival at Transhipment Port
function addATP(){
	atp = atp+1;
	$("#atpCon").append("<tr id='trATP"+atp+"'>" + 
		'<td><input type="text" name="atp_attr_'+[atp]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
		'<td><input type="text" name="atp_val_'+[atp]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
		'<button type="button" id="btnAddATP'+atp+'" onclick="addATP()">&plus;</button></td>'+
		"</tr>");
	$("#btnAddATP"+(atp-1)).html("&#8722;");
	$("#btnAddATP"+(atp-1)).attr("onclick", "removeATP("+(atp-1)+")");
}
function removeATP(val){
	$("#trATP"+val).remove();
}


//General
for (var i = 0; i < pc; i++) {
	if((i+1) == pc){
			
		$("#pcCon").append("<tr id='trPC"+(i+1)+"'>" + 
			'<td><input type="text" name="pigo_attr_'+[i+1]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
			'<td><input type="text" name="pigo_val_'+[i+1]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
			'<button type="button" id="btnAddPC'+(i+1)+'" onclick="addPC()">&plus;</button></td>'+
			"</tr>");
			
		$("#dpolCon").append("<tr id='trDPOL"+(i+1)+"'>" + 
			'<td><input type="text" name="dpol_attr_'+[i+1]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
			'<td><input type="text" name="dpol_val_'+[i+1]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
			'<button type="button" id="btnAddDPOL'+(i+1)+'" onclick="addDPOL()">&plus;</button></td>'+
			"</tr>");
			
		$("#atpCon").append("<tr id='trATP"+(i+1)+"'>" + 
			'<td><input type="text" name="atp_attr_'+[i+1]+'" placeholder="Attribut (Date,Driver)" role="textbox" class="FormElement ui-widget-content ui-corner-all"></td>' + 
			'<td><input type="text" name="atp_val_'+[i+1]+'" placeholder="Data (2002/02/01, Bambang)" role="textbox" class="FormElement ui-widget-content ui-corner-all">' +
			'<button type="button" id="btnAddATP'+(i+1)+'" onclick="addATP()">&plus;</button></td>'+
			"</tr>");
	}
}
</script>