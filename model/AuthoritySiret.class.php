<?php

class AuthoritySiret extends SQL {
	
	public function siretList($authority_id){
		$sql = "SELECT * FROM authority_siret WHERE authority_id=? ORDER BY siret";
		return $this->query($sql,$authority_id);
	}
	
	public function add($authority_id,$siret){
		$sql = "SELECT id FROM authority_siret WHERE authority_id=? AND siret=?";
		$id = $this->queryOne($sql,$authority_id,$siret);
		if ($id){
			return $id;
		}
		$sql = "INSERT INTO authority_siret(authority_id,siret,date) VALUES (?,?,now())";
		$this->query($sql,$authority_id,$siret);
		return $this->getLastInsertId();
	}
	
	public function del($id){
		$sql = "DELETE FROM authority_siret WHERE id=?";
		$this->query($sql,$id);
	}
	
	public function authorityList($siret){
		$sql = "SELECT * FROM authority_siret WHERE siret=?";
		return $this->query($sql,$siret);
	}
	
	public function getInfo($authority_siret_id){
		$sql = "SELECT * FROM authority_siret WHERE id=?";
		return $this->queryOne($sql,$authority_siret_id);
	}
	
}