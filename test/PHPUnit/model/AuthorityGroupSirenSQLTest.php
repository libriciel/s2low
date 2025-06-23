<?php

use S2lowLegacy\Model\AuthorityGroupSirenSQL;

class AuthorityGroupSirenSQLTest extends S2lowTestCase
{
    /**
     * @var AuthorityGroupSirenSQL
     */
    private $authorityGroupSirenSQL;

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

        $listColl1 = $this->authorityGroupSirenSQL->getAvailableSiren(1, 1);
        static::assertSame(['123456789'], $listColl1);
        $listColl1 = $this->authorityGroupSirenSQL->getAvailableSiren(1, 2);
        static::assertSame(['999999999'], $listColl1);
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
}
