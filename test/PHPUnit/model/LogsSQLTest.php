<?php

use S2lowLegacy\Class\Log;
use S2lowLegacy\Model\LogsSQL;

class LogsSQLTest extends S2lowTestCase
{
    /**
     * @var LogsSQL
     */
    private $logsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        Log::newEntry(
            "test",
            "message de test",
            1,
            false,
            "USER",
            "actes",
            false,
            1
        );
        $this->logsSQL = new LogsSQL($this->getSQLQuery());
    }

    public function testGetLogLevelList()
    {
        $this->assertNotEmpty($this->logsSQL->getLogLevelList());
    }

    public function testGetNbLog()
    {
        $this->assertEquals(
            1,
            $this->logsSQL->getNbLog(
                false,
                false,
                false,
                false,
                false,
                -1,
                false,
                false,
                false,
                false
            )
        );
    }

    public function testGetList()
    {
        $result = $this->logsSQL->getList(
            false,
            false,
            false,
            false,
            false,
            -1,
            false,
            false,
            0,
            10,
            false,
            false
        );
        $this->assertEquals("message de test", $result[0]['message']);
    }

    public function testGetListAllFiltre()
    {
        $this->assertEquals(
            1,
            $this->logsSQL->getNbLog(
                1,
                1,
                1,
                "Eric",
                "actes",
                LogsSQL::LEVEL_INFO,
                "message de test",
                array("USER"),
                '1970-01-01',
                '2032-12-31'
            )
        );
    }

    public function testGetMinDate()
    {
        $logsSQL = new LogsSQL($this->getSQLQuery());
        $today = date("Y-m-d H:i:s");
        $logsSQL->addLog(
            $today,
            1,
            "actes",
            "TdT",
            1,
            'SADM',
            'message test 1'
        );
        $min_date = $this->logsSQL->getMinDate();
        $this->assertNotNull($min_date);
    }

    public function testGetNbLogOfAGroupKeepsLogsOfTheModuleItAdministers(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 1 WHERE id = 1');

        $this->assertSame(1, (int)$this->countLogsOfGroup(2));
    }

    public function testGetNbLogOfAGroupLeavesOutLogsOfTheModuleItDoesNotAdminister(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 1');
        Log::newEntry('test', 'message helios', 1, false, 'USER', 'helios', false, 1);

        $this->assertSame(1, (int)$this->countLogsOfGroup(2));
        $this->assertSame(1, (int)$this->countLogsOfGroup(1));
    }

    public function testGetNbLogOfAGroupKeepsLogsWithoutModuleOfAnAuthorityItAdministersForASingleModule(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 1');
        Log::newEntry('test', 'message sans module', 1, false, 'USER', false, false, 1);

        $this->assertSame(1, (int)$this->countLogsOfGroup(2));
    }

    public function testGetNbLogShowsMailLogsToTheActesGroupAndToTheHeliosGroup(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 1');
        Log::newEntry('test', 'message mail', 1, false, 'USER', 'mail', false, 1);

        $this->assertSame(1, (int)$this->countLogsOfGroup(1, 'mail'));
        $this->assertSame(1, (int)$this->countLogsOfGroup(2, 'mail'));
    }

    public function testGetNbLogOfAGroupLeavesOutLogsOfAnAuthorityItDoesNotAdminister(): void
    {
        $this->assertSame(0, (int)$this->countLogsOfGroup(2));
    }

    private function countLogsOfGroup(int $groupId, string|false $module = false): int|string
    {
        return $this->logsSQL->getNbLog($groupId, false, false, false, $module, -1, false, false, false, false);
    }
}
