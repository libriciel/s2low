<?php

require_once(__DIR__."/../init.php");

class UserSQLTest  extends S2lowTestCase {
	
	public function testGetInfo(){
		$userSQL = new UserSQL($this->getSQLQuery());
		$info = $userSQL->getInfo(1);
		$this->assertEquals("Eric",$info['givenname']);
	}
	
}