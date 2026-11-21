<?php
require_once(APPPATH.'libraries/tcpdf/tcpdf.php');

class Tcpdf extends TCPDF {
    public function __construct() {
        parent::__construct();
    }
}