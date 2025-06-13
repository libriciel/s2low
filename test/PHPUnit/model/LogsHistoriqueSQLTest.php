<?php

use S2lowLegacy\Model\LogsHistoriqueSQL;
use S2lowLegacy\Model\LogsRequestData;
use S2lowLegacy\Model\LogsSQL;

class LogsHistoriqueSQLTest extends S2lowTestCase
{
    private $logsHistoriqueSQL;

    private $last_month;

    protected function setUp(): void
    {
        parent::setUp();
        $this->last_month = date("Y-m-d", strtotime("-2 month"));
        $today = date("Y-m-d");

        $logsSQL = self::getContainer()->get(LogsSQL::class);
        $logsSQL->addLog(
            $this->last_month,
            1,
            "actes",
            "TdT",
            101,
            'SADM',
            'message test 1',
            false
        );
        $logsSQL->addLog(
            $today,
            1,
            "actes",
            "TdT",
            101,
            'SADM',
            'message test 2',
            false
        );

        $this->logsHistoriqueSQL = self::getContainer()->get(LogsHistoriqueSQL::class);
        $this->logsHistoriqueSQL->vidange(1);
    }


    public function testVidange()
    {
        $sql = "SELECT count(*) FROM logs";
        $this->assertEquals(1, $this->getSQLQuery()->queryOne($sql));

        $sql = "SELECT count(*) FROM logs_historique";
        $this->assertEquals(1, $this->getSQLQuery()->queryOne($sql));
    }

    public function testGetMaxDate()
    {
        $this->assertMatchesRegularExpression("#^{$this->last_month}#", $this->logsHistoriqueSQL->getMaxDate());
    }

    public function testRequest()
    {
        $output = "php://output";
        $logsRequestData = new LogsRequestData();
        $logsRequestData->date_debut = "1970-01-01";
        $logsRequestData->date_fin = date("Y-m-d");

        $this->expectOutputRegex("#message test 2#");
        $this->logsHistoriqueSQL->request($logsRequestData, $output);
    }
}
