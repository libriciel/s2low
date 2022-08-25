<?php

use S2lowLegacy\Class\helios\PesAllerRetriever;

require_once(__DIR__ . "/../../init/init.php");
$pesAllerRetriever = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(PesAllerRetriever::class);

echo $pesAllerRetriever->getPath("76de99c53565cfd9af1f78478f90293ff0895cd2") . "\n";
