<?php 

class ActesStatusSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getAllStatus(){
		$sql = "SELECT id, name FROM actes_status";
		$result = array();
		foreach($this->sqlQuery->query($sql) as $line){
			$result[$line['id']] = $line['name'];
		}
		return $result;
	}
	
}