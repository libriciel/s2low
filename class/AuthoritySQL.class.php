<?php 


class AuthoritySQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authorities WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getAll() {
		$result = array();
		$sql = "SELECT authorities.id, authorities.name FROM authorities ORDER BY authorities.name ASC";
    	foreach($this->sqlQuery->query($sql) as $line){
    		$result[$line['id']] = $line['name'];
    	}
		return $result;
  	}
	
}