<?php

class HeliosSignatureTechniqueTest extends S2lowTestCase {

	private $transaction_id;

	private function getXadesSignature(){
		return new XadesSignature(XMLSEC1_PATH,new PKCS12(),new X509Certificate());
	}

	private function getHeliosSignatureTechnique(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		return new HeliosSignatureTechnique($heliosTransactionSQL, "/tmp/",$this->getXadesSignature());
	}

	private function getFilePathInHeliosUplload(){
		$pes_aller = __DIR__."/../../helios/fixtures/pes_aller_ok.xml";
		return "/tmp/".sha1_file($pes_aller);
	}

	private function sign(){
		$xadesSignatureProperties = new XadesSignatureProperties();
		$xadesSignatureProperties->claimedRole = "Rôle de test";
		$xadesSignatureProperties->countryName = "France";
		$xadesSignatureProperties->postalCode = "69003";
		$xadesSignatureProperties->city = "Lyon";
		$this->getHeliosSignatureTechnique()->sign($this->transaction_id, __DIR__."/../../lib/fixtures/robert_petitpoids.p12","robert_petitpoids",$xadesSignatureProperties);
	}

	public function setUp(){
		parent::setUp();
		$pes_aller = __DIR__."/../../helios/fixtures/pes_aller_ok.xml";
		copy($pes_aller,"/tmp/".sha1_file($pes_aller));

		$heliosControler = new HeliosController($this->getObjectInstancier());
		$heliosControler->setHeliosFilesUploadRoot("/tmp/");
		$this->transaction_id = $heliosControler->importFile(8,$pes_aller,"pes_aller.xml");
	}

	public function testSign(){
		$this->sign();
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$info = $heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertTrue($info['signature_technique']);
		$this->assertEquals($info['sha1'],sha1_file("/tmp/{$info['sha1']}"));
		$this->assertEquals($info['file_size'],filesize("/tmp/{$info['sha1']}"));
		$this->assertTrue($this->getXadesSignature()->verify("/tmp/{$info['sha1']}",  __DIR__."/../../lib/fixtures/autorite_a_effacer-cert.pem"));
	}

	public function testSignModif(){
		$file = $this->getFilePathInHeliosUplload();
		file_put_contents($file,"toto");
		$this->setExpectedException("Exception","Le fichier a été modifé depuis son postage sur la plateforme");
		$this->sign();
	}
	
}