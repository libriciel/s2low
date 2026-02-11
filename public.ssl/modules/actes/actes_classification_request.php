<?php

// Instanciation du module courant
use S2lowLegacy\Class\actes\ActesClassificationCreation;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->checkDroit($module->get("name"), 'TT')) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$myAuthority = new Authority($me->get("authority_id"));


$classificationCreation = new ActesClassificationCreation();

if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY) {
    $classificationCreation->unsetFrequencyRestriction();
}

$result = $classificationCreation->createEnveloppe($myAuthority, $me);


$transaction_id = $classificationCreation->getLastTransactionId();
Helpers::returnAndExit(! $result, $classificationCreation->getLastMessage(), Helpers::getLink("/modules/actes/actes_transac_show.php?id=$transaction_id"), $transaction_id);
