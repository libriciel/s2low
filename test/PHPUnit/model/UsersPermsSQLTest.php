<?php

declare(strict_types=1);

namespace PHPUnit\model;

use PHPUnit\S2lowTestCase;
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
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(1, 1));
    }

    public function testSetPerms()
    {
        $this->usersPermsSQL->setPerms(2, 1, "RW");
        $this->assertEquals('RW', $this->usersPermsSQL->getInfoPerms(2, 1));
    }

    public function testSetPermsUpdate()
    {
        $this->usersPermsSQL->setPerms(1, 1, "RO");
        $this->assertEquals('RO', $this->usersPermsSQL->getInfoPerms(1, 1));
    }
}
