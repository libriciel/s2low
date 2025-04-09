<?php

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class S2lowSimpleTestCase extends KernelTestCase
{
    public function getLogRecords()
    {
        return $this->testHandler->getRecords();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->testHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->testHandler);
    }
}
