<?php

declare(strict_types=1);

namespace PHPUnit;

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use PHPUnit\Framework\TestCase;
use TestEnvironmentManager;

abstract class S2lowTestCase extends TestCase
{
    protected $backupGlobalsBlacklist = array('sqlQuery');

    private TestEnvironmentManager $testEnvironmentManager;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        LegacyObjectsManager::setLegacyObjectInstancier();
        $this->testEnvironmentManager = new TestEnvironmentManager();
        $this->testEnvironmentManager->setUp();
    }

    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        LegacyObjectsManager::resetObjectInstancier();
    }

    /**
     * @return ObjectInstancier
     */
    public function getObjectInstancier(): ObjectInstancier
    {
        return  $this->testEnvironmentManager->getObjectInstancier();
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery(): SQLQuery
    {
        return $this->testEnvironmentManager->getSQLQuery();
    }

    /**
     * @param array $server_info
     * @return null
     */
    protected function setServerInfo(array $server_info)
    {
        $this->testEnvironmentManager->setServerInfo($server_info);
    }

    /**
     * @return void
     */
    public function setSuperAdminAuthentication(): void
    {
        $this->testEnvironmentManager->setSuperAdminAuthentication();
    }

    /**
     * @return void
     */
    public function setAdminGroupAuthentication(): void
    {
        $this->testEnvironmentManager->setAdminGroupAuthentication();
    }

    /**
     * @return void
     */
    public function setAdminGroup2Authentication(): void
    {
        $this->testEnvironmentManager->setAdminGroup2Authentication();
    }


    /**
     * @return void
     */
    public function setAdminColAuthentication(): void
    {
        $this->testEnvironmentManager->setAdminColAuthentication();
    }

    /**
     * @return void
     */
    public function setAdminCol2Authentication(): void
    {
        $this->testEnvironmentManager->setAdminCol2Authentication();
    }

    /**
     * @return void
     */
    public function setRGSAuthentification(): void
    {
        $rgsConnexion = $this->getMockBuilder(RgsConnexion::class)->disableOriginalConstructor()->getMock();
        $rgsConnexion->method('isRgsConnexion')->willReturn(true);
        $this->getObjectInstancier()->{RgsConnexion::class} = $rgsConnexion;
    }

    /**
     * @return void
     */
    public function setUserAuthentification(): void
    {
        $this->testEnvironmentManager->setUserAuthentification();
    }

    /**
     * @return array
     */
    public function getLogRecords(): array
    {
        return $this->testEnvironmentManager->getLogRecords();
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
        static::assertMatchesRegularExpression(
            $expected_message,
            $this->getLogRecords()[$num_log]['message']
        );
    }

    /** @deprecated  */
    public function setExpectedException($e, string $message): void
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
    /** @deprecated  */
    public function noAssertion(): void
    {
        $this->assertTrue(true);
    }
}
