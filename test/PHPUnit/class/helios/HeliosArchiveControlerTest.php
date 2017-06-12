<?php

require_once SITEROOT."/class/helios/HeliosArchiveControler.class.php";

class HeliosArchiveControlerTest extends S2lowTestCase {

	/** @var  HeliosArchiveControler */
	private $heliosArchiveControler;

	/** @var  HeliosTransactionsSQL */
	private $heliosTransactionsSQL;

	private $last_transaction_workflow_id;

    protected function setUp(){
        parent::setUp();
        $this->heliosArchiveControler = $this->getObjectInstancier()->get("HeliosArchiveControler");
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());

    }

    public function testSetArchiveEnAttenteEnvoiSEA(){
		$transaction_id = $this->createTransaction();
		$this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
	}

	private function createTransaction(){
		$sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename) VALUES (?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,1,1,4,"toto.txt");

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateSAE(1,array(
			'pastell_url'=>'test',
			'pastell_login'=>'test',
			'pastell_password'=>'test',
			'pastell_id_e'=>'12')
		);

		return $transaction_id;
	}

	public function testSetArchiveEnAttenteEnvoiSAEBasState(){
		$transaction_id = $this->createTransaction();
		$this->heliosTransactionsSQL->updateStatus($transaction_id,1,"n'importe quoi");
		$id=  $this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		$this->assertFalse($id);
		$this->assertEquals(
			"Impossible d'archiver une transaction qui n'est pas en état " .
			"« Information disponible », « acquitté » ou « refusé ».",
			$this->heliosArchiveControler->getLastError());
	}

	public function testSetArchiveEnAttenteEnvoiSAEBadTransaction(){
		$result = $this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,12);
		$this->assertFalse($result);
		$this->assertEquals("Impossible de d'envoyer la transaction",$this->heliosArchiveControler->getLastError());
	}

	public function testSetArchiveEnAttenteEnvoieSAEBadUser(){
		$transaction_id = $this->createTransaction();
		$result = $this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(5,$transaction_id);
		$this->assertFalse($result);
		$this->assertEquals("Accès interdit",$this->heliosArchiveControler->getLastError());
	}

	public function testSend(){
		$transaction_id = $this->setTransactionEnattente();
		$this->heliosArchiveControler->setPastellFactory($this->getPastellFactory());
		$this->expectOutputRegex("#Impossible d'envoyer la transaction $transaction_id : Erreur renvoyé par le mock#");
		$this->heliosArchiveControler->sendAllArchive();
	}

	private function setTransactionEnattente(){
		$transaction_id = $this->createTransaction();
		$this->last_transaction_workflow_id = $this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		return $transaction_id;
	}

	/**
	 * @param int $returnCreateActes
	 * @return PastellFactory
	 */
	private function getPastellFactory($returnCreateActes = 0){
		$pastell = $this->getMockBuilder('Pastell')->disableOriginalConstructor()->getMock();
		$pastell->expects($this->any())->method('createActes')->willReturn($returnCreateActes);
		$pastell->expects($this->any())->method('getLastError')->willReturn("Erreur renvoyé par le mock");


		$pastellFactory = $this->getMockBuilder('PastellFactory')->getMock();
		$pastellFactory->expects($this->any())->method('getNewInstance')->willReturn($pastell);
		return $pastellFactory;
	}

	public function testSendTransactionEnErreur(){
		$this->setTransactionEnattente();
		$this->heliosArchiveControler->setPastellFactory($this->getPastellFactory());

		$date = date("Y-m-d",strtotime("-2 days"));

		$sql = "UPDATE helios_transactions_workflow SET date=? WHERE id=?";
		$this->getSQLQuery()->query($sql,$date,$this->last_transaction_workflow_id);

		$this->expectOutputRegex("#Passage de la transaction en erreur !#");
		$this->heliosArchiveControler->sendAllArchive();
	}


}