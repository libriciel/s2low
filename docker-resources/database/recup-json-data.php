<?php

require_once __DIR__ . "/../../init/init.php";
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


$table_to_save = array(
    'authority_types',
    'modules',
    'actes_natures',
    'actes_status',
    'authority_departments',
    'authority_districts',
    'helios_status',
    'actes_messages_status',
);

$result = array();
foreach ($table_to_save as $table) {
    $result[$table] = $sqlQuery->query("SELECT * FROM $table");
}

$data =  json_encode($result, JSON_PRETTY_PRINT);


file_put_contents(__DIR__ . "/database_populate.json", $data);
