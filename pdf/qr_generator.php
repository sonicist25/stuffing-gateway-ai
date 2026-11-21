<?php
require_once("phpqrcode/qrlib.php");
header("Content-Type: image/png");
QRcode::png($_GET['code']);
