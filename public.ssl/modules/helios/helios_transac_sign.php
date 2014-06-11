<?php

print_r($_POST);

exit;

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once( __DIR__ . "/../../../init/init-www-helios.php");
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

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

if (!$module->isActive() || !$me->checkDroit($module->get("name"),'CS')) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit ();
}

$id = Helpers :: getVarFromPost("id");
if (empty($id) ){
	$_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit ();
}



$trans = new HeliosTransaction();
$trans->setId($id);
if ( ! $trans->init()) {
	$_SESSION["error"] = "Erreur d'initialisation de la transaction.";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit ();
}

if ($trans->get('last_status_id') != 13){
	$_SESSION["error"] = "Le fichier PES ne peut plus être signé à ce moment-là (status : ".$trans->get('last_status_id').")";
	header("Location:  ". WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=$id");
}


$signature_id_1 = Helpers :: getVarFromPost("signature_id_1");
$signature_1 = Helpers :: getVarFromPost("signature_1");

$sha1 = $trans->get('sha1');

$file_path = HELIOS_FILES_UPLOAD_ROOT."/".$trans->get('sha1');

$heliosSignature = new HeliosSignature();

$new_pes_content = $heliosSignature->injectSignature($file_path, $signature_1);

$new_sha1 = sha1($new_pes_content);
$new_filesize = strlen($new_pes_content);


if ($new_filesize > HELIOS_MAX_UPLOAD_SIZE) {
	$_SESSION["error"] = "Taille de fichier supérieur à la limite autorisée (". (HELIOS_MAX_UPLOAD_SIZE/1024/1024)."Mo maximum).";
	header("Location: " . WEBSITE_SSL);
	exit ();
}


file_put_contents(HELIOS_FILES_UPLOAD_ROOT."/".$new_sha1, $new_pes_content);

$trans->set('sha1', $new_sha1);
$trans->set('filesize', $new_filesize);

if (! $trans->save(true)){
	$msg =  "Erreur de l'enregistrement de la signature.";
	
	if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$_SESSION["error"] .= "\nErreur de journalisation.";
	}
	$_SESSION["error"] = $msg;
	header("Location:  ". WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=$id");
	exit ();
}


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$heliosTransactionSQL->updateStatus($id, 1, "Fichier signée");

$_SESSION["error"] = "La signature a été enregistrée";
header("Location:  ". WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=$id");

