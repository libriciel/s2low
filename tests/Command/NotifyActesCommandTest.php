<?php

namespace S2low\Tests\Command;

use S2low\Helpers\ClassHelper;
use S2low\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

class NotifyActesCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $command = $application->find('actes:notification');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            'minimumExecutionTime' => '0',
            '--silent' => true

            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
