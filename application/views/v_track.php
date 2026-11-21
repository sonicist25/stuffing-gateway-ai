<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Table Update Track and Trace</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/bootstrap.css'?>">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/jquery.dataTables.css'?>">
</head>
<body>
<div class="container">
	<!-- Page Heading -->
        <div class="row">
            <h1 class="page-header">Table Update Track and Trace
            </h1>
        </div>
	<div class="row">
		<table class="table table-striped" id="mydata">
			<thead>
				<tr>
					<th>Nomor Job</th>
					<th>Customer</th>
					<th>Expected Departure Date</th>
					<th>Actual Departure Date</th>
					<th>Expected Arrival Date</th>
					<th>Actual Arrival Date</th>
					<th>Expected Take In Charge</th>
					<th>Actual Pickup Date</th>
				</tr>
			</thead>
			<tbody id="show_data">
				
			</tbody>
		</table>
	</div>
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
		        url   : window.location.origin+'/track/data_track',
		        async : false,
		        dataType : 'json',
		        success : function(data){
		            var html = '';
		            var i;
		            for(i=0; i<data.length; i++){
		                html += '<tr>'+
		                  		'<td>'+data[i].no_job+'</td>'+
		                        '<td>'+data[i].consignee+'</td>'+
		                        '<td>'+data[i].etd+'</td>'+
								'<td>'+data[i].atd+'</td>'+
								'<td>'+data[i].eta+'</td>'+
								'<td>'+data[i].ata+'</td>'+
								'<td>'+data[i].pickup_do+'</td>'+
								'<td>'+data[i].pickup_cargo+'</td>'+
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