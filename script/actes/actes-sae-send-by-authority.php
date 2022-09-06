<?php

use S2lowLegacy\Class\actes\ActesEnvoiSaeWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\ObjectInstancier;

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);


if ($argc < 2) {
    echo "Usage {$argv[0]} authority_id\n";
    echo "{$argv[0]} : permet d'envoyer au SAE toutes les transactions dont on a préparé l'envoi pour une collectivité\n";
    exit(-1);
}

$s2LowLogger = $objectInstancier->get(S2lowLogger::class);
$s2LowLogger->enableStdOut();

$authority_id = $argv[1];

$actesTransactionsSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$transaction_list = $actesTransactionsSQL->getListByStatusAndAuthority(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE, $authority_id, 0, 100000);


$actesVerifSAEWorker = $objectInstancier->get(ActesEnvoiSaeWorker::class);

echo count($transaction_list) . " transactions à envoyer\n";
foreach ($transaction_list as $transaction_info) {
    $actesVerifSAEWorker->work($transaction_info['id']);
}
