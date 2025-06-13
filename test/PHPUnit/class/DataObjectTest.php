<?php

use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\User;

class DataObjectTest extends S2lowTestCase
{
    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
        $this->user->setId(1);
        $this->user->init();
    }

    public function testConstructId()
    {
        $user = new User(101);
        $this->assertTrue($user->init());
    }

    public function testSetId()
    {
        $this->assertEquals(1, $this->user->getId());
    }

    public function testSet()
    {
        $this->user->set('givenname', 'foo');
        $this->assertEquals('foo', $this->user->get('givenname'));
    }

    public function testIsNotNew()
    {
        $this->assertFalse($this->user->isNew());
    }

    public function testIsNew()
    {
        $user = new User();
        $this->assertTrue($user->isNew());
    }

    public function testInitFailed()
    {
        $user = new User();
        $user->setId(999);
        $this->assertFalse($user->init());
    }

    public function testInitNotSetId()
    {
        $user = new User();
        $this->assertFalse($user->init());
    }

    public function testDelete()
    {
        $this->assertTrue($this->user->delete(1));
    }

    public function testDeleteFailed()
    {
        $dataObject = new DataObject();
        $this->assertFalse($dataObject->delete());
    }

    public function testValidate(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            [],
        );
        $this->assertTrue($dataObject->validate());
    }

    public function testValidateNotMandatory(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','mandatory' => true],],
        );
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateUnique(): void
    {
        $dataObject = new DataObject();
        $dataObject->set('objectName', 'users');
        $dataObject->set(
            'dbFields',
            ['givenname' => ['descr' => 'givenname','mandatory' => true,'unique' => 'true']],
        );
        $dataObject->set('givenname', 'Alice');
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateIsInt(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','type' => 'isInt']],
        );
        $dataObject->foo = 'bar';
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateIsFloat(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','type' => 'isFloat']],
        );
        $dataObject->foo = 'bar';
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateIsEmail(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','type' => 'isEmail']],
        );
        $dataObject->foo = 'bar';
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateMaxLength(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','type' => 'foo','maxlength' => '1']],
        );
        $dataObject->foo = 'bar';
        $this->assertFalse($dataObject->validate());
    }

    public function testValidateRegexep(): void
    {
        $dataObject = new DataObject();
        $dataObject->set(
            'dbFields',
            ['foo' => ['descr' => 'foo','type' => 'foo','regexp' => '#/d+#','regexp_txt' => 'bar']],
        );
        $dataObject->foo = 'bar';
        $this->assertFalse($dataObject->validate());
    }
}
