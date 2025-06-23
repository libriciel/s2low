<?php

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;

class DatabaseTest extends TestCase
{
    private $sqlQueryMock;
    private $pdoMock;
    private $pdoStatementMock;
    private $database;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->sqlQueryMock = $this->createMock(SQLQuery::class);

        $this->sqlQueryMock->method('getPdo')->willReturn($this->pdoMock);

        $this->database = new Database($this->sqlQueryMock);
    }

    public function testSelect()
    {
        $query = "SELECT * FROM users";
        $parameters = [];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->pdoStatementMock);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($parameters);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['id' => 1, 'name' => 'John Doe']]);

        $result = $this->database->select($query, $parameters);

        $this->assertEquals([['id' => 1, 'name' => 'John Doe']], $result->get_all_rows());
    }

    public function testExec()
    {
        $query = "INSERT INTO users (name) VALUES (?)";
        $params = ['John Doe'];

        $this->sqlQueryMock->expects($this->once())
            ->method('query')
            ->with($query, $params)
            ->willReturn([]);

        $result = $this->database->exec($query, $params);

        $this->assertTrue($result);
    }

    public function testBegin()
    {
        $this->sqlQueryMock->expects($this->once())
            ->method('query')
            ->with("BEGIN")
            ->willReturn([]);

        $result = $this->database->begin();

        $this->assertEquals(1, $result);
    }

    public function testCommit()
    {
        $this->database->begin();

        $this->sqlQueryMock->expects($this->once())
            ->method('query')
            ->with("COMMIT")
            ->willReturn([]);

        $result = $this->database->commit();

        $this->assertEquals(1, $result);
    }

    public function testRollback()
    {
        $this->database->begin();

        $this->sqlQueryMock->expects($this->once())
            ->method('query')
            ->with("ROLLBACK")
            ->willReturn([]);

        $result = $this->database->rollback();

        $this->assertEquals(1, $result);
    }

    public function testQuote()
    {
        $value = "'toto'\\a \\'";
        $expected = "'\\'toto\\'\\\\a \\\\\\''";

        $result = $this->database->quote($value);

        $this->assertEquals($expected, $result);
    }

    public function testQuoteNotNull()
    {
        $result = $this->database->quote("", true);

        $this->assertEquals("''", $result);
    }

    public function testQuoteNull()
    {
        $result = $this->database->quote("", false);

        $this->assertEquals('NULL', $result);
    }

    public function testGetOneLine()
    {
        $query = "SELECT * FROM users ORDER BY id";
        $params = [];

        $this->sqlQueryMock->expects($this->once())
            ->method('queryOne')
            ->with($query, $params)
            ->willReturn(['email' => 'eric@sigmalis.com']);

        $result = $this->database->getOneLine($query, $params);

        $this->assertEquals(['email' => 'eric@sigmalis.com'], $result);
    }

    public function testGetOneValue()
    {
        $query = "SELECT email FROM users ORDER BY id";
        $params = [];

        $this->sqlQueryMock->expects($this->once())
            ->method('queryOne')
            ->with($query, $params)
            ->willReturn('eric@sigmalis.com');

        $result = $this->database->getOneValue($query, $params);

        $this->assertEquals('eric@sigmalis.com', $result);
    }

    public function testGetOneCol()
    {
        $query = "SELECT email FROM users ORDER BY id";
        $params = [];

        $this->sqlQueryMock->expects($this->once())
            ->method('queryOneCol')
            ->with($query, $params)
            ->willReturn(['eric@sigmalis.com', 'eric+10@sigmalis.com']);

        $result = $this->database->getOneCol($query, $params);

        $this->assertEquals(['eric@sigmalis.com', 'eric+10@sigmalis.com'], $result);
    }

    public function testFetchAll()
    {
        $query = "SELECT * FROM users ORDER BY id";
        $params = [];

        $this->sqlQueryMock->expects($this->once())
            ->method('query')
            ->with($query, $params)
            ->willReturn([['id' => 1, 'email' => 'eric@sigmalis.com'], ['id' => 2, 'email' => 'eric+10@sigmalis.com']]);

        $result = $this->database->fetchAll($query, $params);

        $this->assertEquals([['id' => 1, 'email' => 'eric@sigmalis.com'], ['id' => 2, 'email' => 'eric+10@sigmalis.com']], $result);
    }

    public function testGetPdo()
    {
        $result = $this->database->getPdo();

        $this->assertInstanceOf(PDO::class, $result);
    }

    public function testDisconnect()
    {
        $this->sqlQueryMock->expects($this->once())
            ->method('disconnect');

        $this->database->disconnect();
    }
}
