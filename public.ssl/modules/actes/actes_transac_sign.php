<?php
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/class/VerifyPKCS7Signature.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

require_once( __DIR__ . "/../../../init/init-www-actes.php");

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

if (!$module->isActive() || ! $me->checkDroit($module->get("name"),'CS')) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit ();
}

$nb_signature = Helpers::getVarFromPost("nb_signature");
if ($nb_signature == 0){
	$_SESSION["error"] = "Les signatures n'ont pas pu être récupérées";
	header("Location:  ". WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=$id");
}

$actesSignature = new ActesSignature($sqlQuery);

$all_transaction_id = array();

try{
	for($i=1; $i<=$nb_signature;$i++){
		$signature = base64_decode(Helpers::getVarFromPost("signature_$i"));
		$signature_id = Helpers::getVarFromPost("signature_id_$i");
		$all_transaction_id[] = $actesSignature->setSignature($signature_id, $signature);

		/** Vérifier la signature ici */
		$verifyPKCS7Signature = new VerifyPKCS7SIgnature(AUTHORIZED_SIGN_CA_PATH);
		$verifyPKCS7Signature->verifyCertificate($signature);


	} 
} catch (Exception $e){
	$_SESSION["error"] = "Erreur lors de la signature : " . $e->getMessage();
	header("Location:  ". WEBSITE_SSL . "/modules/actes/index.php");
}

if (count($all_transaction_id) == 1){
	$_SESSION["error"] = "La signature a été enregistrée";
	header("Location:  ". WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id={$all_transaction_id[0]}");
} else {
	$_SESSION["error"] = "Les signatures ont été enregistrées";
	header("Location:  ". WEBSITE_SSL . "/modules/actes/index.php");
}
