<?php

use Monolog\Handler\TestHandler;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Monolog\Level;
use S2low\Factory\PDOFactory;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\S2lowLogger;
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

        //Database Setup
        self::getContainer()->get(PDOFactory::class)->create()->exec(file_get_contents(__DIR__ . '/../PHPUnit/s2low-test.sql'));

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
        self::getContainer()->get(Database::class)->disconnect();
        self::getContainer()->get(PDOFactory::class)->closeAll();
        self::ensureKernelShutdown();
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

    public function createTestHandler(): TestHandler
    {
        return new Monolog\Handler\TestHandler();
    }

    public function createLogger(TestHandler $testHandler): LoggerInterface
    {
        $logger = new \Monolog\Logger('phpunit');
        $logger->pushHandler($testHandler);

        return $logger;
    }

    public function createS2lowLogger(LoggerInterface $logger): S2lowLogger
    {
        return new S2lowLogger($logger);
    }

    protected function setServerInfo(array $server_info)
    {
        $this->testEnvironmentManager->setServerInfo($server_info);
    }

    public function setAdminGroupAuthentication()
    {
        $this->testEnvironmentManager->setAdminGroupAuthentication();
    }

    public function setAdminGroup2Authentication()
    {
        $this->testEnvironmentManager->setAdminGroup2Authentication();
    }


    public function setAdminColAuthentication()
    {
        $this->testEnvironmentManager->setAdminColAuthentication();
    }

    public function setAdminCol2Authentication()
    {
        $this->testEnvironmentManager->setAdminCol2Authentication();
    }

    /**
     * @return void
     */
    public function setRGSAuthentification(): void
    {
        throw new Exception("seek this answer to debug 6234d5463");
        $rgsConnexion = $this->getMockBuilder(RgsConnexion::class)->disableOriginalConstructor()->getMock();
        $rgsConnexion->method('isRgsConnexion')->willReturn(true);
        $this->getObjectInstancier()->{RgsConnexion::class} = $rgsConnexion;
    }

    public function setUserAuthentification()
    {
        $this->testEnvironmentManager->setUserAuthentification();
    }

    public function setArchAuthentification(): void
    {
        $this->testEnvironmentManager->setArchAuthentification();
    }

    /**
     * @deprecated methode deprecated il faut maintenant injecter un logger avec le testHandler
     */
    public function getLogRecords()
    {
        throw new Exception("seek this answer to debug 62345463");
    }

    public function assertLogMessage($expected_message, Level $level): void
    {
        throw new Exception("seek this answer to debug 848756543");
    }

    public function assertMatchesRegularExpressionLogMessage($expected_message, $num_log = 0)
    {
        $this->assertMatchesRegularExpression(
            $expected_message,
            $this->getLogRecords()[$num_log]['message']
        );
    }

    /** @deprecated  */
    public function setExpectedException($e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
    /** @deprecated  */
    public function noAssertion()
    {
        $this->assertTrue(true);
    }

    /**
     * @deprecated only for refacto purpose, delete this methode si vous la voyez
     */
    public function log()
    {
        dd($this->testHandler->getRecords());
    }
}
