<?php 
class AuthorityGroupSirenSQL extends SQL {

	public function exist($id,$siren){
		$sql = "SELECT * FROM authority_group_siren WHERE authority_group_id=? AND siren=?";
		return $this->queryOne($sql,$id,$siren);
	}
	
	public function add($id,$siren){
		if ($this->exist($id,$siren)){
			return;
		}
		$sql = "INSERT INTO authority_group_siren(authority_group_id,siren) VALUES (?,?)";
		$this->query($sql,$id,$siren);
	}

	public function getUnusedSiren($authority_group_id){
		$sql = "SELECT siren FROM authority_group_siren " .
			" WHERE authority_group_id=? AND siren NOT IN (SELECT siren FROM authorities WHERE siren IS NOT NULL AND authority_group_id=?)".
			" ORDER BY siren";
		return $this->queryOneCol($sql,$authority_group_id,$authority_group_id);
	}
}