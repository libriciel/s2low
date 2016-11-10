<?php 
class AuthoritySQL extends SQL {

  	public static function getSAEProperties(){
  		return  array(
			'pastell_url' => "URL Pastell",
			'pastell_login' => "Login Pastell",
			'pastell_password' => "Mot de passe Pastell",
			'pastell_id_e' => "Identifiant collectivité sur pastell (id_e)"
		);
  	}
	
  	public static function getSAEPropertiesType($properties){
  		return ($properties=='pastell_password')?"password":"text";
  	}
	
	public function getIdBySIREN($siren){
		$sql = "SELECT id FROM authorities where siren=?";
		return $this->queryOne($sql,$siren);
	}
	
	public function getBySIRET($siret){
		$sql = "SELECT id FROM authorities where dia_siret=?";
		return $this->queryOne($sql,$siret);
	}

	public function getAll() {
		$result = array();
		$sql = "SELECT authorities.id, authorities.name FROM authorities ORDER BY authorities.name ASC";
    	foreach($this->query($sql) as $line){
    		$result[$line['id']] = $line['name'];
    	}
		return $result;
  	}
  	
	public function getAllGroup($authority_group_id){
		$result = array();
		$sql = "SELECT authorities.id, authorities.name FROM authorities WHERE authority_group_id=? ORDER BY authorities.name ASC";
		foreach($this->query($sql, $authority_group_id) as $line){
			$result[$line['id']] = $line['name'];
		}
		return $result;
	}
  	
  	public function updateSAE($id,array $info){
  		$sql = "UPDATE authorities SET pastell_url=?,pastell_login=?,pastell_password=?,pastell_id_e=? WHERE id = ?";
    	$data['id'] = $id;

  		$this->query($sql,
  								$info['pastell_url'],
  								$info['pastell_login'],
  								$info['pastell_password'],
  								$info['pastell_id_e'],
  								$id);
  	}
  	
  	public function verifDepartmentAndDistrict($department_code,$district_code){
  		$sql = "SELECT * FROM authority_departments " .
    			" JOIN authority_districts ON authority_department_id=authority_departments.id ".
  				" WHERE authority_departments.code=?  AND authority_districts.code=?";
  		$result = $this->query($sql,$department_code,$district_code);
  		return count($result);
  	}
	
	public function getList($authority_group_id,$authority_type_id,$name,$siren, $siret, $offset,$limit){
		$sql = "SELECT authorities.*, authority_types.description as type_name, authority_groups.name as group_name
					FROM authorities
					LEFT JOIN authority_groups ON authorities.authority_group_id=authority_groups.id
					LEFT JOIN authority_types ON authorities.authority_type_id = authority_types.id ";
		$data = array();
		if ($siret){
			$sql .= " JOIN authority_siret ON authorities.id=authority_siret.authority_id";
		}
		$sql .= " WHERE 1=1 ";
		if ($siret){
			$sql .= " AND authority_siret.siret LIKE ? ";
			$data[] = "%$siret%";
		}
		if ($authority_group_id){
			$sql.= " AND authority_group_id = ? ";
			$data[] = $authority_group_id;
		}
		if ($authority_type_id){
			$sql .= " AND authority_type_id = ?";
			$data[] = $authority_type_id;
		}
		if ($name){
			$sql .=  " AND authorities.name ILIKE ? ";
			$data[] .= "%$name%";
		}
		if ($siren){
			$sql .=  " AND siren LIKE ? ";
			$data[] .= "%$siren%";
		}
		$offset = intval($offset);
		$limit = intval($limit);
		$sql .= " ORDER BY name LIMIT $limit OFFSET $offset";
		return $this->query($sql,$data);
	}

	public function getNb($authority_group_id,$authority_type_id,$name,$siren,$siret){
		$sql = "SELECT count(*) FROM authorities ";
		$data = array();
		if ($siret){
			$sql .= " JOIN authority_siret ON authorities.id=authority_siret.authority_id";
		}
		$sql .= " WHERE 1=1 ";
		if ($siret){
			$sql .= " AND authority_siret.siret LIKE ? ";
			$data[] = "%$siret%";
		}
		if ($authority_group_id){
			$sql.= " AND authority_group_id = ? ";
			$data[] = $authority_group_id;
		}
		if ($authority_type_id){
			$sql .= " AND authority_type_id = ?";
			$data[] = $authority_type_id;
		}
		if ($name){
			$sql .= " AND name ILIKE ? ";
			$data[] .= "%$name%";
		}
		if ($siren){
			$sql .=  " AND siren LIKE ? ";
			$data[] .= "%$siren%";
		}
		return $this->queryOne($sql,$data);
	}

	public function verifHasPastell($authority_id){
		$authorityInfo = $this->getInfo($authority_id);
		if (! $authorityInfo['pastell_url'] ){
			throw new Exception("La collectivité n'a pas de Pastell configuré");
		}
	}

	public function getInfo($id){
		$sql = "SELECT * FROM authorities WHERE id=?";
		return $this->queryOne($sql,$id);
	}

}