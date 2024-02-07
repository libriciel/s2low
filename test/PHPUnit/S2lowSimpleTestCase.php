<?php

declare(strict_types=1);

namespace PHPUnit;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use S2lowLegacy\Lib\ObjectInstancier;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\ObjectInstancierFactory;

/**
 *
 */
class S2lowSimpleTestCase extends TestCase
{
    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->set(Logger::class, new  Logger('PHPUNIT'));
        $testHandler = new TestHandler();
        $testHandler->setLevel(Logger::DEBUG);
        $this->getObjectInstancier()->set(TestHandler::class, $testHandler);
        $this->getObjectInstancier()->get(Logger::class)->pushHandler($testHandler);
    }

    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ObjectInstancierFactory::resetObjectInstancier();
    }

    /**
     * @return \S2lowLegacy\Lib\ObjectInstancier
     */
    public function getObjectInstancier(): ObjectInstancier
    {
        return ObjectInstancierFactory::getObjetInstancier();
    }


    /**
     * @return array
     */
    public function getLogRecords(): array
    {
        /** @var TestHandler $testHandler */
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        return $testHandler->getRecords();
    }
    /** @deprecated  */
    public function setExpectedException(string $e, string $message): void
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
}
