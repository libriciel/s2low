<?php

use S2lowLegacy\Controller\PostgreSQLController;
use S2lowLegacy\Lib\ObjectInstancier;

require_once(__DIR__ . "/../../init/init.php");
$postgreSQLController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->get(PostgreSQLController::class);

# Ce script à utiliser en développement est utiliser pour mettre à jour les fichiers de définition de la base de données

$postgreSQLController->saveDatabaseToFile();
