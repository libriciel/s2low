<?php

use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


# usage : cat /tmp/openssl_version.log | sort | uniq | php client-bad-tls-version.php


$sortie = fopen("php://stdout", "w");

$fr = fopen("php://stdin", "r");

$sql = "SELECT users.id,users.email,givenname,users.name as user_name,authorities.id as authority_id,authorities.name as authority_name,authority_groups.id as group_id,authority_groups.name as group_name FROM users " .

    " JOIN authorities ON users.authority_id=authorities.id " .
    " JOIN authority_groups ON authorities.authority_group_id=authority_groups.id " .
    " WHERE users.id=?";

while ($input = fgets($fr, 1024)) {
    $line = trim($input);
    $info = explode(" ", $line);
    if (!$info[1]) {
        echo "Erreur lors de la génération de la liste";
        exit;
    }

    $result = $sqlQuery->queryOne($sql, $info[1]);
    $result['tls'] = $info[0];

    fputcsv($sortie, $result);
}
fclose($fr);
fclose($sortie);
