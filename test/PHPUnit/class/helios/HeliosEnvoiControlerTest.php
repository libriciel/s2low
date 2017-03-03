<?php


class HeliosEnvoiControlerTest extends S2lowTestCase {

	private $testStreamUrl;


	protected function setUp(){
		parent::setUp();
		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");
		mkdir($this->testStreamUrl."/helios");
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
		$heliosControler = new HeliosController($this->getObjectInstancier());
		$id_t = $heliosControler->importFile(8,$pes_aller,"pes_aller.xml");
		$heliosEnvoiControler = new HeliosEnvoiControler($this->getSQLQuery());
		ob_start();
		$heliosEnvoiControler->validateAllTransactions();
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

}
