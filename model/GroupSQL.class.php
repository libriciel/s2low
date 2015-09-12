<?php 


class GroupSQL extends SQL {

	public function getInfo($id){
		$sql = "SELECT * FROM authority_groups WHERE id=?";
		return $this->queryOne($sql,$id);
	}

}