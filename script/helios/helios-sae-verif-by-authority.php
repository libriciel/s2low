<?php


require_once( __DIR__."/../../init/init.php");


if ($argc < 2){
	echo "Usage {$argv[0]} authority_id\n";
	echo "{$argv[0]} : permet de vérifier toutes les transactions envoyé au SAE d'une collectivité\n";
	exit(-1);
}

$s2LowLogger = $objectInstancier->get(S2lowLogger::class);
$s2LowLogger->enableStdOut();

$authority_id = $argv[1];

$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);

$transaction_list = $heliosTransactionsSQL->getIdsByStatus(HeliosStatusSQL::ENVOYER_AU_SAE,$authority_id);


$heliosVerificationSaeWorker = $objectInstancier->get(HeliosVerificationSaeWorker::class);

$s2LowLogger->info(count($transaction_list). " transactions à vérifier");
foreach($transaction_list as $transaction_id){
	$heliosVerificationSaeWorker->work($transaction_id);
}
