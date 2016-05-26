<?php

class DataObjectTest extends S2lowTestCase {

	/**
	 * @var User
	 */
	private $user;

	public function setUp(){
		parent::setUp();
		$this->user = new User();
		$this->user->setId(1);
		$this->user->init();
	}

	public function testConstructId(){
		$user = new User(1);
		$this->assertTrue($user->init());
	}

	public function testSetId(){
		$this->assertEquals(1,$this->user->getId());
	}

	public function testSet(){
		$this->user->set('givenname','foo');
		$this->assertEquals('foo',$this->user->get('givenname'));
	}

	public function testIsNotNew(){
		$this->assertFalse($this->user->isNew());
	}

	public function testIsNew(){
		$user = new User();
		$this->assertTrue($user->isNew());
	}

	public function testInitFailed(){
		$user = new User();
		$user->setId(999);
		$this->assertFalse($user->init());
	}

	public function testInitNotSetId(){
		$user = new User();
		$this->assertFalse($user->init());
	}

	public function testDelete(){
		$this->assertTrue($this->user->delete(1));
	}

	public function testDelete2(){
		/*$db = DatabasePool::getInstance();
		$db->exit_on_error = false;
		$dataObject = new DataObject();
		$dataObject->setId(1);
		$dataObject->objectName = 'users';
		$this->setExpectedException("Exception");
		$this->expectOutputRegex('#update or delete on table "users" violates foreign key constraint#');
		$this->assertFalse($dataObject->delete());*/
	}

	public function testDeleteFailed(){
		$dataObject = new DataObject();
		$this->assertFalse($dataObject->delete());
	}

	public function testValidate(){
		$dataObject = new DataObject();
		$dataObject->dbFields = array();
		$this->assertTrue($dataObject->validate());
	}

	public function testValidateNotMandatory(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','mandatory'=>true));
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateUnique(){
		$dataObject = new DataObject();
		$dataObject->objectName = 'users';
		$dataObject->dbFields=array("givenname"=>array('descr'=>'givenname','mandatory'=>true,'unique'=>'true'));
		$dataObject->set('givenname','Alice');
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateIsInt(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','type'=>'isInt'));
		$dataObject->foo = 'bar';
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateIsFloat(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','type'=>'isFloat'));
		$dataObject->foo = 'bar';
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateIsEmail(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','type'=>'isEmail'));
		$dataObject->foo = 'bar';
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateMaxLength(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','type'=>'foo','maxlength'=>'1'));
		$dataObject->foo = 'bar';
		$this->assertFalse($dataObject->validate());
	}

	public function testValidateRegexep(){
		$dataObject = new DataObject();
		$dataObject->dbFields=array("foo"=>array('descr'=>'foo','type'=>'foo','regexp'=>'#/d+#','regexp_txt'=>'bar'));
		$dataObject->foo = 'bar';
		$this->assertFalse($dataObject->validate());
	}

}