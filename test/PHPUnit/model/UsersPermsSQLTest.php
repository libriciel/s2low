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
        $this->usersPermsSQL = self::getContainer()->get(UsersPermsSQL::class);
    }

    public function testGetInfoPerms()
    {
        $this->usersPermsSQL->setPerms(1, 103, "RW");
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(1, 103));
    }

    public function testSetPerms()
    {
        $this->usersPermsSQL->setPerms(2, 103, "RW");
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(2, 103));
    }

    public function testSetPermsUpdate()
    {
        $this->usersPermsSQL->setPerms(1, 103, "RO");
        $this->assertEquals('RO', $this->usersPermsSQL->getInfoPerms(1, 103));
    }
}
