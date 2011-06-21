<?php 

class UserSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM users WHERE id=?";
		$result = $this->sqlQuery->queryOne($sql,$id);
		
		$result['pretty_name'] = $result['name']?"{$result['givenname']} {$result['name']}":$result['login'];
		$result['role_str'] = $this->getRoleStr($result['role']);
		return $result;
	}
	
	public function getInfoFromCertificateInfo(array $certificateInfo){
		$sql = "SELECT * FROM users WHERE subject_dn=? AND issuer_dn=?";
		return $this->sqlQuery->query($sql,$certificateInfo['subject'],$certificateInfo['issuer']);
	
	}

	public function  getRoleStr($role) {
		
		$roleTypes = array(
							   "SADM" => "Super administrateur",
							   "GADM" => "Administrateur de groupe",
							   "ADM" => "Administrateur collectivité",
							   "USER" => "Utilisateur"
							   );
		return $roleTypes[$role];
	}
}