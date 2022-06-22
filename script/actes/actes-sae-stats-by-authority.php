<?php

require_once( __DIR__."/../../init/init.php");


if ($argc < 2){
	echo "Usage {$argv[0]} authority_id\n";
	echo "{$argv[0]} : permet d'afficher les informations actes/sae sur une collectivité\n";
	exit(-1);
}

$authority_id = $argv[1];

$actesTransactionsSQL = $objectInstancier->get(ActesTransactionsSQL::class);


$status_info = [
	ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE => 'En attente de transmission au SAE',
	ActesStatusSQL::STATUS_ENVOYE_AU_SAE => 'Envoyé au SAE',
	ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE => 'Archivé par le SAE',
	ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE => "Erreur lors de l'envoi au SAE",
	ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE => 'Erreur lors de l\'archivage',

];

foreach ($status_info as $status_id => $status_libelle) {
	$nb = $actesTransactionsSQL->getNbByStatusAndAuthority($status_id, $authority_id);
	echo "$status_libelle ($status_id): $nb \n";
}

$sql = "SELECT count(*) FROM actes_transactions AS at ".
	" JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) ".
	" WHERE at.authority_id=? AND at.type='1' ".
	" AND at.last_status_id IN (4,5) ".
	" AND atw.date > '2008-06-01' ".
	" AND atw.date < ? " ;

$nb_to_archive = $sqlQuery->queryOne(
	$sql,
	$authority_id,
	date("Y-m-d",strtotime("-".ActesPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER." days"))
);

echo "En retard pour l'archivage: $nb_to_archive\n";


