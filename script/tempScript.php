<?php

require_once(__DIR__ . "/../init/init.php");

$padesValid = new PadesValid("http://pades-valid:8080/",__DIR__."/../lib/fixtures/validca/");
//$padesValid->validate("01_AE_lot5_CRA.pdf");


$padesValid->validate("/var/www/s2low/test/PHPUnit/class/fixtures/signature-pades/Courrier_signe.pdf");