<?php
require_once( __DIR__."/../../init/init.php");

# NE PAS UTILISER EN PRODUCTION !
if (true) exit;

# Ce script permet de mettre automatiquement la base à jour en fonction du fichier de définition de la base


/** @var ObjectInstancier $objectInstancier */
/** @var PostgreSQLController $postgreSQLController */
$postgreSQLController = $objectInstancier->get('PostgreSQLController');

$postgreSQLController->alterDatabase(function($a){echo "[do-dbupdate] $a\n";});
