<?php

require_once(__DIR__ . "/../../init/init.php");


$s2LowLogger = $objectInstancier->get(S2lowLogger::class);
$s2LowLogger->enableStdOut();

//TODO : move to HeliosStatusSQL
function getAllStatus($sqlQuery)
{
    $sql = "SELECT id, name FROM actes_status ORDER BY id";
    $result = array();
    foreach ($sqlQuery->query($sql) as $line) {
        $result[$line['id']] = $line['name'];
    }
    return $result;
}

//TODO : move to HeliosStatusSQL
//$actesStatus = $objectInstancier->get(ActesStatusSQL::class)->getAllStatus();
$actesStatus = getAllStatus($sqlQuery);

if ($argc != 5) {
    $s2LowLogger->error("Nombre de paramètres incorrect. ( 2 Attendus, " . ($argc - 1) . " renseigné(s) )");
    $s2LowLogger->error("Usage {$argv[0]} status_from status_to date_min date_max");
    $s2LowLogger->error("{$argv[0]} : Modifie le status de TOUTES les transactions status_from vers status_to entré dans l'état status_from entre date_min et date_max");
    $s2LowLogger->error("Date au format YYYY-mm-dd");
    echo printStatus($actesStatus);
    exit(-1);
}

$status_from = (int) $argv[1];
$status_to = (int) $argv[2];
$date_min = $argv[3];
$date_max =  $argv[4];

/*$sql = "SELECT actes_transactions.id,actes_transactions.number, authorities.name,date FROM actes_transactions
    JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id AND status_id=?
    JOIN authorities on actes_transactions.authority_id = authorities.id
    WHERE last_status_id=? AND date>? AND date<?";*/

$sql = "SELECT helios_transactions.id,authorities.name, helios_transactions.filename, helios_transactions.submission_date FROM helios_transactions " .
    " JOIN authorities ON authorities.id=helios_transactions.authority_id " .
    "JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id = helios_transactions.id AND helios_transactions_workflow.status_id=helios_transactions.last_status_id" .
    " WHERE last_status_id=? AND helios_transactions.submission_date > ? AND helios_transactions.submission_date < ? AND helios_transactions_workflow.message LIKE 'Modification%'" .
    " ORDER BY submission_date DESC ";

$transaction_info_list = $sqlQuery->query($sql, $status_from, $date_min, $date_max);

if (count($transaction_info_list) < 1) {
    echo "Aucune transaction ne correspond au critère\n";
    exit(-3);
}

foreach ($transaction_info_list as $i => $transaction_info) {
    echo $i . " : " . $transaction_info['id'] . " " . $transaction_info['name'] . " " . $transaction_info['filename'] . " " . $transaction_info['submission_date'] . "\n";
}

echo "Les transactions passeront du status $status_from au status $status_to\n";
echo "Etes-vous sr de vouloir continuer ? Tapez OUI pour continuer : ";

$stdin = fopen('php://stdin', 'r');

$response = fgets($stdin);
if ($response != "OUI\n") {
    echo "Annulé\n";
    exit(-2);
}

$heliosTransactions = $objectInstancier->get(HeliosTransactionsSQL::class);

foreach ($transaction_info_list as $i => $transaction_info) {
    $heliosTransactions->updateStatus($transaction_info['id'], $status_to, "Modification manuelle du statut");
    $s2LowLogger->info("Modification de la transaction {$transaction_info['id']} : status $status_to");
}


function printStatus(array $heliosStatuts)
{
    $message = "status_id doit tre un entier appartenant  la liste suivante :\n";
    $message .= "    status_id\t|\tStatut\n";
    $message .= "----------------|---------------------------------------\n";
    foreach ($heliosStatuts as $key => $heliosStatut) {
        $message .= "\t$key\t|\t($heliosStatut)\n";
    }
    return $message;
}
