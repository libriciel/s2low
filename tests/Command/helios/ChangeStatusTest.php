<?php

declare(strict_types=1);

namespace S2low\Tests\Command\helios;

use HeliosUtilitiesTestTrait;
use S2low\Kernel;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ChangeStatusTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    public function testCommandBadTransactionId(): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->execute(['transaction-id' => 1,'status-id' => 2]);
        static::assertStringContainsString(
            'transaction_id incorrect : aucune transaction trouvée',
            $commandTester->getDisplay()
        );
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
    public function testCommand(): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $transactionId = $this->createTransaction(1, HeliosStatusSQL::POSTE);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->execute(['transaction-id' => $transactionId,'status-id' => 1, '--force' => true]);
        static::assertStringContainsString(
            "Modification de la transaction $transactionId : status Posté [1]",
            $commandTester->getDisplay()
        );
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testCommandAnnulee(): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $transactionId = $this->createTransaction(1, HeliosStatusSQL::POSTE);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->setInputs(['No']);
        $commandTester->execute(['transaction-id' => $transactionId,'status-id' => 1]);
        static::assertStringContainsString(
            'Changement de statut annulé',
            $commandTester->getDisplay()
        );
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
