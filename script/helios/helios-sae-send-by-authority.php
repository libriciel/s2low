<?php

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();

$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);
$authority_id = intval($argv[1] ?? 0);

if (! $authority_id) {
    $s2lowLogger->info("Usage : {$argv[0]} authority_id");
    $s2lowLogger->info("\tEnvoi à l'archivage toutes les transactions PES d'une collectivité");
    $s2lowLogger->info("\tLes transactions sont à l'état en attente d'envoi au SAE");
    exit(-1);
}


$objectInstancier->get(HeliosEnvoiSAE::class)->sendAllArchive($authority_id);
