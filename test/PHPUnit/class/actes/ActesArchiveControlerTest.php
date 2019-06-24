<?php

require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');


class ActesArchiveControlerTest extends S2lowTestCase {

	private $transaction_id;
	private $last_transaction_workflow_id;

	/** @var  ActesArchiveControler */
	private $actesArchiveControler;

	/** @var ActesPrepareEnvoiSAE */
	private $actesPrepareEnvoiSAE;


	protected function setUp(){
		parent::setUp();
		$this->transaction_id = $this->createTransaction(1);
		$this->actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);
		$this->actesPrepareEnvoiSAE = $this->getObjectInstancier()->get(ActesPrepareEnvoiSAE::class);
	}



	private function createTransaction($status){
		$sql="INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID";
		$envelope_id = $this->getSQLQuery()->queryOne($sql);


		$sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,type) VALUES (?,?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,1);

		$actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);

		$actesIncludedFileSQL->addIncludedFile($envelope_id,$transaction_id,"application/xml",980,"TACT--000000000--20170803-16.xml");
		$actesIncludedFileSQL->addIncludedFile($envelope_id,$transaction_id,"application/xml",772,"034-000000000-20170801-20170803E-AI-1-1_0.xml");
		$actesIncludedFileSQL->addIncludedFile($envelope_id,$transaction_id,"application/pdf",101838,"034-000000000-20170801-20170803E-AI-1-1_1.pdf");


		if ($status == 4) {
			$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
			$actesTransactionsSQL->updateStatus(
				$transaction_id,
				ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
				"test",
				file_get_contents(__DIR__ . "/fixtures/001-000000000-20170130-TEST42-DE-1-2_0.xml")
			);
		}

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "toto";
        $pastellProperties->id_e = 12;
		$authoritySQL->updateSAE(1,$pastellProperties);
		return $transaction_id;
	}


	/**
	 * @throws Exception
	 */
	public function testSendCreateActeFailed(){
		$transaction_id = $this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getPastellFactory());
		$this->getObjectInstancier()->unset_object(ActesArchiveControler::class);
		$this->actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);

		//$this->expectOutputString("Impossible d'envoyer la transaction $transaction_id : Erreur pastell : Erreur renvoyé par le mock\n");
		$this->actesArchiveControler->sendArchive($transaction_id);
		$this->assertEquals(
			"Impossible d'envoyer la transaction {$transaction_id} : Erreur pastell : Erreur renvoyé par le mock",
			$this->getLogRecords()[3]['message']
		);
	}

	private function setTransactionEnattente(){
		$transaction_id = $this->createTransaction(4);
		$this->last_transaction_workflow_id = $this->actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
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
		$this->getObjectInstancier()->unset_object(ActesArchiveControler::class);
		$this->actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);

		$date = date("Y-m-d",strtotime("-2 days"));

		$sql = "UPDATE actes_transactions_workflow SET date=? WHERE id=?";
		$this->getSQLQuery()->query($sql,$date,$this->last_transaction_workflow_id);

		//$this->expectOutputRegex("#Erreur renvoyé par le mock#");
		$this->actesArchiveControler->sendArchive($this->transaction_id);
		$this->assertEquals(
			"La transaction {$this->transaction_id} à envoyer au SAE n'est pas dans le bon status ! 1 trouvé",
			$this->getLogRecords()[3]['message']
		);
	}

	private function setActesRetriever($return_path){
		$actesRetriever = $this->getMockBuilder(ActesRetriever::class)->disableOriginalConstructor()->getMock();
		$actesRetriever->expects($this->any())->method('getPath')->willReturn($return_path);

		$this->getObjectInstancier()->set(ActesRetriever::class,$actesRetriever);

	}

	/**
	 * @throws Exception
	 */
	public function testSend(){

		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();

		$tmp_archive_path = $tmp_folder."/abc-TACT--000000000--20170803-16.tar.gz";
		copy(__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz",$tmp_archive_path);

		$this->setActesRetriever($tmp_archive_path);


		$transaction_id = $this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getWorkingPastellFactory("xyzt"));
		$this->getObjectInstancier()->unset_object(ActesArchiveControler::class);
		$this->actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);

		$this->actesArchiveControler->sendArchive($transaction_id);

		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$last_status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_ENVOYE_AU_SAE,$last_status_info['status_id']);

		$log_record = $this->getLogRecords();
		$this->assertEquals(
			"La transaction $transaction_id a été envoyé sur le SAE (id_d pastell : xyzt)",
			$log_record[count($log_record)-1]['message']
		);
		$tmpFolder->delete($tmp_folder);
	}

	/**
	 * @throws Exception
	 */
	public function testSendWhenActesRetriverFailed(){

		$this->setActesRetriever(false);

		$transaction_id = $this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getWorkingPastellFactory("xyzt"));
		$this->getObjectInstancier()->unset_object(ActesArchiveControler::class);
		$this->actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);

		$this->actesArchiveControler->sendArchive($transaction_id);

		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$last_status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,$last_status_info['status_id']);

		$log_record = $this->getLogRecords();
		$this->assertEquals(
			"Une erreur récupérable est survenu : Impossible de récupéré l'enveloppe . La transaction sera retenter.",
			$log_record[count($log_record)-2]['message']
		);
	}

	private function getWorkingPastellFactory($returnCreateActes = 0){
		$pastell = $this->getMockBuilder('PastellWrapper')->disableOriginalConstructor()->getMock();
		$pastell->expects($this->any())->method('createActes')->willReturn($returnCreateActes);
		$pastell->expects($this->any())->method('getLastError')->willReturn(false);
		$pastell->expects($this->any())->method('sendSAE')->willReturn(true);


		$pastellFactory = $this->getMockBuilder('PastellWrapperFactory')->disableOriginalConstructor()->getMock();
		$pastellFactory->expects($this->any())->method('getNewInstance')->willReturn($pastell);
		/** @var PastellWrapperFactory $pastellFactory */
		return $pastellFactory;
	}


}