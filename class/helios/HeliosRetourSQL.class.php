<?php

class HeliosRetourSQL extends SQL {
	
	public function add($authority_id,$siret,$filename){
		$siren = substr($siret, 0,9);
		$sql = "INSERT INTO helios_retour(authority_id, siren, filename, status, date,siret) " .
				" VALUES ( ?,?,?,0,now(),?) ";
		$this->query($sql,$authority_id,$siren,$filename,$siret);
		return $this->getLastInsertId();
	}
	
	public function getInfo($helios_retour_id){
		$sql = "SELECT * FROM helios_retour WHERE id=?";
		return $this->queryOne($sql,$helios_retour_id);
	}

	public function getInfoFromFilename($authority_id,$filename){
		$sql = "SELECT * FROM helios_retour ".
				" WHERE authority_id = ? AND filename = ?";
		return $this->queryOne($sql,$authority_id,$filename);
	}
	
}