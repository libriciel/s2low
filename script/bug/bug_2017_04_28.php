<?php

/*
 * Le 28 avril 2017, la base clamav a détecté que les documents signé PADES contenait un virus :
 * Il s'agit d'un faut positif à priori : https://lists.gt.net/clamav/users/69581
 * Pas mal d'actes sont donc passées en erreur
 * On remet ces actes à l'état posté
 */


use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);




$sql = "SELECT actes_envelopes.id, actes_transactions.id as transaction_id, actes_transactions_workflow.message FROM actes_transactions_workflow  
		JOIN actes_transactions ON actes_transactions_workflow.transaction_id=actes_transactions.id
		JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id 
		WHERE actes_transactions_workflow.date > ?  AND actes_transactions_workflow.status_id=-1
		AND actes_transactions.last_status_id=-1 AND message LIKE ?";


$actes_list = $sqlQuery->query($sql, '2017-04-28', '%Pdf.Exploit.%');


$actesTransactionSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesTransactionsSQL::class);

foreach ($actes_list as $acte) {
    echo "{$acte['id']} : {$acte['message']}\n";

    $actesTransactionSQL->updateStatus(
        $acte['id'],
        ActesStatusSQL::STATUS_POSTE,
        'Transaction repassee en posté'
    );
}
