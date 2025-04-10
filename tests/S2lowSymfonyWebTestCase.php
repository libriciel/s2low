<?php

namespace S2low\Tests;

use Exception;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use TestEnvironmentManager;

abstract class S2lowSymfonyWebTestCase extends WebTestCase
{
    protected $backupGlobalsBlacklist = array('sqlQuery');

    private $testEnvironnementManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = static::getContainer();

        $this->testEnvironnementManager = new TestEnvironmentManager();
        $this->testEnvironnementManager->setUp();
    }

    protected function tearDown(): void
    {
        $this->getActesWorkspace()->clear();
        parent::tearDown();
    }

    /**
     * @return ObjectInstancier
     */
    public function getObjectInstancier()
    {
        return  $this->testEnvironnementManager->getObjectInstancier();
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery()
    {
        return $this->testEnvironnementManager->getSQLQuery();
    }

    public function setSuperAdminAuthentication()
    {
        $this->testEnvironnementManager->setSuperAdminAuthentication();
    }

    public function setAdminGroupAuthentication()
    {
        $this->setAdminGroupAuthentication();
    }

    public function setAdminGroup2Authentication()
    {
        $this->setAdminGroup2Authentication();
    }


    public function setAdminColAuthentication()
    {
        $this->setAdminColAuthentication();
    }

    public function setAdminCol2Authentication()
    {
        $this->setAdminCol2Authentication();
    }

    public function setUserAuthentification()
    {
        $this->setUserAuthentification();
    }

    public function getLogRecords()
    {
        return $this->testEnvironnementManager->getLogRecords();
    }

    public function assertLogMessage($expected_message, $num_log = 0)
    {
        $this->assertEquals(
            $expected_message,
            $this->getLogRecords()[$num_log]['message']
        );
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
    protected function getActesWorkspace(): IActesWorkspace
    {
        return $this->getObjectInstancier()->get(IActesWorkspace::class);
    }
}
