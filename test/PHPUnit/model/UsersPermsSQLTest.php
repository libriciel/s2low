<?php

use S2lowLegacy\Model\UsersPermsSQL;

class UsersPermsSQLTest extends S2lowTestCase
{
    /**
     * @var UsersPermsSQL
     */
    private $usersPermsSQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->usersPermsSQL = new UsersPermsSQL($this->getSQLQuery());
    }

    public function testGetInfoPerms()
    {
        $this->usersPermsSQL->setPerms(1, 3, "RW");
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(1, 3));
    }

    public function testSetPerms()
    {
        $this->usersPermsSQL->setPerms(2, 3, "RW");
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(2, 3));
    }

    public function testSetPermsUpdate()
    {
        $this->usersPermsSQL->setPerms(1, 3, "RO");
        $this->assertEquals('RO', $this->usersPermsSQL->getInfoPerms(1, 3));
    }
}
