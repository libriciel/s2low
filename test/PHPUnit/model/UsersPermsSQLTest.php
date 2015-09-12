<?php


class UsersPermsSQLTest extends S2lowTestCase {

	/**
	 * @var UsersPermsSQL
	 */
	private $usersPermsSQL;

	public function setUp(){
		parent::setUp();
		$this->usersPermsSQL = new UsersPermsSQL($this->getSQLQuery());
	}

	public function testGetInfoPerms(){
		$this->assertEquals('RW',$this->usersPermsSQL->getInfoPerms(1,1));
	}
}
