<?php

class AuthorityGroupSirenSQLTest extends S2lowTestCase
{
	/**
	 * @var AuthorityGroupSirenSQL
	 */
	private $authorityGroupSirenSQL;

	protected function setUp(){
		parent::setUp();
		$this->authorityGroupSirenSQL = new AuthorityGroupSirenSQL($this->getSQLQuery());
	}

	public function testExist(){
		$this->assertFalse($this->authorityGroupSirenSQL->exist(42,"123456789"));
	}

	public function testAdd(){
		$this->authorityGroupSirenSQL->add(1,"123456789");
		$result = $this->authorityGroupSirenSQL->exist(1,"123456789");
		$this->assertEquals("123456789",$result['siren']);
	}

}
