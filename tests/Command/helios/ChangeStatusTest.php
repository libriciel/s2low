<?php

declare(strict_types=1);

namespace S2low\Tests\Command\helios;

use HeliosUtilitiesTestTrait;
use S2low\Command\Helios\ChangeStatus;
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

    private CommandTester $commandTester;
    protected function setUp(): void
    {
        parent::setUp();
        $heliosStatus = self::getContainer()->get(HeliosStatusSQL::class);
        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);

        $command = new ChangeStatus(
            $heliosStatus,
            $heliosTransactionSQL
        );

        $this->commandTester = new CommandTester($command);
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    public function testCommandBadTransactionId(): void
    {
        $this->commandTester->execute(['transaction-id' => 101,'status-id' => 2]);
        static::assertStringContainsString(
            'transaction_id incorrect : aucune transaction trouvée',
            $this->commandTester->getDisplay()
        );
        static::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testCommand(): void
    {
        $transactionId = $this->createTransaction(101, HeliosStatusSQL::POSTE);
        $this->commandTester->execute(['transaction-id' => $transactionId,'status-id' => 1, '--force' => true]);
        static::assertStringContainsString(
            "Modification de la transaction $transactionId : status Posté [1]",
            $this->commandTester->getDisplay()
        );
        static::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testCommandAnnulee(): void
    {
        $transactionId = $this->createTransaction(101, HeliosStatusSQL::POSTE);
        $this->commandTester->setInputs(['No']);
        $this->commandTester->execute(['transaction-id' => $transactionId,'status-id' => 1]);
        static::assertStringContainsString(
            'Changement de statut annulé',
            $this->commandTester->getDisplay()
        );
        static::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
