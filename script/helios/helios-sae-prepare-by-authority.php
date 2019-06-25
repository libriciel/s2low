<?php


require_once(__DIR__ . "/../../init/init.php");

if (empty($argv[1])) {
	echo "Usage : {$argv[0]} authority_id\n";
	echo "\tEnvoi à l'archivage toutes les transactions PES d'une collectivité\n";
	echo "\tLes transactions sont à l'état 'Information disponible' ou 'Erreur lors de l'envoi au SAE' (20)\n";
	exit;
}


$authority_id = $argv[1];
$date=date('Y-m-d',strtotime(date('Y-m-d').'- 15 DAY'));

$sql = "SELECT  helios_transactions.id as id ".
	"FROM helios_transactions ".
	"WHERE authority_id=? ".
	"AND last_status_id IN (?,?) ".
	"AND submission_date < ? ";


$transaction_id_list = $sqlQuery->queryOneCol(
	$sql,
	$authority_id,
	HeliosStatusSQL::INFORMATION_DISPONIBLE,
	HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
	$date
);

if (! $transaction_id_list){
	echo "Aucune transaction trouvée\n";
	exit;
}


$nb_transaction = count($transaction_id_list);

echo "$nb_transaction vont être traité\n";


$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$heliosArchiveControler = $objectInstancier->get(HeliosPrepareEnvoiSAE::class);

foreach($transaction_id_list as $transaction_id){
	$transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);
	echo "Traitement de $transaction_id - {$transaction_info['id']}: ";

	$r = $heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($transaction_info['user_id'],$transaction_id);
	if ($r){
		echo "OK";
	} else {
		echo "Echec - ".$heliosArchiveControler->getLastError();
	}

	echo "\n";
}

echo "Fin du script\n";

