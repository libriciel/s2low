<?php 


class GroupSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authority_groups WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	
}