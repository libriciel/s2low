<?php

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use S2low\Tests\Services\ShellCommandMockBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class S2lowSimpleTestCase extends KernelTestCase
{
    public ShellCommandMockBuilder $shellCommandMockBuilder;

    public function getLogRecords()
    {
        return $this->testHandler->getRecords();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = static::getContainer();
        $this->shellCommandMockBuilder = new ShellCommandMockBuilder($this);
        $this->testHandler = new TestHandler();
        $this->logger = new Logger('test');
        $this->logger->pushHandler($this->testHandler);
    }
}
