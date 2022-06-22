<?php
require_once( __DIR__."/../../init/init.php");

# Ce script permet d'afficher les requêtes à passer pour que la base soit conforme au schéma attendu

/** @var ObjectInstancier $objectInstancier */
/** @var PostgreSQLController $postgreSQLController */
$postgreSQLController = $objectInstancier->get('PostgreSQLController');

$sql_command = $postgreSQLController->getAlterDatabaseCommand();


echo implode("\n", $sql_command);
if ($sql_command) {
	echo "\n";
}