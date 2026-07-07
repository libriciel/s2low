<?php

use Monolog\Handler\TestHandler;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Monolog\Level;
use S2low\Factory\PDOFactory;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class S2lowTestCase extends KernelTestCase
{
    protected $backupGlobalsBlacklist = ['sqlQuery'];
    protected $tmpPathFolder;
    protected string $projectDir;
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // files setup
        $this->projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $this->vfsStreamSetup = vfsStream::setup('test');
        $this->tmpPathFolder = vfsStream::url('test');
        $this->secondTmpPathFolder = vfsStream::url('test2');


        // Loggers setup
        $this->testHandler = $this->createTestHandler();
        $this->logger = $this->createLogger($this->testHandler);
        $this->s2lowLogger = $this->createS2lowLogger($this->logger);

        $objectInstancier = new ObjectInstancier(self::getContainer());
        \S2lowLegacy\Class\LegacyObjectsManager::setObjectInstancier($objectInstancier);
        \S2lowLegacy\Lib\ObjectInstancierFactory::setObjectInstancier($objectInstancier);
        \S2lowLegacy\Class\DatabasePool::setObjectInstancier($objectInstancier);
    }

    public function tearDown(): void
    {
        if (self::getContainer()) {
            try {
                $connection = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
                if ($connection) {
                    if ($connection->getTransactionNestingLevel() === 0) {
                        $sql = file_get_contents($this->projectDir . '/test/PHPUnit/s2low-test.sql');
                        $connection->executeStatement($sql);
                    }
                }
            } catch (\Throwable) {
                // Ignore container/DB errors during shutdown
            }
        }
        parent::tearDown();
    }

    /**
     * @deprecated ObjectInstancier n'existe plus : utiliser self::getContainer à la place
     */
    public function getObjectInstancier(): ContainerInterface
    {
        return self::getContainer();
    }

    public function getSQLQuery(): SQLQuery
    {
        return self::getContainer()->get(SQLQuery::class);
    }

    private function createTestHandler(): TestHandler
    {
        return new Monolog\Handler\TestHandler();
    }

    private function createLogger(TestHandler $testHandler): LoggerInterface
    {
        $logger = new \Monolog\Logger('phpunit');
        $logger->pushHandler($testHandler);

        return $logger;
    }

    private function createS2lowLogger(LoggerInterface $logger): LoggerInterface
    {
        return $logger;
    }

    /** @deprecated  */
    public function setExpectedException($e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
}
