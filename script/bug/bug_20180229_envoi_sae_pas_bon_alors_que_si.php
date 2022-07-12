<?php

/**
 * Certain flux archivé le sont dans l'état "Erreur lors de l'archivage" avec le message "La transaction 2693691 a été refusé par le SAE:
000 - Votre transfert d'archive a été accepté par la plate-forme as@lae"
 *
 * Ceci est du au passage SEDA v0.2 vers SEDA v1.
 *
 * La correction consiste à mettre le flux dans l'état Archivé par le SAE avec le message "La transaction a été acceptée par le SAE : erreur sur le message précédent"
 *
 *
 */

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier, $sqlQuery) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );




$sql = "SELECT actes_transactions.id as transaction_id, actes_transactions_workflow.message FROM actes_transactions_workflow  
		JOIN actes_transactions ON actes_transactions_workflow.transaction_id=actes_transactions.id		 
		WHERE actes_transactions.last_status_id=14 ORDER BY actes_transactions.id ";


$actes_list = $sqlQuery->query($sql);

$objectInstancier = ObjectInstancierFactory::getObjetInstancier();

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);

foreach ($actes_list as $info) {
    if (preg_match("#Votre transfert d'archive a .t. accept. par la plate-forme#", $info['message'])) {
        echo "{$info['transaction_id']} : Mise à jour du status\n";
        $actesTransactions->updateStatus($info['transaction_id'], 13, "La transaction {$info['transaction_id']} a ete acceptee par le SAE : erreur sur le message precedent");
        exit;
    }
}
