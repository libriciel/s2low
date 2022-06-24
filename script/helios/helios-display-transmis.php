<?php

require_once(__DIR__ . "/../../init/init.php");

$debut = "2016-04-26";
//$debut = "2008-04-26";
$fin = "2016-05-04";


$sql = "SELECT xml_nomfic, date, helios_ftp_dest FROM helios_transactions " .
        " JOIN helios_transactions_workflow ON helios_transactions.id = helios_transactions_workflow.transaction_id " .
        " AND helios_transactions_workflow.status_id =helios_transactions.last_status_id " .
        " JOIN authorities ON helios_transactions.authority_id=authorities.id " .
        " WHERE last_status_id=? AND date > ? AND date < ?" ;

$transactions_list = $sqlQuery->query($sql, HeliosTransactionsSQL::TRANSMIS, $debut, $fin);

foreach ($transactions_list as $transaction) {
    echo implode(";", $transaction);
    echo "\n";
}
