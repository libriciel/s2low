<?php

require_once("../../../init/init.php");

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
    $jsonOutput->displayErrorAndExit("Accès refusé");
}


$jsonOutput->display(Authority::getAuthorityTypesIdName());
