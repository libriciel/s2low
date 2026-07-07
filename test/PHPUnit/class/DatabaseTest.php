<?php

use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\DatabasePool;

class DatabaseTest extends S2lowTestCase
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->database = DatabasePool::getInstance();
    }

    /**
     * @throws Exception
     */
    public function testQuery()
    {
        $this->assertEquals(1, $this->database->exec("SELECT 1"));
    }

    /**
     * @throws Exception
     */
    public function testQueryString()
    {
        $queryResult = $this->database->select("SELECT 'foo'");
        $this->assertEquals([['?column?' => 'foo']], $queryResult->get_all_rows());
    }

    /**
     * @throws Exception
     */
    public function testFetchAll()
    {
        $result = $this->database->fetchAll("SELECT * FROM users ORDER BY id");
        $this->assertEquals("eric+10@sigmalis.com", $result[9]['email']);
    }

    /**
     * @throws Exception
     */
    public function testGetOneLine()
    {
        $result = $this->database->getOneLine("SELECT * FROM users ORDER BY id");
        $this->assertEquals("eric@sigmalis.com", $result['email']);
    }

    /**
     * @throws Exception
     */
    public function testGetOneValue()
    {
        $result = $this->database->getOneValue("SELECT email FROM users ORDER BY id");
        $this->assertEquals("eric@sigmalis.com", $result);
    }

    /**
     * @throws Exception
     */
    public function testTransaction()
    {
        $this->database->begin();
        $this->database->exec("INSERT into actes_status(id,name) VALUES (42,'toto')");
        $this->database->commit();
        $this->assertEquals(
            'toto',
            $this->database->getOneValue("SELECT name FROM actes_status WHERE id=42")
        );
    }

    /**
     * @throws Exception
     */
    public function testTransactionRollback()
    {
        $this->database->begin();
        $this->database->exec("INSERT into actes_status(id,name) VALUES (42,'toto')");
        $this->database->rollback();
        $this->assertEmpty(
            $this->database->getOneValue("SELECT name FROM actes_status WHERE id=42")
        );
    }

    /**
     * @throws Exception
     */
    public function testBeginBegin()
    {
        $this->database->begin();
        $this->assertEquals(0, $this->database->begin());
        $this->database->rollback();
    }

    /**
     * @throws Exception
     */
    public function testCommit()
    {
        $this->assertEquals(0, $this->database->commit());
    }

    /**
     * @throws Exception
     */
    public function testRollback()
    {
        $this->assertEquals(0, $this->database->rollback());
    }

    /**
     * @throws Exception
     */
    public function testFailed()
    {
        $this->setExpectedException(Exception::class, 'toto');
        $this->database->select("SELECT toto");
    }

    public function testGetConnection()
    {
        self::assertInstanceOf(\Doctrine\DBAL\Connection::class, $this->database->getConnection());
    }
}
