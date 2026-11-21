<style>
.item1 { grid-area: header; }
.item2 { grid-area: menu; }
.item3 { grid-area: main; }
.item4 { grid-area: right; }
.item5 { grid-area: footer; }

.grid-container {
  display: grid;
  grid-template-areas:
    'header header'
    'main right'
    'footer footer';
  gap: 5px;
  background-color: #2196F3;
  padding: 5px;
}

.grid-container > div {
  background-color: rgba(255, 255, 255, 0.8);
  text-align: center;
  padding: 10px 0;
  font-size: 15px;
}

table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: none;
  padding: 5px;
}

#kiri
{
font-family: arial, sans-serif;
text-align: left;
 margin-left: 3px;
}
#kanan
{
font-family: arial, sans-serif;
text-align: left;
 margin-left: 3px;
}
#bawah
{
font-family: arial, sans-serif;
width:100%;
margin-top:10px;
}
</style>


<div class="grid-container">
<div class="item1">DETAIL BOOKING <?= $booking->booking_code; ?></div>
  <div class="item3">
  <div id="kiri">
<form id="bookingData">
	<table>
	<?php 
		$detailBook = $this->dbx->get_where("trnstuffingplan_booking_detail", array("booking_id" => $booking->booking_code))->result()[0];
	?>
		<tr>
			<td>SHIPPER :</td>
			<td id="shipper">
			<?= $detailBook->shipper_name; ?>
			</td>
		</tr>
		<tr>
			<td>TYPE OF BOOKING</td>
			<td id ="type-of-booking"><?= ($booking->type_of_booking ==="EX") ? "EXPORT" :(($booking->type_of_booking === "IM") ? "IMPORT" : "DOMESTIC"); ?> / <?= $booking->mode; ?></td>
		</tr>
		<tr>
			<td>MOVEMENT TYPE / INCOTERM</td>
			<?php 
			if ( $booking->movement_type == "PTP"){
				$movement = "PORT TO PORT";
			}else if ( $booking->movement_type == "PTD"){
				$movement = "PORT TO DOOR";
			}else if ( $booking->movement_type == "DTP"){
				$movement = "DOOR TO PORT";
			}else if ( $booking->movement_type == "DTD"){
			$movement = "DOOR TO DOOR";}
			?>
			<td id="movement-type"><?= $movement; ?> / <?= $booking->incoterm; ?></td>
		</tr>
		<tr>
			<td>POL/POD :</td>
			<td id="origin"><?= $booking->origin_city?> / <?= $booking->destination_city?> </td>
		</tr>
		<tr>
			<td>TRANSHIPMENT :</td>
			<td>
				<select id="ddl" name="ddl" onchange="configureDropDownLists(this,document.getElementById('ddl2'))" autocomplete="off">
					<option value="no" selected>DIRECT</option>
					<option value="yes">VIA</option>
				</select>
				<select id="ddl2" name="ddl2" style="display:none;"></select>
			</td>
		</tr>
		<tr>
			<td>VESSEL :</td>
			<td>
			<input type="text" id="vessel" name="vessel" value="<?= $booking->vessel; ?>" required />
			</td>
			<td>CONNECTING:</td>
			<td>
			<input type="text" id="voyage" name="voyage" value="<?= $booking->voyage; ?>"></td>
			<input type="hidden" id="booking-code" name="booking-code" value="<?= $booking->booking_code; ?>"></td>
			<input type="hidden" id="mode" name="mode" value="<?= $booking->mode; ?>"></td>
			<input type="hidden" id="destination" name="destination" value=" <?= $booking->destination_city?>"></td>
		</tr>
		<tr>
			<td>ETD :</td>
			<td>
			<input type="date" id="etd" name="etd" value="<?= $booking->expect_etd; ?>" required>
			</td>
			<td>ETA :</td>
			<td>
			<input type="date" id="eta" name="eta" value="<?= $booking->eta; ?>" required></td>
		</tr>
		<tr>
			<td>SHIPPER INTRUCTION :</td>
			<td><textarea id="remark" name="remark" rows="4" cols="20"><?= $booking->remark ?></textarea></td>
		</tr>
		<?php if($booking->pickup_location != "-") {?>
				<tr>
			<td> PICKUP :</td>
			<td><textarea id="pickup" name="pickup" rows="4" cols="20"><?= $booking->pickup_location ?></textarea></td>
			<?php if($booking->drop_location != "-") {?>
			<td> DELIVERY :</td>
			<td><textarea id="delivery" name="delivery" rows="4" cols="20"><?= $booking->drop_location ?></textarea></td>
			<?php } ?>
		</tr>
		<?php } ?>
		
		<?php if(!is_null($booking->file_msds)) {?>
		<tr>
			<td>DG CERTIFICATE :</td>
			<td id="dg"><a href=<?= $booking->file_msds; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
		<?php if($booking->file_invoice != "-") {?>
				<tr>
			<td> FILE INVOICE :</td>
			<td id="invoice"><a href=<?= $booking->file_invoice; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
		<?php if($booking->file_packing_list != "-") {?>
				<tr>
			<td> FILE PACKING LIST :</td>
			<td id="packing_list"><a href=<?= $booking->file_packing_list; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
		<?php if($booking->file_bl != "-") {?>
				<tr>
			<td> FILE BL :</td>
			<td id="bl"><a href=<?= $booking->file_bl; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
		<?php if($booking->file_co != "-") {?>
				<tr>
			<td> FILE COO :</td>
			<td id="file-co"><a href=<?= $booking->file_co; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
		<?php if($booking->file_other != "-") {?>
				<tr>
			<td> FILE PEB :</td>
			<td id="file-peb"><a href=<?= $booking->file_other; ?> target="_blank">LIHAT FILE</a></td>
		</tr>
		<?php } ?>
	</table>
	</form>
</div>
  </div>  
  <div class="item4">
  <div id="kanan">
	<form id="financeData">
	<table>
		<tr>
			<td>CARGO TYPE :</td>
			<td id="cargo-type"><?= $booking->cargo_type; ?></td>
		</tr>
		<tr>
			<td>CLOSING DOCUMENT :</td>
			<td>
			<input type="datetime-local" id="closing_date" name="closing_date" value="<?= $booking->closing_date; ?>" required>
			</td>
		</tr>
		<tr>
			<td>PIC DOCUMENT (PLANNER):</td>
			<td id="pic_document">
			<?php 
			if($this->session->userdata('region_id')=="Jakarta"){
			$options = array(
			''  => '',
			'AYI'  => 'AYI',
			'HERI'  => 'HERI',
			'ANGEL'  => 'ANGEL',
			'HANDOKO'  => 'HANDOKO',
			'WAWAN'  => 'WAWAN',
			'WATI'  => 'WATI',
			'ISHAK'  => 'ISHAK'
			);
			}else if($this->session->userdata('region_id')=="Semarang"){
			$options = array(
			''  => '',
			'HENDRA'  => 'HENDRA',
			'SENNY'  => 'SENNY',
			);	
			}else if($this->session->userdata('region_id')=="Semarang"){
			$options = array(
			''  => '',
			'INTAN'  => 'INTAN',
			'DYAN'  => 'DYAN',
			);
			}
			echo form_dropdown('pic_document', $options, $booking->pic_document);
			?>
			</td>
		</tr>
		<td>WAREHOUSE :</td>
			<td id="warehouse">
			<?php
			$warehouse = $this->afg_m_ref->getListWarehouse()->result();
				$data['option_warehouse']['']= ' ';
				foreach ($warehouse as $row) {	
					$data['option_warehouse'][$row->warehouse_code]=$row->warehouse_code;
				}
				echo form_dropdown('warehouse',$data['option_warehouse'],$booking->warehouse,'id="warehouse"');
				?>
			</td>
		</tr>
		<tr>
			<td>STATUS :</td>
			<td><?= $booking->status ?></td>
		</tr>
		<tr>
			<td>VALID DATE :</td>
			<input type="hidden" name="status" value="prepared">
			<input type="hidden" id="incoterm" name="incoterm" value="<?= $booking->incoterm; ?>">
			<input type="hidden" name="id" value="<?= $booking->id;?>">
			<input type="hidden" name="booking_code" value="<?= $booking->booking_code;?>">
			<input type="hidden" name="transhipment" value="<?= $booking->trans_city;?>" id="transhipment">
			<td>
			<input type="date" id="valid" name="valid_at" value="<?= $booking->valid_at; ?>" required>
			</td>
		</tr>
		<tr >
			<td colspan="2" style = "width:100%; text-align:center; padding-top:20px;"><hr style="height:2px;border-width:0;color:gray;background-color:gray"><h4>REQUESTED SERVICES</h4><hr style="height:2px;border-width:0;color:gray;background-color:gray"></td>
		</tr>
		<tr>
			<td style = "padding-top:20px;">INSURANCE :</td>
			<td id="insurance"><?= $booking->insurance  == 1 ? 'YES' : 'NO'; ?></td>
		</tr>
		<tr>
			<td>TRUCKING / PICKUP :</td>
			<td id="trucking"><?= $booking->trucking  == 1 ? 'YES' : 'NO'; ?></td>
		</tr>
		<tr>
			<td>PEB NPE :</td>
			<td id="clearance"><?= $booking->peb  == 1 ? 'YES' : 'NO'; ?></td>
		</tr>
		<tr>
			<td>COO :</td>
			<td id="coo"><?= $booking->clearance  == 1 ? 'YES' : 'NO'; ?></td>
		</tr>
		<tr>
	<td colspan="2" style = "width:100%; text-align:left; padding-top:20px;"><i> Mohon input selling untuk service requested dengan status "YES".</i></td>
	</tr>
	</table>
	</form>
	</div>
  </div>
  <div class="item5">
  <table border="1px">
		<thead>
			<tr>
				<th>QUANTITY</th>
				<th>PACKAGE TYPE</th>
				<th>CARGO DESCRIPTION</th>
				<th>DIMENSION</th>
				<th>VOLUME</th>
				<th>WEIGHT</th>
				<th>CONTAINER TYPE</th>
			</tr>
		</thead>
		
		<tr>
			<td id ="quantity"><?= $detailBook->quantity?></td>
			<td id ="package_type"><?= $detailBook->package_type?></td>
			<td id="cargo_description"><?= $detailBook->description_print?></td>
			<td id="dimension"><?= $detailBook->dimension ?> (cm³)</td>
			<td id="volume"><?= $detailBook->volume?></td>
			<td id="weight"><?= $detailBook->weight?></td>
			<td id="container_type"><?= $detailBook->container_type?></td>
		</tr>
	</table>
  </div>
</div>
<div id="bawah">
	<div style="width:100%;">
		<h3>FREIGHT PRICE</h3>
		<table id="table-selling-of">
		</table>
		<div id="page-selling-of"></div>
	</div>
	<!--
	<div style="width:50%;float:right;">
		<h3>MISCELLANEOUS PRICE</h3>
		<table id="table-selling-misc">
		</table>
		<div id="page-selling-misc"></div>
	</div>
	-->
	<div style='clear:both;'></div>
	<div id="footer" style="clear:both;text-align:right;margin-top:20px">
	<button onclick="kirimpost()" type="button" form="financeData">Submit</button>
	<button onclick="print_quot()" type="button">Print</button>
	</div>
	<div id="PRINT"></div>
</div>


		
<script>
document.getElementById('valid').valueAsDate = new Date();
var pld = <?=$cities?>;
$.each(pld, function(key, value) {
	$('select#ddl2').append($('<option></option>').attr('value',key).text(value))
})
function myFunction() {
  var x = document.getElementById("ddl2").value;
  document.getElementById("transhipment").innerHTML = x;
}
function kirimpost() {
var vessel = document.getElementById("vessel").value;	
var closing = document.getElementById("closing_date").value;
 if(vessel == ""|| closing==""){
swal('nama vessel atau tanggal closing tidak boleh kosong');
 }else{
  kirim();
  post_quot();
 }
}

function kirim(){
	var data = JSON.stringify($("#financeData").serializeArray());
  	$.ajax({
		type : "POST",
		url : window.location.origin+'/pricelist/afg_c_booking/update_finance',
		data: $("#financeData,#bookingData").serialize(),
		success : function(data){
			$("#Form-Pricing-Quote").dialog('close');
		}
	})	
}
function post_quot() {
	var booking_id = <?= $booking->id;?>;
	var booking_code =  '<?= $booking->booking_code;?>';
	var shipper =  '<?= $booking->shipper_id;?>';
		$.ajax({
			type: "POST", 
			url : window.location.origin+"/pricelist/afg_c_booking/post_quot",
			data: { 
				booking_id,
				booking_code,
				shipper
			},
			success:function(response)
			{
				swal("Berhasil")
			},
			error: function(response){
				swal("Gagal")
			}
		})
}
table_selling_of();
//table_selling_misc();

function table_selling_of()
{
	var grid = $("#table-selling-of"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('pricelist/afg_c_booking/get_table_selling')?>",
		mtype : "post",
		datatype: "json", 
		postData:  {  
			booking_id	: <?= $booking->id;?>,
		},
		colNames:
		[
			'OF/MISC',
			'Item of Service',
			'P/C',
			'Currency',
			'Quantity',
			'Unit Price',
			'Price',
			'Condition',
		],
    	colModel:
		[
			{name:'of_emkl',index:'of_emkl', align:"center", width:50, 
				editable: false, 
				hidden: true
			},
			{name:'item',index:'nama_service', align:"left", width:150, 
				editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'kondisi',index:'kondisi', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"center", width:40, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"RP.:RP.;USD.:USD.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
						},
			},
			{name:'qty',index:'qty', align:"center", width:40, editable: true, 
				edittype:"text", 
				editoptions:{
					dataEvents: 
						[{  type: 'keyup', fn: function(e) {
									var rowid = grid.jqGrid('getGridParam', 'selrow');
									var qty = e.target.value;
									var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
									var price = eval(qty) * eval(unit_price);
									grid.jqGrid('setCell',rowid,'price',price);
								}
						}]
				}
			},
			{name:'unit_price',index:'unit_price', align:"right", width:100, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
    				dataInit: function (el) { 
							$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
							$(el).css({'min-width':'70px'});
						},
						dataEvents: 
						[{  type: 'keyup', fn: function(e) {
									var rowid = grid.jqGrid('getGridParam', 'selrow');
									var qty = grid.jqGrid('getCell',rowid,'qty');
									var unit_price = e.target.value;
									if (!unit_price)
									{swal('unit price belum tersimpan atau belum di tabs');}
									var price_value = eval(qty) * eval(unit_price);
									grid.jqGrid('setCell',rowid,'price',price_value);
								}
						}]
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				},
			},
			{name:'price',index:'price', align:"right", width:100, editable: true, 
				formatter: 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				},
					dataEvents: 
						[{  type: 'keyup', fn: function(e) {
									var cek_price = grid.jqGrid('getCell',rowid,'price');
									if (!cek_price)
									{swal('price belum tersimpan atau belum di tabs');}
								}
						}]
			},
			{name:'condition',index:'condition', align:"left", width:200, editable : true,
			editoptions:{
					dataInit: function(element) {
        				$(element).css({ "text-transform": "uppercase","max-width":"200px"})
												}
				}
			},
		], 
		sortname: "id",
		sortorder: "asc",
		loadonce : false,
		shrinkToFit: true,
		viewrecords: true,
		multiselect:true, 
//	modal: true,
		rownumbers: true,
		rowNum: 5,
//	top: 20,
		autowidth: true,
		height: 150,
		rowList:[5,10,15],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'afg_c_booking/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-selling-of',
		gridComplete : function()
		{
			//$("#btnInsertSeling").children(".ui-pg-div").width("133px");
		},
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
		},
    	
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_data_of(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		},
		afterAddRow: function(rowid, cellname, value, iRow, iCol){
			//alert("")
		},
	})
	
	grid.navGrid('#page-selling-of',{ edit: false, add: false, del: true, refresh: false, <?php echo 'search: false,' ?> },
		{},
		{},
		{},
		{},
		{}
	)
	grid.jqGrid('navButtonAdd','#page-selling-of',{ 
			caption:"",
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_of, 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddDua",
	})
	grid.navButtonAdd('#page-selling-of',{ 
			caption:"", 
			buttonicon:" ui-icon-arrowthickstop-1-s", 
			onClickButton: tarik_data_selling_price_quot, 
			position: "first", 
			title:"Tarik Data Selling Price", 
			cursor: "pointer",
			id:"btnTrkSelling",
	})

}

function saving_data_of(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table-selling-of"), i;
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/afg_c_booking/saving_data',
		data	:
		{
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}


function insert_data_of() {
	var grid = $("#table-selling-of"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/afg_c_booking/insert_data',
		data	:
		{
			booking_id : <?= $booking->id; ?>,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid("setGridParam", {datFatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

function tarik_data_selling_price_quot() {
	

	var shipper = <?= $booking->shipper_id; ?>;
	var sales = <?= $booking->sales; ?>;
	var destination = "<?= $booking->destination_city; ?>";
	var shipment = "<?= $booking->mode; ?>";
	var berat = <?= $detailBook->weight?>;
	var qty = <?= $detailBook->volume?>;
	var valid = <?= date("Ymd", strtotime($booking->valid_at)); ?>;
	var booking_id = <?= $booking->id;?>;
	var container = "<?= !empty($detailBook->container_type)? $detailBook->container_type :""?>";
	
	
	$.ajax
	({
		type: "POST",
		url : window.location.origin+'/pricelist/afg_c_booking/tarik_data_selling_price_quot',
		data:  
		{ 
			shipper_id : shipper,
			sales : sales,
			destination : destination, 
			valid : valid,
			shipment : shipment,
			qty : qty,
			container : container,
			booking_id : booking_id,
			type_of_booking : "<?= $booking->type_of_booking?>",
		},
		success: function(XMLHttpRequest)
		{
			$("#table-selling-of").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
		},
		error:function(XMLHttpRequest)
		{
			return false;
		}
	})
}

/**
 * 
 * ENDING TABLE SELLING OF 
 * 
 */


 
/**
 * 
 * START TABLE SELLING MISC 
 * 
 */

function table_selling_misc()
{
	var grid = $("#table-selling-misc"), i;	
	grid.jqGrid({ 
		url:"<?php echo base_url('pricelist/afg_c_booking/get_table_selling_misc')?>",
		mtype : "post",
		datatype: "json", 
		postData:  {  
			booking_id	: <?= $booking->id;?>,
			of_emkl:1
		},
		colNames:
		[
			'OF/MISC',
			'Item of Service',
			'P/C',
			'Currency',
			'Quantity',
			'Unit Price',
			'Price',
			'',
		],
    	colModel:
		[
			{name:'of_emkl',index:'of_emkl', align:"center", width:50, 
				editable: false, 
				hidden: true
			},
			{name:'item',index:'nama_service', align:"left", width:150, 
				editable: true, 
				edittype:"select",
				editoptions:
				{
					dataUrl:'<?php echo base_url("afg_c_dropdown/dropdownItemService") ?>',
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'150px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'kondisi',index:'kondisi', align:"center", width:50, editable: true, 
				edittype:"select",
				formatter:'select', 
				editoptions:
				{
					value:"PREPAID:PREPAID;COLLECT:COLLECT",
					dataInit: function (el) 
					{ 
						$(el).css({'max-width':'50px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
          	},
			},
			{name:'currency',index:'currency', align:"center", width:70, editable: true, 
				edittype:"select", 
				editoptions:
				{
					value:"RP.:RP.;USD.:USD.",
					dataInit: function (el) 
					{ 
						$(el).css({'min-width':'70px'}); 
					},
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
						},
			},
			{name:'qty',index:'qty', align:"center", width:70, editable: true, 
				edittype:"text", 
				editoptions:{
					dataEvents: 
						[{  type: 'keyup', fn: function(e) {
									var rowid = grid.jqGrid('getGridParam', 'selrow');
									var qty = e.target.value;
									var unit_price = grid.jqGrid('getCell',rowid,'unit_price');
									var price = eval(qty) * eval(unit_price);
									grid.jqGrid('setCell',rowid,'price',price);
								}
						}]
				}
			},
			{name:'unit_price',index:'unit_price', align:"right", width:70, editable: true, 
				formatter: 'number',
    			formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
    			editoptions: { 
					dataInit: function (el) { 
						$(el).attr({'onKeyup':'format_number(this,2)','onChange':'format_number(this,2)','title':'Hanya boleh memasukan angka'}); 
						$(el).css({'min-width':'70px'});
					},
					dataEvents: 
						[{  type: 'keyup', fn: function(e) {
									var rowid = grid.jqGrid('getGridParam', 'selrow');
									var qty = grid.jqGrid('getCell',rowid,'qty');
									var unit_price = e.target.value;
									var price = eval(qty) * eval(unit_price);
									grid.jqGrid('setCell',rowid,'price',price);
								}
						}]
				},
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				},
			},
			{name:'price',index:'price', align:"right", width:70, editable: false, 
				formatter: 'number',
				formatoptions: { decimalSeparator: ".", thousandsSeparator: " ", decimalPlaces: 2, defaultValue: '' },
				searchoptions: {
					searchhidden: true,
					sopt:['cn','eq'],
				},
			},
			{name:'space',index:'space', align:"left", width:30,},
		], 
		sortname: "id",
		sortorder: "asc",
		loadonce : false,
		shrinkToFit: false,
		viewrecords: true,
		multiselect:true, 
//	modal: true,
		rownumbers: true,
		rowNum: 5,
//	top: 20,
		width: 600,
		height: 150,
		rowList:[5,10,15],
		gridview: true,
		caption: false,
		cellEdit: true,
		editurl:'selling_rate/manage_data',
		cellsubmit:'clientArray',
		pager:'#page-selling-misc',
		gridComplete : function()
		{
			//$("#btnInsertSeling").children(".ui-pg-div").width("133px");
		},
		loadComplete: function(data) {
			$("#cb_"+grid[0].id).hide();
			var ids = grid.jqGrid('getDataIDs');
			if (ids.length>=1) {
    			grid.jqGrid('editCell',ids.length,3,true);
    		}
		},
    	/*
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
		*/
		afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
			saving_data_misc(rowid, cellname, value, iRow, iCol);
			if(event.keyCode==13) 
			{
				grid.jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
			}
		},
		afterAddRow: function(rowid, cellname, value, iRow, iCol){
			//alert("")
		},
	})
	
	grid.navGrid('#page-selling-misc',{ edit: false, add: false, del: true, refresh: true, <?php echo 'search: false,' ?> },
		{},
		{},
		{},
		{},
		{}
	)
	
	grid.navButtonAdd('#page-selling-misc',{ 
			caption:"",
			id:"btnInsertSelling", 
			buttonicon:"ui-icon-plus", 
			onClickButton: insert_data_misc, 
			title:"Insert Data Selling Price", 
			cursor: "pointer",
			id:"btnAddDua",
	})
}

function saving_data_misc(rowid, cellname, value, iRow, iCol)
{
	var grid = $("#table-selling-misc"), i;
	var myData = grid.getRowData(rowid);
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/afg_c_booking/saving_data',
		data	:
		{
			id		 : rowid,
			myData : myData,
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
		}
	})
}


function insert_data_misc() {
	var grid = $("#table-selling-misc"), i;
	$.ajax
	({
		type	:"POST",
		url	:window.location.origin+'/pricelist/afg_c_booking/insert_data',
		data	:
		{
			booking_id : <?= $booking->id; ?>,
			of_emkl: '1'
		},
		success:function(response)
		{
			var data = $.parseJSON(response);
			if(data.status==1){
				var dataSatu = [{'id':data.id}];
				grid.jqGrid("setGridParam", {datFatype: 'json'}).trigger("reloadGrid");
			}
		}
	})
}

/** DD TRANSHIPMENT **/
var trans_city = '<?= $booking->trans_city; ?>'
if(trans_city){
	$("select#ddl").val("yes")
	$("select#ddl2").show();
	$("select#ddl2").val(trans_city);
}
function configureDropDownLists(ddl1, ddl2) {
 
  var no = [''];
  var yes = ['SINGAPORE', 'HONGKONG', 'ROTTERDAM'];
	if(ddl1.value == "yes"){
		$("select#ddl2").show();
	}else{
		$("select#ddl2").hide();
	}
}

/** PRINT **/

function print_quot()
{
	var data = {};
	var booking_id = <?= $booking->id;?>;
	var shipper_id = <?= $booking->shipper_id;?>;
	var type_of_booking = document.getElementById('type-of-booking').innerHTML;
	var booking_code = '<?= $booking->booking_code;?>';
	var mode = '<?= $booking->mode;?>';
	var movement_type = document.getElementById('movement-type').innerHTML;
	if(document.getElementById("contact") != null){
    var contact = document.getElementById('contact').innerHTML;
	}else{
	var contact ='';
	}
	var valid = $('#valid').val();
	var company = $('#company').val();
	var email = $('#email').val();
	var etd =  document.getElementById('etd').innerHTML;
	var eta =  document.getElementById('eta').innerHTML;
	var vessel = document.getElementById('vessel').innerHTML;
	var origin = document.getElementById('origin').innerHTML;
	var incoterm = document.getElementById('incoterm').innerHTML;
	var cargo_type = document.getElementById('cargo-type').innerHTML;
	var destination = '<?= $booking->destination_city;?>';
	if(document.getElementById("ddl2") != ''){
	var via = $('#ddl2').val();}
	else {var via="No";}
	var insurance = document.getElementById('insurance').innerHTML;
	var trucking = document.getElementById('trucking').innerHTML;
	var clearance = document.getElementById('clearance').innerHTML;
	var quantity = document.getElementById('quantity').innerHTML;
	var package_type = document.getElementById('package_type').innerHTML;
	var cargo_description = document.getElementById('cargo_description').innerHTML;
	var volume = document.getElementById('volume').innerHTML;
	var weight = document.getElementById('weight').innerHTML;
	data.type_of_booking = type_of_booking;
	data.mode = mode;
	data.contact = contact;
	data.booking_code = booking_code;
	data.movement_type = movement_type;
	data.valid = valid;
	data.email = email;
	data.vessel = vessel;
	data.shipper_id = shipper_id;
	data.company= company;
	data.origin= origin;
	data.destination= destination;
	data.via=via;
	data.incoterm=incoterm;
	data.insurance=insurance;
	data.trucking=trucking;
	data.clearance=clearance;
	data.cargo_type=cargo_type;
	data.quantity=quantity;
	data.package_type=package_type;
	data.cargo_description=cargo_description;
	data.volume=volume;
	data.weight=weight;
	data.booking_id=booking_id;
	data.etd=etd;
	data.eta=eta;
	var url=serialize(data)
	
 		$("#PRINT" ).dialog({
			position: "top",
			autoOpen: true,
			resizable: true,
			height: 'auto',
			width: 980,
			modal: true,
			title: "QUOTATION",
			buttons : {  },
			close: function()  {$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>')}
		});
		
		$('#PRINT').html('<object id="form-data" data="afg_c_booking/print_data?'+url+'" width=" 950px" height="600" type="application/pdf" \>');

}

</script>