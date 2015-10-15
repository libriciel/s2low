<?php

class XadesSignatureTest extends PHPUnit_Framework_TestCase {

	private function getXadesSignature(){
		$xadesSignature = new XadesSignature(XMLSEC1_PATH,new PKCS12(),new X509Certificate());
		return $xadesSignature;
	}

	private function getXadesSignatureProperties(){
		$xadesSignatureProperties = new XadesSignatureProperties();
		$xadesSignatureProperties->city = "Paris";
		$xadesSignatureProperties->postalCode = "75008";
		$xadesSignatureProperties->countryName = "France";
		$xadesSignatureProperties->claimedRole = "Test Tiers de télétransmission";
		return $xadesSignatureProperties;
	}

	private function sign($file_to_sign){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = $this->getXadesSignature();
		$xadesSignature->sign($file_to_sign,__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$signed_file,$this->getXadesSignatureProperties());
		return $signed_file;
	}

	private function verify($file_to_verify){
		$xadesSignature = $this->getXadesSignature();
		$this->assertTrue($xadesSignature->verify($file_to_verify,__DIR__."/fixtures/autorite_a_effacer-cert.pem"));
	}


	public function testSignFileNotExists(){
		$tmp_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$this->setExpectedException("Exception","failed to load external entity");
		$this->sign($tmp_file);
	}

	public function testSign(){
		$signed_file = $this->sign(__DIR__."/fixtures/test.xml");
		$this->verify($signed_file);
	}

	public function testSignWithoutDocumentElementId(){
		$this->setExpectedException("Exception","Le document XML ne contient pas d'Id");
		$this->sign(__DIR__."/fixtures/test-no-id.xml");
	}

	public function testBadPassword(){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = $this->getXadesSignature();
		$this->setExpectedException("Exception","Impossible de lire le certificat PKCS#12");
		$xadesSignature->sign(__DIR__."/fixtures/test.xml",__DIR__."/fixtures/robert_petitpoids.p12","bad password",$signed_file,$this->getXadesSignatureProperties());
	}

	public function testSignPES_Aller(){
		$signed_file = $this->sign(__DIR__."/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml");
		$this->verify($signed_file);
	}

	public function testTargetSignature(){
		$signed_file = $this->sign(__DIR__."/fixtures/test.xml");
		$xml = simplexml_load_file($signed_file);
		$id = strval($xml->children(XadesSignature::NS_DS_URI)->Signature->attributes()->Id);
		$targetId = strval($xml->children(XadesSignature::NS_DS_URI)->Signature->Object->children(XadesSignature::NS_XAD_URI)->QualifyingProperties->attributes()["Target"]);
		$this->assertEquals("#$id",$targetId);
	}

	public function testOutputFileNotWritable(){
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$xadesSignature = $this->getXadesSignature();
		$this->setExpectedException("Exception","Erreur (1) lors de la signature technique");
		$xadesSignature->sign(__DIR__."/fixtures/test.xml",__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$testStreamUrl."/signed.xml",$this->getXadesSignatureProperties());
	}
}
