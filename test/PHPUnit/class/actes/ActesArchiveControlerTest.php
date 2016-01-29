<?php

require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');


class ActesArchiveControlerTest extends S2lowTestCase {

	private $transaction_id;
	private $last_transaction_workflow_id;

	/** @var  ActesArchiveControler */
	private $actesArchiveControler;

	public function testSetArchiveEnAttenteEnvoiSEABadState(){
		$result = $this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$this->transaction_id);
		$this->assertFalse($result);
		$this->assertEquals("Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».",$this->actesArchiveControler->getLastError());
	}

	public function testSetArchiveEnAttenteEnvoiSAE(){
		$transaction_id = $this->createTransaction(4);
		$result = $this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		$this->assertNotFalse($result);
	}

	private function createTransaction($status){
		$sql="INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID";
		$envelope_id = $this->getSQLQuery()->queryOne($sql);


		$sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id) VALUES (?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1);

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateSAE(1,array('pastell_url'=>'test','pastell_login'=>'test','pastell_password'=>'test','pastell_id_e'=>'12'));


		return $transaction_id;
	}

	public function testSetArchiveEnAttenteEnvoiSAEPastellNotConfigured(){
		$transaction_id = $this->createTransaction(4);
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateSAE(1,array('pastell_url'=>'','pastell_login'=>'','pastell_password'=>'','pastell_id_e'=>0));

		$result = $this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		$this->assertFalse($result);
		$this->assertEquals("La collectivité n'a pas de Pastell configuré",$this->actesArchiveControler->getLastError());
	}

	public function testAccesRefuse(){
		$transaction_id = $this->createTransaction(4);
		$result = $this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(5,$transaction_id);
		$this->assertFalse($result);
		$this->assertEquals("Accès refusé (seul le créateur de l'Acte peut l'archiver)",$this->actesArchiveControler->getLastError());
	}

	public function testSendCreateActeFailed(){
		$transaction_id = $this->setTransactionEnattente();
		$this->actesArchiveControler->setPastellFactory($this->getPastellFactory());
		$this->expectOutputString("Impossible d'envoyer la transaction $transaction_id : Erreur renvoyé par le mock\n");
		$this->actesArchiveControler->sendArchive($transaction_id);
	}

	private function setTransactionEnattente(){
		$transaction_id = $this->createTransaction(4);
		$this->last_transaction_workflow_id = $this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
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

	public function testSend(){
		$transaction_id = $this->setTransactionEnattente();
		$this->actesArchiveControler->setPastellFactory($this->getPastellFactory());
		$this->expectOutputRegex("#Impossible d'envoyer la transaction $transaction_id : Erreur renvoyé par le mock#");
		$this->actesArchiveControler->sendAllArchive();
	}

	public function testSendTransactionEnErreur(){
		$this->setTransactionEnattente();
		$this->actesArchiveControler->setPastellFactory($this->getPastellFactory());

		$date = date("Y-m-d",strtotime("-2 days"));

		$sql = "UPDATE actes_transactions_workflow SET date=? WHERE id=?";
		$this->getSQLQuery()->query($sql,$date,$this->last_transaction_workflow_id);

		$this->expectOutputRegex("#Passage de la transaction en erreur !#");
		$this->actesArchiveControler->sendAllArchive();
	}

	protected function setUp(){
		parent::setUp();
		$this->transaction_id = $this->createTransaction(1);
		$this->actesArchiveControler = new ActesArchiveControler($this->getSQLQuery());

	}


}