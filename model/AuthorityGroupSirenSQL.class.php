<?php 
class AuthorityGroupSirenSQL {
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function exist($id,$siren){
		$sql = "SELECT * FROM authority_group_siren WHERE authority_group_id=? AND siren=?";
		return $this->sqlQuery->queryOne($sql,$id,$siren);	
	}
	
	public function add($id,$siren){
		if ($this->exist($id,$siren)){
			return;
		}
		$sql = "INSERT INTO authority_group_siren(authority_group_id,siren) VALUES (?,?)";
		$this->sqlQuery->query($sql,$id,$siren);
	}
	
}