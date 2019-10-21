<?php

class LogsRequestSQLTest extends S2lowTestCase {

	/** @var  LogsRequestData */
	private $logsRequestData;

	/** @var  LogsRequestSQL */
	private $logsRequestSQL;

	private $logs_request_id;

	protected function setUp() : void {
		parent::setUp();
		$this->logsRequestData = new LogsRequestData();
		$this->logsRequestData->user_id_demandeur = 1;
		$this->logsRequestData->date_debut  = "1970-01-01";
		$this->logsRequestData->date_fin  = "2000-01-01";

		$this->logsRequestSQL = new LogsRequestSQL($this->getSQLQuery());
		$this->logs_request_id = $this->logsRequestSQL->newRequest($this->logsRequestData);
	}

	public function testGetAllByUser(){
		$info = $this->logsRequestSQL->getAllByUser($this->logsRequestData->user_id_demandeur);
		$this->assertEquals($this->logsRequestData->date_debut,$info[0]['date_debut']);
	}

	public function testGetAllByState(){
		$info = $this->logsRequestSQL->getAllByState(LogsRequestData::STATE_ASKING);
		$this->assertEquals($this->logsRequestData->date_debut,$info[0]['date_debut']);
	}

	public function testHasRequest(){
		$this->assertTrue($this->logsRequestSQL->hasRequest($this->logsRequestData->user_id_demandeur));
	}

	public function testHasRequestNo(){
		$this->getSQLQuery()->query("DELETE FROM logs_request");
		$this->assertFalse($this->logsRequestSQL->hasRequest($this->logsRequestData->user_id_demandeur));
	}

	public function testDelete(){
		$this->logsRequestSQL->delete($this->logs_request_id,$this->logsRequestData->user_id_demandeur);
		$this->assertFalse($this->logsRequestSQL->hasRequest($this->logsRequestData->user_id_demandeur));

	}


}