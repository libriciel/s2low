<?php

require_once __DIR__."/../../init/init.php";

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);

$nbJours=30;
$help = false;
$test = false;

foreach (array_slice($argv,1, $argc) as $argument){
    if($argument === "-t"){
        $test = true;
    } else if(strval((int) $argument)=== $argument){
        $nbJours=(int) $argument;
    } else{
        $help = true;
    }
}

if($help){
    echo "Usage :  {$argv[0]} [-t ] [nbJours]\n";
    echo "{$argv[0]} : permet de passer à l'état erreur toutes les transactions helios à l'état \"Transmis\" \n";
    echo "depuis plus de nbJours\n";
    echo "-t : mode test (ne réalise pas la transaction)\n";
    echo "nbJours : entier spécifiant le nombre de jours à prendre en compte\n";
    exit(-1);
}

if($test){
    echo "Mode test\n";
}

$dateForRequest = date('Y-m-d', strtotime("-$nbJours days"));

$sql = "SELECT helios_transactions.id,helios_transactions.submission_date,filename,message FROM helios_transactions " .
    " JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id ".
    " WHERE last_status_id=? AND helios_transactions_workflow.date <= ? AND status_id=? order by submission_date";

$all = $sqlQuery->query($sql,HeliosTransactionsSQL::TRANSMIS,$dateForRequest,HeliosTransactionsSQL::TRANSMIS);

$nbTransactions = count($all);
echo "$nbTransactions transaction(s) transmise(s) depuis $nbJours jour(s), soit le $dateForRequest\n";

$i=0;

foreach($all as $line){
    print_r($line);
    echo "Transaction {$line['id']} est soumise depuis {$line['submission_date']}\n";
    $message = "Passage de la transaction {$line['id']} a erreur via le script helios-set-on-error";
    $heliosTransactionSQL->updateStatus($line['id'],HeliosTransactionsSQL::ERREUR,$message);
    echo "$message\n";
    $i++;
}

$action = $test?"à traiter":"traitée(s)";
echo "$i transaction(s) $action\n";