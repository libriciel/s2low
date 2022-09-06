<?php

namespace S2low\Tests\Command;

use S2low\Kernel;
use S2lowLegacy\Model\LogsHistoriqueSQL;
use S2low\Command\ExtractAndDeleteTimestampTokenCommand;
use S2low\Tests\LogsHistoriqueSQLTrait;
use S2lowTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
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
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $extractAndDeleteTimestampTokenCommand = $application->find('log:timestamp-token-extract-and-delete');

        $commandTester = new CommandTester($extractAndDeleteTimestampTokenCommand);
        $commandTester->execute(['--force' => 'true','--limit' => '1']);
        $this->assertStringContainsString("[OK]", $commandTester->getDisplay());
    }
}
