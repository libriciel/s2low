<?php

class HeliosSignatureTechniqueTest extends S2lowTestCase {

	private $transaction_id;

	public function setUp(){
		parent::setUp();
		$this->transaction_id = $this->importFile(__DIR__."/../../helios/fixtures/pes_aller_ok.xml");
	}

	private function importFile($pes_aller){
		copy($pes_aller,"/tmp/".sha1_file($pes_aller));
		$heliosControler = new HeliosController($this->getObjectInstancier());
		$heliosControler->setHeliosFilesUploadRoot("/tmp/");
		return $heliosControler->importFile(8,$pes_aller,"pes_aller.xml");
	}

	private function getXadesSignature(){
		return new XadesSignature(XMLSEC1_PATH, new PKCS12(), new X509Certificate(), __DIR__ . "/../../lib/fixtures/validca/");
	}

	private function getHeliosSignatureTechnique(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		return new HeliosSignatureTechnique($heliosTransactionSQL, "/tmp/",$this->getXadesSignature());
	}

	private function getFilePathInHeliosUplload(){
		$pes_aller = __DIR__."/../../helios/fixtures/pes_aller_ok.xml";
		return "/tmp/".sha1_file($pes_aller);
	}

	private function getXadesSignatureProperties(){
		$xadesSignatureProperties = new XadesSignatureProperties();
		$xadesSignatureProperties->claimedRole = "Rôle de test";
		$xadesSignatureProperties->countryName = "France";
		$xadesSignatureProperties->postalCode = "69003";
		$xadesSignatureProperties->city = "Lyon";
		return $xadesSignatureProperties;
	}

	private function sign(){
		$this->getHeliosSignatureTechnique()->sign(
			$this->transaction_id,
			__DIR__."/../../lib/fixtures/robert_petitpoids.p12",
			"robert_petitpoids",
			$this->getXadesSignatureProperties());
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
		$this->setExpectedException("UnrecoverableHeliosSignatureTechniqueException","Le fichier a été modifé depuis son postage sur la plateforme");
		$this->sign();
	}

	public function testDejaSigne(){
		$transaction_id = $this->importFile(__DIR__."/../../lib/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
		$this->getHeliosSignatureTechnique()->sign(
			$transaction_id,
			__DIR__."/../../lib/fixtures/robert_petitpoids.p12",
			"robert_petitpoids",
			$this->getXadesSignatureProperties());
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

		$info = $heliosTransactionSQL->getInfo($transaction_id);
		$this->assertTrue($info['signature_technique']);
		$this->assertTrue($this->getXadesSignature()->verify("/tmp/{$info['sha1']}",  __DIR__."/../../lib/fixtures/autorite_a_effacer-cert.pem"));
	}

	public function testDejaSigneBadSignature(){
		$transaction_id = $this->importFile(__DIR__."/../../lib/fixtures/HELIOS_SIMU_ALR2_bad_signature.xml");
		$this->setExpectedException("UnrecoverableHeliosSignatureTechniqueException","Le fichier est déjà signé, mais la signature est invalide");
		$this->getHeliosSignatureTechnique()->sign(
			$transaction_id,
			__DIR__."/../../lib/fixtures/robert_petitpoids.p12",
			"robert_petitpoids",
			$this->getXadesSignatureProperties());
	}

}