<?php

class UsersPermsSQL extends SQL {

	const PERM_MODIFICATION = "RW";
	const PERM_VISUALISATION = "RO";
	const PERM_NONE = "NONE";

	public function getInfoPerms($module_id,$user_id){
		$sql = "SELECT perm FROM users_perms WHERE module_id=? AND user_id=? ";
		return $this->queryOne($sql,$module_id,$user_id);
	}

}