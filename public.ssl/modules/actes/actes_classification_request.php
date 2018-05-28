<?php 
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');
require_once(dirname(__FILE__)."/class/ActesClassificationCreation.class.php");

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive()|| ! $me->checkDroit($module->get("name"),'TT')) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
  Helpers::returnAndExit(1, "Mode « papier » actif. Accès interdit.", WEBSITE_SSL . "/modules/actes/");
}

$myAuthority = new Authority($me->get("authority_id"));


$classificationCreation = new ActesClassificationCreation();

if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY){
	$classificationCreation->unsetFrequencyRestriction();
}

$result = $classificationCreation->createEnveloppe($myAuthority,$me);



Helpers::returnAndExit( ! $result, $classificationCreation->getLastMessage(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php",$classificationCreation->getLastTransactionId());
