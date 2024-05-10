<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
list($actesTransactionSQL,$sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ActesTransactionsSQL::class, SQLQuery::class]
    );


$sql = "
SELECT transaction_id FROM actes_transactions  
JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id 
AND actes_transactions_workflow.status_id=3
WHERE last_status_id=3 AND type='7' AND date<'2019-04-12' 
";

$all_transaction = $sqlQuery->query($sql);

foreach ($all_transaction as $info) {
    echo $info['transaction_id'];
    $actesTransactionSQL->updateStatus(
        $info['transaction_id'],
        ActesStatusSQL::STATUS_EN_ERREUR,
        'Transaction passé en erreur'
    );
    echo " [OK]\n";
    exit;
}
