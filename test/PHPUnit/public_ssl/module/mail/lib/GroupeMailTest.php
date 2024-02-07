<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\mail\lib;

use GroupeMail;
use PHPUnit\S2lowTestCase;

class GroupeMailTest extends S2lowTestCase
{
    /** @var \GroupeMail  */
    private $groupeMail;
    /** @var string */
    private $name;
    /** @var int */
    private $groupeId;

    public function setUp(): void
    {
        parent::setUp();
        $this->name = "testMailGroup" . md5(date(DATE_ISO8601));
        $this->groupeMail = new GroupeMail();
        $this->groupeMail->set("authority_id", 1);
        $this->groupeMail->set("name", $this->name);
        $this->groupeMail->save();
        $this->groupeId = $this->groupeMail->getId();
    }

    public function tearDown(): void
    {
        $this->groupeMail->delete($this->groupeMail->getId());
        parent::tearDown();
    }

    public function testAddAndRemoveUserToGroup()
    {
        $this->assertEquals(0, $this->groupeMail->getNbUtilisateur());
        $this->assertEquals(false, $this->groupeMail->isUserInGroup(1));
        $this->groupeMail->addUser(1);
        $this->assertEquals(1, $this->groupeMail->getNbUtilisateur());
        $this->assertEquals(true, $this->groupeMail->isUserInGroup(1));
        $this->groupeMail->removeUser(1);
        $this->assertEquals(0, $this->groupeMail->getNbUtilisateur());
        $this->assertEquals(false, $this->groupeMail->isUserInGroup(1));
    }

    public function testgetGroupeIdFromName()
    {
        $this->assertEquals(
            $this->groupeMail->getId(),
            $this->groupeMail->getGroupeIdFromName($this->name, 1)
        );
    }

    public function testgetGroupeIdFromNameWithInexistantName()
    {
        $this->assertEquals(
            false,
            $this->groupeMail->getGroupeIdFromName("A fake name", 1)
        );
    }

    public function testGetGroupeByAuthorityId()
    {
        $this->assertEquals(
            [
                $this->groupeId =>
                [
                'id' => $this->groupeId,
                'authority_id' => 1,
                'name' => $this->name,
                'nb_contact' => 0
                ]
            ],
            $this->groupeMail->getGroupeByAuthorityId(1)
        );
    }
}
