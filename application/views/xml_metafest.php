<?php
header('Content-type: text/xml; charset=utf-8');
header('Content-Type: application/xml;');
header('Content-Disposition: attachment; filename='.time().'.xml;');
echo $xml;