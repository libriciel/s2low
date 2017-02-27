<?php

class GroupSQLTest extends S2lowTestCase {

	const GROUPE_1_NAME = "Groupe de test";
	const GROUPE_2_NAME = "Groupe & co";

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
		$this->assertEquals(self::GROUPE_1_NAME,$info['name']);
	}

	public function testCreate(){
		$id = $this->groupeSQL->edit(0,self::GROUPE_2_NAME,1);
		$info = $this->groupeSQL->getInfo($id);
		$this->assertEquals(self::GROUPE_2_NAME,$info['name']);
	}

	public function testUpdate(){
		$id = $this->groupeSQL->edit(1,self::GROUPE_2_NAME,1);
		$info = $this->groupeSQL->getInfo($id);
		$this->assertEquals(self::GROUPE_2_NAME,$info['name']);
	}

	public function testGetAll(){
		$info = $this->groupeSQL->getAll();
		$this->assertEquals(self::GROUPE_1_NAME,$info[0]['name']);
	}

	public function testGroupNameAlreadyExistsNewGroup(){
		$this->assertTrue($this->groupeSQL->groupNameAlreadyExists(0,self::GROUPE_1_NAME));
	}

	public function testGroupNameAlreadyExistsSameId(){
		$this->assertFalse($this->groupeSQL->groupNameAlreadyExists(1,self::GROUPE_1_NAME));
	}

	public function testGroupNameAlreadyExistsChangeGroupeName(){
		$this->assertTrue($this->groupeSQL->groupNameAlreadyExists(2,self::GROUPE_1_NAME));
	}

	public function testGroupNameAlreadyExistsChangeOK(){
		$this->assertFalse($this->groupeSQL->groupNameAlreadyExists(1,"autre nom"));
	}

}
