<?php

use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

$jsonOutput = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isAnyAdmin()) {
    $jsonOutput->displayErrorAndExit("Accès refusé");
}

$jsonOutput->display(User::ROLES_DESCR);
