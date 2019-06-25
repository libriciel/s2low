<?php


require_once(__DIR__ . "/../../init/init.php");

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();

$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);
$authority_id = intval($argv[1]??0);

if (! $authority_id) {
	$s2lowLogger->info("Usage : {$argv[0]} authority_id");
	$s2lowLogger->info("\tEnvoi à l'archivage toutes les transactions PES d'une collectivité");
	$s2lowLogger->info("\tLes transactions sont à l'état 'Information disponible' ou 'Erreur lors de l'envoi au SAE' (20)");
	exit(-1);
}
$s2lowLogger->info("Début du script");

$transaction_id_list = $heliosTransactionsSQL->getTransactionToPrepareToSAE(
	HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
	$authority_id,
	false,
	[
		HeliosStatusSQL::INFORMATION_DISPONIBLE,
		HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE
	]
);

$s2lowLogger->info(sprintf(
	"%d transaction(s) vont être traité(s)",
	count($transaction_id_list)
));

$heliosArchiveControler = $objectInstancier->get(HeliosPrepareEnvoiSAE::class);
foreach($transaction_id_list as $transaction_id){
	$transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);
	$s2lowLogger->info( "Traitement de $transaction_id - {$transaction_info['id']}");
	$heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($transaction_info['user_id'],$transaction_id);
}

$s2lowLogger->info("Fin du script");
