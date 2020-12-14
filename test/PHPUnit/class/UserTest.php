<?php

class UserTest  extends S2lowTestCase {

	public function testGetIdFromCertData(){
		$user = new User();
		$ids = $user->getIdFromCertData('J8q8Uwz2niUtisKKvzSzRBbnMx4=');
		$this->assertEquals(1,$ids[0]);
	}

	public function testgetNbUserWithMyCertificate() {
		$user = new User();
		$user->setId(2);
		$user->init();
		$this->assertEquals(2, $user->getNbUserWithMyCertificate());
	}

	public function testGetCertificateInfo(){
		$user = new User();
		$user->setId(2);
		$user->init();
		$info = $user->getCertificateInfo();
		$this->assertEquals('hash_adullact',$info['certificate_hash']);
	}

}