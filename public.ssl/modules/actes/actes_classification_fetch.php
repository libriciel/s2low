<?php

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive()|| ! $me->checkDroit($module->get("name"),'CS')) {
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
