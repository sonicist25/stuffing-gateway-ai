


$(function(){
  $('#year').combodate({
        value: new Date(),
        minYear: 2012,
        maxYear: moment().format('YYYY'),
        customClass: 'form-control'
  });

  $('#month').combodate({
        value: new Date(),
        customClass: 'form-control'
  });
    $('#date').combodate({
          value: new Date(),
          minYear: 2012,
          maxYear: moment().format('YYYY'),
          customClass: 'form-control'
    });




    $('#etd_jkt_input').combodate({
          value: new Date(),
          minYear: 2012,
          maxYear: moment().format('YYYY'),
          customClass: 'form-control'
    });
    $('#eta_input').combodate({
          value: new Date(),
          minYear: 2012,
          maxYear: moment().format('YYYY'),
          customClass: 'form-control'
    });
});

$(document).on("click", ".mod", function () {
     var city_id = $(this).data('city_id');
     var city_name = $(this).data('city_name');
     var year = $(this).data('year');
     $(".modal-body #city_id").val( city_id );
     $(".modal-body #city_name").val( city_name );
     $(".modal-body #year").val( year );
     $(".modal-body .year").val( year );
     $(".modal-body .year").attr('disabled','disabled');

});

/**
 * @author Kishor Mali
 */


jQuery(document).ready(function(){

	jQuery(document).on("click", ".deleteScheduleVia", function(){

		var id = $(this).data("id"),
			hitURL =  window.location.origin+ "/schedule/ScheduleVia/deleteScheduleVia",
			currentRow = $(this);

		var confirmation = confirm("Are you sure to delete this Schedule Via ?");

		if(confirmation)
		{
			jQuery.ajax({
			type : "POST",
			dataType : "json",
			url : hitURL,
			data : { id : id }
			}).done(function(data){
				console.log(data);
				currentRow.parents('tr').remove();
				if(data.status = true) { alert("Schedule Via successfully deleted"); }
				else if(data.status = false) { alert("Schedule Via deletion failed"); }
				else { alert("Access denied..!"); }
			});
		}
	});


});
