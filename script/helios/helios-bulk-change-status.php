<?php

use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . '/../../init/init.php');
/** @var HeliosStatusSQL $helios_statuses */
/** @var HeliosTransactionsSQL $heliosTransactions  */
/** @var S2lowLogger $s2LowLogger */

list($helios_statuses,$heliosTransactions, $s2LowLogger) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [HeliosStatusSQL::class,HeliosTransactionsSQL::class, S2lowLogger::class]
    );

$s2LowLogger->enableStdOut();

if ($argc != 5) {
    $s2LowLogger->error('Nombre de paramètres incorrect. ( 2 Attendus, ' . ($argc - 1) . ' renseigné(s) )');
    $s2LowLogger->error("Usage $argv[0] status_from status_to date_min date_max");
    $s2LowLogger->error("$argv[0] : Modifie le status de TOUTES les transactions status_from vers status_to entré dans l'état status_from entre date_min et date_max");
    $s2LowLogger->error('Date au format YYYY-mm-dd');
    echo printStatus($helios_statuses->getAllStatusMod());
    exit(-1);
}

$status_from = (int) $argv[1];
$status_to = (int) $argv[2];
$date_min = $argv[3];
$date_max =  $argv[4];

$transaction_info_list = $heliosTransactions->getTransactionsByDateAndStatus($status_from, $date_min, $date_max);

if (count($transaction_info_list) < 1) {
    echo "Aucune transaction ne correspond au critère\n";
    exit(-3);
}

foreach ($transaction_info_list as $i => $transaction_info) {
    echo $i . ' : ' . $transaction_info['id'] . ' ' . $transaction_info['name'] . ' ' . $transaction_info['filename'] . ' ' . $transaction_info['submission_date'] . "\n";
}

echo "Les transactions passeront du status $status_from au status $status_to\n";
echo 'Etes-vous sr de vouloir continuer ? Tapez OUI pour continuer : ';

$stdin = fopen('php://stdin', 'r');

$response = fgets($stdin);
if ($response != "OUI\n") {
    echo "Annulé\n";
    exit(-2);
}

foreach ($transaction_info_list as $i => $transaction_info) {
    $heliosTransactions->updateStatus($transaction_info['id'], $status_to, 'Modification manuelle du statut');
    if($status_to === HeliosStatusSQL::POSTE)
    {
        $heliosTransactions->setInfoFromPESAller($transaction_info['id'], [
            'nom_fic' => null,
            'cod_col' => null,
            'cod_bud' => null,
            'id_post' => null
        ]);
    }
    $s2LowLogger->info("Modification de la transaction {$transaction_info['id']} : status $status_to");
}


function printStatus(array $heliosStatuts): string
{
    $message = "status_id doit tre un entier appartenant  la liste suivante :\n";
    $message .= "    status_id\t|\tStatut\n";
    $message .= "----------------|---------------------------------------\n";
    foreach ($heliosStatuts as $key => $heliosStatut) {
        $message .= "\t$key\t|\t($heliosStatut)\n";
    }
    return $message;
}
