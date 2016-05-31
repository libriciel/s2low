<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');

header("Content-type: text/plain");

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$transId = Helpers::getVarFromGet("id");
$unique_id = Helpers::getVarFromGet("unique_id");

if ($unique_id){
	$transId = ActesTransaction::getTransactionFromUniqueId($unique_id);
}	

if (! $transId) {
	echo "KO\nNuméro de transaction invalide.";
	exit();	
}

$zeTrans = new ActesTransaction();
$zeTrans->setId($transId);

if ($zeTrans->init()) {
	$owner = new User($zeTrans->get("user_id"));
	$owner->init();
} else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
}

if ($zeTrans->get("type") == 1){
	$lesTransaction = $zeTrans->getCourrierInfo();
	foreach($lesTransaction as $id => $value){
		$t = new ActesTransaction();
		$t->setId($id);
		$status = $t->getCurrentStatus();
		echo $value['type']."-$status-$id\n";
	}	
} else {
	$envId = $zeTrans->get("envelope_id");
	header("Location: actes_download_file.php?env=$envId");	
}
