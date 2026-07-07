<?php

use S2lowLegacy\Lib\SQLQuery;

class SQLQueryTest extends S2lowTestCase
{
    /**
     * @var SQLQuery
     */
    private $sqlQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlQuery = self::getContainer()->get(SQLQuery::class);
    }


    public function testGetConnection()
    {
        $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, $this->sqlQuery->getConnection());
    }

    public function testQuery()
    {
        $sql = "SELECT 42 as response";
        $result = $this->sqlQuery->query($sql);
        $this->assertEquals(42, $result[0]['response']);
    }

    public function testBadQuery()
    {
        $sql = "NOT_SQL_WORD";
        $this->expectExceptionMessage("NOT_SQL_WORD");
        $this->sqlQuery->query($sql);
    }

    public function testDisconnect()
    {
        $this->sqlQuery->disconnect();
        self::expectNotToPerformAssertions();
    }

    public function testSleep()
    {
        $this->sqlQuery->sleep(0);
        self::assertTrue(true);
    }

    public function testQueryOne()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 1";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertEquals(1, $result);
    }

    public function testQueryOneCol()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 2";
        $result = $this->sqlQuery->queryOneCol($sql);
        $this->assertEquals([1, 2], $result);
    }

    public function testQueryOneEmptyResult()
    {
        $sql = "SELECT id FROM users WHERE givenname=?";
        $result = $this->sqlQuery->queryOne($sql, "not existing givenname");
        $this->assertFalse($result);
    }

    public function testQueryOneColEmptyResult()
    {
        $sql = "SELECT id FROM users WHERE givenname=?";
        $result = $this->sqlQuery->queryOneCol($sql, "not existing givenname");
        $this->assertEmpty($result);
    }

    public function testQueryOneManyResult()
    {
        $sql = "SELECT id FROM users ORDER BY id ";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertEquals(1, $result);
    }

    public function testSlowQuery()
    {
        $this->sqlQuery->setSlowQuery(0);
        $sql = "SELECT id FROM users ORDER BY id ";

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$capturedError) {
            $capturedError = [
                'errno' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
            ];
            return true;
        }, E_USER_WARNING);
        $this->sqlQuery->queryOne($sql);
        restore_error_handler();

        $this->assertNotNull($capturedError);
        $this->assertEquals(E_USER_WARNING, $capturedError['errno']);
        $this->assertStringContainsString('Requete lente', $capturedError['message']);
    }

    public function testQueryOneColManyResult()
    {
        $sql = "SELECT * FROM users ORDER BY id ";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertSame(1, $result['id']);
    }
}
