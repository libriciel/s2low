<?php

class HeliosControllerTest extends S2lowTestCase {

	/**
	 * @var HeliosController
	 */
	private $heliosController;

	private $testStreamUrl;

	protected function setUp(){
		parent::setUp();

		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		mkdir($this->testStreamUrl."/helios");

		$tmp_file = $this->testStreamUrl."/pes_aller.xml";
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));

		$_FILES['enveloppe'] = array('name'=>'pes_aller.xml','tmp_name'=>$tmp_file,'size'=>filesize($tmp_file));
		$this->setUserAuthentification();
		$this->heliosController = new HeliosController($this->getObjectInstancier());
	}

	private function expectedError($message){
		$message = htmlspecialchars($message);
		$this->expectOutputRegex("#$message#");
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportAction(){
		$this->setExpectedException("Exception");
		$this->heliosController->importAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportAPIAction(){
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$this->heliosController->importAPIAction();
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$output = $this->getActualOutput();
		$xml = simplexml_load_string($output);
		$transaction_id = $xml->id;
		$info = $heliosTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$info['last_status_id']);
		$info_wf = $heliosTransactionsSQL->getWorkflow($transaction_id);
		$this->assertEquals(1,$info_wf[0]['status_id']);
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportMustSign(){
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$_POST['must_signed'] = true;
		$this->heliosController->importAPIAction();
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$output = $this->getActualOutput();
		$xml = simplexml_load_string($output);
		$transaction_id = $xml->id;
		$info = $heliosTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::ATTENTE_SIGNEE,$info['last_status_id']);
		$info_wf = $heliosTransactionsSQL->getWorkflow($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::ATTENTE_SIGNEE,$info_wf[0]['status_id']);
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportMustPoster(){
		$userPermsSQL = new UsersPermsSQL($this->getSQLQuery());
		$userPermsSQL->setPerms(2,8,'CS');
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$this->heliosController->importAPIAction();
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$output = $this->getActualOutput();
		$xml = simplexml_load_string($output);
		$transaction_id = $xml->id;
		$info = $heliosTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::ATTENTE_POSTEE,$info['last_status_id']);
		$info_wf = $heliosTransactionsSQL->getWorkflow($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::ATTENTE_POSTEE,$info_wf[0]['status_id']);
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportApiError(){
		unset($_FILES);
		$this->expectedError("Échec lors du téléchargement du fichier");
		$this->heliosController->importAPIAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testBadFile(){
		$tmp_file = $this->testStreamUrl."/pes_aller_not_exist.xml";
		$_FILES['enveloppe']['tmp_name'] = $tmp_file;
		$this->expectedError("Échec lors du téléchargement du fichier");
		$this->heliosController->importAPIAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDuplicate(){
		$tmp_file = $this->testStreamUrl."/pes_aller.xml";
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$this->heliosController->importAPIAction();
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));
		$message = htmlspecialchars("doublon détecté. Ce fichier a déjà été posté.");
		$this->expectOutputRegex("#$message>#");
		$this->heliosController->importAPIAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testMaxSize(){
		$this->heliosController->setHeliosMaxUploadSize(0);
		$this->expectedError("Taille de fichier supérieur à la limite autorisée");
		$this->heliosController->importAPIAction();
	}

	public function testUpdateSiretFromPESAllerNoFile(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$heliosTransactionSQL->create("pes1.xml","42",8,1,42,12);

		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#le fichier PES ALLER n'est pas disponible#");
		$heliosController->updateSiretFromPESAller();
	}

	public function testUpdateSiretFromPESAllerNotXML(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

		$heliosTransactionSQL->create("pes1.xml","42",8,1,42,12);
		file_put_contents($this->testStreamUrl."/helios/pes1.xml","<test/>");
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#le fichier PES ALLER ne contient pas de SIRET#");
		$heliosController->updateSiretFromPESAller();
	}

	public function testUpdateSiretFromPESAllerOK(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

		$heliosTransactionSQL->create("pes1.xml","42",8,1,42,12);
		file_put_contents($this->testStreamUrl."/helios/pes1.xml",file_get_contents(__DIR__."/fixtures/pes_aller.xml"));
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#siret 12345678912345 ajouté à la collectivite 1#");
		$heliosController->updateSiretFromPESAller();
		$authoritySiretSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$list = $authoritySiretSQL->siretList(1);
		$this->assertEquals("12345678912345",$list[0]['siret']);

	}


}
