<?php
require_once( __DIR__."/../../init/init.php");

# Ce script permet de mettre automatiquement la base à jour en fonction du fichier de définition de la base


/** @var ObjectInstancier $objectInstancier */
/** @var PostgreSQLController $postgreSQLController */
$postgreSQLController = $objectInstancier->get('PostgreSQLController');

$postgreSQLController->alterDatabase(function($a){echo "[do-dbupdate] $a\n";});
