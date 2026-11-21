<style type="text/css">
.divider{
    width:5px;
    height:auto;
    display:inline-block;
}

#btnNew,#btnCetak,#btnCopy,#btnDelete,#btnEdit,#btnNext,#btnPrev
{
	box-shadow:inset 0px 1px 0px 0px #bbdaf7;
	background:linear-gradient(to bottom, #79bbff 5%, #378de5 100%);
	background-color:#79bbff;
	border-radius:6px;
	border:1px solid #84bbf3;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:11px;
	padding:6px 24px;
	text-decoration:none;
	text-shadow:0px 1px 0px #528ecc;
}

#btnNew,#btnCetak,#btnCopy,#btnDelete,#btnEdit,#btnNext,#btnPrev:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}

.tableft-1 {
	float: left;
	width: 60px;
	border-top: 2px solid #bbdaf7; 
	border-left: 2px solid #bbdaf7; 
	border-bottom: 2px solid #bbdaf7;
	border-right: 2px solid #bbdaf7;
	margin: 10px 0 2px 0;
	padding: 10px;
	height: 50px;
}
.tableft-2 {
	float: left;
	width: 575px;
	border-top: 2px solid #bbdaf7; 
	border-left: 2px solid #bbdaf7; 
	border-bottom: 2px solid #bbdaf7;
	border-right: 2px solid #bbdaf7;
	margin: 10px 0 2px 10px;
	padding: 10px;
	height: 50px;
	vertical-align: middle;
}
.tableft-3 {
	float: left;
	width: 670px;
	border-top: 2px solid #bbdaf7; 
	border-left: 2px solid #bbdaf7; 
	border-bottom: 2px solid #bbdaf7;
	border-right: 2px solid #bbdaf7;
	margin: 0 2px 2px 0;
	padding: 10px;
	height: 70px;
	vertical-align: middle;
}
.tabright {
	float: right;
	width: 275px;
	border-top: 2px solid #bbdaf7; 
	border-left: 2px solid #bbdaf7; 
	border-bottom: 2px solid #bbdaf7;
	border-right: 2px solid #bbdaf7;
	margin: 10px 0 10px 0;
	padding: 0 10px 0 10px;
	height: 165px;
	vertical-align: middle;
} 
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>jqGrid Inline Edit Example</title>
</head>
<body>
<div id="msg-selling"></div>
<div id="message-selling"></div>
<div id="loading-selling" style="float: right;"></div>
<div id="delete-confirm"></div>
			<input type="hidden" id="idSelling">
			<input type="hidden" id="operForm">
<div id="tabs-lading">
	<ul id="tabs-menu">
		<li><a href="#tab-1">SELLING RATE</a></li>
  		<li><a href="#tab-2">BROWSE DATA</a></li>
	</ul>	
	<div id="tab-1" class="the-tab" style="width: 1000px; min-height: 440px">
	<div id="topbutton">
    		<input type="button"  class="custom" value="NEW" id="btnNew" onclick="new_data()">
			<div class="divider"/>
			<input type="button"  class="custom" value="EDIT" id="btnEdit" onclick="edit_data()">
			 <div class="divider"/>
			<input type="button"  class="custom" value="PREV" id="btnPrev" onclick="prev_data()">
			 <div class="divider"/>
			<input type="button"  class="custom" value="NEXT" id="btnNext" onclick="next_data()">
			 <div class="divider"/>
			<input type="button"  class="custom" value="DEL" id="btnDelete">
			 <div class="divider"/>
			<input type="button"  class="custom" value="PRINT" id="btnCetak">
			 <div class="divider"/>
			<input type="button"  class="custom" value="COPY DATA" id="btnCopy" onclick="copy_data()">
	 </div>	
    <div style="clear: both"></div>	
  
		<div class="tableft-1" style="min-height: 60px">
			<div><input type="radio" name="emkl_type" id="emkl_type_of" value="0"> AIR FREIGHT</div>
			<div style="margin-top: 30px;">
			<input type="radio" name="emkl_type" id="emkl_type_misc" value="1"> MISC</div>
		</div>

		<div class="tableft-2" style="min-height: 60px">
			<div>
				<table>
				<td align="left" height="15">CUSTOMER CODE</td>
				<td width="10" align="center">:</td>
				<td><input type="text" style="width: 436px; float: right; font-weight: bold; margin-top: 5px;" value="<?php echo $shipper_id ?>" id="shipperId" disabled="disabled"></td>
				</table>
			</div>
			<div>
				<table>
				<td align="left" height="15">CUSTOMER NAME</td>
				<td width="10" align="center">:</td>
				<td><input type="text" style="width: 436px; float: right; margin-top: 5px; font-weight: bold;" value="<?php echo $shipper_name ?>" id="shipperAdd" disabled="disabled"></td>
				</table>
			</div>
		</div>
		
		<div class="tabright" style="min-height: 200px">
			<table summary="" width="98%" style="margin: 10px;">
			<tr>
				<td align="left" height="15">Ref No.</td>
				<td width="10" align="center">:</td>
				<td colspan="2"><?= form_input(array("id"=>"refNo","style"=>"width: 180px","style"=>"margin-bottom: 5px")) ?></td>
			</tr>
			<tr>
			<td align="left" height="15">Date</td>
				<td width="10" align="center">:</td>
				<td colspan="2"><input type="text" style="width: 180px; margin-bottom: 5px" id="dateForm"></td>
			</tr>
			<tr>
				<td align="left" height="15">Valid</td>
				<td width="10" align="center">:</td>
				<td colspan="2"><input id="validForm" type="text" style="width: 180px; align: right; margin-bottom: 5px"></td>
			</tr>
			<tr>
				<td align="left" height="15">Salesman</td>
				<td width="10" align="center;">:</td>
				<td colspan="2">
					<?php
					$user_id = $this->session->userdata('user_id');
					$user_name = $this->session->userdata('fullname');
					$user_level = $this->session->userdata('level_id');
					$disabled = '';
					if($user_level == 0 || $user_level!= 1 || $user_level!= 4 ) {
						$disabled = 'disabled';
					}

					$sales = $this->user_model->get_data_sales();
					echo '<select name="from" size="1" style="width: 180px; margin-bottom: 5px" id="fromForm" '.$disabled.'>';
        			echo '
								<option value=""></option>';
								foreach( $sales->result() as $rows ){
                        echo '<option value="'.$rows->user_id.'">'.$this->fungsi->get_user_by_id($rows->user_id).'</option>';
                       }
               echo '</select>';
           		?>
				</td>
			</tr>
			<tr>
				<td align="left" height="15">To</td>
				<td width="10" align="center">:</td>
				<td><input type="text" style="width: 180px; margin-bottom: 5px;" id="toForm"></td>
			</tr>
			<tr>
				<td align="left" height="15">Attn</td>
				<td width="10" align="center">:</td>
				<td><input type="text" style="width: 180px; margin-bottom: 5px;" id="attnForm"></td>
			</tr>
		</table>
   	</div>
   	   	
		<div class="tableft-3" style="margin-top:5px; min-height: 90px ">
			<b>REMARKS : </b><br>
			<textarea style="max-width: 100%; min-width: 98%; min-height: 75px; max-height: 75px; margin-bottom: 5px;" id="remarkForm"></textarea>
    	</div>
    	
    	<div style="clear: both"></div>
    	
    	<div id = "air-freight">
    		<table id="table-selling"></table>
    		<div id="page-selling"></div>
    	</div>
		<div id = "misc">
    		<table id="table-selling-misc"></table>
    		<div id="page-selling-misc"></div>
    	</div>
    	
	</div>
	
	<div id="tab-2" class="the-tab">
		<div class="tableft-lading">
			<table id="table-listing"></table>
			<div id="page-sellisting" class="page-sellisting"></div>
		</div>
	</div>
</div>

<div id="form_copy"></div>

<?php include "v_air_rate_script.php" ?>
