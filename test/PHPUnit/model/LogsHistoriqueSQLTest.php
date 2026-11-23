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

        $logsSQL = new LogsSQL($this->getSQLQuery());
        $logsSQL->addLog(
            $this->last_month,
            1,
            "actes",
            "TdT",
            1,
            'SADM',
            'message test 1'
        );
        $logsSQL->addLog(
            $today,
            1,
            "actes",
            "TdT",
            1,
            'SADM',
            'message test 2'
        );

        $this->logsHistoriqueSQL = new LogsHistoriqueSQL($this->getSQLQuery());
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

    public function testRequestOfAGroupKeepsLogsOfTheModuleItAdministers(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 1 WHERE id = 1');

        $export = $this->exportLogsOfGroup(2);

        $this->assertStringContainsString('message test 1', $export);
        $this->assertStringContainsString('message test 2', $export);
    }

    public function testRequestOfAGroupLeavesOutLogsOfTheModuleItDoesNotAdminister(): void
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 1');

        $this->assertSame('', $this->exportLogsOfGroup(2));
    }

    public function testRequestOfAGroupLeavesOutLogsOfAnAuthorityItDoesNotAdminister(): void
    {
        $this->assertSame('', $this->exportLogsOfGroup(2));
    }

    private function exportLogsOfGroup(int $groupId): string
    {
        $logsRequestData = new LogsRequestData();
        $logsRequestData->authority_group_id = $groupId;
        $logsRequestData->date_debut = '1970-01-01';
        $logsRequestData->date_fin = date('Y-m-d');
        $exportPath = $this->tmpPathFolder . '/logs-export.csv';

        $this->logsHistoriqueSQL->request($logsRequestData, $exportPath);

        return (string)file_get_contents($exportPath);
    }
}
