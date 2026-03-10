<?php

use S2lowLegacy\Lib\FrontController;

/** @var FrontController $frontController */
$frontController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);
$frontController->go("AdminSAE", "edit");
