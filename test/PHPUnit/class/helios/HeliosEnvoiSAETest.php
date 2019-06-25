<?php

class HeliosEnvoiSAETest extends S2lowTestCase {

	use HeliosUtilitiesTestTrait;
	use PastellConfigurationTestTrait;


	public function testSend(){
    	$this->mockPastellFactory();
		$transaction_id = $this->setTransactionEnattente();

		$this->assertTrue(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);

		$this->assertLogMessage("La transaction $transaction_id a été envoyé à Pastell",1);
	}

	private function setTransactionEnattente(){
    	$this->configurePastell();
		$transaction_id = $this->createTransaction();
		$this->getObjectInstancier()
			->get(HeliosPrepareEnvoiSAE::class)
			->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		return $transaction_id;
	}

	/**
	 * @throws Exception
	 */
	public function testSendTransactionEnErreur(){
		$this->mockPastellFactory(false,"Erreur renvoyé par le mock");
		$transaction_id = $this->setTransactionEnattente();

		$this->assertFalse(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);
		$this->assertLogMessage(
			"Le document n'a pas pu être envoyé sur Pastell : Erreur renvoyé par le mock",
			1
		);
	}

	public function testSendAllArchive(){
		$this->mockPastellFactory("xyzt",false);
		$transaction_id = $this->setTransactionEnattente();
		$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendAllArchive();
		$this->assertLogMessage("1 transactions à envoyer...",2);
		$this->assertLogMessage("La transaction $transaction_id a été envoyé à Pastell",4);
	}

	public function testWhenErrorMessageIsTooLong(){
		$error_message = str_repeat('X',1024);
		$this->mockPastellFactory(false,$error_message);
		$transaction_id = $this->setTransactionEnattente();
		$this->assertFalse(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);
		$this->assertLogMessage(
			"Le document n'a pas pu être envoyé sur Pastell : $error_message",
			1
		);

	}
}