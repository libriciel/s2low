<?php

//Permet de poster toutes les transactions d'une collectivité qui sont dans l'état 14
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

if (empty($argv[1])) {
    echo "Usage {$argv[0]} authority_id\n";
    exit;
}



$authority_id = $argv[1];

$sql = "SELECT id,user_id FROM helios_transactions WHERE authority_id = ? AND last_status_id=?";

$all_info = $sqlQuery->query($sql, $authority_id, HeliosTransactionsSQL::ATTENTE_POSTEE);

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);


foreach ($all_info as $info) {
    $id = $info['id'];
    $htw = new HeliosTransactionWorkflow();

    $htw->set("transaction_id", $id);
    $htw->set("status_id", 1);
    $htw->set("message", "Fichier bien reçu par la plate-forme S2low");

    $htw->set("date", date('Y-m-d H:i:s'));

    if (!$htw->save(true)) {
        throw new Exception("Erreur lors de la sauvegarde de $id");
    }
    $heliosTransactionSQL->setLastStatusId($id);
    $msg = "Préparation de la télétransmission Transaction n°" . $id . ". Résultat ok.";

    $me = new User($info['user_id']);
    $me->init();


    Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', 'helios', $me);
    echo $msg . "\n";
}
