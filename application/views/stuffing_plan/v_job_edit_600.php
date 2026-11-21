<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Stuffing Plan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* RESET & DASAR */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
	
	.modal-container {
	  animation: fadeInUp 0.5s ease;
	}

	@keyframes fadeInUp {
	  from {
		opacity: 0;
		transform: translateY(40px);
	  }
	  to {
		opacity: 1;
		transform: translateY(0);
	  }
	}
	

	.btn-submit {
	  transition: background-color 0.3s ease, transform 0.2s ease;
	}
	.btn-submit:hover {
	  transform: translateY(-2px);
	}
	
    .modal-container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 30px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .modal-title {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 25px;
      color: #333;
    }

    .form-group-row {
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      margin-bottom: 30px;
    }

    .form-group {
      flex: 1 1 200px;
      min-width: 200px;
      max-width: 320px;
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      font-size: 12px;
      font-weight: 500;
      color: #333;
      margin-bottom: 6px;
    }

    .form-group input,
    .form-group select {
      padding: 10px;
      font-size: 12px;
      border-radius: 6px;
      border: 1px solid #ccc;
      transition: 0.2s ease;
      width: 100%;
      min-height: 38px;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #007bff;
      box-shadow: 0 0 0 2px rgba(0,123,255,0.15);
      outline: none;
    }

    .btn-submit {
      background-color: #007BFF;
      color: white;
      padding: 12px 25px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn-submit:hover {
      background-color: #218838;
    }

    @media (max-width: 768px) {
      .form-group {
        min-width: 100%;
      }
    }
	/* Select Field Styling in Search Toolbar */
.toolbar-search {
    margin-top: 10px;
}

.toolbar-search .search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.search-form .form-field {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    min-width: 300px;
}

.search-form .btn-primary {
    background-color: #007BFF;
    color: white;
    padding: 12px 25px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s;
}

.search-form .btn-primary:hover {
    background-color: #0056b3;
}

/* Validator */
.validator {
    font-size: 10px;
    color: red;
    margin-top: 0px;
}
.form-group.small-width {
  flex: 0 0 auto !important;
  max-width: 100px !important;
}

.form-group.small-width input {
  width: 100%;
  max-width: 100px;
}
.select2-container {
  width: 100% !important;
  max-width: 100%; /* agar tidak melebihi parent */
}

.select2-selection {
  min-height: 38px;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

/* Mode edit: beri border & latar berbeda */
.modal-container.edit-mode {
  border: 2px solid #ffa726;
  background-color: #fffaf0;
  position: relative;
}

/* Badge kecil pojok kiri atas */
.modal-container.edit-mode::before {
  content: "EDIT MODE";
  position: absolute;
  top: -12px;
  left: 20px;
  background-color: #ffa726;
  color: #fff;
  font-size: 12px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 4px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
  </style>
</head>

<body>

<div class="modal-container">
  <h2 class="modal-title">Edit Stuffing Plan</h2>

  <!-- ROW 1 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="dateInput">Tanggal Job</label>
      <input type="date" id="dateInput">
    </div>
        <div class="form-group">
        <label for="TypeOfTransaction">Type of Transaction</label>
        <select id="TypeOfTransaction" name="TypeOfTransaction" class="form-control" onchange="renderFormFields()">
            <option value="AIR FREIGHT IMPORT">AIR FREIGHT IMPORT</option>
        </select>
    </div>
  </div>
  <!-- Dynamic Fields Container -->
<div id="formFields" class="DynamicForm" style="display: none;">


  <!-- ROW 3 -->
<div class="form-group-row">
				<div class="form-group">
					<label for="liner_d">NAMA AIRLINE</label>
					<select id="liner_d" name="liner_d" class="form-control">
						<option value="">Select Region</option>
					</select>
				</div>

				<div class="form-group">
					<label for="scac_d">AIRLINE CODE</label>
					<input id="scac_d" name="scac_d" type="text" class="form-control" value="" disabled>
				</div>

				<div class="form-group">
					<label for="mbl_d">MAWB NUMBER</label>
					<input id="mbl_d" name="mbl_d" type="text" class="form-control" value="">
				</div>
			</div>
			<div>
				<div class="form-group-row">
					
					  <div class="form-group">
						<label for="seal_d">Agent’s IATA Code</label>
						<input type="text" id="seal_d" value="1530259010">
					  </div>

					  <div class="form-group">
						<label for="jml_container_d">Flight Booking Reference</label>
						<input type="text" id="jml_container_d" value="">
					  </div>

					  <div class="form-group">
						<label for="type_container_d">HAWB Number</label>
						<input type="text" id="type_container_d" name="type_container_d" class="form-control">
					</div>
			</div>
			<div class="form-group-row">
				<div class="form-group">
					<label for="kurs_d">KURS PAJAK</label>
					<input id="kurs_d" name="kurs_d" type="text" class="form-control" oninput="filterAngka(this)">
				</div>

				<div class="form-group">
					<label for="kurs_jual">KURS JUAL</label>
					<input id="kurs_jual" name="kurs_jual" type="text" class="form-control" oninput="filterAngka(this)">
				</div>
				<div class="form-group">
				  <label for="agent_d">KODE AGENT</label>
				  <select id="agent_d" name="agent_d" class="form-control">
				  <option value=""></option>
				  </select>
				  <span id="validator3" class="validator"></span>
				</div>
				
				<div class="form-group">
					<label for="warehouse_d">CFS Name</label>
					<select id="warehouse_d" name="warehouse_d" class="form-control">
					<option value=""></option>
					</select>
					<span id="validator4" class="validator"></span>
				</div>
			</div>
<!-- Divider + Judul -->
<hr style="margin: 30px 0;">
  <!-- ROW 4 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="pol_job">Airport of Departure</label>
      <input id="pol_job" type="text" disabled value="">
	  <span id="validator0" class="validator"></span>
    </div>
    <div class="form-group">
      <label for="pot_job">To</label>
      <input id="pot_job" type="text" disabled value="">
	  <span id="validator1" class="validator"></span>
    </div>
    <div class="form-group">
      <label for="pod_job">Destination Airport</label>
      <input id="pod_job" type="text" disabled value="">
	  <span id="validator2" class="validator"></span>
    </div>
  </div>

  <!-- ROW 5 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="closing">Cargo Ready Date</label>
      <input id="closing" type="datetime-local" disabled>
    </div>
    <div class="form-group">
      <label for="etd_pol">ETD</label>
      <input id="etd_pol" type="date" disabled>
    </div>
    <div class="form-group">
      <label for="eta_des">ETA</label>
      <input id="eta_des" type="date" disabled>
    </div>
  </div>
  
   <div class="form-group-row">
	<div class="form-group">
      <label for="stuffing_d">TANGGAL STUFFING</label>
      <input id="stuffing_d" type="date"/>
    </div>
    <div class="form-group">
      <label for="atd_pol">Departure Date</label>
      <input id="atd_pol" type="date"/>
    </div>
    <div class="form-group">
      <label for="ata_des">Arrival Date</label>
      <input id="ata_des" type="date"/>
    </div>
  </div>

  <!-- ROW 6 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="route_d">ROUTE TYPE</label>
      <input id="route_d" type="text" disabled value="">
    </div>
    <div class="form-group">
      <label for="vessel_d">Flight Number</label>
      <input id="vessel_d" type="text"value="">
    </div>
    <div class="form-group">
      <label for="voyage_d">Next Flight Number</label>
      <input id="voyage_d" type="text" value="">
    </div>
  </div>
</div>
  <!-- Submit Button -->
  <div style="text-align: left; margin-top: 20px;">
    <button type="submit" class="btn-submit">Submit</button>
  </div>
</div>
<script>
function formatDateToDatetimeLocal(dateStr) {
    if (!dateStr) return '';
    return `${dateStr}T00:00`;
}

function convertDate(jsonDate) {
    let parts = jsonDate.split("/"); // Pisahkan berdasarkan '/'
    let formattedDate = `${parts[2]}-${parts[1]}-${parts[0]}`; // Susun ulang ke YYYY-MM-DD
    return formattedDate;
}

$(document).ready(function() {
	
	$.ajax({
		  async: true,
		  type: "POST",
		  url: window.location.origin + '/afg_c_stuffing_plan/get_data_edit_stuffing_plan',
		  data: {
			sp_id: $('#spId').val(),
			kode_ref: $("#txtRefno2").val(),
			mode: $('#dropdownTypeOfTransaction').val()
		  },
		  success: function(response) {
			var data = $.parseJSON(response);

			// Assign input field values
			$('#dateInput').val(convertDate(data.tanggal_job));
			$('#mbl_d').val(data.mother_bl);
			$('#stuffing_d').val(data.stuffing_date);
			$('#closing').val(data.closing_date_time);
			$('#etd_pol').val(data.etd_jkt);
			$('#eta_des').val(data.eta);
			$('#atd_pol').val(data.atd);
			$('#ata_des').val(data.ata);
			$('#container_d').val(data.container_no);
			$('#seal_d').val("1530259010");
			$('#jml_container_d').val(data.type_cont_qty);
			$('#kurs_d').val(data.kurs);
			$('#kurs_jual').val(data.kurs_selling);
			$('#pol_job').val(data.port_loading);
			$('#pot_job').val(data.transhipment_port);
			$('#pod_job').val(data.destination);
			$('#scac_d').val(data.scac);
			$('#vessel_d').val(data.vessel);

			// Type of Transaction - important: trigger change
			$('#TypeOfTransaction').val(data.type_of_trans).trigger('change'); // so renderFormFields() works

			// Vessel + Voyage
			if (data.vessel && data.voyage) {
			  $('#vessel_d').val(data.vessel+' '+data.voyage);
			  $('#voyage_d').val();
			}
			
			// === Liner / Carrier ===
			$.get("<?= base_url('afg_c_dropdown/dropdown_airline') ?>", function(data) {
			  const carrierList = JSON.parse(data);
			  carrierList.forEach(function(item) {
				$('#liner_d').append(`<option value="${item.name}">${item.name}</option>`);
				linerData[item.name] = item.code; // simpan scac berdasarkan code
			  });
			});


			if ($('#liner_d').find(`option[value="${data.carrier}"]`).length === 0) {
			  $('#liner_d').append(`<option value="${data.carrier}">${data.carrier}</option>`);
			}
			$('#liner_d').val(data.carrier).trigger('change');
			
			// Saat liner dipilih, isi otomatis SCAC
			$('#liner_d').on('change', function () {
			  const selectedCode = $(this).val();
			  const scac = linerData[selectedCode] || '';
			  $('#scac_d').val(scac);
			});

			// === Agent Penagihan ===
			let agentData = {}; // simpan data agent berdasarkan code

			// 1. Ambil data dari backend
			$.get("<?= base_url('afg_c_dropdown/dropdown_agent_d') ?>", function(response) {
			  const agents = JSON.parse(response);

			  agents.forEach(function(agent) {
				$('#agent_d').append(`<option value="${agent.agent_company_code}">${agent.agent_company_code}</option>`);
				
				// Simpan data ke agentData
				agentData[agent.agent_company_code] = {
				  name: agent.agent_company_name,
				  city: agent.agent_company_city
				};
			  });

			  // 2. Setelah agentData lengkap → set nilai + trigger
			  const selectedAgent = data.agen_penagihan;

			  if ($('#agent_d').find(`option[value="${selectedAgent}"]`).length === 0) {
				$('#agent_d').append(`<option value="${selectedAgent}">${selectedAgent}</option>`);
			  }

			  $('#agent_d').val(selectedAgent).trigger('change');

			  // 3. Jalankan logika validasi setelah semua siap
			  if (agentData[selectedAgent]) {
				const info = agentData[selectedAgent];
				$('#validator3').text(`${info.name} - ${info.city}`);
			  }
			});
			
			// === Lokasi Stuffing ===
			if ($('#warehouse_d').find(`option[value="${data.warehouse}"]`).length === 0) {
			  $('#warehouse_d').append(`<option value="${data.warehouse}">${data.warehouse}</option>`);
			}
			
			$('#warehouse_d').val(data.warehouse).trigger('change');
			
			// === Type Container ===
			if ($('#type_container_d').find(`option[value="${data.type_cont_size}"]`).length === 0) {
			  $('#type_container_d').append(`<option value="${data.type_cont_size}">${data.type_cont_size}</option>`);
			}
			
			document.querySelector('.modal-container').classList.add('edit-mode');
			$('.modal-title').text('Detail Job '+data.kode_ref);
		  }
		});
    // Init Select2
    $('#agent_d').select2({ placeholder: 'Select Agent', allowClear: true });
    $('#warehouse_d').select2({ placeholder: 'Select Warehouse', allowClear: true });
   let linerData = {}; // objek untuk menyimpan code-scac

	// Inisialisasi select2
	$('#liner_d').select2({
	  placeholder: 'Select Liner',
	  allowClear: true,
	  width: '100%'
	});
	
	let agentData = {}; // simpan data agent berdasarkan code


	// Ambil data agent dari server
	$.get("<?= base_url('afg_c_dropdown/dropdown_agent_d') ?>", function(data) {
	  const agents = JSON.parse(data);
	  agents.forEach(function(agent) {
		// Tambahkan opsi ke dropdown
		$('#agent_d').append(`<option value="${agent.agent_company_code}">${agent.agent_company_code}</option>`);
		
		// Simpan data agent
		agentData[agent.agent_company_code] = {
		  name: agent.agent_company_name,
		  city: agent.agent_company_city
		};
	  });
	});
	
	// Saat agent dipilih, tampilkan info di validator3
	$('#agent_d').on('change', function () {
	  const selectedCode = $(this).val();
	  const validator = $('#validator3');
	  
	  if (selectedCode && agentData[selectedCode]) {
		const info = agentData[selectedCode];
		validator.text(`${info.name} - ${info.city}`);
	  } else {
		validator.text('');
	  }
	});
	
   // Load type Warehouse options
   let warehouseData = {}; // simpan data agent berdasarkan code
	$.get("<?= base_url('afg_c_dropdown/dropdown_warehouse_airport') ?>", function(data) {
		const warehouse_d = JSON.parse(data);
		warehouse_d.forEach(function(item) {
			$('#warehouse_d').append(`<option value="${item.warehouse_code}">${item.warehouse_code}</option>`);
			// Simpan data agent
			warehouseData[item.warehouse_code] = item.warehouse_name;
	  });
	});
	
	// Saat agent dipilih, tampilkan info di validator4
	$('#warehouse_d').on('change', function () {
	  const selectedCode = $(this).val();
	  const validator = $('#validator4');
	  
	  if (selectedCode &&warehouseData[selectedCode]) {
		const info = warehouseData[selectedCode];
		validator.text(`${info}`);
	  } else {
		validator.text('');
	  }
	});
	  $.ajax({
		url: 'afg_c_dropdown/dropdown_airport_d', // Ganti dengan URL backend kamu
		method: 'GET',
		dataType: 'json',
		success: function(data) {
		  // Bikin mapping dari nama => iata
		  const airportMap = {};
		  data.forEach(function(item) {
			airportMap[item.name] = item.iata_code;
		  });

		  // Untuk masing-masing input
		  ['pol_job', 'pot_job', 'pod_job'].forEach(function(id, index) {
			const inputVal = $('#' + id).val();
			const iataCode = airportMap[inputVal] || '-';
			$('#validator' + index).text(iataCode);
		  });
		},
		error: function() {
		  console.error('Gagal memuat data bandara.');
		}
	  });
});
function renderFormFields() {
  const selectedValue = document.getElementById("TypeOfTransaction").value;
  const toolbarSearchDiv = document.querySelector(".DynamicForm");
  if (toolbarSearchDiv) {
    if (selectedValue === "AIR FREIGHT IMPORT") {
      toolbarSearchDiv.style.display = "block";
    } else {
      toolbarSearchDiv.style.display = "none";
    }
  }
}

// Jalankan saat halaman selesai dimuat
document.addEventListener("DOMContentLoaded", function () {
  renderFormFields(); // Cek kondisi awal saat load
});

function filterAngka(input) {
    input.value = input.value.replace(/\D/g, ''); // Hanya angka, hapus selain digit
}

</script>
</body>
</html>
