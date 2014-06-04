<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
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

if (!$module->isActive() || !$me->checkDroit($module->get("name"),'CS')) {
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

if ($trans->get('last_status_id') != 18){
	$_SESSION["error"] = "L'acte ne peut plus être signé à ce moment-là";
	header("Location:  ". WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=$id");	
} 


$nb_signature = Helpers::getVarFromPost("nb_signature");
if ($nb_signature == 0){
	$_SESSION["error"] = "Les signatures n'ont pas pu être récupérées";
	header("Location:  ". WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=$id");
}

$actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);

$signature = array();

for($i=1; $i<=$nb_signature;$i++){
	$signature[$i] = Helpers::getVarFromPost("signature_$i");
	$signature_id = Helpers::getVarFromPost("signature_id_$i");
	$actesIncludedFileSQL->setSignature($id,$signature_id,$signature[$i]);
}


$tmpFolder = new TmpFolder();
$tmpDir = $tmpFolder->create();

$envelope_id = $trans->get('envelope_id');
$actesEnveloppeSQL = new ActesEnvelopeSQL($sqlQuery);
$actes_envelope_info = $actesEnveloppeSQL->getInfo($envelope_id);
$archivePath = ACTES_FILES_UPLOAD_ROOT.'/'.$actes_envelope_info['file_path'];

$tgzExtractor = new TGZExtractor($tmpDir);
$tgzExtractor->extract($archivePath, false);

$actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);
$xml_file = $actesIncludedFileSQL->getXMLFilename($id);

$xml = simplexml_load_file($tmpDir."/".$xml_file);
$namespaces = $xml->getDocNamespaces();

$children = $xml->children($namespaces['actes']);
$children->Document->addChild("Signature",$signature[1],$namespaces['actes']);

/*foreach($children->Annexes->Annexe as $i => $annexe) {
	$annexe->addChild("Signature",$signature[$i+2],$namespaces['actes']);
}*/

$xml->asXML($tmpDir."/".$xml_file);

chdir($tmpDir);
$cmd = "tar cf - * | gzip -9 > $archivePath ";

$status = system($cmd, $ret);

$tmpFolder->delete($tmpDir);

header("Location:  ". WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=$id");
$trans->setNewStatus(1, "L'acte a été signé électroniquement");
$_SESSION["error"] = "La signature a été enregistrée";


