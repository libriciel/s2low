<?php

class LogsHistoriqueSQLTest extends S2lowTestCase {


	public function testVidange(){

		$last_month = date("Y-m-d",strtotime("-2 month"));
		$today = date("Y-m-d");


		$logsSQL = new LogsSQL($this->getSQLQuery());
		$logsSQL->addLog($last_month,1,"actes","TdT",1,'SADM','message test 1',false);
		$logsSQL->addLog($today,1,"actes","TdT",1,'SADM','message test 1',false);

		$logsHistoriqueSQL = new LogsHistoriqueSQL($this->getSQLQuery());
		$logsHistoriqueSQL->vidange(1);

		$sql = "SELECT count(*) FROM logs";
		$this->assertEquals(1,$this->getSQLQuery()->queryOne($sql));

		$sql = "SELECT count(*) FROM logs_historique";
		$this->assertEquals(1,$this->getSQLQuery()->queryOne($sql));
		
	}


}