<?php

class LogsHistoriqueSQL extends SQL {
	
	public function vidange($nb_month_to_keep){
		$date = date("Y-m-d H:i:s",strtotime("-{$nb_month_to_keep} month"));

		$sql = "INSERT INTO logs_historique SELECT * FROM logs WHERE logs.date<?";
		$this->query($sql,$date);

		$sql = "DELETE FROM logs WHERE logs.date<?";
		$this->query($sql,$date);
	}
	
}