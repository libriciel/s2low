<?php 

class UserSQL {
	
	const IDENT_METHOD_NONE = 0;
	const IDENT_METHOD_CERT_ONLY = 1;
	const IDENT_METHOD_LOGIN = 2 ;
	const IDENT_METHOD_RGS_2_ETOILES = 3;
	
	public function __construct(SQLQuery $sqlQuery){
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
		return $this->sqlQuery->query($sql,$subject,$issuer);
	}

	public function getIdListFromCertificateInfo($subject,$issuer){
		$sql = "SELECT id FROM users WHERE subject_dn=? AND issuer_dn=?";
		return $this->sqlQuery->queryOneCol($sql,$subject,$issuer);
	}
	
	public function  getRoleStr($role) {		
		$roleTypes = array( "SADM" => "Super administrateur",
							   "GADM" => "Administrateur de groupe",
							   "ADM" => "Administrateur collectivité",
							   "USER" => "Utilisateur"
							   );
		return $roleTypes[$role];
	}
	
	public function getDIAUser($authority_id){
		$sql = "SELECT users.id as user_id FROM users " . 
				" JOIN users_perms ON users.id = users_perms.user_id " .
				" JOIN modules ON users_perms.module_id = modules.id " .
				" WHERE authority_id=? AND users_perms.perm='RW' AND modules.name='dia'";
		return $this->sqlQuery->query($sql,$authority_id);
	}

	public function getIdentificationMethod($user_id){
		$info = $this->getInfo($user_id);
		if (!$info){
			return self::IDENT_METHOD_NONE;
		}
		if ($info['certificate_rgs_2_etoiles']){
			return self::IDENT_METHOD_RGS_2_ETOILES;
		}
		
		$user_id_list = $this->getIdListFromCertificateInfo($info['subject_dn'],$info['issuer_dn']);
		if (! $user_id_list){
			return self::IDENT_METHOD_NONE;
		}
		if (count($user_id_list) == 1 ){
			return self::IDENT_METHOD_CERT_ONLY;
		} 
		return self::IDENT_METHOD_LOGIN;
	}
	
	public function getIdentificationMethodeLibelle($ident_method_id){
		$libelle = array(self::IDENT_METHOD_NONE=>'aucune',
						self::IDENT_METHOD_CERT_ONLY=>'certificat de connexion uniquement',
						self::IDENT_METHOD_LOGIN => 'login/mot de passe',
						self::IDENT_METHOD_RGS_2_ETOILES => "certificat RGS** (uniquement par API)"
		);
		return $libelle[$ident_method_id];
	}
	
	public function saveCertificateRGS2Etoiles($user_id,$pem_certificate_content){
		$sql = "UPDATE users SET certificate_rgs_2_etoiles=? WHERE id=?";
		$this->sqlQuery->query($sql,$pem_certificate_content,$user_id);
	}
	
	public function deleteCertificateRGS2Etoiles($user_id){
		$this->saveCertificateRGS2Etoiles($user_id, "");
	}
	
}