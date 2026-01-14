<?php

namespace S2low\Tests\Base;

use S2low\Kernel;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Controller\PostgreSQLController;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

class S2lowTestListener implements \PHPUnit\Framework\TestListener
{
    public function addError(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
    }

    public function addFailure(
        \PHPUnit\Framework\Test $test,
        \PHPUnit\Framework\AssertionFailedError $e,
        float $time
    ): void {
    }

    public function addIncompleteTest(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
    }

    public function addRiskyTest(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
    }

    public function addSkippedTest(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        gc_collect_cycles();
        $kernel = new Kernel('test', false);
        $postgreSQLControler = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
            ->get(PostgreSQLController::class);

        $postgreSQLControler->populateDbTest();

        $application = new Application($kernel);
        $command = $application->find('doctrine:migrations:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--no-interaction' => true]);
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
    }

    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        new Kernel('test', false);
        $sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);
        $sqlQuery->query("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
        $sqlQuery->query("SELECT SETVAL('authority_siret_id_seq', (SELECT MAX(id)+1 FROM authority_siret))");
        $sqlQuery->query("SELECT SETVAL('nounce_id_seq', (SELECT MAX(id)+1 FROM nounce))");
        $sqlQuery->query("SELECT SETVAL('authorities_id_seq', (SELECT MAX(id)+1 FROM authorities))");
        $sqlQuery->query("SELECT SETVAL('helios_transactions_id_seq', (SELECT MAX(id)+1 FROM helios_transactions))");
        $sqlQuery->query("SELECT SETVAL('authority_groups_id_seq', (SELECT MAX(id)+1 FROM authority_groups))");
    }

    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
    }
}
