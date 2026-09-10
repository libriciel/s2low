<?php

use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\GroupSQL;

class GroupSQLTest extends S2lowTestCase
{
    private const GROUPE_1_NAME = "Groupe de test";
    private const GROUPE_2_NAME = "Groupe & co";
    private const GROUPE_2_FIXTURE_NAME = "second groupe";
    private const SIREN = "491011698";

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

    public function testGetSelectableGroupsIdNameLeavesOutInactiveGroups(): void
    {
        $this->deactivateGroup(2);

        $this->assertSame([1 => self::GROUPE_1_NAME], $this->groupeSQL->getSelectableGroupsIdName());
    }

    public function testGetSelectableGroupsIdNameKeepsAnInactiveGroupAlreadyDesignated(): void
    {
        $this->deactivateGroup(2);

        $this->assertSame(
            [1 => self::GROUPE_1_NAME, 2 => self::GROUPE_2_FIXTURE_NAME],
            $this->groupeSQL->getSelectableGroupsIdName('', [2])
        );
    }

    public function testGetSelectableGroupsIdNameKeepsOnlyTheGroupsHoldingTheSiren(): void
    {
        $this->givenSirenHeldBy(2, self::SIREN);

        $this->assertSame(
            [2 => self::GROUPE_2_FIXTURE_NAME],
            $this->groupeSQL->getSelectableGroupsIdName(self::SIREN)
        );
    }

    public function testGetSelectableGroupsIdNameKeepsAnAlreadyDesignatedGroupWithoutTheSiren(): void
    {
        $this->givenSirenHeldBy(2, self::SIREN);

        $this->assertSame(
            [1 => self::GROUPE_1_NAME, 2 => self::GROUPE_2_FIXTURE_NAME],
            $this->groupeSQL->getSelectableGroupsIdName(self::SIREN, [1])
        );
    }

    public function testGetSelectableGroupsIdNameWithoutSirenKeepsEveryActiveGroup(): void
    {
        $this->assertSame(
            [1 => self::GROUPE_1_NAME, 2 => self::GROUPE_2_FIXTURE_NAME],
            $this->groupeSQL->getSelectableGroupsIdName()
        );
    }

    public function testIsActive(): void
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
