<?php

require_once(__DIR__ . "/../../init/init.php");

# Ce script à utiliser en développement est utiliser pour mettre à jour les fichiers de définition de la base de données

/** @var ObjectInstancier $objectInstancier */
/** @var PostgreSQLController $postgreSQLController */
$postgreSQLController = $objectInstancier->get('PostgreSQLController');

$postgreSQLController->saveDatabaseToFile();
