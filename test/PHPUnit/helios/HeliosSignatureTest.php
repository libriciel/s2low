<?php 

require_once(__DIR__."/../../../config/config.php");
require_once(__DIR__."/../../../class/helios/HeliosSignature.class.php");

class HeliosSignatureTest extends PHPUnit_Framework_TestCase {
	
	public function testGetInfoForSignature(){
		$helios_signature = new HeliosSignature();
		$info = $helios_signature->getInfoForSignature(__DIR__."/fixtures/pes_aller.xml");
		
		$this->assertEquals("BORD1093357910", $info['bordereau_id']);
		$this->assertEquals("f686479857d4fb5dd1621a274d77c848c5a7342a", $info['bordereau_hash']);
		$this->assertTrue($info['isbordereau']);
	}
	
	public function testGetInfoForSignatureNoIDInBorderau(){
		$helios_signature = new HeliosSignature();
		$info = $helios_signature->getInfoForSignature(__DIR__."/fixtures/pes_aller_no_id_in_bordereau.xml");
		
		$this->assertEquals("c78a9729c1eb89ff9b47401bd7cd9d7e", $info['bordereau_id']);
		//b154007082d85457480a716b350b9452df35ac7b avec xmlstarlet
		//af7231795b1c9c3b050895e3e0444d2f9ec7cac6 avec PHP DOM C14N 
		$this->assertEquals("b154007082d85457480a716b350b9452df35ac7b", $info['bordereau_hash']);
		$this->assertFalse($info['isbordereau']);
	}
	
	public function testGetInfoForSignatureNoIDAtAll(){
		$helios_signature = new HeliosSignature();
		$this->setExpectedException("Exception");
		$info = $helios_signature->getInfoForSignature(__DIR__."/fixtures/pes_aller_no_id_at_all.xml");
	}
	
	public function testGetInfoForSignatureRecette(){
		$helios_signature = new HeliosSignature();
		$info = $helios_signature->getInfoForSignature(__DIR__."/fixtures/pes_aller_recette.xml");
		$this->assertEquals("BORD1093357910", $info['bordereau_id']);
		$this->assertEquals("f686479857d4fb5dd1621a274d77c848c5a7342a", $info['bordereau_hash']);
		$this->assertTrue($info['isbordereau']);
	}
	
	public function testGetInfoForSignatureNoRecetteNoDepense(){
		$helios_signature = new HeliosSignature();
		$this->setExpectedException("Exception");
		$info = $helios_signature->getInfoForSignature(__DIR__."/fixtures/pes_aller_no_recette_no_depense.xml");
	}
	
	public function testInjectSignature(){
		$helios_signature = new HeliosSignature();
		$new_pes = $helios_signature->injectSignature(__DIR__."/fixtures/pes_aller.xml", base64_encode("<a><test>value</test></a>"),true);
		$xml  = simplexml_load_string($new_pes);
		$this->assertEquals("value", strval($xml->PES_DepenseAller->Bordereau->test));		
	}
	
	public function testInjectSignatureNoBordereauID(){
		$helios_signature = new HeliosSignature();
		$new_pes = $helios_signature->injectSignature(__DIR__."/fixtures/pes_aller_no_id_in_bordereau.xml", base64_encode("<a><test>value</test></a>"),false);
		$xml  = simplexml_load_string($new_pes);
		$this->assertEquals("value", strval($xml->test));
	}
	
}