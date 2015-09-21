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


}
