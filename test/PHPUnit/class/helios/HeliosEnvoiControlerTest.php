<?php


class HeliosEnvoiControlerTest extends S2lowTestCase {

	private $testStreamUrl;

	public function testValidateAllTransactions(){
		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		mkdir($this->testStreamUrl."/helios");
		$pes_aller = __DIR__."/../../helios/fixtures/pes_aller_ok.xml";
		copy($pes_aller,$this->testStreamUrl."/helios/".sha1_file($pes_aller));

		$heliosControler = new HeliosController($this->getObjectInstancier());
		$heliosControler->importFile(8,$pes_aller,"pes_aller.xml");
		$heliosEnvoiControler = new HeliosEnvoiControler($this->getSQLQuery());
		$this->expectOutputRegex("#dans la file d'attente#");
		$heliosEnvoiControler->validateAllTransactions();

		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$siret_list = $authoritySiret->siretList(1);
		$this->assertEquals("12345678912345",$siret_list[0]['siret']);
	}


	public function testAccentNomFic(){
		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		mkdir($this->testStreamUrl."/helios");
		$pes_aller = __DIR__."/../../helios/fixtures/PESALR2_accent_dans_nomfic.xml";
		copy($pes_aller,$this->testStreamUrl."/helios/".sha1_file($pes_aller));

		$heliosControler = new HeliosController($this->getObjectInstancier());
		$id_t = $heliosControler->importFile(8,$pes_aller,"pes_aller.xml");
		$heliosEnvoiControler = new HeliosEnvoiControler($this->getSQLQuery());
		$this->expectOutputRegex("#dans la file d'attente#");
		$heliosEnvoiControler->validateAllTransactions();

		$heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
		$info = $heliosTransaction->getInfo($id_t);
		$this->assertEquals("PESALR220001861200016Trésorerie_de_M20141205152530.xml",$info['xml_nomfic']);

		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$siret_list = $authoritySiret->siretList(1);
		$this->assertEquals("12345678912345",$siret_list[0]['siret']);
	}

}
