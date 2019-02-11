<?php

require_once( __DIR__."/../../init/init.php");


if ($argc < 2){
	echo "Usage {$argv[0]} authority_id\n";
	echo "{$argv[0]} : permet de passer les transactions des états 4 et 5 dans l'état prepare-sae (19)\n";
	exit(-1);
}

$authority_id = $argv[1];

$actesTransactionsSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$date=date('Y-m-d',strtotime(date('Y-m-d').'- 62 DAY'));


$sql = "SELECT at.id ".
	"FROM actes_transactions AS at ".
	"INNER JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) ".
	"WHERE ".
	"authority_id=? ".
	"AND at.type='1' ".
	"AND at.last_status_id IN (?,?) ".
	"AND atw.date > '2008-06-01' ".
	"AND atw.date < ? ";

$transaction_id_list = $sqlQuery->queryOneCol($sql,$authority_id,4,5,$date);

if (! $transaction_id_list){
	echo "Aucune transaction trouvée\n";
	exit;
}

$nb_transaction = count($transaction_id_list);

echo "$nb_transaction vont être traité\n";


$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$actesArchiveControler = $objectInstancier->get(ActesArchiveControler::class);


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

