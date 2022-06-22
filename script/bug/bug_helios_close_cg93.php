<?php

require_once(__DIR__ . "/../../init/init.php");

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);


$sql = "SELECT id,helios_transactions.submission_date,filename FROM helios_transactions " .
    " WHERE last_status_id=3 AND helios_transactions.submission_date > ? AND helios_transactions.submission_date < ?";

$all = $sqlQuery->query($sql, "2016-04-26", "2016-05-04");

foreach ($all as $line) {
    print_r($line);
    $message = "La transaction {$line['id']} est de nouveau à l'état posté.";
    $heliosTransactionSQL->updateStatus($line['id'], HeliosTransactionsSQL::POSTE, $message);
    $heliosTransactionSQL->setNomFic($transaction_id, null);
    echo "$message\n";
    exit;
}
