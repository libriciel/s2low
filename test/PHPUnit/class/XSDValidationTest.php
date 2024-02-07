<?php

declare(strict_types=1);

namespace PHPUnit\class;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\XSDValidation;

class XSDValidationTest extends TestCase
{
    /**
     * @var XSDValidation
     */
    private $xsdValidation;

    public function setUp(): void
    {
        $this->xsdValidation = new XSDValidation(__DIR__ . "/fixtures/test.xsd");
    }

    public function testOK()
    {
        $this->assertTrue($this->xsdValidation->validate(file_get_contents(__DIR__ . "/fixtures/test.xml")));
    }

    //http://stackoverflow.com/questions/29953032/large-number-failing-validation-as-type-xsinteger
    public function testBigInt()
    {
        $this->assertFalse(@ $this->xsdValidation->validate(file_get_contents(__DIR__ . "/fixtures/bigint.xml")));
    }
}
