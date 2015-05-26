<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$actionHtml = "";


function return_error_api($error_message){
	$return_error = Helpers :: getVarFromGet("url_return");	
	$return_error = str_replace("%%ERROR%%", 1, $return_error);
	$return_error = str_replace("%%MESSAGE%%", $error_message, $return_error);
	header("Location:  $return_error");
	exit;
}


// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
	return_error_api("Erreur d'intialisation du module");
	
}

$me = new User();

if (!$me->authenticate()) {
	return_error_api("Échec de l'authentification");
}

if (!$module->isActive() || !$me->checkDroit($module->get("name"),'TT')) {
	return_error_api("Accès refusé");
}

$id = Helpers :: getVarFromGet("id");
if (empty($id) ){
	return_error_api("Pas d'identifiant de transaction spécifié");
}


$trans = new ActesTransaction();
$trans->setId($id);
if ( ! $trans->init()) {
	return_error_api("Erreur d'initialisation de la transaction.");
}

$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$owner = new User($envelope->get("user_id"));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser,"actes");

if ( ! $permission->canView($me,$owner)){
	return_error_api("Accès refusé");
}

$msg = "La transaction a été postée par l'agent télétransmetteur {$me->getPrettyName()}";
$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$info = $actesTransactionsSQL->getInfo($id);
if($info['last_status_id'] != 17){
	return_error_api("La transaction n'est pas dans le statut « En attente d'être posté»");
}

$actesTransactionsSQL->updateStatus($id,1,$msg);

Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$connexion->getId());	

$return_ok = Helpers :: getVarFromGet("url_return");
$return_ok = str_replace("%%ERROR%%", 0, $return_ok);
$return_ok = str_replace("%%MESSAGE%%", "", $return_ok);

header("Location:  $return_ok");
exit;

