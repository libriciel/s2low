<?php

use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

if ($argc > 1 && $argv[1] == 'do') {
    $do = true;
} else {
    $do = false;
}

$code_departement = '057';

$sql = "SELECT id FROM authority_departments WHERE code=?";
$authority_department_id = $sqlQuery->queryOne($sql, $code_departement);

echo "Code : 057, #ID : $authority_department_id\n";

$authorities_migrate_district = [
    1 => 3,
    2 => 5,
    4 => 9,
    8 => 7
];

foreach ($authorities_migrate_district as $from => $to) {
    echo "Migration des collectivités de l'arrondissement $from vers $to\n";
    $sql = 'SELECT name,id FROM authorities WHERE department=? AND district=?';
    print_r($sqlQuery->query($sql, $code_departement, $from));
    if ($do) {
        $sql = 'UPDATE authorities SET district=? WHERE department=? AND district=?';
        $sqlQuery->queryOne($sql, $to, $code_departement, $from);
    }
    echo "Suppression de l'arrondissement $from \n";
    $sql = "SELECT name,id FROM authority_districts WHERE authority_department_id=? AND code=?";
    print_r($sqlQuery->query($sql, $authority_department_id, $from));
    if ($do) {
        $sql = "DELETE FROM authority_districts WHERE authority_department_id=? AND code=?";
        $sqlQuery->queryOne($sql, $authority_department_id, $from);
    }
}

$district_new_name = [
    3 => 'Forbach-Boulay-Moselle',
    5 => 'Sarrebourg-Château-Salins',
    6 => 'Sarreguemines',
    7 => 'Thionville',
    9 => 'Metz'
];

foreach ($district_new_name as $district_id => $new_name) {
    echo "Renommage de $district_id vers $new_name\n";

    $sql = "SELECT * FROM authority_districts  WHERE authority_department_id=? AND code=?";
    print_r($sqlQuery->query($sql, $authority_department_id, $district_id));

    if ($do) {
        $sql = "UPDATE authority_districts SET name=? WHERE authority_department_id=? AND code=?";
        $sqlQuery->queryOne($sql, $new_name, $authority_department_id, $district_id);
    }
}
