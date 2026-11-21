<style type="text/css">
#btnNew,#btnEdit
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
#btnNew,#btnEdit:hover {
	background:linear-gradient(to bottom, #378de5 5%, #79bbff 100%);
	background-color:#378de5;
}
/* Grid Row Coloring */
    .BlueRow { background-color: #d1ecf1 !important; color: #0c5460; }
    .YellowRow { background-color: #fff3cd !important; color: #856404; }
    
    /* Legend Style */
    .legend-container {
        margin-top: 5px;
        padding: 10px;
        background: none;
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .legend-item {
        display: flex;
        align-items: center;
        font-size: 11px;
        font-weight: 600;
        color: #555;
    }
    .color-box {
        width: 16px;
        height: 16px;
        margin-right: 6px;
        border: 1px solid #aaa;
        border-radius: 3px;
    }
    .box-blue { background-color: #d1ecf1; }
    .box-yellow { background-color: #fff3cd; }
    .box-white { background-color: #fff; }
	.card {
  border:1px solid #ccc;
  border-radius:6px;
  padding:20px;
  background:#f9f9f9;
  width:890px;
  margin-bottom:20px;
}
.card-columns {
  display:flex;
  gap:20px;
}
.form-card {
  border:1px solid #ccc;
  border-radius:6px;
  padding:20px;
  background:#f9f9f9;
  width:890px;
  margin-bottom:5px;
}

.form-columns {
  display:flex;
  gap:20px;
}

.form-group {
  display:flex;
  align-items:center;
  margin-bottom:4px;
}

.form-group label {
  width:100px; /* konsisten lebar label */
  font-weight:600;
  font-size:12px;
}

.form-group input,
.form-group select {
  flex:1;
  padding:2px 8px;
  font-size:10px;
  height:20px; /* bikin dropdown/input lebih tinggi */
}

.form-group textarea {
  flex:1;              /* ikut lebar kolom */
  min-height:45px;     /* tinggi minimum */
  max-height:70px;    /* tinggi maksimum */
  resize:vertical;     /* user bisa tarik ke atas/bawah */
  padding:6px 8px;
  font-size:10px;
  line-height:1.4;
}
</style>
<div id="form-table-empat">
	<table id="table_empat"></table>
	<div id="pager-empat" style="font-size: 11px"></div>
</div>
<div style="float: right; margin-right: 25px;">
		<?php 
		$user_id = $this->session->userdata('user_id');
		$region_id = $this->session->userdata('region_id');
		if($region_id != 'Jakarta'){
		?>
			<?= form_submit(array('value'=>'NEW','id'=>'btnNew','disabled'=>'disabled','style'=>'width: 80px')) ?>
			<?= form_submit(array('value'=>'EDIT','id'=>'btnEdit','disabled'=>'disabled','style'=>'width: 80px')) ?>
		<?php
		}else{
		?>
		<?php
		if ($user_id == 113 || $user_id == 110 || $user_id == 522 ||$user_id == 120 ||$user_id == 169 ||
		$user_id == 166 || $user_id == 412 || $user_id == 58 || $user_id == 0 || $user_id == 94 || 
		$user_id == 364 || $user_id == 109 || $user_id ==106 || $user_id ==107 || $user_id ==542 ||
		$user_id ==546 || $user_id ==424 || $user_id ==53 || $user_id ==543 || $user_id ==631 ||
		$user_id ==69 || $user_id ==31 ||$user_id ==388 || $user_id ==405 || $user_id ==534 || $user_id ==379 || $user_id ==179 || $user_id ==578 || $user_id == 60 || $user_id==595 || $user_id==568 || $user_id==615 || $user_id==641 
		|| $user_id == 642 || $user_id == 647 || $user_id == 660) {
		?>
			<?= form_submit(array('value'=>'NEW','id'=>'btnNew','disabled'=>'disabled','style'=>'width: 80px')) ?>
			<?= form_submit(array('value'=>'EDIT','id'=>'btnEdit','disabled'=>'disabled','style'=>'width: 80px')) ?>
		<?php
			}else{
			form_submit(array('value'=>'EDIT','id'=>'btnEdit','disabled'=>'disabled','style'=>'width: 80px'));
			}
		}
		?>
			
		</div>
<div id="form-inv-to-shipper" class="main-inv-to-shipper" style="padding:20px;">

 <div class="form-card">
  <div class="form-columns">

    <!-- Kolom kiri -->
    <div style="flex:1;">
      <div class="form-group">
        <label for="NOMER_JOB">Job No</label>
        <input type="text" id="NOMER_JOB" disabled>
      </div>
      <div class="form-group">
        <label for="TANGGAL_JOB">Date</label>
        <input type="text" id="TANGGAL_JOB" disabled>
      </div>
      <div class="form-group">
        <label for="KURS">Kurs</label>
        <input type="text" id="KURS" disabled>
      </div>
      <div class="form-group">
        <label for="SALESMAN">Sales</label>
        <input type="text" id="SALESMAN" disabled>
      </div>
    </div>

    <!-- Kolom kanan -->
    <div style="flex:1;">
      <div class="form-group">
        <label for="KOSTUMER">Costumer</label>
        <?= form_dropdown('KOSTUMER',array(''=>''),'','id="KOSTUMER"'); ?>
      </div>
      <div class="form-group">
        <label for="KET_KOSTUMER">Nama</label>
        <input type="text" id="KET_KOSTUMER" disabled>
      </div>
	   <div class="form-group">
	  <label for="ALAMAT_KOSTUMER">Alamat</label>
      <textarea id="ALAMAT_KOSTUMER" disabled></textarea>
	  </div>
    </div>

  </div>

  <!-- Tabel invoice di bawah card -->
  <div style="margin-top:5px;">
    <table id="table_satu"></table>
    <div id="page-1"></div>
  </div>
</div>
				
	<table id="table_dua"></table>
	<div id="pager-2"></div>
	    <div>
        <table id="table_dua"></table>
        <div id="pager-2"></div>
        
        <div class="legend-container">
            
            <div class="legend-item">
                <div class="color-box box-blue"></div>
                Job Belum Dibuat Invoice
            </div>
            <div class="legend-item">
                <div class="color-box box-yellow"></div>
                Ada Item Income Belum Ditagih (Pending)
            </div>
            <div class="legend-item">
                <div class="color-box box-white"></div>
                Lengkap (All Invoiced)
            </div>
        </div>
       </div>
	</div>
</div>
<div id="form-invoice"></div>
<div id="out-invoice"></div>
<div id="out-proforma"></div>
<?php include "v_inv_to_shipper_script.php"; ?>
