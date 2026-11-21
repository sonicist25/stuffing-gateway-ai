
        var windowURL = window.location.href;
        pageURL = windowURL.substring(0, windowURL.lastIndexOf('/'));
        var x= $('a[href="'+pageURL+'"]');
            x.addClass('active');
            x.parent().addClass('active');
        var y= $('a[href="'+windowURL+'"]');
            y.addClass('active');
            y.parent().addClass('active');
     
      $(function () {
        $('.datatable').DataTable();

      })

      $('.select2').select2()


      $('.datepicker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd',
        viewformat: 'dd M',
        maxDate: "+1m"
      }).val();
