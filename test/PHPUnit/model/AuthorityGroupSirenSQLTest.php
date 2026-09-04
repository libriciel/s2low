<?php

use S2lowLegacy\Model\AuthorityGroupSirenSQL;

class AuthorityGroupSirenSQLTest extends S2lowTestCase
{
    private AuthorityGroupSirenSQL $authorityGroupSirenSQL;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->authorityGroupSirenSQL = self::getContainer()->get(AuthorityGroupSirenSQL::class);
    }

    public function testExist()
    {
        $this->assertFalse($this->authorityGroupSirenSQL->exist(42, "123456789"));
    }

    public function testAdd()
    {
        $this->authorityGroupSirenSQL->add(1, "123456789");
        $result = $this->authorityGroupSirenSQL->exist(1, "123456789");
        $this->assertEquals("123456789", $result['siren']);
    }

    public function testgetAvailableSiren(): void
    {
        $this->authorityGroupSirenSQL->add(1, '123456789');
        $this->authorityGroupSirenSQL->add(1, '999999999');
        $this->authorityGroupSirenSQL->add(1, '123456780');
        $this->authorityGroupSirenSQL->add(1, '000000000');

        $listColl1 = $this->authorityGroupSirenSQL->getAvailableSiren(1, 1);
        static::assertSame(['000000000', '123456789'], $listColl1);
        $listColl1 = $this->authorityGroupSirenSQL->getAvailableSiren(1, 2);
        static::assertSame(['000000000', '999999999'], $listColl1);
    }

    public function testGetUnusedSirenUsedInAnotherGroup()
    {
        $this->authorityGroupSirenSQL->add(2, "123456789"); //Utilisé par la collectivité 1 du groupe 1
        $this->authorityGroupSirenSQL->add(2, "000000000");

        // Si on a changé la coll de groupe, elle doit garder accès à son SIREN
        $list = $this->authorityGroupSirenSQL->getAvailableSiren(2, 1);
        $this->assertEquals(['000000000','123456789'], $list);

        // Si on créé une coll dans un nouveau groupe, elle doit avoir accès au SIREN
        $list = $this->authorityGroupSirenSQL->getAvailableSiren(2, 2);
        $this->assertEquals(['000000000','123456789'], $list);
    }

    public function testGetAvailableSirenForGroupsExcludesSirenUsedByAnAuthorityOfTheGroup(): void
    {
        $this->givenAuthorityAdministeredByGroup(1, 1);
        $this->authorityGroupSirenSQL->add(1, '123456789'); // pris par la collectivité 1
        $this->authorityGroupSirenSQL->add(1, '491011698');
        $this->authorityGroupSirenSQL->add(1, '111111119');

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups([1], 6);

        self::assertSame(['111111119', '491011698'], $sirens);
    }

    public function testGetAvailableSirenForGroupsKeepsSirenOfCurrentAuthority(): void
    {
        $this->givenAuthorityAdministeredByGroup(1, 1);
        $this->authorityGroupSirenSQL->add(1, '123456789'); // le SIREN de la collectivité 1 elle-même

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups([1], 1);

        self::assertSame(['123456789'], $sirens);
    }

    /**
     * Deux mutualisants se partagent une collectivité : elle ne peut porter qu'un SIREN que les
     * deux groupes autorisent.
     */
    public function testGetAvailableSirenForGroupsReturnsTheIntersection(): void
    {
        $this->authorityGroupSirenSQL->add(1, '491011698'); // les deux groupes
        $this->authorityGroupSirenSQL->add(2, '491011698');
        $this->authorityGroupSirenSQL->add(1, '111111119'); // groupe 1 seulement
        $this->authorityGroupSirenSQL->add(2, '443783170'); // groupe 2 seulement

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups([1, 2], 6);

        self::assertSame(['491011698'], $sirens);
    }

    public function testGetAvailableSirenForGroupsWithoutAnyGroupReturnsNothing(): void
    {
        $this->authorityGroupSirenSQL->add(1, '491011698');

        self::assertSame([], $this->authorityGroupSirenSQL->getAvailableSirenForGroups([], 6));
    }

    private function givenAuthorityAdministeredByGroup(int $authorityId, int $groupId): void
    {
        $this->getSQLQuery()->query(
            'UPDATE authorities SET actes_group_id = ? WHERE id = ?',
            [$groupId, $authorityId]
        );
    }
}
