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
            <option value="COLOAD">COLOAD EXPORT</option>
        </select>
    </div>
  </div>
  <!-- Dynamic Fields Container -->
<div id="formFields" class="DynamicForm" style="display: none;">


  <!-- ROW 3 -->
<div class="form-group-row">
				<div class="form-group">
					<label for="liner_d">CODE COLOADER</label>
					<select id="liner_d" name="liner_d" class="form-control">
						<option value="">Select Region</option>
					</select>
					<span id="validator6" class="validator"></span>
				</div>

				<div class="form-group">
					<label for="scac_d">MBL COLOADER</label>
					<input id="scac_d" name="scac_d" type="text" class="form-control" value="">
				</div>

				<div class="form-group">
					<label for="mbl_d">MBL PELAYARAN</label>
					<input id="mbl_d" name="mbl_d" type="text" class="form-control" value="">
					<span id="validator2" class="validator">Jika diisi MBL NVOCC/COLOADER, tracking harus diisi manual</span>
				</div>
			</div>
			<div>
				<div class="form-group-row">
					  <div class="form-group">
						<label for="container_d">NOMOR CONTAINER</label>
						<input id="container_d" name="container_d" type="text" class="form-control" value="" onkeyup="checkValid(this)" maxlength="11"/>
						<span id="validator1" class="validator"></span>
					  </div>

					  <div class="form-group">
						<label for="seal_d">NOMOR SEAL</label>
						<input type="text" id="seal_d" value="">
					  </div>

					  <div class="form-group">
						<label for="jml_container_d">JUMLAH CONTAINER</label>
						<input type="number" id="jml_container_d" value="1" min="1" max="999">
					  </div>

					  <div class="form-group">
						<label for="type_container_d">TYPE CONTAINER</label>
						<select id="type_container_d" name="type_container_d" class="form-control">
						<option value=""></option>
						</select>
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
					<label for="warehouse_d">LOKASI STUFFING</label>
					<select id="warehouse_d" name="warehouse_d" class="form-control">
					<option value=""></option>
					</select>
				</div>
			</div>
<!-- Divider + Judul -->
<hr style="margin: 30px 0;">
		</form>
	</div>
  <!-- ROW 4 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="pol_job">ORIGIN</label>
      <input id="pol_job" type="text" disabled value="">
    </div>
    <div class="form-group">
      <label for="pot_job">TRANSHIPMENT PORT</label>
      <input id="pot_job" type="text" disabled value="">
    </div>
    <div class="form-group">
      <label for="pod_job">DESTINATION</label>
      <input id="pod_job" type="text" disabled value="">
    </div>
  </div>

  <!-- ROW 5 -->
  <div class="form-group-row">
    <div class="form-group">
      <label for="closing">WAKTU CLOSING</label>
      <input id="closing" type="datetime-local">
    </div>
    <div class="form-group">
      <label for="etd_pol">ETD</label>
      <input id="etd_pol" type="date">
    </div>
    <div class="form-group">
      <label for="eta_des">ETA</label>
      <input id="eta_des" type="date">
    </div>
  </div>
  
   <div class="form-group-row">
	<div class="form-group">
      <label for="stuffing_d">TANGGAL STUFFING</label>
      <input id="stuffing_d" type="date"/>
    </div>
    <div class="form-group">
      <label for="atd_pol">ATD</label>
      <input id="atd_pol" type="date"/>
    </div>
    <div class="form-group">
      <label for="ata_des">ATA</label>
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
      <label for="vessel_d">VESSEL</label>
      <input id="vessel_d" type="text" value="">
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
			$('#seal_d').val(data.seal_no);
			$('#jml_container_d').val(data.type_cont_qty);
			$('#kurs_d').val(data.kurs);
			$('#kurs_jual').val(data.kurs_selling);
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
			
			var container1 = document.getElementById('container_d');
            var validator1 = document.getElementById('validator1');
            
            function getValidatorMessage(validator, containerNo){
                var isvalid     = validator.isValid(containerNo);
                var valMsgs     = validator.getErrorMessages();
                var valMsgsTxt  = valMsgs.join(', ');
                msg = isvalid;
                if (msg == false) {
                    msg+= ' : ' + valMsgsTxt + '.';
                    if(valMsgs.indexOf('The check digit does not match') != -1){
                        msg+= ' Check digit must be ' + validator.createCheckDigit(containerNo.substr(0,10)) + '.';
                    }
                }
                
                return msg;
            }
            
            function checkValid(txt) {
                if(txt.value != ''){                    
                    var validator = new ContainerValidator();
                    validator1.innerHTML = getValidatorMessage(validator, txt.value);
                }else{
                    validator1.innerHTML = 'Please enter Container Number';
                }
            }
            checkValid(container1);
			let linerData = {};
			// === Liner / Carrier ===
			$.get("<?= base_url('afg_c_dropdown/dropdown_coloader') ?>", function(data) {
			  const carrierList = JSON.parse(data);
			  carrierList.forEach(function(item) {
				$('#liner_d').append(`<option value="${item.shipping_company_code}">${item.shipping_company_code}</option>`);
				linerData[item.shipping_company_code] = item.shipping_company_name; // simpan nama berdasarkan code
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
			  console.log('Selected:', selectedCode);
				console.log('SCAC:', linerData[selectedCode]);

			  $('#validator6').text(scac);
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
			
			$('#type_container_d').val(data.type_cont_size).trigger('change');
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
	$.get("<?= base_url('afg_c_dropdown/dropdown_warehouse_d') ?>", function(data) {
		const warehouse_d = JSON.parse(data);
		warehouse_d.forEach(function(item) {
			$('#warehouse_d').append(`<option value="${item.warehouse_code}">${item.warehouse_code}</option>`);
		});
	});

	// Load type container options
    $.get("<?= base_url('afg_c_dropdown/dropdown_container_lcl') ?>", function(data) {
        const type_cont_d = JSON.parse(data);
        type_cont_d.forEach(function(item) {
            $('#type_container_d').append(`<option value="${item}">${item.toUpperCase()}</option>`);
        });
    })
	
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
    if (selectedValue === "COLOAD") {
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
 var container1 = document.getElementById('container_d');
            var validator1 = document.getElementById('validator1');
            
            function getValidatorMessage(validator, containerNo){
                var isvalid     = validator.isValid(containerNo);
                var valMsgs     = validator.getErrorMessages();
                var valMsgsTxt  = valMsgs.join(', ');
                msg = isvalid;
                if (msg == false) {
                    msg+= ' : ' + valMsgsTxt + '.';
                    if(valMsgs.indexOf('The check digit does not match') != -1){
                        msg+= ' Check digit must be ' + validator.createCheckDigit(containerNo.substr(0,10)) + '.';
                    }
                }
                
                return msg;
            }
            
            function checkValid(txt) {
                if(txt.value != ''){                    
                    var validator = new ContainerValidator();
                    validator1.innerHTML = getValidatorMessage(validator, txt.value);
                }else{
                    validator1.innerHTML = 'Please enter Container Number';
                }
            }
            checkValid(container1);
  
  // Cek Nomor container
  function ContainerValidator()  {

    /**
     * @functions list
     *
     * isValid()
     * validate()
     * getErrorMessages()
     * getOwnerCode()
     * getProductGroupCode()
     * getRegistrationDigit()
     * getCheckDigit()
     * generate()
     * createCheckDigit()
     * clearErrors()
     * buildCheckDigit()
     * identify()
     *
     **/
    
    //SHS Constants addition
    var STR_PAD_LEFT = 'STR_PAD_LEFT';

    this.alphabetNumerical = {
        'A' : 10, 'B' : 12, 'C' : 13, 'D' : 14, 'E' : 15, 'F' : 16, 'G' : 17, 'H' : 18, 'I' : 19,
        'J' : 20, 'K' : 21, 'L' : 23, 'M' : 24, 'N' : 25, 'O' : 26, 'P' : 27, 'Q' : 28, 'R' : 29,
        'S' : 30, 'T' : 31, 'U' : 32, 'V' : 34, 'W' : 35, 'X' : 36, 'Y' : 37, 'Z' : 38
    };
    this.pattern = /^([A-Z]{3})(U|J|Z)(\d{6})(\d)$/;
    this.patternWithoutCheckDigit = /^([A-Z]{3})(U|J|Z)(\d{6})$/;
    this.errorMessages = [];
    this.ownerCode = [];
    this.productGroupCode;
    this.registrationDigit = [];
    this.checkDigit;
    this.containerNumber;

    /**
     * Check if the container has a valid container code
     *
     * @return boolean
     */
     this.isValid = function(containerNumber)
    {
        valid = this.validate(containerNumber);
        if (this.empty(this.errorMessages)) {
            return "Nomor Container Valid";
        }
        return false;
    }

     this.validate = function(containerNumber)
    {
        matches = [];

        if (!this.empty(containerNumber) && this.is_string(containerNumber)) {
            matches = this.identify(containerNumber);

            if (this.count(matches) !== 5) {
                this.errorMessages.push('The container number is invalid');
            } else {
                checkDigit = this.buildCheckDigit(matches);

                if (this.checkDigit != checkDigit) {
                    this.errorMessages.push('The check digit does not match');
                    matches = [];
                }
            }
        } else {
            this.errorMessages = {0:'The container number must be a string'};
        }
        return matches;
    }

     this.getErrorMessages = function()
    {
        return this.errorMessages;
    }

     this.getOwnerCode = function()
    {
        if (this.empty(this.ownerCode)) {
            this.errorMessages.push('You must call validate or isValid first');
        }
        return this.ownerCode;
    }

     this.getProductGroupCode = function()
    {
        if (this.empty(this.productGroupCode)) {
            this.errorMessages.push('You must call validate or isValid first');
        }
        return this.productGroupCode;
    }

     this.getRegistrationDigit = function()
    {
        if (this.empty(this.registrationDigit)) {
            this.errorMessages.push('You must call validate or isValid first');
        }
        return this.registrationDigit;
    }

     this.getCheckDigit = function()
    {
        if (this.empty(this.checkDigit)) {
            this.errorMessages.push('You must call validate or isValid first');
        }
        return this.checkDigit;
    }

     this.generate = function(ownerCode, productGroupCode, from, to)
    {
        //SHS set default values for params
        from = typeof from !== 'undefined' ? from : 0;
        to = typeof to !== 'undefined' ? to : 999999;

        alphabetCode = this.strtoupper(ownerCode + productGroupCode);
        containers_no = [];

        if (this.is_string(alphabetCode) && this.strlen(ownerCode) === 3 && this.strlen(productGroupCode) === 1) {
            containers_no = [];
            current_container_no = '';
            current_container_check_digit = '';

            if ((from >= 0) && (to < 1000000) && ((to - from) > 0)) {
                for(var i = from; i <= to; i++) {
                    current_container_no = alphabetCode + this.str_pad(i, 6, '0', STR_PAD_LEFT);
                    current_container_check_digit = this.createCheckDigit(current_container_no);

                    if (current_container_check_digit < 0) {
                        this.errorMessages.push('Error generating container number at number ' + i);
                        return containers_no;
                    }

                    containers_no[i] = current_container_no + current_container_check_digit;
                }
            } else {
                this.errorMessages.push('Invalid number to generate, minimal is 0 and maximal is 999999');
            }

        } else {
            this.errorMessages.push('Invalid owner code or product group code');
        }

        return containers_no;
    }

     this.createCheckDigit = function(containerNumber)
    {
        checkDigit = -1;
        if (!this.empty(containerNumber) && this.is_string(containerNumber)) {
            matches = this.identify( containerNumber, true );

            if (this.count(matches) !== 4 || (matches[4])) {
                this.errorMessages.push('Invalid container number');
            } else {
                checkDigit = this.buildCheckDigit(matches);
                if (checkDigit < 0) {
                    this.errorMessages.push('Invalid container number');
                }
            }
        } else {
            this.errorMessages.push('Container number must be a string');
        }
        return checkDigit;
    }

     this.clearErrors = function()
    {
        this.errorMessages = [];
    }

     this.buildCheckDigit = function(matches)
    {

        if ((matches[1])) {
            this.ownerCode = this.str_split(matches[1]);
        }
        if ((matches[2])) {
            this.productGroupCode = matches[2];
        }
        if ((matches[3])) {
            this.registrationDigit = this.str_split(matches[3]);
        }
        if ((matches[4])) {
            this.checkDigit = matches[4];
        }

        // convert owner code + product group code to its numerical value
        numericalOwnerCode = [];
        for(var i = 0; i < this.count(this.ownerCode); i++) {
            numericalOwnerCode[i] = this.alphabetNumerical[this.ownerCode[i]];
        }
        numericalOwnerCode.push(this.alphabetNumerical[this.productGroupCode]);

        // merge numerical owner code with registration digit
        numericalCode = this.array_merge(numericalOwnerCode, this.registrationDigit);
        sumDigit = 0;

        // check six-digit registration number and last check digit
        for(var i = 0; i < this.count(numericalCode); i++) {
            sumDigit += numericalCode[i] * Math.pow(2, i);
        }

        sumDigitDiff = Math.floor(sumDigit / 11) * 11;
        checkDigit = sumDigit - sumDigitDiff;
        return (checkDigit == 10) ? 0 : checkDigit;
    }

     this.identify = function(containerNumber, withoutCheckDigit)
    {
        //SHS set default values for params
        withoutCheckDigit = typeof withoutCheckDigit !== 'undefined' ? withoutCheckDigit : false;
        
        this.clearErrors();

        if (withoutCheckDigit) {
            matches = this.preg_match(this.patternWithoutCheckDigit, this.strtoupper(containerNumber));
        } else {
            matches = this.preg_match(this.pattern, this.strtoupper(containerNumber));
        }
        return matches;
    }
    
    //SHS Helper functions
    this.is_string = function(param){
        return typeof param == 'string' ? true : false;
    }
    
    this.preg_match = function(pattern, string){
        var regex = new RegExp(pattern);
        return regex.exec(string);
    }
    
    this.strtoupper = function(string){
        return string.toUpperCase();
    }
    
    this.count = function(array){
        if (array == null) {
            return 0;
        }else{
            return array.length;
        }
    }
    
    this.strlen = function(string){
        return string.length;
    }
    
    // PHPJS Helper functions    Ref: http://phpjs.org          Remove if using PHPJS 
    this.str_split = function(string, split_length) {
        //  discuss at: http://phpjs.org/functions/str_split/
        // original by: Martijn Wieringa
        // improved by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Onno Marsman
        //  revised by: Theriault
        //  revised by: Rafal Kukawski (http://blog.kukawski.pl/)
        //    input by: Bjorn Roesbeke (http://www.bjornroesbeke.be/)
        //   example 1: str_split('Hello Friend', 3);
        //   returns 1: ['Hel', 'lo ', 'Fri', 'end']
      
        if (split_length == null) {
          split_length = 1;
        }
        if (string == null || split_length < 1) {
          return false;
        }
        string += '';
        var chunks = [],
          pos = 0,
          len = string.length;
        while (pos < len) {
          chunks.push(string.slice(pos, pos += split_length));
        }
        
        return chunks;
    }
    
    this.str_pad = function(input, pad_length, pad_string, pad_type) {
        //  discuss at: http://phpjs.org/functions/str_pad/
        // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Michael White (http://getsprink.com)
        //    input by: Marco van Oort
        // bugfixed by: Brett Zamir (http://brett-zamir.me)
        //   example 1: str_pad('Kevin van Zonneveld', 30, '-=', 'STR_PAD_LEFT');
        //   returns 1: '-=-=-=-=-=-Kevin van Zonneveld'
        //   example 2: str_pad('Kevin van Zonneveld', 30, '-', 'STR_PAD_BOTH');
        //   returns 2: '------Kevin van Zonneveld-----'
      
        var half = '',
          pad_to_go;
      
        var str_pad_repeater = function(s, len) {
          var collect = '',
            i;
      
          while (collect.length < len) {
            collect += s;
          }
          collect = collect.substr(0, len);
      
          return collect;
        };
      
        input += '';
        pad_string = pad_string !== undefined ? pad_string : ' ';
      
        if (pad_type !== 'STR_PAD_LEFT' && pad_type !== 'STR_PAD_RIGHT' && pad_type !== 'STR_PAD_BOTH') {
          pad_type = 'STR_PAD_RIGHT';
        }
        if ((pad_to_go = pad_length - input.length) > 0) {
          if (pad_type === 'STR_PAD_LEFT') {
            input = str_pad_repeater(pad_string, pad_to_go) + input;
          } else if (pad_type === 'STR_PAD_RIGHT') {
            input = input + str_pad_repeater(pad_string, pad_to_go);
          } else if (pad_type === 'STR_PAD_BOTH') {
            half = str_pad_repeater(pad_string, Math.ceil(pad_to_go / 2));
            input = half + input + half;
            input = input.substr(0, pad_length);
          }
        }
        
        return input;
    }
    
    this.array_merge = function() {
        //  discuss at: http://phpjs.org/functions/array_merge/
        // original by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Nate
        // bugfixed by: Brett Zamir (http://brett-zamir.me)
        //    input by: josh
        //   example 1: arr1 = {"color": "red", 0: 2, 1: 4}
        //   example 1: arr2 = {0: "a", 1: "b", "color": "green", "shape": "trapezoid", 2: 4}
        //   example 1: array_merge(arr1, arr2)
        //   returns 1: {"color": "green", 0: 2, 1: 4, 2: "a", 3: "b", "shape": "trapezoid", 4: 4}
        //   example 2: arr1 = []
        //   example 2: arr2 = {1: "data"}
        //   example 2: array_merge(arr1, arr2)
        //   returns 2: {0: "data"}
      
        var args = Array.prototype.slice.call(arguments),
          argl = args.length,
          arg,
          retObj = {},
          k = '',
          argil = 0,
          j = 0,
          i = 0,
          ct = 0,
          toStr = Object.prototype.toString,
          retArr = true;
      
        for (i = 0; i < argl; i++) {
          if (toStr.call(args[i]) !== '[object Array]') {
            retArr = false;
            break;
          }
        }
      
        if (retArr) {
          retArr = [];
          for (i = 0; i < argl; i++) {
            retArr = retArr.concat(args[i]);
          }
          return retArr;
        }
      
        for (i = 0, ct = 0; i < argl; i++) {
          arg = args[i];
          if (toStr.call(arg) === '[object Array]') {
            for (j = 0, argil = arg.length; j < argil; j++) {
              retObj[ct++] = arg[j];
            }
          } else {
            for (k in arg) {
              if (arg.hasOwnProperty(k)) {
                if (parseInt(k, 10) + '' === k) {
                  retObj[ct++] = arg[k];
                } else {
                  retObj[k] = arg[k];
                }
              }
            }
          }
        }
        return retObj;
    }
    
    this.empty = function(mixed_var) {
        //  discuss at: http://phpjs.org/functions/empty/
        // original by: Philippe Baumann
        //    input by: Onno Marsman
        //    input by: LH
        //    input by: Stoyan Kyosev (http://www.svest.org/)
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Onno Marsman
        // improved by: Francesco
        // improved by: Marc Jansen
        // improved by: Rafal Kukawski
        //   example 1: empty(null);
        //   returns 1: true
        //   example 2: empty(undefined);
        //   returns 2: true
        //   example 3: empty([]);
        //   returns 3: true
        //   example 4: empty({});
        //   returns 4: true
        //   example 5: empty({'aFunc' : function () { alert('humpty'); } });
        //   returns 5: false
      
        var undef, key, i, len;
        var emptyValues = [undef, null, false, 0, '', '0'];
      
        for (i = 0, len = emptyValues.length; i < len; i++) {
          if (mixed_var === emptyValues[i]) {
            return true;
          }
        }
      
        if (typeof mixed_var === 'object') {
          for (key in mixed_var) {
            //if (mixed_var.hasOwnProperty(key)) {
            return false;
            //}
          }
          return true;
        }
      
        return false;
    }
}
function filterAngka(input) {
    input.value = input.value.replace(/\D/g, ''); // Hanya angka, hapus selain digit
}

</script>
</body>
</html>
