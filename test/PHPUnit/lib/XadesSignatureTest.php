<?php

class XadesSignatureTest extends PHPUnit_Framework_TestCase {

	public function testSignFileNotExists(){
		$tmp_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$this->setExpectedException("Exception","failed to load external entity");
		$this->sign($tmp_file);
	}

	/**
	 * @param $file_to_sign
	 * @return string
	 * @throws XadesSignatureHasSignatureException
	 */
	private function sign($file_to_sign){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = $this->getXadesSignature();
		$xadesSignature->sign($file_to_sign,__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$signed_file,$this->getXadesSignatureProperties());
		return $signed_file;
	}

	private function getXadesSignature(){
		$xadesSignature = new XadesSignature(
			XMLSEC1_PATH,
			new PKCS12(),
			new X509Certificate(),
			__DIR__ . "/fixtures/validca/"
		);
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

	public function testSign(){
		$signed_file = $this->sign(__DIR__."/fixtures/test.xml");
		$this->verify($signed_file);
	}

	private function verify($file_to_verify){
		$xadesSignature = $this->getXadesSignature();
		$this->assertTrue($xadesSignature->verify($file_to_verify));
	}

	public function testSignWithoutDocumentElementId(){
		$this->setExpectedException("XadesSignatureNoIDException","Le document XML ne contient pas d'Id");
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

	/**
	 * @throws XadesSignatureHasSignatureException
	 */
	public function testOutputFileNotWritable(){
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$xadesSignature = $this->getXadesSignature();
		$this->setExpectedException("Exception","Erreur (1) lors de la signature technique");
		$xadesSignature->sign(
			__DIR__."/fixtures/test.xml",
			__DIR__."/fixtures/robert_petitpoids.p12",
			"robert_petitpoids",
			$testStreamUrl."/signed.xml",
			$this->getXadesSignatureProperties())
		;
		$xadesSignature->sign(
			__DIR__."/fixtures/test.xml",
			__DIR__."/../fixtures/timestamp_certificates/tedetis_timestamp_cert.pem.p12",
			"",
			$testStreamUrl."/signed.xml",
			$this->getXadesSignatureProperties())
		;
	}

	public function testVerifyNOCA(){
		$xadesSignature = $this->getXadesSignature();
		$this->assertTrue($xadesSignature->verify(__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml"));
	}

	public function testHasSignature(){
		$this->setExpectedException("XadesSignatureHasSignatureException");
		$signed_file = $this->sign(__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
		$this->verify($signed_file);
	}

	public function testVerifSignatureNotGlobale() {
		$this->verify(__DIR__ . "/fixtures/signature_bordereau2.xml");
	}

	public function testVerifSignatureNotGlobaleBad() {
		$xadesSignature = $this->getXadesSignature();
		$this->assertFalse($xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau_bad.xml"));
	}

	public function testDeleteSignature(){
		$file = __DIR__."/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml";

		$result =  "/tmp/result.xml";
		$xadesSignature = $this->getXadesSignature();
		$this->assertTrue($xadesSignature->isSigned($file));
		$xadesSignature->deleteSignature($file,$result);
		$this->assertFalse($xadesSignature->isSigned($result));
	}




}
