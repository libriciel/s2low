<?php 

class UserSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM users WHERE id=?";
		$result = $this->sqlQuery->queryOne($sql,$id);
		
		$result['pretty_name'] = $result['name']?"{$result['givenname']} {$result['name']}":$result['login'];
		return $result;
	}
	
	
}