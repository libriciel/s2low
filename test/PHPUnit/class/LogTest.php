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
        $this->assertMatchesRegularExpression("#message#", $last_log['message_horodate']);
        $log = new Log($last_log['id']);
        $log->init();
        $log->generateMessageHorodate();
        $this->assertEquals($log->generateMessageHorodate(), $last_log['message_horodate']);
    }


    public function testLogEntryOldFashioned()
    {
        Log::newEntry("TOTO", "message", 4);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        $sql = "UPDATE logs SET message_horodate=NULL WHERE id=?";
        $this->getSQLQuery()->query($sql, $last_log['id']);

        $log = new Log($last_log['id']);
        $log->init();
        $this->assertNotEquals($last_log['message_horodate'], $log->retrieveMessageHorodate());
    }
}
