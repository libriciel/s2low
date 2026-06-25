<?php

use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

class ModuleSQLTest extends S2lowTestCase
{
    /**
     * @var ModuleSQL
     */
    private $moduleSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleSQL = self::getContainer()->get(ModuleSQL::class);
    }

    public function testGetInfoByName()
    {
        $info  = $this->moduleSQL->getInfoByName('actes');
        $this->assertEquals('Module Actes', $info['description']);
    }

    public function testGetInfoModuleAuthority()
    {
        $this->assertNotEmpty($this->moduleSQL->getInfoModuleAuthority(1, 1));
    }

    public function testGetInfoPerms()
    {
        $this->assertEquals('RW', $this->moduleSQL->getInfoPerms(1, 1));
    }

    public function testHasDroit()
    {
        $this->assertTrue($this->moduleSQL->hasDroit(1, 1, 'RW'));
    }

    public function testHasDroitNone()
    {
        $this->assertFalse($this->moduleSQL->hasDroit(1, 2, 'RO'));
    }

    public function testGetModuleForSuperAdmin()
    {
        $userSQL = new UserSQL($this->getSQLQuery());
        $userInfo = $userSQL->getInfo(1);
        $info = $this->moduleSQL->getModulesForUser($userInfo);
        $this->assertEquals('actes', $info[0]['name']);
    }

    public function testGetModuleForGroupAdmin()
    {
        $userSQL = new UserSQL($this->getSQLQuery());
        $userInfo = $userSQL->getInfo(1);
        $info = $this->moduleSQL->getModulesForUser($userInfo);
        $this->assertEquals('helios', $info[1]['name']);
    }

    public function testGetModuleForUser()
    {
        $userSQL = new UserSQL($this->getSQLQuery());
        $userInfo = $userSQL->getInfo(5);
        $info = $this->moduleSQL->getModulesForUser($userInfo);
        $this->assertEmpty($info);
    }

    public function testgetUsers()
    {
        $info = $this->moduleSQL->getUsers(1);
        $this->assertEquals("Eric", $info[0]['givenname']);
    }


    public function testgetUsersSameGroup()
    {
        $info = $this->moduleSQL->getUsers(1, 1);
        $this->assertEquals("Eric", $info[0]['givenname']);
    }


    public function testgetUsersDifferentGroup()
    {
        $info = $this->moduleSQL->getUsers(1, 2);
        $this->assertEmpty($info);
    }
}
