<?php

require_once( __DIR__."/../../init/init.php");

if (empty($argv[1])){
	echo "Usage : {$argv[0]} authority_id\n";
	echo "\tEnvoi à l'archivage toutes les transactions d'une collectivité\n";
	echo "\tLes transactions sont à l'état 'Acquittement reçu' ou 'Validé' et il s'agit uniquement des envois d'actes (pas des réponses de la préfectures)\n";
	exit;
}

$authority_id = $argv[1];


$sql = "SELECT at.id ".
    "FROM actes_transactions AS at ".
    "INNER JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= at.last_status_id) ".
    "WHERE ".
    "authority_id=? ".
    "AND at.type=1 ".
    "AND at.last_status_id IN (?,?) ".
    "AND AGE(atw.date::TIMESTAMP) > INTERVAL '30 day' ";

$transaction_id_list = $sqlQuery->queryOneCol($sql,$authority_id,4,5);

if (! $transaction_id_list){
	echo "Aucune transaction trouvée\n";
	exit;
}

$nb_transaction = count($transaction_id_list);

echo "$nb_transaction vont être traité\n";


$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$actesArchiveControler = $objectInstancier->get("ActesArchiveControler");


foreach($transaction_id_list as $transaction_id){
	$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
	echo "Traitement de $transaction_id - {$transaction_info['unique_id']}: ";

	$r = $actesArchiveControler->setArchiveEnAttenteEnvoiSEA($transaction_info['user_id'],$transaction_id);
	if ($r){
		echo "OK";
	} else {
		echo "Echec - ".$actesArchiveControler->getLastError();
	}

	echo "\n";
}

echo "Fin du script\n";

