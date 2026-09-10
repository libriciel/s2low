<?php

use S2lowLegacy\Model\AuthorityGroupSirenSQL;

class AuthorityGroupSirenSQLTest extends S2lowTestCase
{
    private const AUTHORITY_1 = 1;
    private const ANOTHER_AUTHORITY = 6;
    private const GROUP_1 = 1;
    private const GROUP_2 = 2;
    private const SIREN_OF_AUTHORITY_1 = '123456789';
    private const FREE_SIREN = '491011698';
    private const ANOTHER_FREE_SIREN = '111111119';
    private const THIRD_FREE_SIREN = '443783170';

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

    /**
     * Un SIREN réservé à deux groupes n'est libre que tant qu'aucune collectivité ne le porte.
     * Le proposer au second menait à un enregistrement voué à échouer sur « Numéro de SIREN doit
     * être unique ».
     */
    public function testGetAvailableSirenExcludesSirenUsedByAnAuthorityOfAnotherGroup(): void
    {
        $this->authorityGroupSirenSQL->add(2, "123456789"); // porté par la collectivité 1, du groupe 1
        $this->authorityGroupSirenSQL->add(2, "000000000");

        // La collectivité qui change de groupe garde le SIREN qu'elle porte déjà
        static::assertSame(['000000000', '123456789'], $this->authorityGroupSirenSQL->getAvailableSiren(2, 1));

        // Une autre collectivité ne peut pas le lui reprendre
        static::assertSame(['000000000'], $this->authorityGroupSirenSQL->getAvailableSiren(2, 2));
    }

    public function testGetAvailableSirenForAllGroupsExcludesSirenUsedByOtherAuthority(): void
    {
        $this->authorityGroupSirenSQL->add(1, '123456789'); // taken by authority 1
        $this->authorityGroupSirenSQL->add(1, '999999999'); // taken by authority 2
        $this->authorityGroupSirenSQL->add(1, '491011698'); // free
        $this->authorityGroupSirenSQL->add(1, '111111119'); // free
        $this->authorityGroupSirenSQL->add(2, '443783170'); // free (no authority in group 2)

        $rows = $this->authorityGroupSirenSQL->getAvailableSirenForAllGroups(6);

        self::assertSame(
            [
                ['authority_group_id' => 1, 'siren' => '111111119'],
                ['authority_group_id' => 1, 'siren' => '491011698'],
                ['authority_group_id' => 2, 'siren' => '443783170'],
            ],
            $rows
        );
    }

    /**
     * Un SIREN identifie une collectivité et une seule : réservé à deux groupes, il reste
     * indisponible pour le second dès que le premier l'a posé, sans quoi on le proposerait pour un
     * enregistrement voué à échouer sur « Numéro de SIREN doit être unique ».
     */
    public function testGetAvailableSirenForAllGroupsExcludesSirenUsedByAnAuthorityOfAnotherGroup(): void
    {
        $this->authorityGroupSirenSQL->add(2, '123456789'); // porté par la collectivité 1, du groupe 1
        $this->authorityGroupSirenSQL->add(2, '491011698');

        $rows = $this->authorityGroupSirenSQL->getAvailableSirenForAllGroups(6);

        self::assertSame([['authority_group_id' => 2, 'siren' => '491011698']], $rows);
    }

    public function testGetAvailableSirenForAllGroupsKeepsSirenOfCurrentAuthority(): void
    {
        $this->authorityGroupSirenSQL->add(1, '123456789'); // siren of authority 1 itself
        $this->authorityGroupSirenSQL->add(1, '999999999'); // taken by authority 2

        // authority 1 keeps its own siren (a.id != ?)
        $rows = $this->authorityGroupSirenSQL->getAvailableSirenForAllGroups(1);

        self::assertSame(
            [['authority_group_id' => 1, 'siren' => '123456789']],
            $rows
        );
    }

    public function testGetAvailableSirenForGroupsExcludesSirenUsedByAnAuthorityOfTheGroup(): void
    {
        $this->givenAuthorityAdministeredByGroup(self::AUTHORITY_1, self::GROUP_1);
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::SIREN_OF_AUTHORITY_1);
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::FREE_SIREN);
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::ANOTHER_FREE_SIREN);

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups(
            [self::GROUP_1],
            self::ANOTHER_AUTHORITY
        );

        self::assertSame([self::ANOTHER_FREE_SIREN, self::FREE_SIREN], $sirens);
    }

    public function testGetAvailableSirenForGroupsExcludesSirenUsedByAnAuthorityOfAnotherGroup(): void
    {
        $this->givenAuthorityAdministeredByGroup(self::AUTHORITY_1, self::GROUP_1);
        $this->authorityGroupSirenSQL->add(self::GROUP_2, self::SIREN_OF_AUTHORITY_1);
        $this->authorityGroupSirenSQL->add(self::GROUP_2, self::FREE_SIREN);

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups(
            [self::GROUP_2],
            self::ANOTHER_AUTHORITY
        );

        self::assertSame([self::FREE_SIREN], $sirens);
    }

    public function testGetAvailableSirenForGroupsKeepsSirenOfCurrentAuthority(): void
    {
        $this->givenAuthorityAdministeredByGroup(self::AUTHORITY_1, self::GROUP_1);
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::SIREN_OF_AUTHORITY_1);

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups(
            [self::GROUP_1],
            self::AUTHORITY_1
        );

        self::assertSame([self::SIREN_OF_AUTHORITY_1], $sirens);
    }

    public function testGetAvailableSirenForGroupsReturnsTheIntersection(): void
    {
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::FREE_SIREN);
        $this->authorityGroupSirenSQL->add(self::GROUP_2, self::FREE_SIREN);
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::ANOTHER_FREE_SIREN);
        $this->authorityGroupSirenSQL->add(self::GROUP_2, self::THIRD_FREE_SIREN);

        $sirens = $this->authorityGroupSirenSQL->getAvailableSirenForGroups(
            [self::GROUP_1, self::GROUP_2],
            self::ANOTHER_AUTHORITY
        );

        self::assertSame([self::FREE_SIREN], $sirens);
    }

    public function testGetAvailableSirenForGroupsWithoutAnyGroupReturnsNothing(): void
    {
        $this->authorityGroupSirenSQL->add(self::GROUP_1, self::FREE_SIREN);

        self::assertSame(
            [],
            $this->authorityGroupSirenSQL->getAvailableSirenForGroups([], self::ANOTHER_AUTHORITY)
        );
    }

    private function givenAuthorityAdministeredByGroup(int $authorityId, int $groupId): void
    {
        $this->getSQLQuery()->query(
            'UPDATE authorities SET actes_group_id = ? WHERE id = ?',
            [$groupId, $authorityId]
        );
    }
}
