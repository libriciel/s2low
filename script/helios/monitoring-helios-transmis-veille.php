<?php

// Script chargÃ© de retourner le nombre de flux PES Ã  l'Ã©tat transmis depuis la veille
// Sortie au format Influxdb
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

$status_id = "3";
$timestamp = date("Y-m-d");
$sql = "SELECT count(*) " .
     "FROM helios_transactions " .
     "WHERE helios_transactions.last_status_id = '" . $status_id . "' " .
     "AND helios_transactions.submission_date < '" . $timestamp . "'";
#echo "$sql \n";

$nb_transac = $sqlQuery->queryOne($sql);
echo "helios_transaction,status=transmislimit,application=s2low nb_transac=$nb_transac";
