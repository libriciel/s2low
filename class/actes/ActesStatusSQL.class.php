<?php 

class ActesStatusSQL {

	const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;
	const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;
    const STATUS_EN_ATTENTE_DE_TRANSMISSION = 2;
    const STATUS_TRANSMIS = 3;


    public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getAllStatus(){
		$sql = "SELECT id, name FROM actes_status";
		$result = array();
		foreach($this->sqlQuery->query($sql) as $line){
			$result[$line['id']] = $line['name'];
		}
		return $result;
	}

}