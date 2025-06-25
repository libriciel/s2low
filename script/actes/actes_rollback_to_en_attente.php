<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
list($sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);

$sql = "
SELECT transaction_id,date FROM actes_transactions  
JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id 
AND actes_transactions_workflow.status_id=3
WHERE last_status_id=3 AND type='1' AND date>'2016-11-28 16:00:00' AND date<'2016-11-29' 
ORDER BY date
";

$all_transaction = $sqlQuery->query($sql);

foreach ($all_transaction as $info) {
    echo $info['transaction_id'];
    $actesTransactionSQL->updateStatus(
        $info['transaction_id'],
        ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
        'Transaction repassee manuellement en attente de transmission'
    );
    echo " [OK]\n";
    exit;
}
