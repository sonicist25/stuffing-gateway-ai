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
            <option value=""></option>
            <option value="FCL">FCL EXPORT</option>
            <option value="LCL">LCL EXPORT</option>
            <option value="COLOAD">COLOAD EXPORT</option>
            <option value="AIR FREIGHT">AIR FREIGHT EXPORT</option>
            <option value="AIR FREIGHT IMPORT">AIR FREIGHT IMPORT</option>
            <option value="SF IMPORT FCL">FCL IMPORT</option>
            <option value="SF IMPORT LCL">LCL IMPORT</option>
            <option value="COLOAD IMPORT">COLOAD IMPORT</option>
            <option value="MESCELLENEOUS">MISCELLANEOUS</option>
            <option value="DOMESTICS">LCL DOMESTICS</option>
            <option value="FCL DOMESTICS">FCL DOMESTICS</option>
            <option value="OTHER DOMESTICS">OTHER DOMESTICS</option>
            <option value="CUSTOM">CUSTOM EXPORT</option>
            <option value="CUSTOM IMPORT">CUSTOM IMPORT</option>
            <option value="TRUCKING ONLY">TRUCKING ONLY</option>
            <option value="TRUCKING VENDOR">TRUCKING VENDOR</option>
            <option value="TRUCKING CUSTOMER">TRUCKING CUSTOMER</option>
            <option value="TRUCKING EXTERNAL">TRUCKING EXTERNAL</option>
            <option value="PROFIT SHARE PPJK">PROFIT SHARE PPJK</option>
            <option value="WAREHOUSE">WAREHOUSE</option>
        </select>
    </div>
  </div>
  <!-- Dynamic Fields Container -->
<div id="formFields" class="DynamicForm" style="display: none;">


  <!-- ROW 3 -->
<div class="form-group-row">
				<div class="form-group">
					<label for="liner_d">JENIS BIAYA</label>
					<select id="liner_d" name="liner_d" class="form-control">
								<option value=""></option>
								<option value="DENDA">DENDA</option>
								<option value="COST TAMBAHAN">COST TAMBAHAN</option>
								<option value="KEKURANGAN INVOICE">KEKURANGAN INVOICE</option>
								<option value="GAJI">GAJI</option>
								<option value="LISTRIK">LISTRIK</option>
								<option value="INTERNET">INTERNET</option>
								<option value="ALOKASI BIAYA">ALOKASI BIAYA</option>
								<option value="SERVICE LAINNYA">SERVICE LAINNYA</option>
								<option value="BIAYA LAIN-LAIN">BIAYA LAIN-LAIN</option>
					</select>
				</div>

				<div class="form-group">
					<label for="mbl_d">DESKRIPSI JOB</label>
					<input id="mbl_d" name="mbl_d" type="text" class="form-control" value="" style="width: 600px;">
				</div>
			</div>
	
			<div class="form-group-row">
				<div class="form-group">
					<label for="kurs_d">KURS PAJAK</label>
					<input id="kurs_d" name="kurs_d" type="text" class="form-control" oninput="filterAngka(this)">
				</div>

				<div class="form-group">
					<label for="scac_d">JOB TERKAIT</label>
					<select id="scac_d" name="scac_d" type="text" class="form-control"></select>
					<span id="validator2" class="validator">Jika berkaitan dengan job lain</span>
				</div>
				
				<div class="form-group">
					<label for="warehouse_d">DIVISI TERKAIT</label>
					<select id="warehouse_d" name="warehouse_d" class="form-control">
								<option value=""></option>
								<option value="EXPORT ">EXPORT</option>
								<option value="IMPORT">IMPORT</option>
								<option value="AIR FREIGHT">AIR FREIGHT</option>
								<option value="PPJK">PPJK</option>
								<option value="SALES">SALES</option>
								<option value="ACCT">FINANCE</option>
								<option value="HR">HR</option>
								<option value="DOMESTIK">DOMESTIK</option>
								<option value="DOMESTIK">TRUCKING</option>
								<option value="LAIN-LAIN">LAIN-LAIN</option>
					</select>
				</div>
			</div>
<!-- Divider + Judul -->
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
			$('#seal_d').val(data.seal_no);
			$('#jml_container_d').val(data.type_cont_qty);
			$('#kurs_d').val(data.kurs);
			$('#pol_job').val(data.port_loading);
			$('#pot_job').val(data.transhipment_port);
			$('#pod_job').val(data.destination);
			$('#scac_d').val(data.scac);

			// Type of Transaction - important: trigger change
			$('#TypeOfTransaction').val(data.type_of_trans).trigger('change'); // so renderFormFields() works

			// Vessel + Voyage
			if (data.vessel && data.voyage) {
			  $('#vessel_d').val(data.vessel+' '+data.voyage);
			  $('#voyage_d').val();
			}
			$('#liner_d').val(data.carrier).trigger('change');
			$('#scac_d').select2({
			  placeholder: 'Pilih Job',
			  ajax: {
				url: '<?= base_url('afg_c_dropdown/dropdown_job') ?>',
				dataType: 'json',
				delay: 250,
				data: function(params) {
				  return {
					q: params.term // kirim inputan sebagai query ?q=
				  };
				},
				processResults: function(data) {
				  const results = data.map(item => ({
					id: item.kode_ref,
					text: item.kode_ref
				  }));
				  return {
					results
				  };
				},
				cache: true
			  }
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

			document.querySelector('.modal-container').classList.add('edit-mode');
			$('.modal-title').text('Detail Job '+data.kode_ref);
		  }
		});
    // Init Select2
    $('#region_id_d').select2({ placeholder: 'Select POL', allowClear: true });
    $('#city_identifier').select2({ placeholder: 'Select POD', allowClear: true });
    $('#etd_jkt_d').select2({ placeholder: 'Select ETD', allowClear: true });
    $('#type_container_d').select2({ placeholder: 'Select Container', allowClear: true });
    $('#agent_d').select2({ placeholder: 'Select Agent', allowClear: true });
    $('#warehouse_d').select2({ placeholder: 'Select Divisi', allowClear: true });
   let linerData = {}; // objek untuk menyimpan code-scac

	// Inisialisasi select2
	$('#liner_d').select2({
	  placeholder: 'Select Job Type',
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
	

    // Load region_id options
    $.get("<?= base_url('afg_c_dropdown/get_region_options') ?>", function(data) {
        const regions = JSON.parse(data);
        regions.forEach(function(item) {
            $('#region_id_d').append(`<option value="${item}">${item.toUpperCase()}</option>`);
        });
    });

    // Load city_identifier options
    $.get("<?= base_url('afg_c_dropdown/get_city_options') ?>", function(data) {
        const cities = JSON.parse(data);
        cities.forEach(function(item) {
            $('#city_identifier').append(`<option value="${item}">${item}</option>`);
        });
    });

    // Load etd_jkt_d based on region_id + city_identifier
    function loadEtdOptions() {
        let region = $('#region_id_d').val();
        let city = $('#city_identifier').val();

        if (region && city) {
            $.get("<?= base_url('afg_c_dropdown/get_etd_options') ?>", { region_id: region, city_identifier: city }, function(data) {
                let options = JSON.parse(data);
                $('#etd_jkt_d').empty().append(`<option value="">Select ETD JKT</option>`);
                options.forEach(function(item) {
                    $('#etd_jkt_d').append(`<option value="${item}">${item}</option>`);
                });
            });
        }
    }

    $('#region_id_d, #city_identifier').on('change', loadEtdOptions);
});


    $('#searchForm').on('submit', function(e) {
        e.preventDefault(); // prevent reload

        let formData = {
            region_id: $('#region_id_d').val(),
            city_identifier: $('#city_identifier').val(),
            etd_jkt: $('#etd_jkt_d').val()
        };

        $.ajax({
            url: '<?= base_url('afg_c_dropdown/search_json') ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
				if (response.length > 0) {
					const data = response[0];
					let transId; // Deklarasi di luar blok if-else agar bisa diakses

					const cityId = data.eta_city_id ?? data.city_id;
					if (cityId === 'HONG KONG' || cityId === 'BUSAN' || cityId === 'SINGAPORE' || 
						cityId === 'PORT KELANG' || cityId === 'WESTPORT/PORT KLANG') {
						transId = cityId;	
					} else {
						transId = data.etd_city_con_id ?? '';
					}

					const con_ves = data.connecting ?? '';
					const con_voy = data.voy_con ?? '';
					let con_ves_voy = '';
					if (con_ves) {
						con_ves_voy = con_ves + ' ' + con_voy;
					}

					const voyage = data.voy_vessel ?? data.voy ?? '';
					const closingDateFormatted = formatDateToDatetimeLocal(data.stf_cls);

					$('#etd_pol').val(data.etd_jkt ?? '');
					$('#eta_des').val(data.eta ?? '');

					$('#closing').val(closingDateFormatted);
					let route_d; // Deklarasi di luar blok if-else agar bisa diakses

					if (cityId === 'HONG KONG' || cityId === 'BUSAN' || cityId === 'SINGAPORE' || 
						cityId === 'PORT KELANG' || cityId === 'WESTPORT/PORT KLANG') {
						route_d = "TRANSSHIPMENT";	
					} else if (data.route_type === "VIA"){
						route_d = "VIA";
					} else{
						route_d = "DIRECT";
					}
					$('#route_d').val(route_d);
					const portLoadingMap = {
						'Jakarta': 'TANJUNG PRIOK',
						'Surabaya': 'SURABAYA (PTT)',
						'Semarang': 'SEMARANG (PTT)',
						'Medan': 'BELAWAN'
					};

					const region = data.region_id ?? '';
					const portLoadingValue = portLoadingMap[region] ?? '';

					
					$('#pol_job').val(portLoadingValue).attr('disabled', true);
					$('#pot_job').val(transId).attr('disabled', true);
					$('#pod_job').val(cityId).attr('disabled', true);


					$('#vessel_d').val(`${data.vessel ?? ''} ${voyage}`);
					$('#voyage_d').val(con_ves_voy);
					$('#schedule_d').val(data.id ?? '');
				} else {
					console.warn("Data kosong. Tidak ada form yang diisi.");
				}
			}
        });
    });

function renderFormFields() {
  const selectedValue = document.getElementById("TypeOfTransaction").value;
  const toolbarSearchDiv = document.querySelector(".DynamicForm");
  if (toolbarSearchDiv) {
    if (selectedValue === "MESCELLENEOUS") {
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
