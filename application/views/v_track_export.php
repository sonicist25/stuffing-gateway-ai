<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Table Update Track and Trace</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/bootstrap.css'?>">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/jquery.dataTables.css'?>">
</head>
<body>
<div class="container-fluid">
	<!-- Page Heading -->
		<h1 class="page-header">Table Update Track and Trace</h1>
	<!-- <div class="row"> -->
		<div class="table-responsive">
			<table class="table table-striped" id="mydata">
				<thead>
					<tr>
						<th>Nomor Job</th>
						<th>Customer</th>
						<th>Actual Pickup Date</th>
						<th>Actual Departure Date</th>
						<th>Actual Arrival Date at Connecting Port</th>
						<th>Actual Departure Date at Connecting Port</th>
						<th>Actual Arrival Date</th>
						<th>Actual Delivery Date</th>	
					</tr>
				</thead>
				<tbody id="show_data">
					
				</tbody>
			</table>
		</div>
	<!-- </div> -->
</div>

<script type="text/javascript" src="<?php echo base_url().'assets/js/jquery.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/bootstrap.js'?>"></script>
<script type="text/javascript" src="<?php echo base_url().'assets/js/jquery.dataTables.js'?>"></script>
<script type="text/javascript">
	$(document).ready(function(){
		tampil_data_barang();	//pemanggilan fungsi tampil barang.
		
		$('#mydata').dataTable();
		 
		//fungsi tampil barang
		function tampil_data_barang(){
		    $.ajax({
		        type  : 'ajax',
		        url   : window.location.origin+'/track_export/data_track',
		        async : false,
		        dataType : 'json',
		        success : function(data){
		            var html = '';
		            var i;
		            for(i=0; i<data.length; i++){
						var pc = JSON.parse(data[i].pickup_cargo);
						var pcText = pc["Pickup Cargo"]
						
						var pc = JSON.parse(data[i].dpol);
						var dpolText = pc["ATD"]

						var pc = JSON.parse(data[i].atp);
						var atpText = pc["ATP"]

						var pc = JSON.parse(data[i].atd);
						var atdText = pc["ATA"]

						var pc = JSON.parse(data[i].pickup_do);
						var pickup_doText = pc["Pickup DO"]

						var pc = JSON.parse(data[i].cargo_delivery);
						var cargo_deliveryText = pc["Cargo delivered"]

		                html += '<tr>'+
		                  		'<td>'+data[i].no_job+'</td>'+
		                        '<td>'+data[i].shipper+'</td>'+
		                        '<td>'+ pcText +'</td>'+
								'<td>'+ dpolText+'</td>'+
								'<td>'+ atpText+'</td>'+
								'<td>'+ atdText+'</td>'+
								'<td>'+ pickup_doText+'</td>'+
								'<td>'+ cargo_deliveryText+'</td>'+
		                        '</tr>';
		            }
		            $('#show_data').html(html);
		        }

		    });
		}

	});

</script>
</body>
</html>