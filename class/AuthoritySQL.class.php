<?php 


class AuthoritySQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM authorities WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
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
			'sae_wsdl' => "SAE WSDL",
			'sae_login' => "SAE Login",
			'sae_password' => "SAE mot de passe", 
			'sae_id_versant' =>  "SAE Identifiant service versant",//TransferringAgency
			'sae_id_archive' => "SAE Identifiant service d'archive", //ArchivalAgency
  			'sae_originating_agency' => "SAE Identifiant service producteur", //OriginatingAgency  		
			'sae_numero_aggrement' => "SAE Accord de versement",
		);
  	}
  	
  	public static function isTextarea($properties){
  		return in_array($properties, array('sae_id_versant','sae_id_archive','sae_originating_agency'));
  	}
  	
  	public function updateSAE($id,array $info){
  		$sql = "UPDATE authorities SET sae_wsdl=?,sae_login=?,sae_password=?,sae_id_versant=?,sae_id_archive=?,sae_numero_aggrement=?,sae_originating_agency=? WHERE id = ?";
    	$data['id'] = $id;
  		
  		$this->sqlQuery->query($sql,
  								$info['sae_wsdl'],
  								$info['sae_login'],
  								$info['sae_password'],
  								$info['sae_id_versant'],
  								$info['sae_id_archive'],
  								$info['sae_numero_aggrement'],
  								$info['sae_originating_agency'],
  								$id);
  	}
	
}