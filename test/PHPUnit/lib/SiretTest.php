<?php

class SiretTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Siret
     */
    private $siret;

    public function setUp(): void
    {
        $this->siret = new Siret(new LuhnKey(), new Siren(new LuhnKey()));
    }

    public function testGood()
    {
        $this->assertTrue($this->siret->isValid("49358727300035"));
    }

    public function testBad()
    {
        $this->assertFalse($this->siret->isValid("49358727300036"));
    }

    public function testBadNotASiren()
    {
        $this->assertFalse($this->siret->isValid("49358727400033"));
    }

    public function testBadLength()
    {
        $this->assertFalse($this->siret->isValid("493587273"));
    }

    public function testGenerate()
    {
        $this->assertTrue($this->siret->isValid($this->siret->generate()));
    }
}
