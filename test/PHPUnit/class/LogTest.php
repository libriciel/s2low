<?php

use S2lowLegacy\Class\Log;
use S2lowLegacy\Model\LogsSQL;

class LogTest extends S2lowTestCase
{
    private const int GROUP_1_ADMINISTRATOR_ID = 7;

    public function testNewEntry()
    {

        Log::newEntry("TOTO", "message", 4);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        $this->assertEquals("message", $last_log['message']);
    }

    public function testNewEntryLeavesTheGroupEmptyEvenForAGroupAdministrator(): void
    {
        Log::newEntry('TOTO', 'message', 1, false, 'GADM', 'actes', false, self::GROUP_1_ADMINISTRATOR_ID);

        $this->assertNull($this->getLastLogGroupId());
    }

    private function getLastLogGroupId(): ?int
    {
        $groupId = $this->getSQLQuery()->queryOne('SELECT authority_group_id FROM logs ORDER BY id DESC LIMIT 1');

        return $groupId === null ? null : (int)$groupId;
    }
}
