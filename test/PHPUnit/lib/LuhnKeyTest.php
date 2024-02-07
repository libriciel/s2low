<?php

declare(strict_types=1);

namespace PHPUnit\lib;

//http://www.webozor.com/php/clef-de-luhn-ou-formule-de-luhn
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\LuhnKey;

class LuhnKeyTest extends TestCase
{
    /**
     * @var LuhnKey
     */
    private $luhnKey;

    public function setUp(): void
    {
        $this->luhnKey = new LuhnKey();
    }

    public function test1()
    {
        $this->assertTrue($this->luhnKey->isValid(49927398716));
    }

    public function test2()
    {
        $this->assertFalse($this->luhnKey->isValid(49927398717));
    }

    public function test3()
    {
        $this->assertFalse($this->luhnKey->isValid(1234567812345678));
    }

    public function test4()
    {
        $this->assertTrue($this->luhnKey->isValid(1234567812345670));
    }

    public function testGetNumber()
    {
        $this->assertEquals("1234567812345670", $this->luhnKey->getValidNumberWithBegin(123456781234567));
    }

    public function testGetOther()
    {
        $this->assertEquals("49927398716", $this->luhnKey->getValidNumberWithBegin(4992739871));
    }

    public function testGenerate()
    {
        $this->assertTrue($this->luhnKey->isValid($this->luhnKey->generateValidNumber(9)));
    }
}
