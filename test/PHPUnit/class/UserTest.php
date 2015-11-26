<?php

class UserTest  extends S2lowTestCase {

	public function testGetIdFromCertData(){
		$user = new User();
		$ids = $user->getIdFromCertData('q2UZmkpQTMgJgyQBfsnw40wOUCvH7SVy54EVEcgq9kc=');
		$this->assertEquals(1,$ids[0]);
	}

	public function testgetNbUserWithMyCertificate()
	{
		$user = new User();
		$user->setId(2);
		$user->init();
		$this->assertEquals(2, $user->getNbUserWithMyCertificate());
	}
}