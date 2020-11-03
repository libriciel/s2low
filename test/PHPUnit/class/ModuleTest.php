<?php

class ModuleTest extends S2lowTestCase
{
    public function testSecurityHole()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("invalid input syntax for integer");
        $module = new Module("0' UNION SELECT 'you','have been','hacked','0");
        $module->init();
    }

}