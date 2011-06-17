<?php 


class AuthoritySQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authorities WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	
}