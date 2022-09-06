<?php

use S2lowLegacy\Class\actes\ActesExport;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\S2lowLogger;

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier();

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();
$s2lowLogger->setName("actes-export");


if (count($argv) < 3) {
    $s2lowLogger->error(
        sprintf(
            "Usage: %s authority_id output_directory [min_transaction_id = 0] [max_transaction_id = %d] [TAMPON]",
            $argv[0],
            ActesTransactionsSQL::MAX_ID
        )
    );
    $s2lowLogger->error(
        "Exporte l'ensemble des transactions actes de la collectivité autority_id " .
        "dans le répertoire output_directory entre min_transaction_id (inclu) et max_transaction_id (inclu). Si le 5eme argument est TAMPON, alors les fichiers seront tamponnés"
    );
    exit(-1);
}

$authority_id = $argv[1];
$output_directory = $argv[2];
$min_transaction_id = $argv[3] ?? 0;
$max_transaction_id = $argv[4] ?? ActesTransactionsSQL::MAX_ID;
$tampon = $argv[5] ?? false;

$actesExport = $objectInstancier->get(ActesExport::class);
$actesExport->setTamponnerFichier($tampon);

try {
    $actesExport->export($authority_id, $output_directory, $min_transaction_id, $max_transaction_id);
} catch (Exception $e) {
    $s2lowLogger->error($e->getMessage());
    $s2lowLogger->error($e->getTraceAsString());
    exit(-2);
}

$s2lowLogger->info("Export terminé");
exit(0);
