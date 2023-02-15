<?php

use S2lowLegacy\Lib\LuhnKey;
use S2lowLegacy\Lib\Siren;
use S2lowLegacy\Lib\Siret;

class SiretTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Siret
     */
    private $siretFactory;

    public function setUp(): void
    {
        $this->siretFactory = new \S2lowLegacy\Lib\SiretFactory(new LuhnKey(), new \S2lowLegacy\Lib\SirenFactory(new LuhnKey()));
    }

    public function testGood()
    {
        $this->assertTrue($this->siretFactory->get("49358727300035")->isValid());
    }

    public function testBad()
    {
        $this->assertFalse($this->siretFactory->get("49358727300036")->isValid());
    }

    public function testBadNotASiren()
    {
        $this->assertFalse($this->siretFactory->get("49358727400033")->isValid());
    }

    public function testBadLength()
    {
        $this->assertFalse($this->siretFactory->get("493587273")->isValid());
    }

    public function testGenerate()
    {
        $this->assertTrue($this->siretFactory->get($this->siretFactory->generate()->getValue())->isValid());
    }
}
