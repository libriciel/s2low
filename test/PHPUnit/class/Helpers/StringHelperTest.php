<?php

namespace S2lowLegacy\Test\Class\Helpers;

use PHPUnit\class\Helpers\LegacyHelpers;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\StringHelper;

class StringHelperTest extends TestCase
{
    public function testGetFromBDD()
    {
        $input = "some val";
        $this->assertEquals(LegacyHelpers::getFromBDD($input), StringHelper::getFromBDD($input));
    }

    public function testEscapeForXML()
    {
        $input = 'some " value';
        $this->assertEquals(LegacyHelpers::escapeForXML($input), StringHelper::escapeForXML($input));
    }

    public function testTruncateString()
    {
        $input = str_repeat("a", 50);
        $this->assertEquals(LegacyHelpers::truncateString($input), StringHelper::truncateString($input));
    }

    public function testChunkString()
    {
        $input = str_repeat("a", 50);
        $this->assertEquals(LegacyHelpers::chunkString($input, 10), StringHelper::chunkString($input, 10));
    }

    public function testGenTempName()
    {
        $this->assertIsString(StringHelper::genTempName());
    }
}
