<?php

class XadesSignatureTest extends PHPUnit_Framework_TestCase {


	public function testSignFileNotExists(){
		$tmp_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$tmp_file_2 = sys_get_temp_dir()."/".uniqid("phpunit");
		$xmlSecWrapper = new XadesSignature();
		$this->setExpectedException("Exception","failed to load external entity");
		$xmlSecWrapper->sign($tmp_file,__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$tmp_file_2);
	}

	public function testSign(){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xmlSecWrapper = new XadesSignature();
		$xmlSecWrapper->sign(__DIR__."/fixtures/test.xml",__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$signed_file);
		$this->assertTrue(file_exists($signed_file));
		$this->assertTrue($xmlSecWrapper->verify($signed_file,__DIR__."/fixtures/autorite_a_effacer-cert.pem"));
	}

	public function testSignWithoutDocumentElementId(){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = new XadesSignature();
		$this->setExpectedException("Exception","Le document XML ne contient pas d'Id");
		$xadesSignature->sign(__DIR__."/fixtures/test-no-id.xml",__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$signed_file);
	}


	public function testBadPassword(){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xmlSecWrapper = new XadesSignature();
		$this->setExpectedException("Exception","Error: failed to load pkcs12 key from");
		$xmlSecWrapper->sign(__DIR__."/fixtures/test.xml",__DIR__."/fixtures/robert_petitpoids.p12","bad password",$signed_file);
	}

}
