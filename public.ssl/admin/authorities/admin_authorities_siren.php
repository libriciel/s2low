<?php
require_once( __DIR__ . "/../../../init/init.php");

$me = new User();

if (! $me->authenticate()) {
	$jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
	$jsonOutput->displayErrorAndExit("Accès refusé");
}

if($me->isSuper()){
	$authority_group_id =  Helpers::getVarFromGet("authority_group_id");
} else {
	$authority_group_id = $me->get("authority_group_id");
}

$group = new Group($authority_group_id);

$sirenList = $group->getAuthorizedSiren();

$jsonOutput->display($sirenList);