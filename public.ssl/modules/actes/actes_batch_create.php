<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if (!$module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$description = Helpers::getVarFromPost("intitule");
$num_prefix = Helpers::getVarFromPost("prefixe");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

if (count($_FILES) > 0) {
  if (! $zeBatch->importFilesFromForm($_FILES)) {
	Helpers::returnAndExit(1, $zeBatch->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
  } else {
	if (! $zeBatch->save()) {
	  $msg = "Erreur lors de l'enregistrement du lot : " . $zeBatch->getErrorMsg();
	  
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.";
	  }

	  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
	} else {
	  $msg = "Lot n°" . $zeBatch->getId() . " créé avec succès.";
	  
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.";
	  }

	  Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=" . $zeBatch->getId(), $zeBatch->getId());
	}
  }
} else {
	Helpers::returnAndExit(1, "Aucun fichier soumis.", WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
}


?>