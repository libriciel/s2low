<?php

require_once(__DIR__ . "/../../../init/init.php");
$frontController = LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);

$frontController->go("ActesSAE", "changeStatus");
