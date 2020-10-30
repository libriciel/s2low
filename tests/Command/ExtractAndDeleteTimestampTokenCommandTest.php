<?php

namespace S2low\Tests\Command;

use LogsHistoriqueSQL;
use S2low\Command\ExtractAndDeleteTimestampTokenCommand;
use S2low\Tests\LogsHistoriqueSQLTrait;
use S2lowTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ExtractAndDeleteTimestampTokenCommandTest extends S2lowTestCase
{
    use LogsHistoriqueSQLTrait;

    public function getLogHistoriqueSQL(): LogsHistoriqueSQL
    {
        return $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);
    }

    public function testCommand()
    {
        $this->addFixtures();
        $this->getObjectInstancier()->set('old_timestamp_token_directory',"/tmp/");
        $this->getObjectInstancier()->set('timestamp_token_retention_nb_days',42);
        $extractAndDeleteTimestampTokenCommand = $this->getObjectInstancier()
            ->get(ExtractAndDeleteTimestampTokenCommand::class);
        $commandTester = new CommandTester($extractAndDeleteTimestampTokenCommand);
        $commandTester->execute(['--force'=>'true','--limit'=>'1']);
        $this->assertStringContainsString("[OK] Done", $commandTester->getDisplay());
    }


}
