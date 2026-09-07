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
}
