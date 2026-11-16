<?php

use S2lowLegacy\Class\Log;
use S2lowLegacy\Model\LogsSQL;

class LogTest extends S2lowTestCase
{
    private const int GROUP_1_ADMINISTRATOR_ID = 7;
    private const int USER_WITHOUT_GROUP_ID = 5;

    public function testNewEntry()
    {

        Log::newEntry("TOTO", "message", 4);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $last_log = $logsSQL->getLastLog();

        $this->assertEquals("message", $last_log['message']);
    }

    public function testNewEntryTracesTheGroupOfTheActingUser(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET authority_group_id = 2, actes_group_id = 2, helios_group_id = 2 WHERE id = 2');

        Log::newEntry('TOTO', 'message', 1, false, 'GADM', 'actes', false, self::GROUP_1_ADMINISTRATOR_ID);

        $this->assertSame(1, $this->getLastLogGroupId());
    }

    public function testNewEntryTracesNoGroupForAUserWithoutGroup(): void
    {
        Log::newEntry('TOTO', 'message', 1, false, 'USER', 'actes', false, self::USER_WITHOUT_GROUP_ID);

        $this->assertNull($this->getLastLogGroupId());
    }

    private function getLastLogGroupId(): ?int
    {
        $groupId = $this->getSQLQuery()->queryOne('SELECT authority_group_id FROM logs ORDER BY id DESC LIMIT 1');

        return $groupId === null ? null : (int)$groupId;
    }
}
