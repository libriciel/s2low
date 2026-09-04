<?php

use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\GroupSQL;

class GroupSQLTest extends S2lowTestCase
{
    private const GROUPE_1_NAME = "Groupe de test";
    private const GROUPE_2_NAME = "Groupe & co";

    /**
     * @var GroupSQL
     */
    private $groupeSQL;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->groupeSQL = new GroupSQL($this->getSQLQuery());
    }

    public function testGetInfo()
    {
        $info = $this->groupeSQL->getInfo(1);
        $this->assertEquals(self::GROUPE_1_NAME, $info['name']);
    }

    public function testCreate()
    {
        $id = $this->groupeSQL->edit(1, self::GROUPE_2_NAME, 1);
        $info = $this->groupeSQL->getInfo($id);
        $this->assertEquals(self::GROUPE_2_NAME, $info['name']);
    }

    public function testUpdate()
    {
        $id = $this->groupeSQL->edit(1, self::GROUPE_2_NAME, 1);
        $info = $this->groupeSQL->getInfo($id);
        $this->assertEquals(self::GROUPE_2_NAME, $info['name']);
    }

    public function testGetAll()
    {
        $info = $this->groupeSQL->getAll();
        $this->assertEquals(self::GROUPE_1_NAME, $info[0]['name']);
    }

    public function testGroupNameAlreadyExistsNewGroup()
    {
        $this->assertTrue($this->groupeSQL->groupNameAlreadyExists(0, self::GROUPE_1_NAME));
    }

    public function testGroupNameAlreadyExistsSameId()
    {
        $this->assertFalse($this->groupeSQL->groupNameAlreadyExists(1, self::GROUPE_1_NAME));
    }

    public function testGroupNameAlreadyExistsChangeGroupeName()
    {
        $this->assertTrue($this->groupeSQL->groupNameAlreadyExists(2, self::GROUPE_1_NAME));
    }

    public function testGroupNameAlreadyExistsChangeOK()
    {
        $this->assertFalse($this->groupeSQL->groupNameAlreadyExists(1, "autre nom"));
    }

    public function testgetGroupsIdName()
    {
        $this->assertEquals([1 => 'Groupe de test', 2 => 'second groupe'], $this->groupeSQL->getGroupsIdName());
    }

    /**
     * @throws Exception
     */
    public function testGroupeVide()
    {
        $this->groupeSQL->getGroupsIdName();
        self::expectNotToPerformAssertions();
    }

    public function testGetSelectableGroupsIdNameLeavesOutInactiveGroups()
    {
        $this->deactivateGroup(2);

        $this->assertEquals([1 => self::GROUPE_1_NAME], $this->groupeSQL->getSelectableGroupsIdName());
    }

    /**
     * Un groupe désactivé après coup reste affiché tant qu'il administre la collectivité, sinon
     * l'enregistrement changerait sa désignation en silence.
     */
    public function testGetSelectableGroupsIdNameKeepsAnInactiveGroupAlreadyDesignated()
    {
        $this->deactivateGroup(2);

        $this->assertEquals(
            [1 => self::GROUPE_1_NAME, 2 => 'second groupe'],
            $this->groupeSQL->getSelectableGroupsIdName('', [2])
        );
    }

    /**
     * Un groupe à qui le SIREN de la collectivité n'est pas réservé n'a pas le droit de
     * l'administrer : il n'est pas proposé.
     */
    public function testGetSelectableGroupsIdNameKeepsOnlyTheGroupsHoldingTheSiren()
    {
        $this->givenSirenHeldBy(2, '491011698');

        $this->assertEquals(
            [2 => 'second groupe'],
            $this->groupeSQL->getSelectableGroupsIdName('491011698')
        );
    }

    /**
     * Dépossédé du SIREN après coup, le groupe déjà désigné reste affiché, sans quoi
     * l'enregistrement changerait sa désignation en silence.
     */
    public function testGetSelectableGroupsIdNameKeepsAnAlreadyDesignatedGroupWithoutTheSiren()
    {
        $this->givenSirenHeldBy(2, '491011698');

        $this->assertEquals(
            [1 => self::GROUPE_1_NAME, 2 => 'second groupe'],
            $this->groupeSQL->getSelectableGroupsIdName('491011698', [1])
        );
    }

    public function testGetSelectableGroupsIdNameWithoutSirenKeepsEveryActiveGroup()
    {
        $this->assertEquals(
            [1 => self::GROUPE_1_NAME, 2 => 'second groupe'],
            $this->groupeSQL->getSelectableGroupsIdName()
        );
    }

    public function testIsActive()
    {
        $this->deactivateGroup(2);

        $this->assertTrue($this->groupeSQL->isActive(1));
        $this->assertFalse($this->groupeSQL->isActive(2));
    }

    private function deactivateGroup(int $groupId): void
    {
        $this->getSQLQuery()->query('UPDATE authority_groups SET status = 0 WHERE id = ?', [$groupId]);
    }

    private function givenSirenHeldBy(int $groupId, string $siren): void
    {
        $this->getSQLQuery()->query(
            'INSERT INTO authority_group_siren (authority_group_id, siren) VALUES (?, ?)',
            [$groupId, $siren]
        );
    }
}
