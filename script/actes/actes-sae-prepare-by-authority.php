<?php

require_once(__DIR__ . "/../../init/init.php");

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} authority_id\n";
    echo "\tEnvoi à l'archivage toutes les transactions actes d'une collectivité\n";
    echo "\tLes transactions sont à l'état 'Acquittement reçu' ou 'Validé' et il s'agit uniquement des envois d'actes (pas des réponses de la préfectures)\n";
    exit;
}

$authority_id = $argv[1];
$date = date('Y-m-d', strtotime(date('Y-m-d') . '- 62 DAY'));


$sql = "SELECT at.id " .
    "FROM actes_transactions AS at " .
    "INNER JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) " .
    "WHERE " .
    "authority_id=? " .
    "AND at.type='1' " .
    "AND at.last_status_id IN (?,?) " .
    "AND atw.date > '2008-06-01' " .
    "AND atw.date < ? ";

$transaction_id_list = $sqlQuery->queryOneCol($sql, $authority_id, 4, 5, $date);

if (! $transaction_id_list) {
    echo "Aucune transaction trouvée\n";
    exit;
}

$nb_transaction = count($transaction_id_list);

echo "$nb_transaction vont être traité\n";


$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$actesPrepareEnvoiSAE = $objectInstancier->get(ActesPrepareEnvoiSAE::class);


foreach ($transaction_id_list as $transaction_id) {
    $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
    echo "Traitement de $transaction_id - {$transaction_info['unique_id']}: ";

    $r = $actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA($transaction_info['user_id'], $transaction_id, false);
    if ($r) {
        echo "OK";
    } else {
        echo "Echec - " . $actesPrepareEnvoiSAE->getLastError();
    }

    echo "\n";
}

echo "Fin du script\n";
