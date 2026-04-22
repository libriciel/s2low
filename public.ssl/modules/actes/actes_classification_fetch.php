<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->checkDroit($module->get("name"), 'CS')) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$myAuthority = new Authority($me->get("authority_id"));

$zeClassif = new ActesClassification();

if (! $zeClassif->initWithLastSuccessful($myAuthority->getId())) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur de récupération de la dernière classification.", WEBSITE_SSL);
}

if (! $zeClassif->pushXMLData()) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur lors de l'envoi de la classification.", WEBSITE_SSL);
}
