<?php

require_once(__DIR__ . "/../../init/init.php");

# Ce script permet de mettre remplir automatiquemnet la base de données avec les département, états, module de base, ...
# Ce script peut être rejoué plusieurs fois


/** @var ObjectInstancier $objectInstancier */
$slowBootrap = $objectInstancier->get('S2lowBootstrap');

$slowBootrap->populateDatabase();
