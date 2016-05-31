<?php

class LogsSQLTest extends S2lowTestCase {

	/**
	 * @var LogsSQL
	 */
	private $logsSQL;

	protected function setUp(){
		parent::setUp();
		Log::newEntry("test","message de test",1,false,"USER","actes",false,6);
		$this->logsSQL = new LogsSQL($this->getSQLQuery());
	}

	public function testGetLogLevelList(){
		$this->assertNotEmpty($this->logsSQL->getLogLevelList());
	}

	public function testGetNbLog(){
		$this->assertEquals(1,$this->logsSQL->getNbLog(false,false,false,false,false,-1,false,false));
	}

	public function testGetList(){
		$result = $this->logsSQL->getList(false,false,false,false,false,-1,false,false,0,10);
		$this->assertEquals("message de test",$result[0]['message']);
	}

	public function testGetListAllFiltre(){
		$this->assertEquals(1,$this->logsSQL->getNbLog(1,2,6,"Eric","actes",LogsSQL::LEVEL_INFO,"message de test",array("USER"),0,10));
	}

}