<?php 

require_once("../../config/config.php");


set_include_path(dirname(__FILE__)."/../../ext/" . PATH_SEPARATOR .   get_include_path());

require_once(dirname(__FILE__)."/../../class/TamponPDF.class.php");
		
$pdf = Zend_Pdf::load("pdf17o.pdf");

$tampon = new TamponPDF($pdf);
$tampon->setText(array("Envoyé en préfécture le 01/01/2010",
"Reçu en préfécture le 02/01/2010",
"Affiché le " . date("d/m/Y")));
$tampon->render();


