<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

$jsonOutput = LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
    $jsonOutput->displayErrorAndExit('Accès refusé');
}

$jsonOutput->display(Authority::getAuthorityTypesIdName());
