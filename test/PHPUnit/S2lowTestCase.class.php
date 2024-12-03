<?php

use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use PHPUnit\Framework\TestCase;

abstract class S2lowTestCase extends TestCase
{
    protected $backupGlobalsBlacklist = array('sqlQuery');

    private TestEnvironmentManager $testEnvironmentManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testEnvironmentManager = new TestEnvironmentManager();
        $this->testEnvironmentManager->setUp();
    }

    public function getObjectInstancier(): ObjectInstancier
    {
        return  $this->testEnvironmentManager->getObjectInstancier();
    }

    public function getSQLQuery(): SQLQuery
    {
        return $this->testEnvironmentManager->getSQLQuery();
    }

    protected function setServerInfo(array $server_info)
    {
        $this->testEnvironmentManager->setServerInfo($server_info);
    }

    public function setSuperAdminAuthentication()
    {
        $this->testEnvironmentManager->setSuperAdminAuthentication();
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
        $rgsConnexion = $this->getMockBuilder(RgsConnexion::class)->disableOriginalConstructor()->getMock();
        $rgsConnexion->method('isRgsConnexion')->willReturn(true);
        $this->getObjectInstancier()->{RgsConnexion::class} = $rgsConnexion;
    }

    public function setUserAuthentification()
    {
        $this->testEnvironmentManager->setUserAuthentification();
    }

    public function setArchAuthentification()
    {
        $this->testEnvironmentManager->setArchAuthentification();
    }

    public function getLogRecords()
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
}
