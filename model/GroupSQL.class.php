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

	public function edit($id,$name,$status){
		if ($id) {
			$sql = "UPDATE authority_groups SET name=?, status=? WHERE id=?";
			$this->query($sql, $name, $status, $id);
		} else {
			$sql = "INSERT INTO authority_groups(name,status) VALUES (?,?) RETURNING id";
			$id = $this->queryOne($sql,$name,$status);
		}
		return $id;
	}

	public function groupNameAlreadyExists($id, $name){
		$sql = "SELECT id FROM authority_groups WHERE name= ? ";
		$id_from_database = $this->queryOne($sql,$name);

		if ($id){
			return ($id != $id_from_database);
		} else {
			return $id_from_database != 0;
		}
	}


}