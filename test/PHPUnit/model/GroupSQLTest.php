<?php

require_once __DIR__."/../init.php";

class GroupSQLTest extends S2lowTestCase {

	/**
	 * @var GroupSQL
	 */
	private $groupeSQL;

	protected function setUp(){
		parent::setUp();
		$this->groupeSQL = new GroupSQL($this->getSQLQuery());
	}

	public function testGetInfo(){
		$info = $this->groupeSQL->getInfo(1);
		$this->assertEquals("Groupe de test",$info['name']);
	}

}
