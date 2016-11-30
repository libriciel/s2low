<?php


/*
 * Faire passer le script rev919.sql
 * Faire passer le script v2.4-to-v2.5.php (ce script)
 * Faire passer le script rev928.sql
 * mettre le script cron/journal-vidange.php dans le cron
 *
 */

//Le mieux est d'indexé la table logs de de calculer l'id.

//A FAIRE AVANT : mise à jour de la base de données

require_once( __DIR__."/../../init/init.php");

migration_log("Migration S2low 2.4 vers 2.5");

//1. Passage au nouveau système de notification forcé pour tout le monde.
migration_log("Passage au nouveau système de notification : déporter à une date ultérieur");
//$sql = "UPDATE authorities SET new_notification=true";
//$sqlQuery->query($sql);
migration_log("[PASS]");

$date_purge = date("Y-m-d H:i:s",strtotime("-" . KEEP_NB_MONTHS_IN_LOGS . " month"));

migration_log("Copie de la table logs vers logs_historique");
$sql = "CREATE TABLE logs_historique AS SELECT * FROM logs WHERE logs.date<?;";
$sqlQuery->query($sql, $date_purge);
migration_log("[DONE]");

migration_log("Ajout de la clé primaire sur la table logs_historique");
$sql = "ALTER TABLE logs_historique ADD PRIMARY KEY (id);";
$sqlQuery->query($sql);
migration_log("[DONE]");

migration_log("Suppression des lignes en trop dans logs");
$sql = "DELETE FROM logs WHERE logs.date < ?";
$sqlQuery->query($sql,$date_purge);
migration_log("[DONE]");


migration_log("Migration terminée");

function migration_log($message){
	echo date("Y-m-d H:i:s")." - $message\n";
}