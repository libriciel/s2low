<?php

use PHPUnit\Framework\TestCase;

class S2lowSimpleTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testEnvironment = new TestEnvironmentManager();
        $testEnvironment->setUp();
        $this->objectInstancieur = $testEnvironment->getObjectInstancier();
    }

    public function getObjectInstancier()
    {
        return $this->objectInstancieur;
    }


    public function getLogRecords()
    {
        $testHandler = $this->getObjectInstancier()->get(Monolog\Handler\TestHandler::class);
        return $testHandler->getRecords();
    }
    /** @deprecated  */
    public function setExpectedException(string $e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
}
