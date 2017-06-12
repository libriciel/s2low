<?php


class HeliosEnvoiControlerTest extends S2lowTestCase {

	private $testStreamUrl;

	/** @var  HeliosController */
	private $heliosController;

	/** @var  HeliosEnvoiControler */
	private $heliosEnvoiControler;

	protected function setUp(){
		parent::setUp();
		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");
		mkdir($this->testStreamUrl."/helios");
        $this->getObjectInstancier()->set("helios_files_upload_root",$this->testStreamUrl."/helios/");
		$this->heliosController = new HeliosController($this->getObjectInstancier());
		$this->heliosEnvoiControler = $this->getObjectInstancier()->get("HeliosEnvoiControler");

	}

	public function testValidateAllTransactions(){
		$this->validatePesAller("pes_aller_ok.xml");
		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$siret_list = $authoritySiret->siretList(1);
		$this->assertEquals("12345678912345",$siret_list[0]['siret']);
	}

	private function validatePesAller($filename){
		$pes_aller = __DIR__."/../../helios/fixtures/{$filename}";
		copy($pes_aller,$this->testStreamUrl."/helios/".sha1_file($pes_aller));
		$id_t = $this->heliosController->importFile(8,$pes_aller,"pes_aller.xml");
		ob_start();
		$this->heliosEnvoiControler->validateAllTransactions();
		ob_end_clean();
		return $id_t;
	}


	public function testAccentNomFic(){
		$id_t = $this->validatePesAller("PESALR2_accent_dans_nomfic.xml");

		$heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
		$info = $heliosTransaction->getInfo($id_t);

		$this->assertEquals("PESALR220001861200016Trésorerie_de_M20141205152530.xml",$info['xml_nomfic']);

		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$siret_list = $authoritySiret->siretList(1);
		$this->assertEquals("12345678912345",$siret_list[0]['siret']);
	}

	public function testRetrieveAllPesInfo(){
		$id_t = $this->validatePesAller("pes_aller_ok.xml");
		$heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
		$info = $heliosTransaction->getInfo($id_t);
		$this->assertEquals("03f432a4f6d35110bf309fb525eb61f7",$info['xml_nomfic']);
		$this->assertEquals("123",$info['xml_cod_col']);
		$this->assertEquals("12",$info['xml_cod_bud']);
		$this->assertEquals("034000",$info['xml_id_post']);
	}

	public function testSendSamePESAller(){
		$this->sendSamePESAllerFailed();
	}

	private function sendSamePESAllerFailed(){
		$this->validatePesAller("pes_aller_ok.xml");
		$id_t = $this->validatePesAller("pes_aller_ok.xml");
		$heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());

		$info = $heliosTransaction->getLastStatusInfo($id_t);
		$this->assertEquals(-1,$info['status_id']);
		$this->assertRegExp("#ce fichier existe déjà sur la plateforme#",$info['message']);
	}


	public function testSendSamePESAllerDoNotVerify(){
		$this->heliosEnvoiControler->setDoNotVerifyNomFicUnicity(true);
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateDoNotVerifyNomFicUnicity(1,true);
		$this->validatePesAller("pes_aller_ok.xml");
		$id_t = $this->validatePesAller("pes_aller_ok.xml");
		$heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
		$info = $heliosTransaction->getLastStatusInfo($id_t);
		$this->assertEquals(2,$info['status_id']);
	}

	public function testSendSamePESAllerDoNotVerifyOnlyConst(){
		$this->heliosEnvoiControler->setDoNotVerifyNomFicUnicity(true);
		$this->sendSamePESAllerFailed();
	}

	public function testSendSamePESAllerDoNotVerifyOnlyAuthority(){
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateDoNotVerifyNomFicUnicity(1,true);
		$this->sendSamePESAllerFailed();
	}


}
