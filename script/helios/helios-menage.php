<?php

/**
 *
 * @deprecated 4.2.4 use helios-purge-transaction.php instead
 *
 */
require_once( __DIR__ . "/../../init/init.php");

$sql = "SELECT * FROM helios_transactions WHERE last_status_id=".HeliosStatusSQL::ADETRUIRE;

$allTransaction = $sqlQuery->query($sql);
$heliosFile = new HeliosFiles(HELIOS_FILES_UPLOAD_ROOT,HELIOS_RESPONSES_ROOT);

echo count($allTransaction) . " transactions Helios trouvées dans l'état a détruire\n";

foreach($allTransaction as $transactionInfo){
    $heliosFile->deleteFiles($transactionInfo);

    $msg = "Les fichiers de la transaction {$transactionInfo['id']} ont été détruits";

    $heliosTransactionsSQL->updateStatus($transactionInfo['id'],HeliosStatusSQL::DETRUITE, $msg);
    Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$transactionInfo['user_id']);

    echo $msg."\n";
}
