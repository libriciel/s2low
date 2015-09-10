<?php 
class AuthoritySQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authorities WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getIdBySIREN($siren){
		$sql = "SELECT id FROM authorities where siren=?";
		return $this->sqlQuery->queryOne($sql,$siren);
	}
	
	public function getBySIRET($siret){
		$sql = "SELECT id FROM authorities where dia_siret=?";
		return $this->sqlQuery->queryOne($sql,$siret);
	}
	
	public function getAll() {
		$result = array();
		$sql = "SELECT authorities.id, authorities.name FROM authorities ORDER BY authorities.name ASC";
    	foreach($this->sqlQuery->query($sql) as $line){
    		$result[$line['id']] = $line['name'];
    	}
		return $result;
  	}
  	
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
  	
  	public function updateSAE($id,array $info){
  		$sql = "UPDATE authorities SET pastell_url=?,pastell_login=?,pastell_password=?,pastell_id_e=? WHERE id = ?";
    	$data['id'] = $id;
  		
  		$this->sqlQuery->query($sql,
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
  		$result = $this->sqlQuery->query($sql,$department_code,$district_code);
  		return count($result);
  	}
  	
  	public function isInGroup($authority_group_id){
  		
  	}
  	
}