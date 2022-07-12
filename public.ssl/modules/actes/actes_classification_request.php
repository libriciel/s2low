<?php

require_once("../../../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->checkDroit($module->get("name"), 'TT')) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
    Helpers::returnAndExit(1, "Mode « papier » actif. Accès interdit.", Helpers::getLink("/modules/actes/"));
}

$myAuthority = new Authority($me->get("authority_id"));


$classificationCreation = new ActesClassificationCreation();

if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY) {
    $classificationCreation->unsetFrequencyRestriction();
}

$result = $classificationCreation->createEnveloppe($myAuthority, $me);


$transaction_id = $classificationCreation->getLastTransactionId();
Helpers::returnAndExit(! $result, $classificationCreation->getLastMessage(), Helpers::getLink("/modules/actes/actes_transac_show.php?id=$transaction_id"), $transaction_id);
