<?php 


class GroupSQL {
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authority_groups WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getAllSiren($id){
		$sql = "SELECT siren FROM authority_group_siren " .
				" WHERE authority_group_id= ? " . " ORDER BY siren";
		return $this->sqlQuery->query($sql,$id);
	}
	
	
}