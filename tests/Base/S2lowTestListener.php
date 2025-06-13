<?php

namespace S2low\Tests\Base;

use S2low\Kernel;
use S2lowLegacy\Controller\PostgreSQLController;
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
