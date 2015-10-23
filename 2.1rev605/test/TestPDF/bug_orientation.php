<?php 

require_once("../../config/config.php");


set_include_path(dirname(__FILE__)."/../../ext/" . PATH_SEPARATOR .   get_include_path());

require_once(dirname(__FILE__)."/../../class/TamponPDF.class.php");

$pdf1 = Zend_Pdf::load("2010_07_01_36.pdf")->pages[0];

$pdf2 = Zend_Pdf::load("specification.pdf")->pages[0];

echo "PDF 1 : " . $pdf1->getWidth() . " * " . $pdf1->getHeight() . "<br/>";
echo "PDF 2 : " . $pdf2->getWidth() . " * " . $pdf2->getHeight() . "<br/>";

print_r(Zend_Pdf::load("2010_07_01_36.pdf")->properties);
print_r(Zend_Pdf::load("specification.pdf")->properties);
