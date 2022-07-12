<?php

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


$sql = "SELECT authority_id,name,count(*) as count FROM actes_transactions " .
    " JOIN authorities ON authorities.id=authority_id " .
    " WHERE last_status_id=? GROUP BY authority_id,name ORDER BY name";

$list = $sqlQuery->query($sql, ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

foreach ($list as $info) {
    echo "{$info['name']} ({$info['authority_id']}): {$info['count']}\n";
}
