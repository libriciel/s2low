<?php

class UserTest  extends S2lowTestCase {

	public function testGetIdFromCertData(){
		$user = new User();
		$ids = $user->getIdFromCertData('test_subject', 'test_issuer');
		$this->assertEquals(1,$ids[0]);
	}
}