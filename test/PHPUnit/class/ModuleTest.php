<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\Module;

class ModuleTest extends S2lowTestCase
{
    public function testSecurityHole()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("SQLSTATE[22P02]");
        $module = new Module("0' UNION SELECT 'you','have been','hacked','0");
        $module->init();
    }
}
