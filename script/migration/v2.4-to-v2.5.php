<?php

/*
 * Faire passer le script rev919.sql
 * Faire passer le script v2.4-to-v2.5.php (ce script)
 * Faire passer le script rev928.sql
 * mettre le script cron/journal-vidange.php dans le cron
 *
 */


exit;

//Il est nécessaire de spécifier l'identifiant de la table log a partir de laquelle on va vider les lignes dans
//la table log_historique.
//Il faut être au plus pret des 6 mois.
//Le mieux est d'indexé la table logs de de calculer l'id.

//Coupe la table en deux à partir du id_cut
$id_cut_journal = 0;

//A FAIRE AVANT : mise à jour de la base de données

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

migration_log("Migration S2low 2.4 vers 2.5");

//1. Passage au nouveau système de notification forcé pour tout le monde.
migration_log("Passage au nouveau système de notification : déporter à une date ultérieur");
//$sql = "UPDATE authorities SET new_notification=true";
//$sqlQuery->query($sql);
migration_log("[PASS]");


migration_log("Copie de la table logs vers logs_historique");
$sql = "CREATE TABLE logs_historique AS SELECT * FROM logs;";
$sqlQuery->query($sql);
migration_log("[DONE]");

migration_log("Ajout de la clé primaire sur la table logs_historique");
$sql = "ALTER TABLE logs_historique ADD PRIMARY KEY (id);";
$sqlQuery->query($sql);
migration_log("[DONE]");

migration_log("Suppression des lignes en trop dans logs_historique");
$sql = "DELETE FROM logs_historique WHERE id > ?";
$sqlQuery->query($sql, $id_cut_journal);
migration_log("[DONE]");


migration_log("Suppression des lignes en trop dans logs");
$sql = "DELETE FROM logs WHERE id <= ?";
$sqlQuery->query($sql, $id_cut_journal);
migration_log("[DONE]");


migration_log("Migration terminée");

function migration_log($message)
{
    echo date("Y-m-d H:i:s") . " - $message\n";
}
