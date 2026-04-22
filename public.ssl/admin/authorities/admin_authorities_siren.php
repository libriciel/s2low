<?php

use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

$jsonOutput = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
    $jsonOutput->displayErrorAndExit("Accès refusé");
}

if ($me->isSuper()) {
    $authority_group_id =  \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("authority_group_id");
} else {
    $authority_group_id = $me->get("authority_group_id");
}

$group = new Group($authority_group_id);

$sirenList = $group->getAuthorizedSiren();

$jsonOutput->display($sirenList);
