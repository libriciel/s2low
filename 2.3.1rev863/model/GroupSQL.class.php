<?php 

class GroupSQL extends SQL {

	public function getInfo($id){
		$sql = "SELECT * FROM authority_groups WHERE id=?";
		return $this->queryOne($sql,$id);
	}

	public function getAll(){
		$sql = "SELECT * FROM authority_groups ORDER BY authority_groups.name ASC";
		return $this->query($sql);
	}

}