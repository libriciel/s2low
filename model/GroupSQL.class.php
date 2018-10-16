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
			$sql = "INSERT INTO authority_groups(id,name,status) VALUES (nextval('authority_groups_id_seq'),?,?) RETURNING id";
			$id = $this->queryOne($sql,$name,$status);
		}
		return $id;
	}

	public function groupNameAlreadyExists($id, $name){
		$sql = "SELECT id FROM authority_groups WHERE name= ? ";
		$id_from_database = $this->queryOne($sql,$name);

		if (! $id_from_database){
			return false;
		}

		if ($id){
			return ($id != $id_from_database);
		}

		return true;
	}

	public function getGroupsIdName(){
		$result = [];
		$sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";
		foreach($this->query($sql) as $line){
			$result[$line['id']] = $line['name'];
		}
		return $result;
	}




}