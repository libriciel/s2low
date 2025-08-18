<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\HeliosVerificationSaeWorker;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);


if ($argc < 2) {
    echo "Usage {$argv[0]} authority_id\n";
    echo "{$argv[0]} : permet de vérifier toutes les transactions envoyé au SAE d'une collectivité\n";
    exit(-1);
}

$s2LowLogger = $objectInstancier->get(LoggerInterface::class);

$authority_id = $argv[1];

$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);

$transaction_list = $heliosTransactionsSQL->getIdsByStatus(HeliosStatusSQL::ENVOYER_AU_SAE, $authority_id);


$heliosVerificationSaeWorker = $objectInstancier->get(HeliosVerificationSaeWorker::class);

$s2LowLogger->info(count($transaction_list) . " transactions à vérifier");
foreach ($transaction_list as $transaction_id) {
    $heliosVerificationSaeWorker->work($transaction_id);
}
