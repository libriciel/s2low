<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->checkDroit($module->get("name"), 'CS')) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$myAuthority = new Authority($me->get("authority_id"));

$zeClassif = new ActesClassification();

if (! $zeClassif->initWithLastSuccessful($myAuthority->getId())) {
    Helpers::returnAndExit(1, "Erreur de récupération de la dernière classification.", WEBSITE_SSL);
}

if (! $zeClassif->pushXMLData()) {
    Helpers::returnAndExit(1, "Erreur lors de l'envoi de la classification.", WEBSITE_SSL);
}
