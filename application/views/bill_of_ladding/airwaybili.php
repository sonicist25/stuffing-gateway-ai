<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
<style>
/** Add css rules here for your application. */


/** Example rules used by the template application (remove for your app) */
h1 {
  font-size: 2em;
  font-weight: bold;
  color: #777777;
  margin: 40px 0px 70px;
  text-align: center;
}




iron-icon {
	color: #616161;
}

iron-icon.disabled {
	color: #a8a8a8;
}

.sendButton {
  display: block;
  font-size: 16pt;
}

/** Most GWT widgets already have a style name defined */
.gwt-DialogBox {
 /* width: 400px;*/
}

.gwt-TextArea {
	overflow: auto;	
}

.dialogVPanel {
  margin: 5px;
}

.serverResponseLabelError {
  color: red;
}

/** Set ids using widget.getElement().setId("idOfElement") */
#closeButton {
  margin: 15px 6px 6px;
}

.dataTable th {
	padding-right: 2px;
	padding-left: 2px;
	
}

.dataTablePanel_full  {
	margin-top: 1em;	
}

.dataTablePanel paper-button  {
	margin-bottom:1em;
	vertical-align: middle;
}

.dataTablePanel paper-button iron-icon {
	margin-right: 0.4em;
}


.noWrapCell {
	padding: 2px !important;
}

.noWrapCell div {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor:default;
}

.noUppercasePaperButton {
	text-transform: none;	
}

.toggleButtonTabList paper-button[toggles] {
	/*background-color: #ebedf7;*/
	background-color: white;
	color: black;
	justify-content: left;
	/*float: left;*/
	text-transform: none;

}

.toggleButtonTabList paper-button[toggles][active] {
	background-color: #3f51b5;
	color: white;
}


.abstractPaperDialog {
	 font-size: inherit;
      font-weight: inherit;
      line-height: inherit;
}

.formPanel-FlexHorizontal {
	display: -webkit-box;      /* OLD - iOS 6-, Safari 3.1-6 */
	display: -moz-box;         /* OLD - Firefox 19- (buggy but mostly works) */
	display: -ms-flexbox;      /* TWEENER - IE 10 */
	display: -webkit-flex;     /* NEW - Chrome */
	display: flex;             /* NEW, Spec - Opera 12.1, Firefox 20+ */ 
}


.formPanel-FlexVertical {
	display: -webkit-box;      /* OLD - iOS 6-, Safari 3.1-6 */
	display: -moz-box;         /* OLD - Firefox 19- (buggy but mostly works) */
	display: -ms-flexbox;      /* TWEENER - IE 10 */
	display: -webkit-flex;     /* NEW - Chrome */
	display: flex;             /* NEW, Spec - Opera 12.1, Firefox 20+ */ 
	
	-webkit-box-orient: vertical;
	-moz-box-orient: vertical;
	-webkit-flex-direction: column;
	-moz-flex-direction: column;
	flex-direction: column;
}

.formElement-Grow-1 {
	-webkit-flex:  1 1 auto; 
	-moz-flex:  1 1 auto;
	-ms-flex:  1 1 auto;
	flex: 1 1 auto; /* flex-grow: 1; */
}

.formElement-VAlign-Bottom {
	-webkit-align-self: flex-end;
	-ms-flex-item-align:  flex-end;
	-moz-flex-item-align:  flex-end;
	align-self: flex-end;
}


.billing_rates_table2 {
	border-collapse: collapse;
}

.billing_rates_table2 td {
	padding: 3px 0;
}

.billing_rates_table2 td.billing_rates_table_item {
	text-align: center;
}

.billing_rates_table2 tr.billing_rates_table_transmission {
	background-color: #fff5e6;
}

.billing_rates_table2 .billing_rates_table_transmission_item {
	padding-left: 2em;
}

.billing_rates_table2 .billing_rates_table_bottom_text {
	padding-top: 2em;
	font-size: 11pt;
}


.billing_rates_table {
	border-collapse: collapse;
}


.billing_rates_table tr td:nth-child(2), .billing_rates_table tr td:nth-child(3) {
	text-align: center;
}

.billing_rates_table tr:nth-child(2) td:nth-child(2), .billing_rates_table tr:nth-child(2) td:nth-child(3) {
	font-style: oblique;	
}

.billing_rates_table .billing_rates_table_transmission_item {
	padding-left: 2em;
}

.billing_rates_table tr:nth-child(3), .billing_rates_table tr:nth-child(6), .billing_rates_table tr:nth-child(7), .billing_rates_table tr:nth-child(8) {
	background-color: #f3f7fb;
}

.billing_rates_table .billing_rates_table_bottom_text {
	padding-top: 2em;
}





/** For dialogs **/
.leftMarginSubgroup {
	margin-left: 2em;
}

.controlLineSeparation {
	margin-left: 0.5em;
}

.controlLineSeparationRight {
	margin-right: 0.5em;
}



.cursorPointer {
	cursor: pointer;
}




.gwt-ToggleButton {
	margin: 5pt;
	padding: 5pt;
	display: inline-block;
	opacity: 1;
	/*border: none;*/
	background-image: none;
}
/*.gwt-ToggleButton-up, .gwt-ToggleButton-up-hovering, .gwt-ToggleButton-up-disabled, .gwt-ToggleButton-down, .gwt-ToggleButton-down-hovering, .gwt-ToggleButton-down-disabled {*/
.gwt-ToggleButton-up, .gwt-ToggleButton-up-hovering, .gwt-ToggleButton-up-disabled, .gwt-ToggleButton-down, .gwt-ToggleButton-down-hovering {
	background-color: white;
	
}

.gwt-ToggleButton-down-disabled, .gwt-ToggleButton-down {
	background-color: #3f51b5;
	color: white;
	border: 1px solid #3f51b5
}

.menuPanel .gwt-ToggleButton {
	border: none;
}

.gwt-ToggleButton .html-face {
	display:inline
}

.gwt-ToggleButton svg {
	width: 20px;
	height: 20px;
	display: inline;
}

.gwt-PushButton-up, .gwt-PushButton-down, .gwt-PushButton-up-hovering, .gwt-PushButton-up-disabled, .gwt-PushButton-down-hovering, .gwt-PushButton-down-disabled   {
	margin: 5pt;
	padding: 5pt;
	display: inline-block;
}

.gwt-PushButton-up .html-face, .gwt-PushButton-up-hovering .html-face, .gwt-PushButton-up-disabled .html-face, .gwt-PushButton-down-hovering .html-face{
	display:inline
}

.gwt-PushButton-up, .gwt-PushButton-up-hovering, .gwt-PushButton-down, .gwt-PushButton-down-hovering {
	background: white;
	color: black;
}

.gwt-PushButton-up-disabled {
	background: #eaeaea;
	color: #a8a8a8;
}

.sideMenu .gwt-PushButton-up, .sideMenu  .gwt-PushButton-up-hovering, .sideMenu  .gwt-PushButton-down, .sideMenu .gwt-PushButton-down-hovering,
.sideMenu .gwt-PushButton-up-disabled  {
	border: none;
}

.sideMenu .gwt-PushButton-up-disabled {
	background: white;
}


.mainToolBar .gwt-ToggleButton-down-disabled { /*seleccionado*/
	background: white;
	color: #3f51b5;
	border: none;
	padding: 6pt 12pt 6pt 12pt;
}

.mainToolBar .gwt-PushButton-up, .mainToolBar .gwt-ToggleButton-up, .mainToolBar .gwt-ToggleButton-up-hovering, .mainToolBar .gwt-PushButton-up-hovering, .mainToolBar .gwt-PushButton-down-hovering { /* no seleccionado */
	background: rgba(255, 255, 255, 0.25);
	color: white;
	border: none;
	padding: 6pt 12pt 6pt 12pt;
}

div.buttons {
	text-align: right;
}

.gwt-DialogBox .dialogContent {
	padding: 3pt;
}


/* to show tabs and other characters correctly in tables */
pre.inherit-font {
	margin: 0;
	font: inherit;
	white-space: pre-wrap;
}
</style>	
</head>
 <title>Web AWB Editor</title>

  <body>
<div style="left: 0%; top: 0%; width: 100%; height: 100%; overflow: hidden; position: absolute;">
<div style="left: 10px; top: 5px; right: 5px; bottom: 5px; overflow: auto; position: absolute; -ms-zoom: 1;">
<div style="position: relative; -ms-zoom: 1;">
<div style="padding-bottom: 20px; float: left;">
<div style="position: relative;">
<table>
	<tbody>
		<tr>
			<td>
			<div class="gwt-Label">Shipper name</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 187px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-HTML">Shipper address</div>
			</td>
			<td style="vertical-align: top;">
				<textarea class="gwt-TextArea" style="width: 354px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" rows="5"></textarea>
			</td>
		</tr>
	</tbody>
</table>
</div>
<div style="position: relative;">
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-Label">Consignee name</div>
			</td>
			<td>
				<input class="gwt-TextBox" style="width: 187px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-HTML">Consignee name</div>
			</td>
			<td>
				<textarea class="gwt-TextArea" style="width: 354px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" rows="5"></textarea>
			</td>
		</tr>
	</tbody>
</table>
</div>
<div style="position: relative;">
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-Label">Departure</div>
			</td>
			<td style="vertical-align: top;">
			<input class="gwt-SuggestBox" style="width: 354px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Route</div>
			</td>
			<td style="vertical-align: top;">
				<div><span class="gwt-InlineLabel">To</span>
				<input class="gwt-SuggestBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text">
				<span class="gwt-InlineLabel" style="margin-left: 8px;">By first carrier</span>
				<input class="gwt-TextBox" style="width: 187px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label"></div>
			</td>
			<td style="vertical-align: top;">
				<div><span class="gwt-InlineLabel">To</span>
				<input class="gwt-SuggestBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text">
				<span class="gwt-InlineLabel" style="margin-left: 8px;">By</span>
				<input class="gwt-TextBox" style="width: 40px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
				<span class="gwt-InlineLabel" style="margin-left: 8px;">To</span>
				<input class="gwt-SuggestBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text">
				<span class="gwt-InlineLabel" style="margin-left: 8px;">By</span>
				<input class="gwt-TextBox" style="width: 40px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
			    </div>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Destination</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-SuggestBox" style="width: 187px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Flight/Date</div>
			</td>
			<td style="vertical-align: top;">
				<div><input class="gwt-TextBox" style="width: 95px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
				<input class="gwt-TextBox" style="width: 95px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
				</div>
			</td>
		</tr>
	</tbody>
</table>
</div>
<div style="position: relative;">
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-Label">AWB number</div>
			</td>
			<td style="vertical-align: top;">
				<div><input class="gwt-TextBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
				<input class="gwt-TextBox" style="width: 134px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Airport of departure</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-SuggestBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">HAWB number</div>
				</td>
			<td style="vertical-align: top;">
				<div><input class="gwt-TextBox" style="width: 162px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
				</div>
			</td>
		</tr>
	</tbody>
</table>
</div>
<div>
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-HTML">Accounting information</div>
			</td>
			<td style="vertical-align: top;">
				<textarea class="gwt-TextArea" style="width: 354px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" rows="7"></textarea>
			</td>
		</tr>
	</tbody>
</table>
</div>
<div>
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-Label">Reference number</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 150px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
	</tbody>
</table>
</div>
<div>
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-Label">Currency</div>
			</td>
			<td style="vertical-align: top;">
			<input class="gwt-TextBox" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
			<td>
				<div class="gwt-Label">CHCG</div>
			</td>
				<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 40px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Value for carriage</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 132px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
			<td>
				<div class="gwt-Label">WT/VAL</div>
			</td>
				<td style="vertical-align: top;">
				<select class="gwt-ListBox" style="font-family: Consolas, Inconsolata, monospace; font-size: 11pt;">
					<option value="P">PPD</option>
					<option value="C">COLL</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Value for customs</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 113px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
			<td>
			<div class="gwt-Label">Other</div>
			</td>
			<td style="vertical-align: top;">
				<select class="gwt-ListBox" style="font-family: Consolas, Inconsolata, monospace; font-size: 11pt;">
					<option value="P">PPD</option>
					<option value="C">COLL</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Amount of insurance</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 113px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
	</tbody>
</table>
</div>
<table>
	<tbody>
		<tr>
			<td>
				<div class="gwt-HTML">Handling information</div>
			</td>
			<td style="vertical-align: top;" rowspan="2">
				<textarea class="gwt-TextArea" style="width: 592px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" rows="4"></textarea>
			</td>
			<td>
				<div class="gwt-Label">SCI</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" style="width: 113px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label" aria-hidden="true" style="display: none;">Destination</div>
			</td>
			<td style="vertical-align: top;">
				<input class="gwt-TextBox" aria-hidden="true" style="width: 113px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; display: none;" type="text" autocomplete="nope">
			</td>
		</tr>
	</tbody>
</table>
<fieldset>
<legend>Rate description</legend>
<table><colgroup>
<col></colgroup>
	<tbody>
		<tr>
			<td>
				<div>
				<div style="padding-bottom: 8px; display: flex;">
				<button class="gwt-Button" type="button">Add</button>
				<button disabled="" class="gwt-Button" style="margin-left: 4px;" type="button">Modify</button>
				<button disabled="" class="gwt-Button" style="margin-left: 4px;" type="button">Remove</button>
				</div>
				</div>
				<div class="GD3K2OHCED" style="width: 821px; height: 250px; overflow: hidden; clear: both; position: relative;" __gwtCellBasedWidgetImplDispatchingfocus="true" __gwtCellBasedWidgetImplDispatchingblur="true">
				<div style="left: 0px; top: 0px; width: 100%; overflow: hidden; position: absolute; min-height: 20px; min-width: 20px;">
				<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
				<div style="width: 921px; height: 121px;">
				</div>
				</div>
				<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
				<div style="width: 200%; height: 200%;"></div></div><div><table style="width: 100%; table-layout: fixed;" cellspacing="0">
					<thead>
						<tr __gwt_header_row="0"><th class="GD3K2OHCEC GD3K2OHCCC" colspan="1" __gwt_header="header-gwt-uid-18" __gwt_column="column-gwt-uid-17">Pieces</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-20" __gwt_column="column-gwt-uid-19">Gross w.</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-22" __gwt_column="column-gwt-uid-21">k/l</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-24" __gwt_column="column-gwt-uid-23">rc</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-26" __gwt_column="column-gwt-uid-25">Item no.</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-28" __gwt_column="column-gwt-uid-27">Charg. w.</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-30" __gwt_column="column-gwt-uid-29">Rate/Charge</th>
							<th class="GD3K2OHCEC" colspan="1" __gwt_header="header-gwt-uid-32" __gwt_column="column-gwt-uid-31">Total</th>
							<th class="GD3K2OHCEC GD3K2OHCMC" colspan="1" __gwt_header="header-gwt-uid-34" __gwt_column="column-gwt-uid-33">Nature and quantity</th>
						</tr>
					</thead>
<colgroup>
	<col style="width: 49px;">
	<col style="width: 76px;">
	<col style="width: 31px;">
	<col style="width: 31px;">
	<col style="width: 76px;">
	<col style="width: 76px;">
	<col style="width: 85px;">
	<col style="width: 122px;">
	<col style="width: 214px;">
</colgroup>
</table>
</div>
</div>
<div style="left: 0px; width: 100%; bottom: 0px; overflow: hidden; display: none; position: absolute; min-height: 20px; min-width: 20px;">
<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
<div style="width: 100px; height: 100px;">
</div>
</div>
<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
<div style="width: 200%; height: 200%;">
</div>
</div>
</div>
<div style="left: 0px; top: 21px; width: 100%; height: 229px; overflow: hidden; position: absolute;">
<div class="GD3K2OHCPL" style="height: 100%; overflow: hidden; position: relative;">
<div aria-hidden="true" style="top: -20ex; width: 10em; height: 10ex; visibility: hidden; position: absolute; z-index: -32767;">&nbsp;</div>
<div style="left: 0px; top: 0px; right: -17px; bottom: -17px; overflow: hidden; position: absolute;">
<div style="left: 0px; top: 0px; right: 0px; bottom: 0px; overflow: scroll; position: absolute; -ms-zoom: 1;">
<div class="GD3K2OHCKE" style="display: block; position: relative; -ms-zoom: 1; min-height: 20px; min-width: 20px;">
<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
<div style="width: 921px; height: 120px;">
</div>
</div>
<div style="width: 100%; height: 100%; overflow: scroll; visibility: hidden; position: absolute; z-index: -1;">
<div style="width: 200%; height: 200%;"></div></div>
<table align="center">
	<colgroup>
		<col></colgroup>
			<tbody></tbody>
</table>
</div>
</div>
</div>
<div style="width: 0px; height: 0px; right: 0px; bottom: 0px; overflow: hidden; position: absolute;">
<div class="GD3K2OHCAM" style="left: 0px; top: 0px; right: 0px; bottom: 0px; position: absolute;">
</div>
</div>
<div style="left: 0px; top: 0px; right: 0px; bottom: 0px; overflow: hidden; display: none; position: absolute;">
<div class="GD3K2OHCMN GD3K2OHCKE" style="left: 0px; top: 0px; height: 17px; right: 0px; bottom: 0px; position: absolute;">
<div class="GD3K2OHCLN GD3K2OHCBM"> <div class="GD3K2OHCKN">
</div>
</div>
</div>
</div>
<div style="left: 0px; top: 0px; right: 0px; bottom: 0px; overflow: hidden; display: none; position: absolute;">
<div class="GD3K2OHCGM GD3K2OHCKE" style="left: 0px; top: 0px; width: 17px; right: 0px; bottom: 0px; position: absolute;">
<div class="GD3K2OHCFM GD3K2OHCDM"> <div>
</div> 
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</td>
</tr><tr>
<td>
	<div class="formPanel-FlexHorizontal" style="position: relative;">
	<input disabled="" class="gwt-TextBox formElement-VAlign-Bottom" style="width: 49px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
	<div class="formPanel-FlexVertical" style="position: relative;">
	<textarea class="gwt-TextArea" style="width: 408px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-bottom: 4px; margin-left: 4px;" rows="3"></textarea>
	<div class="formPanel-FlexHorizontal" style="position: relative;">
	<input disabled="" class="gwt-TextBox" style="width: 76px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
	<div class="formElement-Grow-1" style="position: relative;">
	<input disabled="" class="gwt-TextBox" style="width: 31px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" type="text" autocomplete="nope">
	</div><input disabled="" class="gwt-TextBox" style="width: 122px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
	</div>
	</div>
	<textarea class="gwt-TextArea formElement-VAlign-Bottom" style="width: 214px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt; margin-left: 4px;" rows="3"></textarea>
	</div>
	</td>
	</tr>
	</tbody>
	</table
	></fieldset>
	<div class="formPanel-FlexHorizontal" style="position: relative;">
	<div class="formPanel-FlexVertical formElement-Grow-1" style="position: relative;">
<fieldset class="formElement-Grow-1">
	<legend>Shipper's certification</legend>
	<div style="position: relative;"><table><colgroup><col></colgroup>
		<tbody><tr><td colspan="2">
		<textarea class="gwt-TextArea" style="width: 427px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;"></textarea>
		</td>
	</tr>
	<tr>
		<td>
			<div class="gwt-Label">Signature</div>
		</td>
			<td style="vertical-align: top;">
			<input class="gwt-TextBox" style="width: 427px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
			</td>
		</tr>
	</tbody>
</table>
<fieldset class="formElement-Grow-1">
<legend>Carrier's execution</legend>
<div style="position: relative;">
<table><colgroup><col></colgroup>
	<tbody>
		<tr>
			<td colspan="2">
				<textarea class="gwt-TextArea" style="width: 427px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;"></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<div class="gwt-Label">Date</div>
			</td>
			<td style="vertical-align: top;">
				<div><input class="gwt-TextBox" style="width: 111px; text-transform: uppercase; font-family: Consolas, Inconsolata, monospace; font-size: 11pt;" type="text" autocomplete="nope">
				</div>
</fieldset>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</body>
</html>