<?php

require_once ( __DIR__."/../../init/init.php");

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);


$sql = "SELECT helios_transactions.id,helios_transactions.submission_date,filename,message FROM helios_transactions " .
    " JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id ".
    " WHERE last_status_id=? AND helios_transactions.submission_date > ? AND status_id=? order by submission_date";

$all = $sqlQuery->query($sql,HeliosTransactionsSQL::ERREUR,"2018-01-26",HeliosTransactionsSQL::ERREUR);

$i=0;

foreach($all as $line){
    if (! preg_match("#un virus#",$line['message'])){
        continue;
    }
    print_r($line);
    echo "Transaction {$line['id']} est en erreur\n";
    $message = "Passage de la transaction {$line['id']} a poste";
    $heliosTransactionSQL->updateStatus($line['id'],HeliosTransactionsSQL::POSTE,$message);
    echo "$message\n";
    exit;
    $i++;
}
echo "$i transaction traité\n";
