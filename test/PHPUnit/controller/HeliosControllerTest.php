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

        $_FILES['enveloppe'] = array(
            'name'=>'pes_aller.xml',
            'tmp_name'=>$tmp_file,
            'size'=>filesize($tmp_file),
            'error' => UPLOAD_ERR_OK
        );

        $rgsConnexion = $this->getMockBuilder('RgsConnexion')->disableOriginalConstructor()->getMock();
        $rgsConnexion->expects($this->any())->method('isRgsConnexion')->willReturn(true);

        $this->getObjectInstancier()->{'RgsConnexion'} = $rgsConnexion;
        $this->getObjectInstancier()->set("helios_files_upload_root",$this->testStreamUrl);

        $this->setUserAuthentification();
        $this->heliosController = new HeliosController($this->getObjectInstancier());
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
		$this->importAPI();
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$output = $this->getActualOutput();
		echo $output;
		$xml = simplexml_load_string($output);
		$transaction_id = $xml->id;

		$info = $heliosTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$info['last_status_id']);
		$info_wf = $heliosTransactionsSQL->getWorkflow($transaction_id);
		$this->assertEquals(1,$info_wf[0]['status_id']);
	}

	private function importAPI()
	{
		try {
			$this->heliosController->importAPIAction();
		} catch (Exception $e) {

		}
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportMustSign(){
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$_POST['must_signed'] = true;
		$this->importAPI();
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
		$this->importAPI();
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
		$this->importAPI();
	}

	private function expectedError($message){
		$message = htmlspecialchars($message);
		$this->expectOutputRegex("#$message#");
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testBadFile(){
		$tmp_file = $this->testStreamUrl."/pes_aller_not_exist.xml";
		$_FILES['enveloppe']['tmp_name'] = $tmp_file;
		$this->expectedError("Échec lors du téléchargement du fichier");
		$this->importAPI();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDuplicate(){
		$tmp_file = $this->testStreamUrl."/pes_aller.xml";
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$this->importAPI();
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));
		$message = htmlspecialchars("doublon détecté. Ce fichier a déjà été posté.");
		$this->expectOutputRegex("#$message>#");
		$this->importAPI();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testMaxSize(){
		$this->heliosController->setHeliosMaxUploadSize(0);
		$this->expectedError("Taille de fichier supérieur à la limite autorisée");
		$this->importAPI();
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

		$heliosTransactionSQL->create("pes1.xml", "d8d1a344f31de311d32134064695df85f3801897", 8, 1, 42, 12);
		file_put_contents($this->testStreamUrl . "/d8d1a344f31de311d32134064695df85f3801897", "<test/>");
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#le fichier PES ALLER ne contient pas de SIRET#");
		$heliosController->updateSiretFromPESAller();
	}

	public function testUpdateSiretFromPESAllerOK(){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

		$heliosTransactionSQL->create("pes1.xml", "d8d1a344f31de311d32134064695df85f3801897", 8, 1, 42, 12);
		file_put_contents($this->testStreamUrl . "/d8d1a344f31de311d32134064695df85f3801897", file_get_contents(__DIR__ . "/fixtures/pes_aller.xml"));
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#siret 12345678912345 ajouté à la collectivite 1#");
		$heliosController->updateSiretFromPESAller();
		$authoritySiretSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$list = $authoritySiretSQL->siretList(1);
		$this->assertEquals("12345678912345",$list[0]['siret']);
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testGetPESRetourEmptyListAction(){
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#<idColl>1</idColl>#");
		$this->setExpectedException("Exception","exit() called");
		$heliosController->getPESRetourListAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testGetPESRetourListAction(){
		$heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
		$heliosRetourSQL->add(1,"123456789","toto.xml");

		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#<nom>toto.xml</nom>#");
		$this->setExpectedException("Exception","exit() called");
		$heliosController->getPESRetourListAction();
	}

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourListActionForAdmin(){
        $this->setAdminColAuthentication();
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $heliosRetourSQL->add(1,"123456789","toto.xml");

        $heliosController = new HeliosController($this->getObjectInstancier());
        $this->expectOutputRegex("#<nom>toto.xml</nom>#");
        $this->setExpectedException("Exception","exit() called");
        $heliosController->getPESRetourListAction();
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourListActionForAdminUnauthorized(){
        $this->setAdminCol2Authentication();
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $heliosRetourSQL->add(1,"123456789","toto.xml");

        $heliosController = new HeliosController($this->getObjectInstancier());
        $this->expectOutputRegex("#^((?!toto.xml).)*$#s");
        $this->setExpectedException("Exception","exit() called");
        $heliosController->getPESRetourListAction();
    }

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testGetPostPESRetourWithoutRGS(){
		$rgsConnexion = $this->getMockBuilder('RgsConnexion')->disableOriginalConstructor()->getMock();
		$rgsConnexion->expects($this->any())->method('isRgsConnexion')->willReturn(false);

		$this->getObjectInstancier()->{'RgsConnexion'} = $rgsConnexion;
		$this->expectOutputRegex("#<message>Votre certificat n'est pas RGS et ne vous permet donc pas de#");
		$this->setExpectedException("Exception","exit() called");
		$this->heliosController->importAPIAction();
	}




}
