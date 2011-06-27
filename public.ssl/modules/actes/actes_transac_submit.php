<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
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

if ($me->isSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
  Helpers::returnAndExit(1, "Mode « papier » actif. Accès interdit.", WEBSITE_SSL . "/modules/actes/");
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du POST
$enveloppe = $_FILES["enveloppe"];

if (! is_array($enveloppe) || count($enveloppe) <= 0) {
  Helpers::returnAndExit(1, "Pas de fichier archive spécifié.", WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

if (! is_uploaded_file($enveloppe["tmp_name"])) {
  Helpers::returnAndExit(1, "Envoi de fichier incorrect.", WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

$env = new ActesEnvelope();

// Initialisation de l'enveloppe
$env->set("user_id", $me->getId());
$env->set("siren", $myAuthority->get("siren"));
$env->set("department", $myAuthority->get("department"));
$env->set("district", $myAuthority->get("district"));
$env->set("authority_type_code", $myAuthority->get("authority_type_id"));
$env->set("file_path", "");

// Destination de création des fichiers
$dest = $env->get("siren") . "/import/";
$env->set("destDir", $dest);

// Import XML enveloppe et obtention du nom des fichiers XML de description des messages contenus dans l'enveloppe
if (($xmlTransFiles = $env->importArchiveFile($enveloppe["name"], $enveloppe["tmp_name"])) === false) {
  $env->purgeFiles();
  $env->deleteArchiveFile();
  Helpers::returnAndExit(1, "Erreur d'importation de l'enveloppe :\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

// Création des transactions d'après les fichiers XML contenus dans l'enveloppe
$classifRequests = array();
$transacs = array();

foreach ($xmlTransFiles as $xmlFile) {
  $trans = new ActesTransaction();
  $trans->set("destDir", $dest);
  $trans->set("envelope_id", $env->getId());

  if (! $trans->createFromXML($xmlFile)) {
	$env->purgeFiles();
	$env->deleteArchiveFile();
	Helpers::returnAndExit(1, "Erreur d'importation transaction : " . $trans->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
  }

  $env->addTransaction($trans);

  if ($trans->get("type") == 1) {
	// Vérification qu'une transaction ayant le même numéro interne n'existe pas déjà
	if (! $trans->isUnique($myAuthority->getId())){
	  $env->purgeFiles();
	  $env->deleteArchiveFile();
	  Helpers::returnAndExit(1, "Un numéro interne d'acte entre en conflit avec un acte existant dans la base de données.", WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
	}

	// Vérification des signatures éventuelles des fichiers
	if (! $trans->checkSign()) {
	  $env->purgeFiles();
	  $env->deleteArchiveFile();
	  Helpers::returnAndExit(1, "Erreur de vérification des signatures des fichiers : " . $trans->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
	}
  }

  // En cas de demande de classification, création de la requête dans la table idoine
  if ($trans->get("type") == 7) {
	$classifRequest = new ActesClassification();

	$classifRequest->set("requested_by", $me->getId());
	$now = date('Y-m-d H:i:s');
	$classifRequest->set("request_date", $now);

	$classifRequests[] = $classifRequest;
  }

  $transacs[] = $trans;
}


// Création de l'archive .tar.gz
if (! $env->generateArchiveFile()) {
  $env->purgeFiles();
  $env->deleteArchiveFile();
  Helpers::returnAndExit(1, "Erreur lors de la regénération de l'archive.\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

// Vérification taille après regénération
// Le scan anti-virus a déjà été fait lors de l'import (gruik !!)
if (! $env->checkArchiveSize()) {
  $env->purgeFiles();
  $env->deleteArchiveFile();
  Helpers::returnAndExit(1, $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

// Purge des fichiers intermédiaires
$env->purgeFiles();

// Contrôle de l'archive par le module transactionnel
if (! $env->externalArchiveCheck()) {
  $env->deleteArchiveFile();
  Helpers::returnAndExit(1, "L'archive n'est pas conforme :\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

// Enregistrement de l'enveloppe
if (! $env->save()) {
  $env->deleteArchiveFile();
  $msg = "Erreur lors de l'enregistrement de l'enveloppe :\n" . $env->getErrorMsg();

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
}

// Enregistrement des transactions
foreach ($transacs as $trans) {
  $trans->set("envelope_id", $env->getId());
  $trans->set("broadcasted", 'FALSE');
  $trans->set("user_id",$me->getId());
	$trans->set("authority_id",$me->get("authority_id"));
	
  if (! $trans->save()) {
	$msg = "Erreur lors de l'enregistrement de la transaction.\n" . $trans->getErrorMsg();
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}

	foreach ($transacs as $trans) {
	  $trans->delete();
	}
	$env->deleteArchiveFile();
	$env->delete();

	Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
  }
}

// Enregistrement des demandes de classification
if (count($classifRequests) > 0) {
  foreach ($classifRequests as $classifRequest) {
	if (! $classifRequest->save()) {
	  $msg = "Erreur lors de l'enregistrement de la requête de classification.\n" . $classifRequest->getErrorMsg();
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.";
	  }

	  foreach ($transacs as $trans) {
		$trans->delete();
	  }
	  $env->deleteArchiveFile();
	  $env->delete();

	  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_import.php");
	}
  }
}

$msg = "Importation fichier archive réussie. Enveloppe n°" . $env->getId() . " contenant " . count($transacs);
$msg .= (count($transacs) > 1) ? " transactions créée." : " transaction créée.";

if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
  $msg .= "\nErreur de journalisation.";
}

// Message réservé à l'appel via API
// Nombre total de transaction
$apiMsg = count($transacs) . "\n";
// Id de chaque transaction
foreach ($transacs as $trans) {
  $apiMsg .= $trans->getId() . "\n";
}

Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/index.php", $apiMsg);
