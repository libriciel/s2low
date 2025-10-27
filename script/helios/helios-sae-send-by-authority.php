<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);

$s2lowLogger = $objectInstancier->get(LoggerInterface::class);

$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);
$authority_id = intval($argv[1] ?? 0);

if (! $authority_id) {
    $s2lowLogger->info("Usage : {$argv[0]} authority_id");
    $s2lowLogger->info("\tEnvoi à l'archivage toutes les transactions PES d'une collectivité");
    $s2lowLogger->info("\tLes transactions sont à l'état en attente d'envoi au SAE");
    exit(-1);
}


$objectInstancier->get(HeliosEnvoiSAE::class)->sendAllArchive($authority_id);
