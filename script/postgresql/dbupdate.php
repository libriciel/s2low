<?php

use S2lowLegacy\Controller\PostgreSQLController;

require_once(__DIR__ . "/../../init/init.php");
$postgreSQLController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(PostgreSQLController::class);

# Ce script permet d'afficher les requêtes à passer pour que la base soit conforme au schéma attendu

$sql_command = $postgreSQLController->getAlterDatabaseCommand();


echo implode("\n", $sql_command);
if ($sql_command) {
    echo "\n";
}
