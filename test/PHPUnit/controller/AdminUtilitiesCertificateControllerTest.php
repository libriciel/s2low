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

		$this->assertEquals(array (
			'certificate_info' =>
				array (
					'name' => '/C=FR/ST=France/L=Lyon/O=Sigmalis/OU=sigmalis/CN=Eric_Pommateau_RGS_2_etoiles',
					'subject' =>
						array (
							'C' => 'FR',
							'ST' => 'France',
							'L' => 'Lyon',
							'O' => 'Sigmalis',
							'OU' => 'sigmalis',
							'CN' => 'Eric_Pommateau_RGS_2_etoiles',
						),
					'hash' => 'f3d776ff',
					'issuer' =>
						array (
							'C' => 'FR',
							'ST' => 'France',
							'L' => 'Lyon',
							'O' => 'Sigmalis',
							'CN' => 'Sigmalis Certificate Autority',
							'emailAddress' => 'eric@sigmalis.com',
						),
					'version' => 0,
					'serialNumber' => '8',
					'serialNumberHex' => '08',
					'validFrom' => '150819083359Z',
					'validTo' => '250816083359Z',
					'validFrom_time_t' => 1439973239,
					'validTo_time_t' => 1755333239,
					'signatureTypeSN' => 'RSA-SHA1',
					'signatureTypeLN' => 'sha1WithRSAEncryption',
					'signatureTypeNID' => 65,
					'purposes' =>
						array (
							1 =>
								array (
									0 => true,
									1 => false,
									2 => 'sslclient',
								),
							2 =>
								array (
									0 => true,
									1 => false,
									2 => 'sslserver',
								),
							3 =>
								array (
									0 => true,
									1 => false,
									2 => 'nssslserver',
								),
							4 =>
								array (
									0 => true,
									1 => false,
									2 => 'smimesign',
								),
							5 =>
								array (
									0 => true,
									1 => false,
									2 => 'smimeencrypt',
								),
							6 =>
								array (
									0 => true,
									1 => false,
									2 => 'crlsign',
								),
							7 =>
								array (
									0 => true,
									1 => true,
									2 => 'any',
								),
							8 =>
								array (
									0 => true,
									1 => false,
									2 => 'ocsphelper',
								),
							9 =>
								array (
									0 => false,
									1 => false,
									2 => 'timestampsign',
								),
						),
					'extensions' =>
						array (
						),
					'expiration_date' => '2025-08-16 10:33:59',
					'issuer_name' => '/C=FR/ST=France/L=Lyon/O=Sigmalis/CN=Sigmalis Certificate Autority/emailAddress=eric@sigmalis.com',
					'subject_name' => '/C=FR/ST=France/L=Lyon/O=Sigmalis/OU=sigmalis/CN=Eric_Pommateau_RGS_2_etoiles',
					'certificate_hash' => 'ieQoLUcitdU9iZIJLPoIdp8TcUY=',
				),
			'is_rgs' => false,
			'is_extended' => false,
			'nb_users' => 1,
			'user_id' => 1,
		),
			$environnement->session()->get(AdminUtilitiesCertificateController::SESSION_KEY)
		);
	}

	/**
	 * @throws RedirectException
	 */
	public function testDoActionNoFile(){
		$adminUtilitiesCertificateController = $this->getObjectInstancier()->get(AdminUtilitiesCertificateController::class);
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