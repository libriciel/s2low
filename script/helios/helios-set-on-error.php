<?php

require_once __DIR__."/../../init/init.php";

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);

if($argc < 2){
    $nbJours=30;
}

$nbJours = (int) $argv[1];

if( !(strval($nbJours) === $argv[1])){
    echo "{$argv[1]} n'est pas un entier\n";
    exit(-1);
}

$dateForRequest = date('Y-m-d', strtotime("-$nbJours days"));

$sql = "SELECT helios_transactions.id,helios_transactions.submission_date,filename,message FROM helios_transactions " .
    " JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id ".
    " WHERE last_status_id=? AND helios_transactions_workflow.date < ? AND status_id=? order by submission_date";

$all = $sqlQuery->query($sql,HeliosTransactionsSQL::TRANSMIS,$dateForRequest,HeliosTransactionsSQL::TRANSMIS);

echo "transmis depuis $dateForRequest :\n";

$i=0;

foreach($all as $line){
    print_r($line);
    echo "Transaction {$line['id']} est soumise depuis {$line['submission_date']}\n";
    $message = "Passage de la transaction {$line['id']} a erreur via le script helios-set-on-error";
    $heliosTransactionSQL->updateStatus($line['id'],HeliosTransactionsSQL::ERREUR,$message);
    echo "$message\n";
    $i++;
}
echo "$i transaction(s) traité(s)\n";