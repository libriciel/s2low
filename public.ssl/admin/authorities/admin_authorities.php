<?php

$frontController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Lib\FrontController::class);
$frontController->go("Admin", "authorities");
