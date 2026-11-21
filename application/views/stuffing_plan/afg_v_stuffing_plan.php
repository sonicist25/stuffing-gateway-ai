<?php if ($this->session->userdata('show_announcement')) : ?>
<div id="securityModal" class="system-modal-overlay">
    <div class="system-modal-content">
        <div class="modal-header-gcl">
            <h2>PEMBERITAHUAN KEAMANAN SISTEM</h2>
        </div>
        
        <div class="modal-body-content">
            <!-- INFO 1: PASSWORD EXPIRY -->
            <div class="warning-section rule-box">
                <div class="rule-badge" style="background: #00245E; color: #FFCA33;">KEBIJAKAN IT TERBARU</div>
                <h3>Pembaruan Kata Sandi (Password) Berkala</h3>
                <p>Dalam rangka menjaga keamanan data perusahaan serta mematuhi standar keamanan <strong>Authorized Economic Operator (AEO)</strong>, standar <strong>ISO</strong>, dan merujuk pada SOP Internal <strong>No: GCL/IT/SOP/01</strong>, kami menerapkan kebijakan kedaluwarsa kata sandi.</p>
                <div class="highlight-danger" style="border-left-color: #00245E; background: #f4f6f9;">
                    Masa berlaku kata sandi (password) sistem Gateway Container Line kini dibatasi maksimal <strong>3 (tiga) bulan</strong>. Sistem akan secara otomatis meminta Anda untuk melakukan pembaruan password secara berkala.
                </div>
            </div>

            <!-- INFO 2: REMINDER CC -->
            <div class="warning-section">
                <h3>Pengingat Tegas Manajemen: Pembatasan CC Email</h3>
                <p>Kami kembali mengingatkan seluruh karyawan untuk <strong>TIDAK MENGABAIKAN</strong> instruksi manajemen terkait pembatasan cc dan penerima email. Praktik <em>spamming</em> distribusi email sangat berisiko bagi keamanan data.</p>
                <div class="highlight-warning">
                    <strong>TERAPKAN "NEED TO KNOW":</strong> Dilarang keras men-CC atau menambah penerima email internal atau manajemen yang tidak memiliki kaitan langsung/tidak perlu mengambil tindakan pada email tersebut. <strong>HAPUS</strong> alamat yang tidak relevan sebelum Anda menekan <em>Reply All</em>.
                </div>
            </div>

            <!-- INFO 3: KEAMANAN UMUM -->
            <div class="warning-section">
                <h3 style="border-left-color: #10b981;">💡 Standar Keamanan & Kesadaran (Awareness)</h3>
                <ul class="tips-list">
                    <li>Hindari pengiriman satu email dengan banyak CC internal (misalnya men‑CC Accounting untuk draft BL).</li>
					<li>Gunakan CC hanya kepada PIC yang relevan, jangan asal men‑CC seluruh tim. Tanya ke atasan atau team jika blm pasti</li>
                </ul>
            </div>
        </div>

        <div class="modal-footer-action">
            <label class="checkbox-container">
                <input type="checkbox" id="agreeCheckbox">
                <span class="checkmark"></span>
                Saya telah membaca, memahami, dan akan mematuhi kebijakan keamanan sistem serta pembatasan CC email perusahaan.
            </label>
            <button id="dismissSecurityBtn" class="btn-disabled" disabled>Konfirmasi & Mengerti</button>
        </div>
    </div>
</div>

<style>
/* Penyesuaian CSS Tema Login (Open Sans, Blue #00245E, Yellow #FFCA33) */
.system-modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0, 36, 94, 0.85); 
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Open Sans', sans-serif;
}

.system-modal-content {
    background: #ffffff;
    border: 1px solid #00245E;
    border-radius: 4px;
    width: 90%;
    max-width: 650px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    color: #333;
}

.modal-header-gcl {
    background-color: #00245E;
    color: #ffffff;
    padding: 15px 20px;
    text-align: center;
    border-bottom: 4px solid #FFCA33;
}

.modal-header-gcl h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.modal-body-content {
    padding: 20px 25px;
}

.warning-section {
    margin-bottom: 20px;
}

.warning-section h3 {
    color: #00245E;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 8px;
    border-left: 4px solid #FFCA33;
    padding-left: 10px;
}

.warning-section p {
    font-size: 13px;
    line-height: 1.6;
    margin: 0 0 10px 0;
    color: #333;
}

.rule-box {
    background: #f4f6f9;
    border: 1px solid #c8d4e3;
    padding: 15px;
    border-radius: 4px;
}

.rule-badge {
    background: #FFCA33;
    color: #00245E;
    font-size: 11px;
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 3px;
    display: inline-block;
    margin-bottom: 10px;
}

.highlight-warning {
    background: #fffdf5;
    border-left: 4px solid #FFCA33;
    padding: 10px;
    color: #555;
    font-size: 12.5px;
    line-height: 1.5;
}

.highlight-danger {
    background: #fff5f5;
    border-left: 4px solid #FF0000;
    padding: 10px;
    color: #555;
    font-size: 12.5px;
    line-height: 1.5;
}

.tips-list {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: #333;
    line-height: 1.5;
}

.tips-list li {
    margin-bottom: 6px;
}

.modal-footer-action {
    background-color: #f4f4f4;
    padding: 15px 25px;
    border-top: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.checkbox-container {
    font-size: 13px;
    color: #00245E;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.checkbox-container input {
    margin-top: 2px;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* Tombol mengikuti gaya login Gateway Container Line */
#dismissSecurityBtn {
    font-family: 'Open Sans', sans-serif;
    width: 100%;
    max-width: 280px;
    text-transform: uppercase;
    padding: 10px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid transparent;
    transition: 0.3s all ease;
}

.btn-disabled {
    background-color: #cccccc;
    color: #666666;
    cursor: not-allowed;
}

.btn-active {
    background-color: #FFCA33;
    color: #ffffff;
    cursor: pointer;
}

.btn-active:hover {
    background-color: #0060a9;
    border-color: #0060a9;
}

.system-modal-content::-webkit-scrollbar { width: 8px; }
.system-modal-content::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
.system-modal-content::-webkit-scrollbar-thumb { background: #00245E; border-radius: 4px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('securityModal');
    const checkbox = document.getElementById('agreeCheckbox');
    const dismissBtn = document.getElementById('dismissSecurityBtn');
    
    let showAnnouncement = <?= $this->session->userdata('show_announcement') ? 'true' : 'false' ?>;

    if (showAnnouncement && modal) {
        modal.style.display = 'flex';
    }

    checkbox.addEventListener('change', function() {
        if (this.checked) {
            dismissBtn.disabled = false;
            dismissBtn.classList.remove('btn-disabled');
            dismissBtn.classList.add('btn-active');
        } else {
            dismissBtn.disabled = true;
            dismissBtn.classList.remove('btn-active');
            dismissBtn.classList.add('btn-disabled');
        }
    });

    dismissBtn.onclick = function () {
        if (!checkbox.checked) return;
        
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);

        fetch("<?= base_url('afg_c_stuffing_plan/dismiss_announcement') ?>", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
        }).catch(err => console.error("Gagal update session:", err));
    };
});
</script>
<?php endif; ?>


<input type="hidden" name="txtMode" id="txtMode" style="width: 50px">
<input type="hidden" name="spId" id="spId" readonly="readonly" style="width: 50px">
<input type="hidden" name="firstId" id="firstId" readonly="readonly" style="width: 50px">
<input type="hidden" name="lastId" id="lastId" readonly="readonly" style="width: 50px">
<input type="hidden" name="testing" id="testing" readonly="readonly" style="width: 300px">
<input type="hidden" id="scheduleId" value="">

<div class="fix clearfix"></div>

<div id="Form-Schedule"></div>

<div id="dialog_shipment_request">
	<center>
		<table id="tabel_shipment_request"></table>
		<div id="page-req"></div>
	</center>			
</div>

<div id="tabel_connect">
	<center><table id="tabel_connecting"></table></center>
	<div id="page-connect"></div>
</div>

<div id="Error-Message"><p id="content-error"></p></div>
<div id="FORM_DISPLAY_BL"></div>
<div id="agent-insert"></div>
<div id="PRINT-FORM-BL">
	<div id="loading-bl-print"></div>
</div>

<div id="Insert-Form" style="font-size: 11px; margin: 10px;"><p id="inputan"></p></div>
<div id="Insert-Schedule" style="font-size: 11px; margin: 10px;"></div>
<div id="Form-Stuffing" style="font-size: 11px; margin: 10px;"></div>
<div id="Form-Instruksi-Stuffing" style="font-size: 11px; margin: 10px;"></div>
<div id="form-print-stuffing"></div>
<div id="form-cetak-stuffing"><center><table id="tabel-cetak-stuffing"></table><div id="page-cetak-stuf"></div></center></div>
<div id="Form-Input-Detail"></div>
<div id="form-manifest"></div>
<div id="open-manifest"></div>
<div id="open-bc11"></div>
<div id="open-schedule"></div>
<div id="tambah-count"></div>
<div id="tambah-count-seal"></div>
<div id="print-option"></div>
<div id="pre_alert"></div>
<!-- PRE ALERT AGENT: separate from existing jobsheet pre_alert -->
<div id="pre_alert_agent"></div>

<!--
<input type="text" name="testing" id="testing" style="width: 150px">
!-->

<div id="Form-Atachement"></div>
<div id="Form-Trucking"></div> 
<div id="Req-Trucking"></div>
<div id="Form-Income"></div>
<div id="Form-Cost"></div>
<div id="Form-Input-GlbCost">
	<div id="Input-GlbCost"></div>
</div>
<div id="Form-delete-GlbCost">
	<div id="confirm"></div>
</div>
<div id="Form-Global-Cost-Edit"></div>
<div id="Form-Global-Cost"></div>
<div id="Form-Input-GlbIncome">
	<div id="Input-GlbIncome"></div>
</div>
<div id="Form-delete-GlbIncome">
	<div id="confirm"></div>
</div>
<div id="Form-Global-Income-Edit"></div>
<div id="Form-Global-Income"></div>
<div id="Form-Job-Sheet"></div>
<div id="Form-Print-Job"></div>
<div id="Form-Container-Size"></div>
<div id="Form-Container-Seal"></div>
<div id="Print-Rekap"></div>
<div id="msg"></div>
<div id="REQUEST"></div>
<div id="Form-Open-Booking-Depan"></div>
<div id="Form-BC-Depan"></div>
<div id="Bulk-Export"></div>

<style type="text/css">
/* 1. Mengatur tinggi kotak Select2 */
.select2-container .select2-selection--single {
    height: 26px !important; /* Ubah angka ini sesuai keinginan Anda */
    min-height: 26px !important;
}

/* 2. Menyelaraskan teks pilihan agar tetap di tengah secara vertikal */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px !important; /* Biasanya diatur sekitar 2px lebih kecil dari height utama */
    padding-top: 0px !important;
    padding-bottom: 0px !important;
}

/* 3. Menyesuaikan posisi ikon panah dropdown di sebelah kanan */
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 24px !important; /* Samakan dengan line-height teks */
}
#btnAddDetail {
    cursor: pointer !important;
}
#txtVessel, #txtVoyage, #txtCarrier, #txtMotherBL, #txtContSealNumber, #txtAirLine { text-transform: uppercase; }
.Blue { color: #33F; }
.Red { color: #f44336; }
.left_1 { float: left; width: 160px; margin-bottom: 5px; }
.left_2 { float: left; width: 10px; margin-bottom: 5px; }
.left_3 { float: left; width: 50px; margin-bottom: 5px; }

.ui-autocomplete {
  position: absolute;
  cursor: default;
  z-index: 9999999999 !important;
}

/* Modal Background */
.custom-modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-image: none;
  background-size: cover;
  background-position: center;
  backdrop-filter: blur(5px);
}

/* Modal Content */
.custom-modal-content {
  /* background: rgba(255, 255, 255, 0.9); */
  background-image: url('https://bestanimations.com/media/fireworks/1119870824colorful-fan-firework-gif.gif');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  border-left: 8px solid #007BFF;
  border-radius: 12px;
  margin: 5% auto;
  max-width: 650px;
  padding: 30px 20px;
  position: relative;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  font-family: 'Segoe UI', sans-serif;
  color: #333;
  transition: transform 0.3s ease-in-out;
}

.custom-close {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 28px;
  color: #888;
  cursor: pointer;
  transition: color 0.2s ease;
}

.custom-close:hover {
  color: #f44336;
}

.custom-modal h2 {
  color: #0056b3;
  margin-bottom: 15px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: bold;
}

.custom-modal h4 {
  color: #ffc107;
  margin-top: 20px;
  margin-bottom: 5px;
  text-align: center;
  font-size: 1.25rem;
}

.announcement-body {
  font-size: 1.1rem;
  line-height: 1.6;
  color: #444;
}

.announcement-body a {
  color: #007bff;
  text-decoration: none;
  font-weight: bold;
  transition: color 0.3s ease;
}

.announcement-body a:hover {
  color: #0056b3;
}

.modal-actions {
  text-align: center;
  margin-top: 30px;
}

.modal-actions button {
  background-color: #ffc107;
  color: #000;
  border: none;
  padding: 12px 20px;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  font-size: 1.1rem;
  transition: background-color 0.3s ease;
}

.modal-actions button:hover {
  background-color: #e0a800;
}

.modal-actions button:focus {
  outline: none;
}

/* Responsiveness */
@media (max-width: 768px) {
  .custom-modal-content {
    width: 90%;
  }

  .modal-actions button {
    width: 100%;
  }
}

/* Modal full screen rapi */
#modal_bulk_tracking.modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 5000 !important;
    width: 100% !important;
    height: 100% !important;
    display: none;
    background: rgba(0,0,0,0.5); /* backdrop */
}
#modal_bulk_tracking .modal-dialog {
    position: relative;
    margin: 30px auto;
    max-width: 98vw;
}

/* Table wrapper scrollable */
.preview-table-wrapper {
    max-height: 70vh;
    overflow-y: auto;
    margin-top: 10px;
    border: 1px solid #ddd;
}

/* Table styling */
.preview-table {
    width: 100%;
    border-collapse: collapse;
}

.preview-table th {
    background: #2c3e50;
    color: white;
    padding: 6px;
    position: sticky;
    top: 0;
    z-index: 3;
    font-size: 12px;
}

.preview-table td {
    padding: 4px 6px;
    font-size: 11px;
    border: 1px solid #ddd;
}

/* Warna data database */
.cell-db {
    background-color: #e7f4ff; /* biru lembut */
}

/* Warna data Excel */
.cell-excel {
    background-color: #fff6c9; /* kuning lembut */
}

/* Hover row */
.preview-table tr:hover td {
    background: #f0f8ff;
}


/* warna cell */
.cell-db {
    background-color: #d9edf7;
}
.cell-excel {
    background-color: #fff8c6;
}


/* Isi modal */
#modal_bulk_tracking .modal-content {
    background: #ffffff; /* putih solid */
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 5px 25px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease-out;
}

/* Header */
#modal_bulk_tracking .modal-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e5e5;
    background: #f7f7f7;
    border-radius: 8px 8px 0 0;
}

/* Title */
#modal_bulk_tracking .modal-title {
    font-size: 16px;
    font-weight: bold;
}

/* Button close */
#modal_bulk_tracking .close {
    background: none;
    border: none;
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
}

/* Body */
#modal_bulk_tracking .modal-body {
    padding: 18px;
}

/* Footer */
#modal_bulk_tracking .modal-footer {
    padding: 12px 16px;
    border-top: 1px solid #e5e5e5;
    text-align: right;
}

/* Animasi */
@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.preview-table th {
    position: sticky;
    top: 0;
    background: #333;
    color: #fff;
    z-index: 2;
}

/* data dari DB → biru muda */
.cell-db {
    background-color: #d9edf7;   /* light blue */
}

/* data dari Excel → kuning muda */
.cell-excel {
    background-color: #fff8c6;   /* light yellow */
}

/* ======================================================= */
/* 1. PANEL KIRI (MASTER TIMELINE)                         */
/* ======================================================= */
.gcl-left-timeline-panel {
    position: fixed;
    top: 25px;
    bottom: 20px;
    width: 250px;
    z-index: 90;
    overflow-y: auto;
    overflow-x: hidden;
    box-sizing: border-box;
    color: #fff;
    font-family: 'Open Sans', Arial, sans-serif;
    background-color: rgba(0, 36, 94, 0.95); 
    padding: 15px;
    border-radius: 0 8px 8px 0;
    box-shadow: 2px 0 15px rgba(0,0,0,0.5);
    transition: left 0.3s ease-in-out;
    left: 0; /* Posisi Buka */
}
.gcl-left-timeline-panel.collapsed {
    left: -250px; /* Posisi Tutup (Sembunyi ke kiri) */
}

/* Tombol Panah Kiri (Selalu Muncul di Tepi Layar) */
.timeline-handle-left {
    position: fixed;
    top: 45%;
    left: 250px; /* Mengikuti lebar panel saat terbuka */
    width: 24px;
    height: 70px;
    background: #FFCA33;
    color: #00245E;
    line-height: 70px;
    text-align: center;
    font-weight: bold;
    cursor: pointer;
    z-index: 95;
    border-radius: 0 6px 6px 0;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
    transition: left 0.3s ease-in-out;
}
.timeline-handle-left.collapsed {
    left: 0; /* Menempel di ujung kiri layar saat panel ditutup */
}
.timeline-handle-left::after { content: '◀'; }
.timeline-handle-left.collapsed::after { content: '▶'; }


/* ======================================================= */
/* 2. PANEL KANAN (HOUSE TIMELINE)                         */
/* ======================================================= */
.gcl-right-timeline-panel {
    position: fixed;
    top: 25px;
    bottom: 20px;
    width: 250px;
    z-index: 90;
    overflow-y: auto;
    overflow-x: hidden;
    box-sizing: border-box;
    color: #fff;
    font-family: 'Open Sans', Arial, sans-serif;
    background-color: rgba(0, 36, 94, 0.95);
    padding: 15px;
    border-radius: 8px 0 0 8px;
    box-shadow: -2px 0 15px rgba(0,0,0,0.5);
    transition: right 0.3s ease-in-out;
    right: 0; /* Posisi Buka */
}
.gcl-right-timeline-panel.collapsed {
    right: -250px; /* Posisi Tutup (Sembunyi ke kanan) */
}

/* Tombol Panah Kanan (Selalu Muncul di Tepi Layar) */
.timeline-handle-right {
    position: fixed;
    top: 45%;
    right: 250px; /* Mengikuti lebar panel saat terbuka */
    width: 24px;
    height: 70px;
    background: #FFCA33;
    color: #00245E;
    line-height: 70px;
    text-align: center;
    font-weight: bold;
    cursor: pointer;
    z-index: 95;
    border-radius: 6px 0 0 6px;
    box-shadow: -2px 0 5px rgba(0,0,0,0.3);
    transition: right 0.3s ease-in-out;
}
.timeline-handle-right.collapsed {
    right: 0; /* Menempel di ujung kanan layar saat panel ditutup */
}
.timeline-handle-right::after { content: '▶'; }
.timeline-handle-right.collapsed::after { content: '◀'; }


/* Mencegah teks panjang melebar */
#timeline-master-content, #timeline-house-content {
    word-wrap: break-word;
    overflow-wrap: break-word;
}
/* EDITOR PEB / NPE / PPB (tombol PEB) */
#modal_peb.modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 4000 !important;     /* di bawah SweetAlert (dinaikkan di bawah) supaya pratinjau/konfirmasi widget tampil di atasnya */
    width: 100% !important;
    height: 100% !important;
    display: none;
    background: rgba(0,0,0,0.5);
    overflow-y: auto;
}
.swal2-container { z-index: 20000 !important; }
#modal_peb .modal-dialog { position: relative; margin: 30px auto; width: 1040px; max-width: 96vw; }
#modal_peb .modal-content { background: #fff; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.3); }
#modal_peb .modal-header { padding: 10px 16px; border-bottom: 1px solid #e5e5e5; background: #f7f7f7; border-radius: 8px 8px 0 0; overflow: hidden; }
#modal_peb .modal-title { font-size: 15px; font-weight: bold; margin: 0; float: left; }
#modal_peb .close { float: right; background: none; border: none; font-size: 24px; font-weight: bold; line-height: 1; cursor: pointer; }
#modal_peb .modal-body { padding: 14px 16px; max-height: 80vh; overflow-y: auto; font-size: 12px; }
#modal_peb .modal-footer { padding: 10px 16px; border-top: 1px solid #e5e5e5; text-align: right; }
#modal_peb .pwsp-head { display: flex; flex-wrap: wrap; gap: 4px 18px; margin: 0 0 10px 0; padding: 6px 10px; background: #eef3f8; border: 1px solid #c9d6e4; border-radius: 5px; font-size: 12px; color: #1f3a57; }
#modal_peb .pwsp-head b { color: #1f4e79; margin-right: 4px; }
#modal_peb .pwsp-msg { padding: 14px; color: #7a4b00; background: #fff4e5; border: 1px solid #f0c27b; border-radius: 5px; line-height: 1.5; }

/* TALLYSHEET + PEB: aktif hanya saat row detail dipilih */
#btnTallySheet.detail-pdf-action,
#btnPeb.detail-pdf-action,
#bulkexport.detail-row-action {
    opacity: .45;
    cursor: not-allowed;
}
#btnTallySheet.detail-pdf-action.detail-pdf-active,
#btnPeb.detail-pdf-action.detail-pdf-active,
#bulkexport.detail-row-action.detail-row-active {
    opacity: 1;
    cursor: pointer;
}

</style>

<div class="fix clearfix"></div>

<div style="float: left; width: 870px; height: 30px; font-size: 11px; margin-left:50px; margin-top: 20px; margin-right:30px">	 	 
	<?php
$user_id = $this->session->userdata('user_id');
$allowed_ids = [
    126,
    140,
    146,
    289,
    323,
    359,
    466,
    575,
    663,
    576,
    602,
    622,
    69
];
?>

<?php if (in_array($user_id, $allowed_ids)) : ?>
    <input type="button" class="custom" id="btnNew" value="NEW" onclick="add_new_job()" >
<?php else : ?>
    <input type="button" class="custom" id="btnNew" value="NEW" onclick="new_stuff()" >
<?php endif; ?>
	<input type="button" class="custom" id="btnEdit" value="EDIT" onclick="handleEditButton()">
	<input type="button" class="custom" id="btnDelete" value="DELETE" onclick="del_stuff()">
    <input type="button" class="custom" id="btnBrowse" value="BROWSE" onclick="browse_stuff()">
    <input type="button" class="custom" id="btnPrev" value="PREV" onclick="prev_stuff()">
    <input type="button" class="custom" id="btnNext" value="NEXT" onclick="next_stuff()">
</div>
<div style="float: right;>
</div>

<div style="clear: both"></div>

<style>
/* Kolom kiri memuat form dan panel dalam satu float; panel tidak turun
   mengikuti tinggi tombol kanan. */
#gcl-stuffing-left-column { float:left; max-width:100%; }
#gcl-stuffing-left-column::after { content:""; display:block; clear:both; }
/* Progress bar tanpa kotak: langsung mengisi ruang bawah main-work.
   Proporsi warna berdasarkan JUMLAH shipment; label juga memuat total CBM. */
#gcl-cargo-mix {
    display:none; float:left; clear:left; box-sizing:border-box;
    width:750px; max-width:100%; margin:7px 0 8px; padding:0;
    border:0; border-radius:0; background:transparent; box-shadow:none;
    font:11px Arial,sans-serif; color:#213653;
}
#gcl-cargo-mix * { box-sizing:border-box; }
.gcl-mix-track {
    position:relative; display:flex; overflow:hidden; width:100%; height:26px;
    margin:0; border-radius:3px; background:#e4e9f0;
}
.gcl-mix-bar-agent { background:#1760aa; height:100%; width:0%; transition:width .2s ease; }
.gcl-mix-bar-gateway { background:#ffca33; height:100%; width:0%; transition:width .2s ease; }
.gcl-mix-labels {
    position:absolute; inset:0; z-index:1; display:grid;
    grid-template-columns:1fr 1fr; align-items:center; pointer-events:none;
}
.gcl-mix-label {
    display:block; min-width:0; overflow:hidden; text-overflow:ellipsis;
    white-space:nowrap; padding:0 9px; font-size:11px; line-height:26px;
    font-weight:600; color:#1a324c; text-shadow:0 1px 1px rgba(255,255,255,.65); pointer-events:auto;
}
.gcl-mix-label-agent { text-align:left; }
.gcl-mix-label-gateway { text-align:right; }
.gcl-mix-label.is-on-blue { color:#fff; text-shadow:0 1px 2px rgba(0,20,55,.9); }
@media(max-width:740px) {
    .gcl-mix-track {height:44px;}
    .gcl-mix-labels {grid-template-columns:1fr; grid-template-rows:1fr 1fr;}
    .gcl-mix-label {line-height:22px; text-align:left;}
}
/* -100 saja: WG/CTN ditampilkan sebagai status, nilai asli tidak diubah.
   jqGrid.getRowData() tetap dapat membaca angka WG/CTN untuk prosedur lama. */
#tabel_sp_detail.gcl-cargo-receive-mode tr.jqgrow td[aria-describedby="tabel_sp_detail_wg_ctn"] {
    font-size:0 !important; color:transparent !important;
}
#tabel_sp_detail.gcl-cargo-receive-mode tr.jqgrow td[aria-describedby="tabel_sp_detail_wg_ctn"]::before {
    content:"—"; display:inline-block; font-size:10px; line-height:17px;
    padding:0 5px; border-radius:3px; color:#667085; background:#edf1f5;
    white-space:nowrap; vertical-align:middle;
}
#tabel_sp_detail.gcl-cargo-receive-mode tr.gcl-cargo-wh-arrived td[aria-describedby="tabel_sp_detail_wg_ctn"]::before {
    content:"✓ INBOUND"; color:#16663d; background:#e1f5e9; border:1px solid #a9dfbf;
    font-weight:bold;
}
#tabel_sp_detail.gcl-cargo-receive-mode tr.gcl-cargo-wh-pending td[aria-describedby="tabel_sp_detail_wg_ctn"]::before {
    content:"NOT RECEIVED"; color:#965a00; background:#fff5dd; border:1px solid #f0d69e;
}
/* Link Tally Sheet di sebelah badge INBOUND; tanpa mengubah isi data WG/CTN. */
#tabel_sp_detail.gcl-cargo-receive-mode .gcl-wh-tally-link {
    display:inline-block; width:19px; height:18px; margin-left:4px;
    vertical-align:middle; box-sizing:border-box; border:1px solid #b2cce5;
    border-radius:3px; background:#edf5ff; color:#155a99 !important;
    text-align:center; text-decoration:none !important; cursor:pointer;
}
#tabel_sp_detail.gcl-cargo-receive-mode .gcl-wh-tally-link::before {
    content:"\2197"; font-family:Arial,sans-serif; font-size:14px;
    font-weight:bold; line-height:16px;
}
#tabel_sp_detail.gcl-cargo-receive-mode .gcl-wh-tally-link:hover,
#tabel_sp_detail.gcl-cargo-receive-mode .gcl-wh-tally-link:focus {
    background:#155a99; border-color:#155a99; color:#fff !important;
    outline:1px solid #155a99;
}
</style>

<div id="gcl-stuffing-left-column">
<div id="form_input" class="main-work">
	<div style="clear: both; height: 10px"></div>
		<div style="float:left; width: 365px;">
			
        	<div style="clear: both; height: 5px"></div>
			<div class="left_1">Job Number</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtRefNo2 = array(
					'id'=>'txtRefno2',
					'name'=>'txtRefno2',
					'value'=> set_value('kode_ref', isset($default['kode_ref']) ? $default['kode_ref'] : ''),
					'style'    => 'width:170px; background:transparent;',
					'readonly'=>'readonly'
				);
				echo form_input($txtRefNo2);
			?>
			</div>
			<div style="clear: both"></div>
			<div class="left_1">Tanggal Job</div>
			<div class="left_2">:</div>
			<div class="left_3">
				<?php
				$combo_month = array(
					'id'=>'combo_month',
					'name'=>'combo_month',
					'disabled'=>'disabled',
					'style'=>"width: 170px",
				);
				echo form_input($combo_month);
			?>
			</div>
			<div style="clear: both"></div>
			<div class="left_1">Type of transaction</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php
		
				echo form_dropdown('dropdownTypeOfTransaction',array(''=>''),'','class="dropdown-stuffingplan" id="dropdownTypeOfTransaction" disabled="disabled" style="width: 177px" onchange="get_ref_no()"');
				
			?>
			</div>
        	<div style="clear: both"></div>
			<div class="left_1" id="warehouse">Warehouse</div>
			<div class="left_2">:</div>
			<div class="left_3" id="cbwarehouse">
			<?php
				
				echo form_dropdown('dropdownWarehouse',array(''=>''),'','class="dropdown-stuffingplan" id="dropdownWarehouse" disabled="disabled" style="width: 177px;"');
			?>
        	</div>
        	
        	<div style="clear: both"></div>
			<div class="left_1" id ="carriers">Shipping Line</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php
				
				echo form_dropdown('txtCarrier',array(''=>''),'','class="dropdown-stuffingplan" id="txtCarrier" disabled="disabled" style="width: 177px;" onchange="update_shipp_co()"');
			?>
        	</div>
			<div style="clear: both"></div>
			<div class="left_1" id ="scac">SCAC</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php
				echo form_dropdown('txtPelayaran',array(''=>''),'','class="dropdown-stuffingplan" id="txtPelayaran" disabled="disabled" style="width: 177px;" onchange="update_pelayaran()"');
			?>
        	</div>
        	
        	<div style="clear: both"></div>
			<div class="left_1" id = "mbl">Shipping Line MBL</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtMotherBL = array(
					'id'=>'txtMotherBL',
					'name'=>'txtMotherBL',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtMotherBL);
			?>
        	</div>
        	
        	<div style="clear: both"></div>
			<div style="float: left; width: 80px; margin-left: 0px; margin-bottom: 5px;">
			<?php
				echo form_radio(array('id'=>'shipment-1', 'name'=>'shipment_type', 'class'=>'shipment_type', 'disabled'=>'disabled', 'value'=>'Consol')). ' Consol';
			?>
			</div>
			<div style="float: left; width: 80px; margin-bottom: 5px;">
			<?php
				echo form_radio(array('id'=>'shipment-2', 'name'=>'shipment_type', 'class'=>'shipment_type', 'disabled'=>'disabled', 'value'=>'Coload')). ' Coload';
			?>
			</div>
			<div style="float: left; width: 60px; margin-bottom: 5px;">
			<?php
				echo form_radio(array('id'=>'shipment-3', 'name'=>'shipment_type', 'class'=>'shipment_type', 'disabled'=>'disabled', 'value'=>'FCL')). ' FCL';
			?>
			</div>
			<div style="float: left; width:80px; margin-bottom: 5px;">
			<?php
				echo form_radio(array('id'=>'shipment-4', 'name'=>'shipment_type', 'class'=>'shipment_type', 'disabled'=>'disabled', 'value'=>'Other')). 'Other';
			?>
			</div>
			<div style="float: left; width:60px; margin-bottom: 5px;">
			<?php
				echo form_radio(array('id'=>'shipment-5', 'name'=>'shipment_type', 'class'=>'shipment_type', 'disabled'=>'disabled', 'value'=>'Air')). 'AIR';
			?>
			</div>
	
			<div style="clear: both"></div>
			<div class="left_1" id="vessels">Vessel</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtVessel = array(
					'id'=>'txtVessel',
					'name'=>'txtVessel',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtVessel);
			?>
        	</div>
        	
        	<div style="clear: both"></div>
			<div class="left_1" id="voyage">Voyage</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtVoyage = array(
					'id'=>'txtVoyage',
					'name'=>'txtVoyage',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtVoyage);
			?>
        	</div>
        	
        	<div id="check-export"></div>
			<div style="clear: both"></div>
      	<div class="left_1">Cont / Seal Number</div>
			<div class="left_2">:</div>
			<div class="left_3" style="width: 170px;">
			<?php 
				$txtContSealNumber = array(
					'id'=>'txtContSealNumber',
					'name'=>'txtContSealNumber',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtContSealNumber);
			?>
			</div>
		<div style= "float: right;">
      	<?php 
      	$tambahCount = array(
					'id'=>'tambahCount',
					'name'=>'tambahCount',
					'disabled'=>'disabled',
					'style'=>'width: 25px;height:20px',
					'value'=>'+',
					'type'=>'button',
					'onclick'=>'tambahCount()',
					'title'=>'Insert Data Countainer',
				);
				echo form_input($tambahCount);
			?>
      	</div>
        	
        	<div style="clear: both"></div>
      	<div class="left_1">Type Of Container</div>
			<div class="left_2">:</div>
			<div class="left_3" style="width: 40px;">
			<?php 
				$txtTOC1 = array(
					'id'=>'txtTOC1',
					'name'=>'txtTOC1',
					'disabled'=>'disabled',
					'style'=>'width: 40px; margin-right: 10px; text-align: center;',
				);
				echo form_input($txtTOC1);
			?>
			</div>
			<div class="left_3" style="margin-left: 10px;">
			<?php
				
				$portloading = $this->afg_m_ref->getListContainer()->result();
				$data['option_container'][' ']=' ';
					foreach ($portloading as $row){	
						$data['option_container'][$row->typecontainer_name]=$row->typecontainer_name;
					}
					echo form_dropdown('txtTOC2',$data['option_container'],'','id="txtTOC2" disabled="disabled" style="width: 130px;"');
				
				
				//echo form_input(array('id'=>'txtTOC2','class'=>'datalist','disabled'=>'disabled','style'=>'width: 110px','list'=>'list_txtTOC2')).'<datalist id="list_txtTOC2"></datalist>';
		
				
				//echo form_dropdown('txtTOC2',array(''=>''),'','class="dropdown-stuffingplan" id="txtTOC2" disabled="disabled" style="width: 118px;"');
			?>
        	</div>
        	<div style= "float: right;">
			<?php 
				$tambahCount = array(
					'id'=>'tambahCountSeal',
					'name'=>'tambahCountSeal',
					'disabled'=>'disabled',
					'style'=>'width:25px;height:20px',
					'value'=>'+',
					'type'=>'button',
					'onclick'=>'tambahCountSeal()',
					'title'=>'Insert Data Countainer / Seal',
				);
				echo form_input($tambahCount);
			?>
      	</div>
		</div>
		<div style="float: left;">  
			
			<div class="left_1" id = "stuffing_date">Stuffing Date</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php
				$txtStuffingDate = array(
					'id'=>'txtStuffingDate',
					'name'=>'txtStuffingDate',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtStuffingDate);
			?>
        </div>
		<div style="clear: both"></div> 
      	<div class="left_1">Closing Date & Time</div>
			<div class="left_2">:</div>
			<div class="left_3" style="width: 80px">
			<?php 
				$txtClosingDate = array(
					'id'=>'txtClosingDate',
					'name'=>'txtClosingDate',
					'disabled'=>'disabled',
					'style'=>'width: 80px',
				);
				echo form_input($txtClosingDate);
			?>
        	</div>
        	
        	<div class="left_3" style="margin-left: 10px;">
			<?php 
				$txtClosingTime = array(
					'id'=>'txtClosingTime',
					'name'=>'txtClosingTime',
					'disabled'=>'disabled',
					'style'=>'width: 80px',
					'onclick'=>'format_waktu(this)',
				);
				echo form_input($txtClosingTime);
			?>
        	</div>
      
      	<div style="clear: both"></div>
      	<div class="left_1">ETD</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtETDJKT = array(
					'id'=>'txtETDJKT',
					'name'=>'txtETDJKT',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtETDJKT);
			?>
        	</div>
			<div style="clear: both"></div>
			<div class="left_1">Actual Time Departure</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php
				$txtATD = array(
					'id'=>'txtATD',
					'name'=>'txtATD',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtATD);
			?>
        	</div>
        <div style="clear: both"></div>
			 <div class="left_1">ETA</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtETA = array(
					'id'=>'txtETA',
					'name'=>'txtETA',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtETA);
			?>
        	</div>
			 <div style="clear: both"></div>
			 <div class="left_1">Actual Time Arrival</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
				$txtATA = array(
					'id'=>'txtATA',
					'name'=>'txtATA',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtATA);
			?>
        	</div>
			<div style="clear: both"></div>
      	<div class="left_1" id="pol">Port of Loading</div>
			<div class="left_2">:</div>
			<div style="float:left; width: 140px; margin-bottom: 5px;">
			<?php 
				echo  form_dropdown('dropdownPortloading',array(''=>''),'','class="dropdown-stuffingplan" id="dropdownPortLoading" disabled="disabled" style="width: 178px;"');
			?>
        	</div>
        	
        	
      	<div style="clear: both"></div>
      	<div class="left_1" id ="trans">Transhipment Port</div>
			<div class="left_2">:</div>
			<div style="float:left; width: 140px; margin-bottom: 5px;">
			<?php	
				echo  form_dropdown('dropdownTranshipmentPort',array(''=>''),'','class="dropdown-stuffingplan" id="dropdownTranshipmentPort" disabled="disabled" style="width: 178px;"');
			?>
        	</div>
      	
      	<div style="clear: both"></div>
      	<div class="left_1" id = "pod">Destination</div>
			<div class="left_2">:</div>
			<div style="float:left; width: 140px; margin-bottom: 5px;">
			<?php
				echo form_dropdown('dropdownCountries',array(''=>''),'','class="dropdown-stuffingplan" id="dropdownCountries" disabled="disabled" style="width: 178px;"');
			?>
        	</div>
      	<div style="clear: both"></div>
      	<div class="left_1">Agent# (Penagihan)</div>
		<div class="left_2">:</div>
		<div style="float:left; width: 140px; margin-bottom: 5px;" id="opt-agent-penagihan">
			<select name="dropdownAgent" id="dropdownAgent" style="width: 178px;" disabled="disabled">
				<option value=""></option> 
			</select>
		</div>
		<div style="clear: both"></div>
      	<div class="left_1">Kurs Pajak/Kurs Selling</div>
			<div class="left_2">:</div>
			<div class="left_3" style="width: 80px;">
			<?php 
				$txtKurs = array(
					'id'=>'txtKurs',
					'name'=>'txtKurs',
					'disabled'=>'disabled',
					'style'=>'width: 80px',
				);
				echo form_input($txtKurs);
			?>
			</div>
			
			<div class="left_3" style="margin-left: 10px;">
			<?php
				$txtSelling = array(
					'id'=>'txtSelling',
					'name'=>'txtSelling',
					'disabled'=>'disabled',
					'style'=>'width: 80px',
				);
				echo form_input($txtSelling);
			?>
        	</div>
			<div style="clear: both"></div>
			<div class="left_1">Status Shipment</div>
			<div class="left_2">:</div>
			<div style="float:left; width: 140px; margin-bottom: 5px;">
			<?php 
				$txtStatus = array(
					'id'=>'txtStatus',
					'name'=>'txtStatus',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtStatus);
			?>
        	</div>
      	
      	<!-- 
      	<div style="clear: both"></div>
      	<div class="left_1">No. AJU</div>
			<div class="left_2">:</div>
			<div class="left_3">
			<?php 
			/*
				$txtAirLine = array(
					'id'=>'txtAirLine',
					'name'=>'txtAirLine',
					'disabled'=>'disabled',
					'style'=>'width: 170px',
				);
				echo form_input($txtAirLine);
			*/
				echo form_input(array('id'=>'txtAirLine','class'=>'datalist','disabled'=>'disabled','style'=>'width: 170px','list'=>'list_txtAirLine')).'<datalist id="list_txtAirLine"></datalist>';
				
			?>
        	</div>
			-->
      </div>   
</div>
<section id="gcl-cargo-mix" aria-label="Komposisi cargo, jumlah shipment dan kubikasi">
    <div class="gcl-mix-track" role="group" aria-label="Komposisi cargo berdasarkan jumlah shipment">
        <div id="gcl-mix-bar-agent" class="gcl-mix-bar-agent" aria-hidden="true"></div>
        <div id="gcl-mix-bar-gateway" class="gcl-mix-bar-gateway" aria-hidden="true"></div>
        <div class="gcl-mix-labels">
            <span id="gcl-mix-agent" class="gcl-mix-label gcl-mix-label-agent">Agent: -</span>
            <span id="gcl-mix-gateway" class="gcl-mix-label gcl-mix-label-gateway">Gateway: -</span>
        </div>
    </div>
</section>
</div><!-- /#gcl-stuffing-left-column -->

<div id="bill-of-ladding" class="bill-of-ladding">
	<div id="btnBL-1" class="btn-bill-of-lading">BILL OF LADING</div>
	<?php if ($this->access_control->can_feature('stuffing_income')): ?>
	<div id="btnBL-4" class="half-space" >Income</div>
	<?php endif; ?>
	<?php if ($this->access_control->can_feature('stuffing_cost')): ?>
	<div id="btnBL-5" class="half-space" >Cost</div>
	<?php endif; ?>
	<?php if ($this->access_control->can_feature('stuffing_global_income')): ?>
	<div id="btnGI" class="half-space" >Gbl Income</div>
	<?php endif; ?>	
	<?php if ($this->access_control->can_feature('stuffing_global_cost')): ?>
	<div id="btnBL-6" class="half-space" >Gbl Cost</div>
	<?php endif; ?>	
	<div class="clearfix"></div>
	<?php if ($this->access_control->can_feature('stuffing_job_sheet')): ?>
	<div id="btnBL-7">Job Sheet</div>
	<?php endif; ?>	
	<div id="btnBL-15">Tracking Table</div>
	<?php if ($this->access_control->can_feature('list_si')): ?>
	<!--<div id="tombol-1">LIST SI</div>
	<?php endif; ?>
	<div id="btnBL-14" class="half-space">Req Truck</div>-->
	<div style="clear: both; height: 1px;"></div>	
	<!--<div id="btnBL-13" onclick="show_schedule()" class="half-space" >SCHEDULE</div>-->
	<?php if ($this->access_control->can_feature('stuffing_upload_tracking')): ?>
	<div id="bulkexport" class="detail-row-action" title="Pilih row detail terlebih dahulu">Upload Tracking</div>
	<?php endif; ?>
	<div style="clear: both; height: 1px;"></div>
	<?php 
	if ($this->access_control->can_feature('stuffing_rekap') 
			&& $this->session->userdata('level_id') != 2): ?>
			<div id="btnBL-17">Print Rekap</div>
	<?php endif; ?> 	
	<div style="clear: both; height: 1px;"></div>			
	<!-- Tambahkan di dalam blok <div id="bill-of-ladding"> -->
	<?php if ($this->access_control->can_feature('stuffing_manifest')): ?>
	<div id="btnManifest"  class="half-space detail-pdf-action" onclick="show_manifest()">MANIFEST</div>
	<!-- TOMBOL BC 1.1: membuka modal berisi 2 pilihan (AWB & Respon Penerimaan) -->
	<div id="btnUploadManifestBC"  class="half-space detail-pdf-action" onclick="show_bc11_modal()">BC 1.1</div>
	<?php endif; ?>

	<div style="clear: both; height: 1px;"></div>
	<div id="btnTallySheet" class="half-space detail-pdf-action" title="Pilih row detail terlebih dahulu">TALLYSHEET</div>
	<div id="btnPeb" class="half-space detail-pdf-action" title="Pilih row detail terlebih dahulu">PEB</div>
	<div style="clear: both; height: 1px;"></div>
	<?php if ($this->access_control->can_feature('all_job_sheet')&& $this->session->userdata('level_id') != 2): ?>
	<div onclick="show_pre_alert()" id="btnSI">Print all jobsheet</div>
	<?php endif; ?>	
	<div style="clear: both; height: 1px;"></div>
	<?php if ($this->access_control->can_feature('stuffing_plan')): ?>	
	<div onclick="stuffing_plan()" id="btnStuffingPlan">STUFFING PLAN</div>
	<?php endif; ?>
	<div style="clear: both; height: 1px;"></div>
	<?php if ($this->access_control->can_feature('stuffing_plan')): ?>
	<div onclick="open_stuffing_photos()" id="btnStuffingPhotos" title="Kosongkan pilihan row detail untuk buka folder container, atau centang 1 row customer untuk folder spesifik">📷 FOTO STUFFING</div>
	<?php endif; ?>
	<div style="clear: both; height: 1px;"></div>
	<?php if ($this->access_control->can_feature('stuffing_pre_alert_agent') && $this->session->userdata('level_id') != 2): ?>
	<div id="btnPreAlertAgent">PRE ALERT AGENT</div>
	<?php endif; ?>
	<div style="clear: both; height: 8px;"></div>
	<?php if ($this->access_control->can_feature('stuffing_add_detail')): ?>	
	<div id="btnAddDetail">Add New Detail</div>
	<?php endif; ?>
	<div style="clear: both; height: 1px;"></div>
	<?php if ($this->access_control->can_feature('stuffing_delete_detail')): ?>	
	<div id="btnDelDetail">Delete Detail</div>
	<?php endif; ?>
	<div style="clear: both; height: 1px;"></div>
	
</div>
<div class="fix clearfix"></div>
<!-- Area kerja utama -->
<div id="main-work">
    <div id="export" data-can-tally-sheet="<?= $this->access_control->can_feature('stuffing_upload_tracking') ? '1' : '0' ?>">
        <center>
            <table id="tabel_sp_detail"></table>
            <div id="pagination_sp"></div>
        </center>
    </div>

    <div id="import">
        <center>
            <table id="tabel_sp_detail_export"></table>
            <div id="pagination_sp_export"></div>
        </center>
    </div>

</div>
<div id="empty-message" style="display:none; color: red">Please select from option</div>
<div id="FORM_NEW_JOB"></div>
<!-- Modal Bulk Tracking -->
<div class="modal fade" id="modal_bulk_tracking" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Bulk Upload Tracking Export</h5>
        <button type="button" class="close" onclick="close_bulk_tracking()" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <form id="form_bulk_tracking" enctype="multipart/form-data">
        <div class="modal-body" style="font-size:12px;">
          
          <div id="bulk_upload_msg"></div>
          
          <!-- Hidden untuk kirim info tambahan -->
          <input type="hidden" name="rowid" id="bulk_rowid">
          <input type="hidden" name="kode_ref" id="bulk_kode_ref">
          
          <div class="form-group">
            <label for="bulk_format">Pilih Format Template</label>
            <select name="format_type" id="bulk_format" class="form-control input-sm">
              <option value="HK">Template 1 - TRANSSHIPMENT</option>
              <option value="JOB">Template 2 - DIRECT</option>
            </select>
          </div>

          <div class="form-group">
            <label for="bulk_file">File Excel (.xlsx / .xls)</label>
            <input type="file" name="file_excel" id="bulk_file" class="form-control input-sm" accept=".xlsx,.xls" required>
            <small>Gunakan salah satu dari dua template Excel yang sudah disediakan.</small>
          </div>
          
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-sm" onclick="close_bulk_tracking()">Close</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btn_bulk_submit">Upload</button>
        </div>
      </form>
      
    </div>
  </div>
</div>
<!-- Modal Editor PEB / NPE / PPB (tombol PEB di panel detail) -->
<div class="modal fade" id="modal_peb" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal_peb_title">PEB / NPE / PPB</h5>
        <button type="button" class="close" onclick="close_peb_editor()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body" id="modal_peb_body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-sm" onclick="close_peb_editor()">Tutup</button>
      </div>
    </div>
  </div>
</div>
<!-- ========================================================= -->
<!-- 1. PANEL KIRI: MASTER TIMELINE & TOMBOLNYA -->
<!-- ========================================================= -->
<div id="gcl-left-timeline" class="gcl-left-timeline-panel" style="display: none;">
    <h3 style="margin: 0 0 10px 0; font-size: 13px; color: #FFCA33; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 5px;">MASTER TIMELINE</h3>
    <span id="tl-kode-ref" style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 10px;">Loading...</span>
    
    <div id="timeline-master-content" style="font-size: 11px; border-left: 2px solid rgba(255,255,255,0.3); margin-left: 5px; padding-left: 15px;">
        <!-- Data dinamis -->
    </div>
</div>
<!-- Tombol Panah Kiri (Berdiri Sendiri) -->
<div id="handle-left" class="timeline-handle-left" onclick="toggleTimeline('left')" title="Show/Hide Timeline" style="display: none;"></div>


<!-- ========================================================= -->
<!-- 2. PANEL KANAN: HOUSE TIMELINE & TOMBOLNYA -->
<!-- ========================================================= -->
<div id="gcl-right-timeline" class="gcl-right-timeline-panel" style="display: none;">
    <h3 style="margin: 0 0 10px 0; font-size: 13px; color: #4CAF50; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 5px; text-align: right;">HOUSE TIMELINE</h3>
    <span id="tl-hbl-ref" style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 10px; text-align: right;">Loading...</span>
    
    <div id="timeline-house-content" style="font-size: 11px; border-right: 2px solid rgba(255,255,255,0.3); margin-right: 5px; padding-right: 15px; text-align: right;">
        <!-- Data dinamis -->
    </div>
</div>
<!-- Tombol Panah Kanan (Berdiri Sendiri) -->
<div id="handle-right" class="timeline-handle-right" onclick="toggleTimeline('right')" title="Show/Hide Timeline" style="display: none;"></div>

<script type="text/javascript" >
 var base_url = "<?php echo base_url(); ?>";
// submit form: upload + preview
$(document).on('submit', '#form_bulk_tracking', function(e){
    e.preventDefault();

    var formData = new FormData(this);
    $('#btn_bulk_submit').prop('disabled', true).text('Processing...');
    $('#bulk_upload_msg').html('');

    $.ajax({
        url: window.location.origin+'/tracking_export/preview_import',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            $('#btn_bulk_submit').prop('disabled', false).text('Upload & Preview');

            if (res.status === 'ok') {
			window.previewData = res.data;

			var html = '<div class="alert alert-success">'+res.message+'</div>';

			if (res.data.length > 0) {

					var columnsHK = [
						'hbl_no','shipper','connecting_db','destination','feeder_name',
						'container_no_db','container_no_excel','connecting_excel','atd_ts_port','ata_destination',
						'collect_cargo','collect_do'
					];

					var columnsJOB = [
						'hbl_no','shipper','destination','feeder_name',
						'etd','eta','collect_cargo','collect_do'
					];

					var isHK = $('#bulk_format').val() === 'HK';
					var columnSet = isHK ? columnsHK : columnsJOB;

					// START table wrapper
					html += '<div class="preview-table-wrapper">';
					html += '<table class="preview-table">';
					html += '<thead><tr>';

					// HEADER
					var headerLabel = {
					hbl_no: "HBL No",
					shipper: "Shipper Name",
					connecting_db: "Connecting Port",
					destination: "Destination",
					feeder_name: "Feeder Vessel",
					container_no_db: "Container Number",
					container_no_excel: "Second Container",
					atd_ts_port: "ATD TS Port",
					ata_destination: "ATA Destination",
					connecting_excel: "Connecting Vessel",
					collect_cargo: "Collect Cargo",
					collect_do: "Collect DO",
					etd: "ETD",
					eta: "ETA"
				};
					columnSet.forEach(function(col){
						let label = headerLabel[col] ?? col.replace(/_/g, ' ').toUpperCase();
						html += '<th>'+label+'</th>';
					});

					html += '</tr></thead><tbody>';

					// ROWS
					res.data.forEach(function(row){
						html += '<tr>';
						columnSet.forEach(function(col){
							var val = row[col] ?? '';
							var src = row.__source?.[col] ?? '';
							var cls = src === 'excel' ? 'cell-excel' : (src === 'db' ? 'cell-db' : '');
							html += '<td class="'+cls+'">'+val+'</td>';
						});
						html += '</tr>';
					});

					html += '</tbody></table></div>'; // END wrapper
				}

				html += '<button type="button" id="btn_do_insert" class="btn btn-success btn-sm" style="margin-top:10px;">Insert ke Database</button>';

				$('#bulk_upload_msg').html(html);
			} else {
                $('#bulk_upload_msg').html('<div class="alert alert-danger">'+res.message+'</div>');
            }
        },
        error: function(xhr, status, err){
            $('#btn_bulk_submit').prop('disabled', false).text('Upload & Preview');
            $('#bulk_upload_msg').html('<div class="alert alert-danger">Error: '+err+'</div>');
        }
    });
});

// klik Insert ke DB
$(document).on('click', '#btn_do_insert', function(){
    if (!window.previewData) {
        alert('Tidak ada data preview.');
        return;
    }

    $('#btn_do_insert').prop('disabled', true).text('Processing...');

    $.ajax({
        url: window.location.origin+'/tracking_export/insert_import',
        type: 'POST',
        data: { data: JSON.stringify(window.previewData), format_type: $('#bulk_format').val() },
        dataType: 'json',
        success: function(res){
            if (res.status === 'ok') {
                $('#bulk_upload_msg').html('<div class="alert alert-success">'+res.message+'</div>');
                // contoh kalau mau close modal & reload grid:
                // setTimeout(function(){
                //     close_bulk_tracking();
                //     reload_jqgrid();
                // }, 1000);
            } else {
                $('#bulk_upload_msg').html('<div class="alert alert-danger">'+res.message+'</div>');
            }
        },
        error: function(xhr, status, err){
            $('#btn_do_insert').prop('disabled', false).text('Insert ke Database');
            $('#bulk_upload_msg').html('<div class="alert alert-danger">Error: '+err+'</div>');
        }
    });
});

// Menyimpan daftar respon penerimaan BC 1.1 hasil cek terakhir (dari Neo Customs Partner API)
var bc11_respon_list = [];

/**
 * Membuka modal BC 1.1 berisi 2 tombol:
 * 1. BC 1.1 (AWB)          -> GET /manifests/by-reference/{referenceId}/awb-pdf
 * 2. Respon Penerimaan     -> GET /manifests/by-reference/{referenceId}/respons/{idRespon}/pdf
 */
function show_bc11_modal() {
    var kode_ref = $('#txtRefno2').val();
    if(!kode_ref || kode_ref.trim() === '') {
        alert("Pilih Master Job terlebih dahulu!");
        return;
    }

    $('#open-bc11').dialog({
        modal: true,
        title: "DOKUMEN BC 1.1 - " + kode_ref,
        width: 420,
        height: 240,
        resizable: false
    });

    $('#open-bc11').html(`
        <div style="margin-bottom: 10px; font-size: .85em; text-align: center;">
            <br>
            <div class="button-row">
                <input onclick="bc11_view_awb()" type="button" value="BC 1.1" class="manifest-btn bc11-awb">
                <input id="btn-bc11-respon" onclick="bc11_view_respon()" type="button" value="RESPON PENERIMAAN" class="manifest-btn bc11-respon">
            </div>
            <div id="bc11-status-msg" style="margin-top:15px; color:#555;">Mengecek status respon penerimaan...</div>
        </div>
    `);

    $('.button-row').css({
        'display': 'flex',
        'justify-content': 'center',
        'gap': '10px',
        'margin-bottom': '10px'
    });
    $('.manifest-btn').css({
        'width': '170px',
        'height': '40px',
        'border': 'none',
        'color': 'white',
        'font-size': '13px',
        'cursor': 'pointer',
        'border-radius': '5px'
    });
    $('.bc11-awb').css('background-color', '#dc3545');    // Merah
    $('.bc11-respon').css('background-color', '#28a745'); // Hijau

    cek_respon_bc11(kode_ref);
}

/**
 * Cek ke backend apakah respon penerimaan BC 1.1 sudah ada (GET .../respons).
 * Jika sudah ada isinya, backend otomatis mencatat timeline "BC 1.1 sudah Recon."
 */
function cek_respon_bc11(kode_ref) {
    bc11_respon_list = [];
    $.ajax({
        url: base_url + 'afg_c_stuffing_plan/cek_respon_bc11',
        type: 'POST',
        data: { kode_ref: kode_ref },
        dataType: 'json',
        success: function(res) {
            if(res.status) {
                if(res.has_data) {
                    bc11_respon_list = res.respons;
                    $('#bc11-status-msg').html('<b style="color:green;">BC 1.1 sudah Recon.</b><br>Tersedia ' + bc11_respon_list.length + ' respon penerimaan.');
                } else {
                    $('#bc11-status-msg').html('<span style="color:#999;">Belum ada respon penerimaan dari Bea Cukai.</span>');
                }
            } else {
                $('#bc11-status-msg').html('<span style="color:red;">' + res.message + '</span>');
            }
        },
        error: function() {
            $('#bc11-status-msg').html('<span style="color:red;">Gagal mengecek status respon ke server Bea Cukai.</span>');
        }
    });
}

/**
 * Tombol 1: Buka dokumen BC 1.1 (AWB) di tab baru.
 * GET /manifests/by-reference/{referenceId}/awb-pdf
 */
function bc11_view_awb() {
    var kode_ref = $('#txtRefno2').val();
    window.open(base_url + 'afg_c_stuffing_plan/show_bc11_awb/' + encodeURIComponent(kode_ref), '_blank');
}

/**
 * Tombol 2: Buka dokumen Respon Penerimaan BC 1.1 di tab baru.
 * Jika hanya ada 1 respon, langsung dibuka. Jika lebih dari 1, tampilkan daftar pilihan idRespon.
 * GET /manifests/by-reference/{referenceId}/respons/{idRespon}/pdf
 */
function bc11_view_respon() {
    var kode_ref = $('#txtRefno2').val();

    if(!bc11_respon_list || bc11_respon_list.length === 0) {
        alert("Belum ada respon penerimaan untuk BC 1.1 ini.");
        return;
    }

    if(bc11_respon_list.length === 1) {
        var id_respon = bc11_respon_list[0].idRespon;
        window.open(base_url + 'afg_c_stuffing_plan/show_bc11_respon_pdf/' + encodeURIComponent(kode_ref) + '/' + encodeURIComponent(id_respon), '_blank');
        return;
    }

    // Lebih dari 1 respon: tampilkan daftar pilihan agar user memilih idRespon yang mau dilihat
    var opsi_html = '<div style="text-align:left; max-height:150px; overflow-y:auto; border:1px solid #ddd; border-radius:4px;">';
    $.each(bc11_respon_list, function(i, r) {
        var id_respon = r.idRespon;
        var label = r.tanggal || r.createdAt || r.status || ('Respon #' + (i + 1));
        opsi_html += '<div class="bc11-respon-item" data-id="' + id_respon + '" ' +
            'style="padding:6px; border-bottom:1px solid #eee; cursor:pointer;">' +
            'ID Respon: ' + id_respon + ' - ' + label + '</div>';
    });
    opsi_html += '</div>';

    $('#bc11-status-msg').html('<b>Pilih Respon Penerimaan:</b>' + opsi_html);

    $('.bc11-respon-item').off('click').on('click', function() {
        var id_respon = $(this).data('id');
        window.open(base_url + 'afg_c_stuffing_plan/show_bc11_respon_pdf/' + encodeURIComponent(kode_ref) + '/' + encodeURIComponent(id_respon), '_blank');
    });
}

var udata = <?=$user?>;

get_user=function(id) {if (id==0) {return ''} var fullname=udata[id];if (fullname==undefined) {return ''} else {return fullname}}
get_id=function(fullname,list) {if (udata instanceof Array) alert();if(fullname=='') {return ''}else{return (_.invert(list))[fullname]}}

var shp_exp = {};
var shp_imp = {};
var ref_agn = {};
var ref_dst = {};
var ref_srv = {};
var csg_ntf = {};

var wrh = <?=$warehouses?>;
var cri = <?=$carriers?>;
var pld = <?=$cities?>;
var aip = <?=$airport?>;
var ail = <?=$airline?>;
var tcs = <?=$container?>;
var tot = <?=$type_of_trans?>;
var pel = <?=$pelayaran?>;
var cal = <?=$pelayaran_lcl?>;

/* var alc = '';

ref_agn.results = '';
ref_dst.results = '';
ref_srv.results = '';
csg_ntf.results = '';
shp_exp.results = '';
shp_imp.results = '';
ref_agn.total = ref_agn.results.length;
ref_dst.total = ref_dst.results.length;
ref_srv.total = ref_srv.results.length;
csg_ntf.total = csg_ntf.results.length;
shp_exp.total = shp_exp.results.length;
shp_imp.total = shp_imp.results.length; */

//custom_editor();

/* update dropdown agen_penagihan */
var level ="<?php echo $this->session->userdata('level_id'); ?>";
var tahun ="<?php echo $this->session->userdata('db'); ?>";
var agn_png = <?=$agent_penagihan?>;

var agn = <?= $agent ?>;
 
$(function (){
	
	<?= $authentic_level ?>;
	//var agn = toObjectVal(ref_agn.results,'code');
	
	$(".dropdown-stuffingplan").each(function() {
		var obj={};
		var id = this.id;
		if (id=='dropdownTypeOfTransaction') obj=tot;
		else if (id=='dropdownWarehouse') obj=wrh;
		else if (id=='txtCarrier') obj=cri;
		else if (id=='dropdownPortLoading') obj=pld;
		else if (id=='dropdownTranshipmentPort') obj=pld;
		else if (id=='dropdownCountries') obj=pld;
		else if (id=='txtTOC2') obj=tcs;
		else if (id=='dropdownAgent') obj=agn;
		else if (id=='txtPelayaran') obj=pel;
		$.each(obj, function(key, value) {
			$('select#' + id).append($('<option></option>').attr('value',key).text(value))
		})
	})
	
	/* tambahan plugin underscore.js */

	
	load_file_head('js',tmppub+'/js/underscore-min.js');
	
	
});

$(document).ready(function() {
    $('#dropdownAgent').select2({
        placeholder: "",
        allowClear: true,
        ajax: {
            url: base_url + "afg_c_stuffing_plan/get_list_agent", 
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { keyword: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        // --- TAMBAHKAN DUA BARIS INI ---
        templateResult: formatDropdownItem,
        templateSelection: formatSelectedItem
    });

    // Fungsi untuk memformat daftar list yang drop ke bawah
    function formatDropdownItem(agent) {
        // Jika sedang loading, tampilkan teks default
        if (agent.loading) {
            return agent.text;
        }

        // Render HTML dengan 2 baris (Kode tebal, Nama kecil & abu-abu)
        var $container = $(
            "<div style='line-height: 1.2; padding: 2px 0;'>" +
                "<div style='font-weight: bold; font-size: 13px;'>" + agent.text + "</div>" +
                "<div style='font-size: 8px; color: #666;'>" + (agent.name || "") + "</div>" +
            "</div>"
        );
        return $container;
    }

    // Fungsi untuk memformat tampilan saat item sudah dipilih dan masuk ke kotak input
    function formatSelectedItem(agent) {
        // Biarkan tetap satu baris (Kode saja) agar tinggi kotak form tetap kecil seperti yang Anda setting sebelumnya
        return agent.text || agent.id; 
    }
});
var user  = <?php echo json_encode($this->session->userdata('user_id')); ?>; 

<!--- Tambahan modal --->
function add_new_job() {
		$("#FORM_NEW_JOB").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
		$("#FORM_NEW_JOB").dialog({
				autoOpen: true,
				resizable: false,
				height: 'auto',
				width: '60%',
				modal: true,
				title :"CREATE NEW JOB",
				position: {
					my: "center top",
					at: "center top+20",
					of: window
				},
				close: function(){	
					$(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
					//$("#tabel_shipment_request").jqGrid("setGridParam", {datatype: 'json'}).trigger("reloadGrid");
				}
		});
		
		$.ajax({
				type: "POST", 
				url : window.location.origin+'/afg_c_stuffing_plan/open_job_form',
				data: {},
				success:function(XMLHttpRequest)
				{
					$("#FORM_NEW_JOB").css('background-color','#fff');
					$("#FORM_NEW_JOB").html(XMLHttpRequest);
					$("#FORM_NEW_JOB").dialog("option", "position", {
						my: "center top",
						at: "center top+20",
						of: window
					});
				},	
				error:function(XMLHttpRequest)
				{
					$('#FORM_NEW_JOB').html(XMLHttpRequest.responseText)
				}	
		});
	//}
}

function edit_job_form() {
    $("#FORM_NEW_JOB").html('<b style="font-size: 11px; font-style:italic; text-align:center">LOADING...</b>');
    $("#FORM_NEW_JOB").dialog({
        autoOpen: true,
        resizable: false,
        height: 'auto',
        width: '60%',
        modal: true,
        title: "",
        position: {
            my: "center top",
            at: "center top+20",
            of: window
        },
        close: function(){	
            $(this).html('<b style="font-size: 11px; font-style:italic">LOADING...</b>');
        }
    });

    $.ajax({
        type: "POST",
        url: window.location.origin + '/afg_c_stuffing_plan/edit_job_form',
        data: {
            sp_id: $('#spId').val(),
            kode_ref: $("#txtRefno2").val(),
            mode: $('#dropdownTypeOfTransaction').val()
        },
        success: function(XMLHttpRequest) {
            $("#FORM_NEW_JOB").css('background-color', '#fff');
            $("#FORM_NEW_JOB").html(XMLHttpRequest);
            $("#FORM_NEW_JOB").dialog("option", "position", {
                my: "center top",
                at: "center top+20",
                of: window
            });
        },
        error: function(XMLHttpRequest) {
            $('#FORM_NEW_JOB').html(XMLHttpRequest.responseText);
        }
    });
}
function handleEditButton() {
    var refno = document.getElementById('txtRefno2').value || '';

    if (refno.indexOf('-100') !== -1) {
        edit_job_form();
    } else {
        edit_stuff();
    }
}
function toggleTimeline(side) {
    if (side === 'left') {
        $('#gcl-left-timeline').toggleClass('collapsed');
        $('#handle-left').toggleClass('collapsed');
    } else {
        $('#gcl-right-timeline').toggleClass('collapsed');
        $('#handle-right').toggleClass('collapsed');
    }
}

$(document).ready(function() {
    // Pindahkan panel dan tombol ke body agar posisinya aman di lapisan terluar
    $('#gcl-left-timeline').appendTo('body');
    $('#handle-left').appendTo('body');
    $('#gcl-right-timeline').appendTo('body');
    $('#handle-right').appendTo('body');

    // Opsional: Jika resolusi layar agak kecil, default-kan dalam keadaan tertutup agar tidak sempit
    if ($(window).width() < 1450) {
        $('#gcl-left-timeline, #handle-left').addClass('collapsed');
        // $('#gcl-right-timeline, #handle-right').addClass('collapsed'); // Uncomment jika kanan juga mau auto-tutup di layar sedang
    }
});
</script>