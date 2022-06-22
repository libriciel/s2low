<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isAdmin()) {
    $jsonOutput->displayErrorAndExit("Accès refusé");
}

$jsonOutput->display($me->get("roleTypes"));
