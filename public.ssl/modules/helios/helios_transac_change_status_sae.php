<?php

use S2lowLegacy\Lib\FrontController;

$frontController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);

$frontController->go("HeliosSAE", "changeStatus");
