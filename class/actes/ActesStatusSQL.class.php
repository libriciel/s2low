<?php 

class ActesStatusSQL {

    const STATUS_EN_ERREUR = -1;
    const STATUS_ANNULER = 0;

    const STATUS_POSTE = 1;
    const STATUS_EN_ATTENTE_DE_TRANSMISSION = 2;
    const STATUS_TRANSMIS = 3;
    const STATUS_ACQUITTEMENT_RECU = 4;

    const STATUS_VALIDE = 5;

    const STATUS_DOCUMENT_RECU = 7;
    const STATUS_ACQUITTEMENT_ENVOYE = 8;

	const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;

	const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;

	const STATUS_DOCUMENT_RECU_PAS_DAR = 21;

	const STATUS_ENVOYE_AU_SAE = 12;
	const STATUS_ARCHIVE_PAR_LE_SAE = 13;

	const STATUS_ERREUR_LORS_DE_L_ARCHIVAGE = 14;
	const STATUS_EN_ATTENTE_D_ETRE_SIGNEE = 18;

	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getAllStatus(){
		$sql = "SELECT id, name FROM actes_status ORDER BY id";
		$result = array();
		foreach($this->sqlQuery->query($sql) as $line){
			$result[$line['id']] = $line['name'];
		}
		return $result;
	}

	public static function getStatusLibelle($status_id){
	    $status_libelle_list = [
	        19 => "En attente de transmission au SAE",
	        20 => "Erreur lors de l'envoi au SAE",
            12 => "Envoyé au SAE",
            13 => "Archivé par le SAE",
            14 => "Erreur lors de l'archivage",
        ];

	    return $status_libelle_list[$status_id]??$status_id;

    }

}