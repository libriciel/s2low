<?php

class HeliosPrepareEnvoiSAETest extends S2lowTestCase {

	use HeliosUtilitiesTestTrait;
	use PastellConfigurationTestTrait;

	/**
	 * @return HeliosPrepareEnvoiSAE|mixed
	 */
	private function getHeliosPrepareEnvoiSAE(){
		return $this->getObjectInstancier()->get(HeliosPrepareEnvoiSAE::class);
	}

	private function getHeliosTransactionSQL(){
		return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
	}

	public function testSetArchiveEnAttenteEnvoiSEA(){
		$this->configurePastell();
		$transaction_id = $this->createTransaction();
		$this->assertTrue(
			$this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1,$transaction_id)
		);
		$this->assertEquals(
			HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			$this->getHeliosTransactionSQL()->getLastStatusInfo($transaction_id)['status_id']
		);
		$this->assertLogMessage(
			"La transaction $transaction_id passe en attente de transmission au SAE"
		);
	}

	public function testSetArchiveEnAttenteEnvoiSAEBadState(){
		$transaction_id = $this->createTransaction();
		$this->getHeliosTransactionSQL()->updateStatus($transaction_id,1,"n'importe quoi");
		$this->assertFalse(
			$this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1,$transaction_id)
		);
		$this->assertEquals(
			HeliosStatusSQL::POSTE,
			$this->getHeliosTransactionSQL()->getLastStatusInfo($transaction_id)['status_id']
		);
		$this->assertLogMessage(
			"Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé »."
		);
	}

	public function testSetArchiveEnAttenteEnvoiSAEBadTransaction(){
		$this->assertFalse(
			$this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1,12)
		);
		$this->assertLogMessage(
			"La transaction 12 n'existe pas"
		);
	}

	public function testSetArchiveEnAttenteEnvoieSAEBadUser(){
		$transaction_id = $this->createTransaction();
		$this->assertFalse(
			$this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(5,$transaction_id)
		);
		$this->assertLogMessage("Accès interdit");
	}
}