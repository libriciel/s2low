<?php

namespace S2low\Tests\Command;

use S2low\Command\ExtractAndDeleteTimestampTokenCommand;
use S2lowTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ExtractAndDeleteTimestampTokenCommandTest extends S2lowTestCase
{
    public function testCommand()
    {
        $this->getObjectInstancier()->set('old_timestamp_token_directory',"/tmp/");
        $this->getObjectInstancier()->set('timestamp_token_retention_nb_days',42);
        $extractAndDeleteTimestampTokenCommand = $this->getObjectInstancier()
            ->get(ExtractAndDeleteTimestampTokenCommand::class);
        $commandTester = new CommandTester($extractAndDeleteTimestampTokenCommand);
        $commandTester->execute(['--force'=>'']);
        $this->assertStringContainsString("[OK] Pass", $commandTester->getDisplay());
    }
}
