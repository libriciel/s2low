<?php

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use S2lowLegacy\Lib\ObjectInstancier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class S2lowSimpleTestCase extends KernelTestCase
{
    public function getObjectInstancier()
    {
        return \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
    }

    public function getLogRecords()
    {
        return $this->testHandler->getRecords();
    }


    protected function setUp(): void
    {
        parent::setUp();
        \S2lowLegacy\Lib\ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->testHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->testHandler);
    }
}
