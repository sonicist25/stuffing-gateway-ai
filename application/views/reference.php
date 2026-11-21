<?php
echo
div('id="main-table"',array('<center><table id="'.$table.'"></table></center><div id="pager"></div>'));
if($table=='REF_PDT'||$table=='REF_CTY') :
echo '
<script type="text/javascript" >
var ref_crt = {};
ref_crt.results = '.$country.';
ref_crt.total = ref_crt.results.length;
</script>';
endif;
if($table=='REF_PLD') :
echo '
<script type="text/javascript" >
var ref_cty = {};
ref_cty.results = '.$city.';
ref_cty.total = ref_cty.results.length;
</script>';
endif;
if($table=='REF_SHP_EXP'||$table=='REF_SLS') :
echo '
<script type="text/javascript" >
var udata = '.$user.';
get_user=function(id) {if (id==0) {return ""} var fullname=udata[id];if (fullname==undefined) {return ""} else {return fullname}}
</script>';
endif;
if($table=='REF_USR') :
echo '
<script type="text/javascript" >
var udata = '.$user.';
var ldata = '.$level.';
get_user=function(id) {if (id==0) {return ""} var fullname=udata[id];if (fullname==undefined) {return ""} else {return fullname}}
get_level=function(idl) {var lvl=ldata[idl];if (lvl==undefined) {return ""} else {return lvl}}
</script>';
endif;