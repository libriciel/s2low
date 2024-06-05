<?php

declare(strict_types=1);

namespace S2low\Tests\Command\helios;

use HeliosUtilitiesTestTrait;
use S2low\Kernel;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ChangeStatusTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    /**
     * @dataProvider badTransactionId
     */
    public function testCommandBadTransactionId(string $transactionId, string $message): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->execute(['transaction-id' => $transactionId]);
        static::assertStringContainsString(
            $message,
            $commandTester->getDisplay()
        );
        static::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function badTransactionId(): iterable
    {
        yield ['1','transaction_id incorrect : aucune transaction trouvée'];
        yield ['pouet','transaction_id doit être un entier'];
    }

    public function testCommand(): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $transactionId = $this->createTransaction(1, HeliosStatusSQL::POSTE);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->execute(['transaction-id' => $transactionId,'--status-id' => 1, '--force' => true]);
        static::assertStringContainsString(
            "Modification de la transaction $transactionId : status Posté [1]",
            $commandTester->getDisplay()
        );
        static::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testCommandAnnulée(): void
    {
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $transactionId = $this->createTransaction(1, HeliosStatusSQL::POSTE);

        $commandTester = new CommandTester($application->find('helios:change-status'));
        $commandTester->setInputs(['No']);
        $commandTester->execute(['transaction-id' => $transactionId,'--status-id' => 1]);
        static::assertStringContainsString(
            'Changement de statut annulé',
            $commandTester->getDisplay()
        );
        static::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
