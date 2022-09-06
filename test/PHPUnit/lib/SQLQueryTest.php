<?php

use S2lowLegacy\Lib\SQLQuery;

class SQLQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SQLQuery
     */
    private $sqlQuery;

    protected function setUp(): void
    {
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
    }

    public function testGetPdo()
    {
        $this->assertInstanceOf("PDO", $this->sqlQuery->getPdo());
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
        $this->setExpectedException("Exception", 'NOT_SQL_WORD');
        $this->sqlQuery->query($sql);
    }

    public function testDisconnect()
    {
        $this->sqlQuery->disconnect();
        $this->noAssertion();
    }

    public function testDisconnectAndReconnect()
    {
        $this->sqlQuery->disconnect();
        $sql = "SELECT 42 as response";
        $result = $this->sqlQuery->query($sql);
        $this->assertEquals(42, $result[0]['response']);
    }

    public function testSleep()
    {
        $this->sqlQuery->sleep(0);
        $this->noAssertion();
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
        $this->assertEquals(array(1,2), $result);
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
        $this->setExpectedException("Exception", "Requete lente");
        $this->sqlQuery->queryOne($sql);
    }

    public function testQueryOneColManyResult()
    {
        $sql = "SELECT * FROM users ORDER BY id ";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertEquals(1, $result['id']);
    }
}
