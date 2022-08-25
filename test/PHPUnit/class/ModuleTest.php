<?php

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
