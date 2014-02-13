<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$actionHtml = "";

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
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

if (!$module->isActive() || !$me->checkDroit($module->get("name"),'TT')) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$id = Helpers :: getVarFromPost("id");
if (empty($id) ){
	$_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
	header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
	exit ();
}


$trans = new ActesTransaction();
$trans->setId($id);
if ( ! $trans->init()) {
    $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
    header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
    exit ();
}

$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$owner = new User($envelope->get("user_id"));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser,"actes");

if ( ! $permission->canView($me,$owner)){
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
	exit ();
}

$msg = "La transaction a été postée par l'agent télétransmetteur";
$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$actesTransactionsSQL->updateStatus($id,1,$msg);


Helpers :: returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $id);
