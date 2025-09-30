<?php

use S2lowLegacy\Class\Log;
use S2lowLegacy\Model\LogsSQL;

class LogTest extends S2lowTestCase
{
    public function testNewEntry()
    {

        Log::newEntry("TOTO", "message", 4);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        $this->assertEquals("message", $last_log['message']);
    }
}
