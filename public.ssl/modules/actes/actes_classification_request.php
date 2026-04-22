<?php

// Instanciation du module courant
use S2lowLegacy\Class\actes\ActesClassificationCreation;
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

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->checkDroit($module->get("name"), 'TT')) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Mode « papier » actif. Accès interdit.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/"));
}

$myAuthority = new Authority($me->get("authority_id"));


$classificationCreation = new ActesClassificationCreation();

if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY) {
    $classificationCreation->unsetFrequencyRestriction();
}

$result = $classificationCreation->createEnveloppe($myAuthority, $me);


$transaction_id = $classificationCreation->getLastTransactionId();
\S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(! $result, $classificationCreation->getLastMessage(), \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=$transaction_id"), $transaction_id);
