<?php

/*
 * L'ajout d'une colonne non-nulle avec une valeur par défaut verrouille toute la table, potentiellement plusieurs heures pour une très grosse table
 * on a ici un moyen d'ajouter une colonne sans aucun verrou
 */

use S2lowLegacy\Lib\SQLQuery;

require_once __DIR__ . "/../../init/init.php";
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

if ($argc < 5) {
    echo "{$argv[0]} - create a not null column with a defaut value in the database\n";
    echo "Usage : {$argv[0]} table_name column_name column_type default_value\n";
    exit(1);
}



$table_name = $argv[1];
$column_name = $argv[2];
$column_type = $argv[3];
$default_value = $argv[4];

echo "1) Create column $column_name:$column_type on table $table_name\n";
$sqlQuery->query("ALTER TABLE $table_name ADD COLUMN $column_name $column_type;");

echo "2) Set defaut value $default_value for column $column_name\n";
$sqlQuery->query("ALTER TABLE $table_name ALTER COLUMN $column_name SET DEFAULT $default_value;");

echo "3) Set value $default_value for column $column_name on all tuples\n";
$connection = $sqlQuery->getConnection();
$total = 0;
do {
    $row_count = $connection->executeStatement(
        "UPDATE $table_name SET $column_name=$default_value WHERE id in (
                    SELECT id FROM $table_name WHERE $column_name IS NULL LIMIT 100
                )"
    );
    $total += $row_count;
    echo "$total\n";
} while ($row_count != 0);

echo "4) Add not null constraint\n";
$sqlQuery->query("ALTER TABLE $table_name ALTER COLUMN $column_name SET NOT NULL");

echo "OK\n";

exit(0);
