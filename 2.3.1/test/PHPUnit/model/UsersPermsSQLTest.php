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

	public function testSetPerms(){
		$this->usersPermsSQL->setPerms(2,1,"RW");
		$this->assertEquals('RW',$this->usersPermsSQL->getInfoPerms(2,1));
	}

	public function testSetPermsUpdate(){
		$this->usersPermsSQL->setPerms(1,1,"RO");
		$this->assertEquals('RO',$this->usersPermsSQL->getInfoPerms(1,1));
	}

}
