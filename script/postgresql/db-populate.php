<?php

require_once(__DIR__ . "/../../init/init.php");
$slowBootrap = LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowBootstrap::class);

# Ce script permet de mettre remplir automatiquemnet la base de données avec les département, états, module de base, ...
# Ce script peut être rejoué plusieurs fois


$slowBootrap->populateDatabase();
