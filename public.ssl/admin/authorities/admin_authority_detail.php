<?php
require_once( __DIR__ . "/../../../init/init.php");

$me = new User();

if (! $me->authenticate()) {
	$jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isAdmin()) {
	$jsonOutput->displayErrorAndExit("Accès refusé");
}

$id = Helpers::getVarFromGet("id");

$authority = new Authority();
$authority->setId($id);
$authority->init();
  	
// Vérification permission sur la collectivité
if (($me->isGroupAdmin() && ! $authority->isInGroup($me->get("authority_group_id"))) 
	|| ($me->isAuthorityAdmin() && $authority->getId() != $me->get("authority_id"))) {
	$jsonOutput->displayErrorAndExit("Accès refusé pour la consultation de cette collectivité");
}

$info_to_display = array("id","name","siren","authority_type_id","status","email","default_broadcast_email","broadcast_email",
							"address","postal_code","city","department","district","telephone","fax","email_mail_securise");
foreach(AuthoritySQL::getSAEProperties() as $id_properties => $properties){
	$info_to_display[] = $id_properties;
} 


if ($me->isGroupAdminOrSuper()){
	$info_to_display = array_merge($info_to_display,array("authority_group_id","helios_ftp_dest","ext_siret"));
}

$authoritySQL = new AuthoritySQL($sqlQuery);
$info = $authoritySQL->getInfo($id);

foreach($info_to_display as $i){
	$result[$i] = $info[$i];
}

if ($me->isGroupAdminOrSuper()){
	$modules = Module::getActiveModulesList();
	  foreach ($modules as $module) {
		$result['module'][$module["id"]] =  $authority->getModulePerm($module["id"]);
	  }
}


echo $jsonOutput->display($result);



