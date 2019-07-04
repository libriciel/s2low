<?php

require_once ( __DIR__."/../../init/init.php");

$actesTransactionSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$sql = "SELECT id FROM actes_transactions WHERE authority_id=3202 AND last_status_id=14";

$id_list = $sqlQuery->queryOneCol($sql);

foreach($id_list as $transaction_id){
	echo $transaction_id."\n";
	$actesTransactionSQL->updateStatus($transaction_id,12,"Reprise récupération SAE");
}
