<?php

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$id = Helpers::getVarFromPost("id");
$url = Helpers::getVarFromPost("url");

if (isset($id) && is_numeric($id)) {
  $trans = new ActesTransaction($id);

  if (! $trans->init()) {
	Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  // Vérification des permissions
  if (! $trans->userCanEdit($me)) {
	Helpers :: returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  if (! isset($url) || empty($url)) {
	Helpers::returnAndExit(1, "Pas d'url spécifiée pour l'archivage.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  $trans->set("archive_url", $url);

  if (! $trans->save()) {
	$msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}

	Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/index.php");
  } else {
	$msg = "Définition de l'URL d'archivage pour la transaction n°" . $trans->getId() . ". Résultat ok.";
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $trans->getId());
  }
} else {
  Helpers::returnAndExit(1, "Pas d'identifiant de transaction spécifié ou type invalide.", WEBSITE_SSL . "/modules/actes/index.php");
}

