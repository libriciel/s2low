<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\helios\HeliosExport;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier();

$s2lowLogger = $objectInstancier->get(LoggerInterface::class);

if (count($argv) < 3) {
    $s2lowLogger->error(
        sprintf(
            "Usage: %s authority_id output_directory [min_transaction_id = 0] [max_transaction_id = %d]",
            $argv[0],
            HeliosTransactionsSQL::MAX_ID
        )
    );
    $s2lowLogger->error(
        "Exporte l'ensemble des transactions helios (PES ALLER et PES Acquit) de la collectivité autority_id " .
        "dans le répertoire output_directory entre min_transaction_id (inclu) et max_transaction_id (inclu)"
    );
    exit(-1);
}

$authority_id = $argv[1];
$output_directory = $argv[2];
$min_transaction_id = $argv[3] ?? 0;
$max_transaction_id = $argv[4] ?? HeliosTransactionsSQL::MAX_ID;

$heliosExport = $objectInstancier->get(HeliosExport::class);

try {
    $heliosExport->export($authority_id, $output_directory, $min_transaction_id, $max_transaction_id);
} catch (Exception $e) {
    $s2lowLogger->error($e->getMessage());
    $s2lowLogger->error($e->getTraceAsString());
    exit(-2);
}

$s2lowLogger->info("Export terminé");
exit(0);
