<?php

class ActesPostWithoutSignatureControllerTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;

	/**
	 * @throws RedirectException
	 */
	public function testPost(){
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
		$this->setSuperAdminAuthentication();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->getObjectInstancier()->get(Environnement::class)->post()->set('id',$transaction_id);
		try {
			$actesPostWithoutSignature->postAction();
		} catch (Exception $e){
			$this->assertRegExp("#La transaction $transaction_id a été posté sans signature#",$e->getMessage());
		}

		$actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$info = $actesTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_POSTE,$info['last_status_id']);
	}

	/**
	 * @throws RedirectException
	 */
	public function testPostNoTransactionId(){
		$this->setUserAuthentification();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->setExpectedException(RedirectException::class,"Aucun identifiant de transaction trouvé");
		$actesPostWithoutSignature->postAction();
	}


	/**
	 * @throws RedirectException
	 */
	public function testPostNoRight(){
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
		$this->setUserAuthentification();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->getObjectInstancier()->get(Environnement::class)->post()->set('id',$transaction_id);
		$this->setExpectedException(RedirectException::class,"Vous n'avez pas le droit de faire cela");
		$actesPostWithoutSignature->postAction();
	}

}