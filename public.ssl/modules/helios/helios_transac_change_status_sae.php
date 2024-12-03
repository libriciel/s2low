<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\FrontController;

$frontController = LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);

$frontController->go('HeliosSAE', 'changeStatus');
