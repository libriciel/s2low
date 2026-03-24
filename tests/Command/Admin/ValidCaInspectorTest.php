<?php

namespace Command\Admin;

use S2low\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidCaInspectorTest extends KernelTestCase
{
    public function testExecute()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $command = $application->find('admin:validca-inspector');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            'validca_dir' => '/data/certificates/_validca/validca/',

            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
