<?php

require_once("Zend/Pdf.php");



class TamponPDFTest extends PHPUnit_Framework_TestCase {
	
	public function testGetTampon(){

		$pdf = Zend_Pdf::load(__DIR__."/fixtures/vide.pdf");

		$tamponPDF = new TamponPDF($pdf);

		$tamponPDF->setText(array("Texte de test","ligne2"));

		$file = $tamponPDF->getFileAsString();

		$this->assertEquals(file_get_contents(__DIR__."/fixtures/vide-tampon.pdf"),$file);

	}

	public function testZendCannotLoad(){
		$this->setExpectedException("Exception","Cross-reference streams are not supported yet.");
		Zend_Pdf::load(__DIR__."/fixtures/file-with-cross-reference.pdf");
	}

	
	

}