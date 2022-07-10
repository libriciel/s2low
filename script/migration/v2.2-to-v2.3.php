<?php

require_once(__DIR__ . "/../../init/init.php");
$heliosController = LegacyObjectsManager::getLegacyObjectInstancier()->get(HeliosController::class);

$min_id = 0;

$heliosController->updateSiretFromPESAller($min_id);
