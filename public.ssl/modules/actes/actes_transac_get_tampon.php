<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  echo "KO\nErreur d'initialisation du module";
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  echo "KO\nÉchec de l'authentification";
  exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
  echo "KO\nAccès refusé";
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du GET
$transId = Helpers::getVarFromGet("transaction");
$transUniqueId = Helpers::getVarFromGet("unique_id");

if(isset($transUniqueId) && ! empty($transUniqueId)){
	$transId = ActesTransaction::getTransactionFromUniqueId($transUniqueId);	
}


if (isset($transId) && ! empty($transId)) {
	$zeTrans = new ActesTransaction();
	$zeTrans->setId($transId);
} else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
}

if ($zeTrans->init()) {
	$owner = new User($zeTrans->get("user_id"));
	$owner->init();
} else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
  }

$zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
if (! $zeEnv->init()) {
  echo "KO\nEnveloppe invalide.";
  exit();
}

// Vérification des permissions
if (! $me->isSuper()) {
  if (! ($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $zeEnv->get("user_id") && $me->canAccess($module->get("name")))) {
	echo "KO\nAccès refusé";
	exit();
  }
}

$workflow = $zeTrans->fetchWorkflow();


$has_file = false;
foreach ($workflow as $stage) {
	if ($stage['status_id'] == 4){
		$has_file = true;
	}
}
if(! $has_file){
	echo "KO\nPas d'aquittement recu";
	exit();
}


$files = $zeTrans->fetchFilesList();
foreach ($files as $file) {
	if (preg_match("/pdf$/i",$file["posted_filename"])) {
		header("Location:  actes_download_file.php?file={$file['id']}&tampon=true");
		exit;
	}
}
