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
            <option value="TRUCKING ONLY">TRUCKING ONLY</option>
        </select>
    </div>
  </div>
  <!-- Dynamic Fields Container -->
<div id="formFields" class="DynamicForm" style="display: none;">


  <!-- ROW 3 -->
<div class="form-group-row">
				<div class="form-group">
					<label for="etd_pol">TANGGAL PICKUP</label>
					<input id="etd_pol" name="etd_pol" type="date"/>
				</div>
				<div class="form-group">
					<label for="kurs_d">KURS PAJAK</label>
					<input id="kurs_d" name="kurs_d" type="text" class="form-control" oninput="filterAngka(this)">
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
			$('#etd_pol').val(data.etd_jkt);
			$('#kurs_d').val(data.kurs);
			// Type of Transaction - important: trigger change
			$('#TypeOfTransaction').val(data.type_of_trans).trigger('change'); // so renderFormFields() works
			// Vessel + Voyage
			document.querySelector('.modal-container').classList.add('edit-mode');
			$('.modal-title').text('Detail Job '+data.kode_ref);
		  }		
	});
});
function renderFormFields() {
  const selectedValue = document.getElementById("TypeOfTransaction").value;
  const toolbarSearchDiv = document.querySelector(".DynamicForm");
  if (toolbarSearchDiv) {
    if (selectedValue === "TRUCKING ONLY") {
      toolbarSearchDiv.style.display = "block";
    } else {
      toolbarSearchDiv.style.display = "none";
    }
  }
}



function filterAngka(input) {
    input.value = input.value.replace(/\D/g, ''); // Hanya angka, hapus selain digit
}

</script>
</body>
</html>
