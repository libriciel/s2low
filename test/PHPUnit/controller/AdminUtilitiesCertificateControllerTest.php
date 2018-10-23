<?php

class AdminUtilitiesCertificateControllerTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
	public function testTestAction(){
		$this->setSuperAdminAuthentication();
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$adminUtilitiesCertificateController->testAction();
		$this->assertEmpty($adminUtilitiesCertificateController->getViewParameter('certificate_info'));
	}

	/**
	 * @throws Exception
	 */
	public function testTestActionSession(){
		$certificate_info = ['foo'=>'bar'];
		$this->setSuperAdminAuthentication();
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$this->getObjectInstancier()->get(Environnement::class)->session()->set(AdminUtilitiesCertificateController::SESSION_KEY,$certificate_info);
		$adminUtilitiesCertificateController->testAction();
		$this->assertEquals($certificate_info,$adminUtilitiesCertificateController->getViewParameter('certificate_info'));
	}

	/**
	 * @throws RedirectException
	 */
	public function testDoAction(){
		$environnement = $this->getObjectInstancier()->get(Environnement::class);
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$adminUtilitiesCertificateController->setFiles([
			'certificat'=> [
				'tmp_name' => __DIR__.'/fixtures/user_test.pem'
			]
		]);
		try {
			$adminUtilitiesCertificateController->doTestAction();
		} catch (Exception $e){}

		$result = $environnement->session()->get(AdminUtilitiesCertificateController::SESSION_KEY);

		$this->assertEquals('/C=FR/ST=France/L=Lyon/O=Sigmalis/OU=sigmalis/CN=Eric_Pommateau_RGS_2_etoiles', $result['certificate_info']['name']);
		$this->assertEquals(1, $result['nb_users']);

	}

	/**
	 * @throws RedirectException
	 */
	public function testDoActionNoFile(){
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$adminUtilitiesCertificateController->setFiles([]);
		$this->setExpectedException(RedirectException::class,"Il faut fournir un fichier");
		$adminUtilitiesCertificateController->doTestAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testDoActionNoCertificate(){
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$adminUtilitiesCertificateController->setFiles([
			'certificat'=> [
				'tmp_name' => __DIR__.'/fixtures/pes_aller.xml'
			]
		]);
		$this->setExpectedException(RedirectException::class,"Impossible de lire le certificat");
		$adminUtilitiesCertificateController->doTestAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testDoActionNoUsers(){
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
		$adminUtilitiesCertificateController->setFiles([
			'certificat'=> [
				'tmp_name' => __DIR__.'/fixtures/user1.pem'
			]
		]);
		try {
			$adminUtilitiesCertificateController->doTestAction();
		} catch (Exception $e){}
		$session_info = $this->getObjectInstancier()->get(Environnement::class)->session()->get(AdminUtilitiesCertificateController::SESSION_KEY);
		$this->assertEquals(0,$session_info['nb_users']);

	}


}