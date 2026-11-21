<!DOCTYPE html>
<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
$('a[href^="#"]').on('click', function(event) {

    var target = $(this.getAttribute('href'));

    if( target.length ) {
        event.preventDefault();
        $('html, body').stop().animate({
            scrollTop: target.offset().top
        }, 100);
    }

});


</script>
 <style>
body, html, .main {
  height: 100%;
}

section {
  min-height: 100%;
}
</style>
</head>
<body>

<a href="#bottom" id="top">Click to scroll down</a>



<a href="#top" id="bottom">Click to scroll up</a>


</body>
</html>



