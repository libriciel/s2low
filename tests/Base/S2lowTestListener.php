<?php

namespace S2low\Tests\Base;

use S2lowLegacy\Lib\ObjectInstancier;
use PostgreSQLController;
use Throwable;

class S2lowTestListener implements \PHPUnit\Framework\TestListener
{
    public function addError(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
    }

    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
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
        if ($suite->getName() === "S2low_integration") {
            \S2lowLegacy\Class\LegacyObjectsManager::resetObjectInstancier();
        } elseif ($suite->getName() === "S2low") {
            /** @var ObjectInstancier $objectInstancier */
            $objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
                ->get(ObjectInstancier::class);
            $postgreSQLControler = $objectInstancier->get(PostgreSQLController::class);

            $postgreSQLControler->alterDatabase(function ($message) {
                echo $message . "\n";
            });
        }
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
    }

    public function startTest(\PHPUnit\Framework\Test $test): void
    {
    }

    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
    }
}
