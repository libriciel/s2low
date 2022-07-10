<?php

require_once("../../../init/init.php");
$jsonOutput = LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isAdmin()) {
    $jsonOutput->displayErrorAndExit("Accès refusé");
}

$jsonOutput->display($me->get("roleTypes"));
