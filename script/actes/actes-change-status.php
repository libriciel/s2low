<?php



require_once( __DIR__."/../../init/init.php");

$s2LowLogger = $objectInstancier->get(S2lowLogger::class);
$s2LowLogger->enableStdOut();

if ($argc < 3){
	$s2LowLogger->error("Usage {$argv[0]} transaction_id new_status_id");
	$s2LowLogger->error("{$argv[0]} : permet de modifier le statut d'une transaction");
	exit(-1);
}

$transaction_id = $argv[1];
$status_id = $argv[2];

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);
$actesTransactions->updateStatus($transaction_id,$status_id,"Modification manuelle du status");
$s2LowLogger->info("Modification de la transaction $transaction_id : status $status_id");

exit(0);