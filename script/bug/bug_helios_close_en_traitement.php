<?php

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);


$sql = "SELECT id,helios_transactions.submission_date,filename FROM helios_transactions " .
    " WHERE last_status_id=? AND helios_transactions.submission_date < ?";

$all = $sqlQuery->query($sql, HeliosTransactionsSQL::EN_TRAITEMENT, "2016-08-01");

foreach ($all as $line) {
    print_r($line);
    $message = "Passage de la transaction {$line['id']} en erreur";
    //$heliosTransactionSQL->updateStatus($line['id'],HeliosTransactionsSQL::ERREUR,$message);
    echo "$message\n";
    exit;
}
