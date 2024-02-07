<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\DatabasePool;

class QueryResultTest extends S2lowTestCase
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
        $queryResult = $this->database->select("SELECT name,email,id FROM users ORDER BY id");
        $this->assertEquals(11, $queryResult->num_row());
        $this->assertEquals(3, $queryResult->num_field());
        $this->assertEquals('eric@sigmalis.com', $queryResult->get_all_rows()[0]['email']);
    }


    /**
     * @throws Exception
     */
    public function testGetNextRow()
    {
        $queryResult = $this->database->select("SELECT name,email,id FROM users ORDER BY id");
        $this->assertEquals('eric@sigmalis.com', $queryResult->get_next_row()['email']);
    }
}
