<?php

class HeliosPESValidationTest extends PHPUnit_Framework_TestCase {
	
	public function testValidPesAller(){
		$heliosPESValidation  = new HeliosPESValidation(HELIOS_XSD_PATH);
		$this->assertTrue($heliosPESValidation->validate(file_get_contents(__DIR__."/fixtures/pes_aller_ok.xml")));
	}
	
	public function testNotValidPesAller(){
		$heliosPESValidation  = new HeliosPESValidation(HELIOS_XSD_PATH);
		$this->assertFalse($heliosPESValidation->validate(file_get_contents(__DIR__."/fixtures/pes_aller.xml")));
	}
	
	public function testGetError(){
		$heliosPESValidation  = new HeliosPESValidation(HELIOS_XSD_PATH);
		$heliosPESValidation->validate(file_get_contents(__DIR__."/fixtures/pes_aller.xml"));
		$last_error = $heliosPESValidation->getLastError();
		$this->assertEquals(1837,$last_error[1]->code);
	}

	//http://stackoverflow.com/questions/29953032/large-number-failing-validation-as-type-xsinteger
	public function testPESallerSigner(){
		$heliosPESValidation  = new HeliosPESValidation(HELIOS_XSD_PATH);
		$result = $heliosPESValidation->validate(file_get_contents(__DIR__."/fixtures/pes_signe.xml"));
		if (! $result){
			print_r($heliosPESValidation->getLastError());
		}
		$this->assertTrue($result);
	}

	
}