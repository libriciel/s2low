<?php

require_once ( __DIR__."/../../init/init.php");


if ($argc < 2){
	echo "Usage {$argv[0]} authority_id\n";
	echo "{$argv[0]} : permet de mettre toutes les transaction en erreur arcihvage (14) dans l'état envoyé au SAE (12)\n";
	echo "Utilie quand Pastell n'a pas eu le temps de récuperer l'AR dans le temps impartie et qu'il a mis la transaction en erreur à tort";
	exit(-1);
}

$authority_id = $argv[1];


$actesTransactionSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$sql = "SELECT id FROM actes_transactions WHERE authority_id=? AND last_status_id=14";

$id_list = $sqlQuery->queryOneCol($sql,$authority_id);

foreach($id_list as $transaction_id){
	echo $transaction_id."\n";
	$actesTransactionSQL->updateStatus($transaction_id,12,"Reprise récupération SAE");
}
