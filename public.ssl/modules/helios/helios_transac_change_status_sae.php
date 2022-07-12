<?php

require_once(__DIR__ . "/../../../init/init.php");
$frontController = LegacyObjectsManager::setLegacyObjectInstancier();

$frontController->go("HeliosSAE", "changeStatus");
