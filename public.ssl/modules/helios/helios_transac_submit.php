<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

$module = new Module();
if (! $module->initByName("helios")) {
	$_SESSION["error"] = "Erreur d'initialisation du module";
	header("Location: " . WEBSITE_SSL);
	exit();
}

$me = new User();

if (! $me->authenticate()) {
	$_SESSION["error"] = "Éhec de l'authentification";
	header("Location: " . WEBSITE);
	exit();
}

if (! $module->isActive()|| ! $me->checkDroit("actes", "TT")) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit();
}


$id = Helpers :: getVarFromPost("id");
if (empty($id) ){
	$_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit ();
}


$htw = new HeliosTransactionWorkflow();

$htw->set("transaction_id", $id);
$htw->set("status_id", 1);
$htw->set("message", "Fichier bien reçu par la plate-forme Helios");

$htw->set("date", date('Y-m-d H:i:s'));

if (!$htw->save(true)) {
	$_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
	if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$_SESSION["error"] .= "\nErreur de journalisation.";
	}
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	echo $_SESSION["error"];
	exit ();
}

$msg = "Préparation de la télétransmission Transation n°" . $id . ". Résultat ok.";
if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
}

Helpers :: returnAndExit(0,"Préparation de la télétransmission réusssie.", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id);