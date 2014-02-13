<?php 
class ModuleSQL {
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfoByName($name){
		$sql = "SELECT * FROM modules WHERE name=?";
		return $this->sqlQuery->queryOne($sql,$name);
	}
	
	public function getInfoModuleAuthority($module_id,$authority_id){
		$sql = "SELECT * FROM modules_authorities ". 
				" WHERE module_id=? AND authority_id=? ";
		return $this->sqlQuery->queryOne($sql,$module_id,$authority_id);
	}
	
	public function getInfoPerms($module_id,$user_id){
		$sql = "SELECT perm FROM users_perms WHERE module_id=? AND user_id=? ";
		return $this->sqlQuery->queryOne($sql,$module_id,$user_id);
	}
	
 	public function getModulesForUser($userInfo) {
 		
 		if ($userInfo['role'] == 'SADM'){
 			$sql = "SELECT * FROM modules WHERE status=1";
 			return $this->sqlQuery->query($sql);
 		}
 		if ($userInfo['role'] == 'GADM'){
 			$sql = "SELECT modules.* FROM modules " .
 				" JOIN modules_authorities ON modules_authorities.module_id=modules.id " .
 				" WHERE modules_authorities.authority_id=?  AND modules.status=1";
 			return $this->sqlQuery->query($sql,$userInfo['authority_id']);
 		}
 		
 		 $sql = "SELECT modules.* FROM modules " .
 				" JOIN modules_authorities ON modules_authorities.module_id=modules.id " .
 				" JOIN users_perms ON modules.id=users_perms.module_id " .
 				" WHERE modules_authorities.authority_id=? " .
 		 		" AND users_perms.user_id= ? ". 
 		 		" AND perm != 'NONE' ".
 				" AND modules.status=1";

 		return  $this->sqlQuery->query($sql,$userInfo['authority_id'],$userInfo['id']);

	}
	
	
}