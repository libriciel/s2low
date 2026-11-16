<?php

use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


# usage : cat /tmp/openssl_version.log | sort | uniq | php client-bad-tls-version.php


$sortie = fopen("php://stdout", "w");

$fr = fopen("php://stdin", "r");

$sql = <<<SQL
SELECT users.id, users.email, givenname, users.name AS user_name,
       authorities.id AS authority_id, authorities.name AS authority_name,
       actes_group.id AS actes_group_id, actes_group.name AS actes_group_name,
       helios_group.id AS helios_group_id, helios_group.name AS helios_group_name
FROM users
JOIN authorities ON users.authority_id = authorities.id
LEFT JOIN authority_groups actes_group ON authorities.actes_group_id = actes_group.id
LEFT JOIN authority_groups helios_group ON authorities.helios_group_id = helios_group.id
WHERE users.id = ?
  AND (authorities.actes_group_id IS NOT NULL OR authorities.helios_group_id IS NOT NULL)
SQL;

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
