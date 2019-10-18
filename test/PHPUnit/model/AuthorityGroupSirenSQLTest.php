<?php

class AuthorityGroupSirenSQLTest extends S2lowTestCase
{
	/**
	 * @var AuthorityGroupSirenSQL
	 */
	private $authorityGroupSirenSQL;

	/**
	 * @throws Exception
	 */
	protected function setUp() : void {
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

	public function testgetUnusedSiren(){
		//Les deux SIREN suivant sont déjà utilisé par les collectivités de test
		$this->authorityGroupSirenSQL->add(1,"123456789");
		$this->authorityGroupSirenSQL->add(1,"999999999");
		$this->authorityGroupSirenSQL->add(1,"000000000");
		$list = $this->authorityGroupSirenSQL->getUnusedSiren(1);
		$this->assertEquals(['000000000'],$list);
	}

	public function testGetUnusedSirenUsedInAnotherGroup(){
		$this->authorityGroupSirenSQL->add(2,"123456789"); //Utilisé par la collectivité 1 du groupe 1
		$this->authorityGroupSirenSQL->add(2,"000000000");

		$list = $this->authorityGroupSirenSQL->getUnusedSiren(2);
		$this->assertEquals(['000000000','123456789'],$list);
	}


}
