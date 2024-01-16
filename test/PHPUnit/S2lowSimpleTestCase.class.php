<?php

use S2lowLegacy\Lib\ObjectInstancier;
use PHPUnit\Framework\TestCase;

class S2lowSimpleTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \S2lowLegacy\Lib\ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->set(Monolog\Logger::class, new  Monolog\Logger('PHPUNIT'));
        $testHandler = new Monolog\Handler\TestHandler();
        $testHandler->setLevel(\Monolog\Logger::DEBUG);
        $this->getObjectInstancier()->set(Monolog\Handler\TestHandler::class, $testHandler);
        $this->getObjectInstancier()->get(Monolog\Logger::class)->pushHandler($testHandler);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        \S2lowLegacy\Lib\ObjectInstancierFactory::resetObjectInstancier();
    }

    public function getObjectInstancier()
    {
        return \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
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
