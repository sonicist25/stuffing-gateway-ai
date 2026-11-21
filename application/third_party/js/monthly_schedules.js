jQuery(document).ready(function(){

	jQuery(document).on("change", "#selectYear", function(){

			url = baseURL + "selectYear",
      jQuery.ajax({
			type : "POST",
			dataType : "json",
			url : url,
			data : { id : id}
    }).success(function(data){
				console.log(data);
        alert("successfully");
      });

  });
  });
