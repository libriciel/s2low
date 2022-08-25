<?php

use S2lowLegacy\Model\NounceSQL;

class NounceSQLTest extends S2lowTestCase
{
    /** @var  NounceSQL */
    private $nounceSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nounceSQL = $this->getObjectInstancier()->{NounceSQL::class};
    }

    public function testGetNounce()
    {
        $nounce = $this->nounceSQL->create("toto", "MonMotDePasse", 1);
        $this->assertNotEmpty($nounce);
    }

    public function testMenage()
    {
        $sql = "INSERT into nounce(creation) VALUES (?)";
        $this->getSQLQuery()->query($sql, date("c", strtotime("now -1 hours")));
        $sql = "SELECT count(*) FROM nounce";
        $this->assertEquals(1, $this->getSQLQuery()->queryOne($sql));
        $this->nounceSQL->menage();
        $this->assertEquals(0, $this->getSQLQuery()->queryOne($sql));
    }

    public function testVerify()
    {
        $nounce = $this->nounceSQL->create("toto", "MonMotDePasse", 1);
        $hash = hash("sha256", "MonMotDePasse:$nounce");
        $this->assertEquals(1, $this->nounceSQL->verify("toto", $nounce, $hash));
    }

    public function testVerifyFalse()
    {
        $nounce = $this->nounceSQL->create("toto", "MonMotDePasse", 1);
        $hash = "badhash";
        $this->assertFalse($this->nounceSQL->verify("toto", $nounce, $hash));
    }
}
