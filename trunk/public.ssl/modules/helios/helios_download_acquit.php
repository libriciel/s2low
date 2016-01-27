<?php

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$transaction_id = Helpers :: getVarFromGet("id");

if (! $transaction_id){
  $_SESSION["error"] = "Id non trouvé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

//tmp
//echo "Transaction:" . $transaction_id;

$myAuthority = new Authority($me->get("authority_id"));

$entity = new HeliosTransaction($transaction_id);
$filename = $entity->getAcquitFilenameForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();


if (!$entity->sendAcquit(trim($filename))) {
  $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $entity->getErrorMsg();
  //header("Location: " . WEBSITE_SSL);
  exit ();
}

