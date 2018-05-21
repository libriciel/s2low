<?php

class DatabaseTest extends S2lowTestCase {

	/**
	 * @var Database
	 */
	private $database;

	/**
	 * @throws Exception
	 */
	protected function setUp() {
		parent::setUp();
		$this->database = DatabasePool::getInstance();
	}

	/**
	 * @throws Exception
	 */
	public function testQuery(){
		$this->assertEquals(1,$this->database->exec("SELECT 1"));
	}

	/**
	 * @throws Exception
	 */
	public function testLastRequet(){
		$this->assertEquals(1,$this->database->exec("SELECT 1"));
		$this->assertEquals("SELECT 1",$this->database->lastRequest());
		$this->assertEmpty($this->database->lastRequestError());
	}

	/**
	 * @throws Exception
	 */
	public function testQueryString(){
		$queryResult = $this->database->select("SELECT 'foo'");
		$this->assertEquals([['?column?'=>'foo']],$queryResult->get_all_rows());
	}

	public function testFetchAll(){
		$result = $this->database->fetchAll("SELECT * FROM users ORDER BY id");
		$this->assertEquals("eric+10@sigmalis.com",$result[9]['email']);
	}

	public function testGetOneLine(){
		$result = $this->database->getOneLine("SELECT * FROM users ORDER BY id");
		$this->assertEquals("eric@sigmalis.com",$result['email']);
	}

	public function testGetOneValue(){
		$result = $this->database->getOneValue("SELECT email FROM users ORDER BY id");
		$this->assertEquals("eric@sigmalis.com",$result);
	}

	public function testQuote(){
		$quote = $this->database->quote("'toto'\\a \\'");
		$this->assertEquals("'\'toto\'\\\\a \\\\\''",$quote);
	}

	public function testQuoteNotNull(){
		$quote = $this->database->quote("",true);
		$this->assertEquals("''",$quote);
	}

	public function testQuoteNull(){
		$quote = $this->database->quote("",false);
		$this->assertEquals("NULL",$quote);
	}

	/**
	 * @throws Exception
	 */
	public function testTransaction(){
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
	public function testTransactionRollback(){
		$this->database->begin();
		$this->database->exec("INSERT into actes_status(id,name) VALUES (42,'toto')");
		$this->database->rollback();
		$this->assertEmpty(
			$this->database->getOneValue("SELECT name FROM actes_status WHERE id=42")
		);
	}

	public function testBeginBegin(){
		$this->database->begin();
		$this->assertEquals(0,$this->database->begin());
		$this->database->rollback();
	}

	public function testCommit(){
		$this->assertEquals(0,$this->database->commit());
	}

	public function testRollback(){
		$this->assertEquals(0,$this->database->rollback());
	}

	/**
	 * @throws Exception
	 */
	public function testFailed(){
		$this->setExpectedException(Exception::class,'column "toto" does not exist');
		$this->database->select("SELECT toto");
		$this->assertRegExp('#column "toto" does not exist#',$this->database->lastRequestError());
	}

	/**
	 * @throws Exception
	 */
	public function testLog(){
		$this->database->log(true);
		$this->expectOutputString("<br />SELECT 1");
		$this->assertEquals(1,$this->database->exec("SELECT 1"));
	}

	/**
	 * @throws Exception
	 */
	public function testClose(){
		$this->assertEquals(1,$this->database->exec("SELECT 1"));
		$this->database->close();
	}

	/**
	 * @throws Exception
	 */
	public function testSelectData(){
		$this->assertEquals(1,$this->database->selectData("SELECT * FROM users","id","id")[1]);
	}

	/**
	 * @throws Exception
	 */
	public function testSelectDataWithoutValue(){
		$this->assertEquals(1,$this->database->selectData("SELECT * FROM users","id")[1]['id']);
	}

	/**
	 * @throws Exception
	 */
	public function testSelectDataWithoutKey(){
		$this->assertEquals(1,$this->database->selectData("SELECT * FROM users")[0]['id']);
	}

}