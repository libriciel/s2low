<?php

namespace S2lowLegacy\Test\Class\Helpers;

use PHPUnit\class\Helpers\LegacyHelpers;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\SessionHelper;

class SessionHelperTest extends TestCase
{
    public function testPutAndGet()
    {
        LegacyHelpers::putInSession('test2', 'val2');
        $expected = LegacyHelpers::getFromSession('test2', false);

        SessionHelper::putInSession('test', 'val');
        $actual = SessionHelper::getFromSession('test', false);

        $this->assertEquals('val', $actual);
        $this->assertEquals('val2', $expected);
    }
}
