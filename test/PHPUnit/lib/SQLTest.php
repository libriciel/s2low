<?php

use S2lowLegacy\Lib\SQL;

class SQLTest extends S2lowTestCase
{
    /**
     * @var SQL
     */
    private $sqlSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlSQL = $this->getMockForAbstractClass(SQL::class, array($this->getSQLQuery()));
    }

    public function testQuery()
    {
        $sql = "SELECT 1 as id;";
        $result = $this->sqlSQL->query($sql);
        $this->assertEquals(array("id" => "1"), $result[0]);
    }

    public function testQueryOne()
    {
        $sql = "SELECT 1 as id;";
        $result = $this->sqlSQL->queryOne($sql);
        $this->assertEquals(1, $result);
    }

    public function testQueryOneCol()
    {
        $sql = "SELECT 1 as id;";
        $result = $this->sqlSQL->queryOneCol($sql);
        $this->assertEquals(array(1), $result);
    }
}
