<?php

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


echo "Mise à jour du libellé du status 11 - Aquittement de document reçu -> Acquittement de document reçu\n";
$sql = "UPDATE actes_status SET name='Acquittement de document reçu' WHERE id=11";
$sqlQuery->query($sql);

echo "Mise à jour du libellé du status 18 - En attente d'être signée -> En attente d'être signé\n";
$sql = "UPDATE helios_status SET name=? WHERE id=?";
$sqlQuery->query($sql, "En attente d'être signé", 13);


echo "(re)Mise à jour de la colonne actes_transactions.user_id\n";
$sql = "UPDATE actes_transactions  SET user_id=actes_envelopes.user_id FROM actes_envelopes WHERE actes_transactions.envelope_id=actes_envelopes.id;";
$sqlQuery->query($sql);

echo "(re)Mise à jour de la colonne actes_transactions.authority_id\n";
$sql = "UPDATE actes_transactions  SET authority_id=users.authority_id FROM users WHERE users.id=actes_transactions.user_id;";
$sqlQuery->query($sql);

//Cela permet d'éviter de notifier toutes les vieilles transactions autre que les 1-1
echo "Suppression de toutes les notifications message 2,3,4 et 5\n";
$sql = "UPDATE actes_transactions SET auto_broadcasted=TRUE WHERE type IN ('2','3','4','5','6')";
$sqlQuery->query($sql);
