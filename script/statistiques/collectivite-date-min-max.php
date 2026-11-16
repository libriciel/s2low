<?php

use S2lowLegacy\Class\CSVOutput;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


$sql = <<<SQL
SELECT authorities.id, authorities.name, authorities.siren, department,
       actes_group.name AS actes_group_name, helios_group.name AS helios_group_name
FROM authorities
LEFT JOIN authority_groups actes_group ON authorities.actes_group_id = actes_group.id
LEFT JOIN authority_groups helios_group ON authorities.helios_group_id = helios_group.id
WHERE authorities.actes_group_id IS NOT NULL OR authorities.helios_group_id IS NOT NULL
ORDER BY name
SQL;

$all = $sqlQuery->query($sql);
$nb_col = count($all);
echo "nb col = $nb_col\n";

foreach ($all as $nb_line => $line) {
    echo "{$line['name']} - {$line['id']} - $nb_line/$nb_col\n";

    $sql2 = "SELECT min(decision_date) as _min,max(decision_date) as max FROM actes_transactions WHERE authority_id=? GROUP BY authority_id";
    $actes = $sqlQuery->queryOne($sql2, $line['id']);
    $all[$nb_line]['min'] = $actes['_min'];
    $all[$nb_line]['max'] = $actes['max'];

    $sql2 = "SELECT min(submission_date) as _min,max(submission_date) as max FROM helios_transactions WHERE authority_id=? GROUP BY authority_id";
    $actes = $sqlQuery->queryOne($sql2, $line['id']);
    $all[$nb_line]['min2'] = $actes['_min'];
    $all[$nb_line]['max2'] = $actes['max'];
    print_r($all[$nb_line]);
}


echo "*****\n";
echo "id,nom,siren,departement,groupe actes,groupe helios,min(actes),max(actes),min(helios),max(helios)\n";
$csvOutput = new CSVOutput();
$csvOutput->display($all);
