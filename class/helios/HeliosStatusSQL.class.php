<?php

/**
 * @deprecated v4.3.12
 */
class HeliosStatusSQL {

	const ERREUR = -1;
	const POSTE = 1;
	const ATTENTE = 2;
	const INFORMATION_DISPONIBLE = 8;
	const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;
	const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;
	const ENVOYER_AU_SAE = 9;
	const ACCEPTER_PAR_LE_SAE = 10;

    const ADETRUIRE = 21;
    const DETRUITE = 22;



	static public function getStatusLibelle($status_id){
		$status_libelle_list = [
			19 => "En attente de transmission au SAE",
			20 => "Erreur lors de l'envoi au SAE",
			9 => "Envoyï¿½ au SAE",
			10 => "Acceptï¿½ par le SAE",
		];

		return $status_libelle_list[$status_id]??$status_id;
	}
}