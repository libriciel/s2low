<?php

use S2lowLegacy\Controller\PostgreSQLController;

require_once(__DIR__ . "/../../init/init.php");
$postgreSQLController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(PostgreSQLController::class);

# Ce script permet de mettre automatiquement la base à jour en fonction du fichier de définition de la base

$postgreSQLController->alterDatabase(function ($a) {
    echo "[do-dbupdate] $a\n";
});
