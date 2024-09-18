<?php

declare(strict_types=1);

namespace PHPUnit\class;

use LogSeverity;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Model\LogsSQL;
use S2lowTestCase;

class LogTest extends S2lowTestCase
{
    public function testNewEntry()
    {

        Log::newEntry('TOTO', 'message', LogSeverity::CRITICAL);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        static::assertEquals('message', $last_log['message']);
        static::assertMatchesRegularExpression('#message#', $last_log['message_horodate']);
        $log = new Log($last_log['id']);
        $log->init();
        $log->generateMessageHorodate();
        static::assertEquals($log->generateMessageHorodate(), $last_log['message_horodate']);
    }


    /**
     * @throws \Exception
     */
    public function testLogEntryOldFashioned()
    {
        Log::newEntry('TOTO', 'message', LogSeverity::CRITICAL);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        $sql = 'UPDATE logs SET message_horodate=NULL WHERE id=?';
        $this->getSQLQuery()->query($sql, $last_log['id']);

        $log = new Log($last_log['id']);
        $log->init();
        static::assertNotEquals($last_log['message_horodate'], $log->retrieveMessageHorodate());
    }
}
