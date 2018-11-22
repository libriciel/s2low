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
        $this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
    }

    public function testSetArchiveEnAttenteEnvoiSEA(){
		$transaction_id = $this->createTransaction();
		$this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
	}

	private function createTransaction(){
		$sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename) VALUES (?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,1,1,4,"toto.txt");

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "test";
        $pastellProperties->password = "test";
        $pastellProperties->id_e = 12;

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateSAE(1,$pastellProperties);

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
		$this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getPastellFactory());
		$this->getObjectInstancier()->unset_object(HeliosArchiveControler::class);
		$this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);
		$this->expectOutputRegex("#Erreur renvoyé par le mock#");
		$this->heliosArchiveControler->sendAllArchive();
	}

	private function setTransactionEnattente(){
		$transaction_id = $this->createTransaction();
		$this->last_transaction_workflow_id = $this->heliosArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		return $transaction_id;
	}

	/**
	 * @param int $returnCreateActes
	 * @return PastellWrapperFactory
	 */
	private function getPastellFactory($returnCreateActes = 0){
		$pastell = $this->getMockBuilder('PastellWrapper')->disableOriginalConstructor()->getMock();
		$pastell->expects($this->any())->method('createActes')->willReturn($returnCreateActes);
		$pastell->expects($this->any())->method('getLastError')->willReturn("Erreur renvoyé par le mock");


		$pastellFactory = $this->getMockBuilder('PastellWrapperFactory')->disableOriginalConstructor()->getMock();
		$pastellFactory->expects($this->any())->method('getNewInstance')->willReturn($pastell);
		/** @var PastellWrapperFactory $pastellFactory */
		return $pastellFactory;
	}

	/**
	 * @throws Exception
	 */
	public function testSendTransactionEnErreur(){
		$this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getPastellFactory());
		$this->getObjectInstancier()->unset_object(HeliosArchiveControler::class);
		$this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);

		$date = date("Y-m-d",strtotime("-2 days"));

		$sql = "UPDATE helios_transactions_workflow SET date=? WHERE id=?";
		$this->getSQLQuery()->query($sql,$date,$this->last_transaction_workflow_id);

		$this->expectOutputRegex("#Erreur renvoyé par le mock#");
		$this->heliosArchiveControler->sendAllArchive();
	}


}