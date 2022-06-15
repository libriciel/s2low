<?php

class ActesPostWithoutSignatureControllerTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;
	use RgsConnexionTestTrait;

	/**
	 * @throws Exception
	 */
	public function testPost(){

		$this->setRGS2stars();

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
		$this->setRGS2stars();
		$this->setUserAuthentification();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->setExpectedException(RedirectException::class,"Aucun identifiant de transaction trouvé");
		$actesPostWithoutSignature->postAction();
	}

	/**
	 * @throws RedirectException
	 * @throws Exception
	 */
	public function testPostNoRight(){
		$this->setRGS2stars();
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
		$this->setUserAuthentification();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->getObjectInstancier()->get(Environnement::class)->post()->set('id',$transaction_id);
		$this->setExpectedException(RedirectException::class,"Vous n'avez pas le droit de faire cela");
		$actesPostWithoutSignature->postAction();
	}

	/**
	 * @throws Exception
	 */
	public function testPostNoRgs2Stars(){
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
		$this->setSuperAdminAuthentication();
		$actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
		$this->getObjectInstancier()->get(Environnement::class)->post()->set('id',$transaction_id);
		try {
			$actesPostWithoutSignature->postAction();
			$this->assertTrue(false);
		} catch (Exception $e){
			$this->assertRegExp(
				"#La télétransmission nécessite un certificat RGS<br/>Erreur : Impossible de vérifier la connexion HTTPS#",
				$e->getMessage()
			);
		}
	}

}