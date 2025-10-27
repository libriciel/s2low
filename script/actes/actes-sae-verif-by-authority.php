<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesVerifSaeWorker;
use S2lowLegacy\Lib\ObjectInstancier;

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class ]
    );


if ($argc < 2) {
    echo "Usage {$argv[0]} authority_id\n";
    echo "{$argv[0]} : permet de vérifier toutes les transactions envoyé au SAE d'une collectivité\n";
    exit(-1);
}

$s2LowLogger = $objectInstancier->get(LoggerInterface::class);

$authority_id = $argv[1];

$actesTransactionsSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$transaction_list = $actesTransactionsSQL->getListByStatusAndAuthority(ActesStatusSQL::STATUS_ENVOYE_AU_SAE, $authority_id, 0, 100000);


$actesVerifSAEWorker = $objectInstancier->get(ActesVerifSaeWorker::class);

echo count($transaction_list) . " transactions à vérifier\n";
foreach ($transaction_list as $transaction_info) {
    $actesVerifSAEWorker->work($transaction_info['id']);
}
