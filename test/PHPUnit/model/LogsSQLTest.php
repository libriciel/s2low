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
            101
        );
        $this->logsSQL = self::getContainer()->get(LogsSQL::class);
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
                101,
                101,
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
        $logsSQL = self::getContainer()->get(LogsSQL::class);
        $today = date("Y-m-d H:i:s");
        $logsSQL->addLog(
            $today,
            1,
            "actes",
            "TdT",
            101,
            'SADM',
            'message test 1',
            false
        );
        $min_date = $this->logsSQL->getMinDate();
        $this->assertNotNull($min_date);
    }
}
