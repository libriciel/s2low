<?php 

class UserSQL extends SQL {
	
	const IDENT_METHOD_NONE = 0;
	const IDENT_METHOD_CERT_ONLY = 1;
	const IDENT_METHOD_LOGIN = 2 ;
	const IDENT_METHOD_RGS_2_ETOILES = 3;


	const STATUS_DESACTIVE = 0;
	const STATUS_ACTIVE = 1;

	public function getPrettyName($name,$givenname,$login){
		return $name?"$givenname $name":$login;
	}

	public function getInfo($id){
		$sql = "SELECT * FROM users WHERE id=?";
		$result = $this->queryOne($sql,$id);
		if (! $result){
			return array();
		}
		$result['pretty_name'] = $this->getPrettyName($result['name'],$result['givenname'],$result['login']);
		$result['role_str'] = $this->getRoleStr($result['role']);
		$result['nb_user_with_my_certificate'] = $this->getNbUserWithMyCertificate($result['subject_dn'],
																					$result['issuer_dn']);
		return $result;
	}
	
	public function getNbUserWithMyCertificate($subject_dn,$issuer_dn){
		$sql = "SELECT count(*) as nb FROM users " .
				" WHERE subject_dn= ? ".
				" AND issuer_dn= ?";
		return $this->queryOne($sql,$subject_dn,$issuer_dn);
	}	
	
	public function getInfoFromCertificateInfo(array $certificateInfo){
		$sql = "SELECT * FROM users " .
				" WHERE subject_dn=? AND issuer_dn=?" .
				" ORDER BY id ";
		return $this->query($sql,$certificateInfo['subject'],$certificateInfo['issuer']);
	}

	public function getIdListFromCertificateInfo($subject,$issuer){
		$sql = "SELECT id FROM users WHERE subject_dn=? AND issuer_dn=?";
		return $this->queryOneCol($sql,$subject,$issuer);
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
		return $this->query($sql,$authority_id);
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
		$this->query($sql,$pem_certificate_content,$user_id);
	}
	

	public function deleteCertificateRGS2Etoiles($user_id){
		$this->saveCertificateRGS2Etoiles($user_id, "");
	}
	
	//Hack affreux pour prévenir les NULL introduit par le DataObject !
	public function updateCertificatRGS2EtoilesIfNull($user_id){
		$sql = "SELECT * FROM users WHERE id=? AND certificate_rgs_2_etoiles IS NULL";
		if ($this->queryOne($sql,$user_id)){
			$this->saveCertificateRGS2Etoiles($user_id, '');
		}
	}
	
	public function getIdFromConnexionInfo($subject_dn,$issuer_dn,$certificate_rgs_2_etoile,$login,$password){
		$sql = "SELECT id FROM users " .
				" WHERE subject_dn=? AND issuer_dn=? " .
				" AND certificate_rgs_2_etoiles = ? ";
		
		$data = array($subject_dn,$issuer_dn,$certificate_rgs_2_etoile);
		if ($login){
			$sql .= " AND login=? AND password=?";
			$data[] = $login;
			$data[] = md5($password);
		}
		$sql .= " ORDER BY id ";
		return $this->queryOneCol($sql,$data);
	}
	
	public function getListIdFromConnexion($subject_dn,$issuer_dn,$certificate_rgs_2_etoile){
		$sql = "SELECT id FROM users " .
			" WHERE subject_dn=? AND issuer_dn=? " .
			" AND certificate_rgs_2_etoiles = ? " .
			" ORDER BY id ";
		return $this->queryOneCol($sql,$subject_dn,$issuer_dn,$certificate_rgs_2_etoile);
	}

	public function getGroupeName($user_id){
		$sql = "SELECT authority_groups.name FROM users JOIN authority_groups ON users.authority_group_id= authority_groups.id WHERE users.id=?";
		return $this->queryOne($sql,$user_id);
	}
}