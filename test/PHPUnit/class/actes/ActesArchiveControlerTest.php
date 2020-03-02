<?php

require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');


class ActesArchiveControlerTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;
	use PastellConfigurationTestTrait;

	/**
	 * @return ActesArchiveControler
	 */
	private function getActesArchivesControler() {
		return $this->getObjectInstancier()->get(ActesArchiveControler::class);
	}

	/**
	 * @throws Exception
	 */
	public function testSendArchiveWhenCreateOnPastellFailed(){
		$this->mockActesTamponne();
		$this->mockPastellFactory(0,"Erreur renvoyé par le mock");

		$transaction_id = $this->createTransactionEnAttenteEnvoiSAE();
		$this->getActesArchivesControler()->sendArchive($transaction_id);
		$this->assertEquals(
			"Impossible d'envoyer la transaction {$transaction_id} : Erreur pastell : Erreur renvoyé par le mock",
			$this->getLogRecords()[1][S2lowLogger::MESSAGE]
		);
	}

	/**
	 * @throws Exception
	 */
	public function testSendArchiveWhenNoRightStatus(){
		$this->mockPastellFactory();
		$transaction_id = $this->createTransaction(4);
		$this->getActesArchivesControler()->sendArchive($transaction_id);
		$this->assertEquals(
			"La transaction {$transaction_id} à envoyer au SAE n'est pas dans le bon status ! 4 trouvé",
			$this->getLogRecords()[1][S2lowLogger::MESSAGE]
		);
	}

	/**
	 * @throws Exception
	 */
	public function testSendArchiveCasNominal(){
		$this->mockPastellFactory();
		$this->mockActesTamponne();

		$actesEnvelopeStorage = $this->getMockBuilder(ActesEnvelopeStorage::class)
			->disableOriginalConstructor()
			->getMock();
		$actesEnvelopeStorage

			->method("deleteIfIsInCloud")
			->willReturn(false);
		$this->getObjectInstancier()->set(ActesEnvelopeStorage::class,$actesEnvelopeStorage);

		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$transaction_id = $this->createTransactionEnAttenteEnvoiSAE();

		$this->getObjectInstancier()->unset_object(ActesArchiveControler::class);

		$this->getActesArchivesControler()->sendArchive($transaction_id);
		$last_status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);

		$this->assertEquals(ActesStatusSQL::STATUS_ENVOYE_AU_SAE,$last_status_info['status_id']);

		$log_record = $this->getLogRecords();

		$this->assertEquals(
			"La transaction $transaction_id a été envoyé sur le SAE (id_d pastell : xyzt)",
			$log_record[count($log_record)-1][S2lowLogger::MESSAGE]
		);
	}

	/**
	 * @throws Exception
	 */
	public function testgetAllTransactionIdToSend(){
		$this->configurePastell();
		$transaction_id = $this->createTransactionEnAttenteEnvoiSAE();
		$all = $this->getActesArchivesControler()->getAllTransactionIdToSend();
		$this->assertEquals([$transaction_id],$all);
	}

	/**
	 * @throws Exception
	 */
	public function testSendArchiveWhenActesRetrieverFailed(){
		$this->mockPastellFactory();
		$this->mockActesRetriever();

		$transaction_id = $this->createTransactionEnAttenteEnvoiSAE();

		$this->getActesArchivesControler()->sendArchive($transaction_id);

		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$last_status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,$last_status_info['status_id']);

		$log_record = $this->getLogRecords();
		$this->assertEquals(
			"Une erreur récupérable est survenue : Impossible de récupérer l'enveloppe abc-TACT--000000000--20170803-16.tar.gz. La transaction sera retentée.",
			$log_record[count($log_record)-1]['message']
		);
	}

	/**
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	private function createTransactionEnAttenteEnvoiSAE(){
		$this->configurePastell();
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE,__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz");
		$actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);
		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

		$actesIncludedFileSQL->addIncludedFile($transaction_info[ActesTransactionsSQL::ENVELOPE_ID],$transaction_id,"application/xml",772,"034-000000000-20170801-20170803E-AI-1-1_0.xml");
		$actesIncludedFileSQL->addIncludedFile($transaction_info[ActesTransactionsSQL::ENVELOPE_ID],$transaction_id,"application/pdf",101838,"034-000000000-20170801-20170803E-AI-1-1_1.pdf");

		$actesTransactionsSQL->updateStatus(
			$transaction_id,
			ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
			"test",
			file_get_contents(__DIR__ . "/fixtures/001-000000000-20170130-TEST42-DE-1-2_0.xml")
		);
		$actesTransactionsSQL->updateStatus(
			$transaction_id,
			ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			"test"
		);
		return $transaction_id;
	}



	private function mockActesTamponne(){
		$acteTamponne = $this->getMockBuilder(ActeTamponne::class)->disableOriginalConstructor()->getMock();
		$acteTamponne->method('tamponnerPDF')->willReturn(
			file_get_contents(__DIR__."/fixtures/convention-exemple.pdf")
		);
		$this->getObjectInstancier()->set(ActeTamponne::class,$acteTamponne);
	}


	private function mockActesRetriever(){
		$actesRetriever = $this->getMockBuilder(ActesRetriever::class)
			->disableOriginalConstructor()
			->getMock();
		$actesRetriever->method('getPath')->willReturn(false);
		$this->getObjectInstancier()->set(ActesRetriever::class,$actesRetriever);
	}


}