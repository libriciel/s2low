<?php

namespace S2low\Tests\Base;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Controller\PostgreSQLController;
use Throwable;

class S2lowTestListener implements TestListener
{
    public function addError(Test $test, Throwable $t, float $time): void
    {
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
    }

    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
    }

    public function addRiskyTest(Test $test, Throwable $t, float $time): void
    {
    }

    public function addSkippedTest(Test $test, Throwable $t, float $time): void
    {
    }

    public function startTestSuite(TestSuite $suite): void
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

    public function endTestSuite(TestSuite $suite): void
    {
    }

    public function startTest(Test $test): void
    {
    }

    public function endTest(Test $test, float $time): void
    {
    }
}
