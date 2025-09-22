<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\helios\HeliosSAEDateManager;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\helios\HeliosPurge;

require_once(__DIR__ . '/../../init/init.php');

list($s2lowLogger, $heliosTransactionSQL, $heliosSAEDateManager, $heliosPurge ) =
    LegacyObjectsManager::getLegacyObjectInstancier()->getArray([
        LoggerInterface::class, HeliosTransactionsSQL::class, HeliosSAEDateManager::class, HeliosPurge::class
    ]);

/**
 * Appeller le script avec l'argument "do" permet de ne pas poser la question pour chaque transaction
 */

$delete_all = (isset($argv[1]) && $argv[1] == 'do');

try {
    $date = $heliosSAEDateManager->getDateBeforeWhichWeDestroy();
    $s2lowLogger->info(sprintf("Transaction passé à l'état information disponible ou erreur avant le %s", $date));
} catch (Exception $exception) {
    $s2lowLogger->info($exception->getMessage());
    exit();
}

$transaction_ids = $heliosTransactionSQL->getTransactionsADetruire($date);
$s2lowLogger->info(sprintf("%d transactions trouvées à détruire", count($transaction_ids)));

foreach ($transaction_ids as $transaction_id) {
    $s2lowLogger->info("Traitement de la transaction $transaction_id");

    if ($delete_all || ask("Voulez-vous supprimer la transaction $transaction_id  ? (oui/non)")) {
        $heliosPurge->purge($transaction_id);
        $heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosTransactionsSQL::DETRUITE,
            "Destruction de la transaction"
        );
        $s2lowLogger->info("Les fichiers de la transaction $transaction_id ont été détruits");
    }
}

sleep_wrapper(60);

function ask($question)
{
    echo "$question";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    return (trim($line) == 'oui');
}
