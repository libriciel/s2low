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


$objectInstancier->get(HeliosPrepareEnvoiSAE::class)->setArchiveEnAttenteEnvoiSEAManuellement($authority_id);
