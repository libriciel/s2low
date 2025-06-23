<?php

use S2lowLegacy\Model\AuthoritySiretSQL;

class AuthoritySiretTest extends S2lowTestCase
{
    private const SIRET_EXEMPLE = 49358727300035;

    /**
     * @var AuthoritySiretSQL
     */
    private $authoritySiret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authoritySiret = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
    }

    public function testSiretListEmpty()
    {
        $this->assertEmpty($this->authoritySiret->siretList(1000));
    }

    public function testAddSiret()
    {
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $siret_list = $this->authoritySiret->siretList(1);
        $sirets = array_column($siret_list, 'siret');

        $this->assertContains((string) self::SIRET_EXEMPLE, $sirets);
    }

    public function testAddMultipleSiret()
    {
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $siret_list = $this->authoritySiret->siretList(1);
        $this->assertEquals(2, count($siret_list));
    }

    public function testDel()
    {
        $id = $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->authoritySiret->del($id);
        $this->assertEquals(1, count($this->authoritySiret->siretList(1)));
    }

    public function testAuthorityListEmpty()
    {
        $this->assertEmpty($this->authoritySiret->authorityList(self::SIRET_EXEMPLE));
    }

    public function testAuthorityList()
    {
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $authority_list = $this->authoritySiret->authorityList(self::SIRET_EXEMPLE);
        $this->assertEquals(1, $authority_list[0]['authority_id']);
    }

    public function testBlockedEmpty()
    {
        $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->assertEmpty($this->authoritySiret->siretListBlocked(1));
    }

    public function testBlocked()
    {
        $id = $this->authoritySiret->add(1, self::SIRET_EXEMPLE);

        $this->authoritySiret->blocked($id);
        $blocked_list = $this->authoritySiret->siretListBlocked(1);
        $this->assertEquals(1, count($blocked_list));
        $this->assertEmpty($this->authoritySiret->authorityList(self::SIRET_EXEMPLE));
        $this->assertEquals(1, count($this->authoritySiret->siretList(1)));
    }

    public function testUnblocked()
    {
        $id = $this->authoritySiret->add(1, self::SIRET_EXEMPLE);
        $this->authoritySiret->blocked($id);
        $this->authoritySiret->unblocked($id);
        $this->assertEmpty($this->authoritySiret->siretListBlocked(1));
        $this->assertNotEmpty($this->authoritySiret->authorityList(self::SIRET_EXEMPLE));
        $this->assertNotEmpty($this->authoritySiret->siretList(1));
    }
}
