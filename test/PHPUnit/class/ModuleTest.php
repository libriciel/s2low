<?php

use S2lowLegacy\Model\ModuleSQL;

class ModuleTest extends S2lowTestCase
{
    public function testSecurityHole()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("SQLSTATE[22P02]");

        $moduleSQL = $this->getObjectInstancier()->get(ModuleSQL::class);
        $moduleSQL->getById("0' UNION SELECT 'you','have been','hacked','0");
    }
}
