<?php

$frontController = LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);

$frontController->go("HeliosSAE", "changeStatus");
