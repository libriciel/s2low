<?php

require_once("init.php");

$connexion = new Connexion();
if (!$connexion->isConnected()){
	header("Location: " . WEBSITE_SSL."/login.php");
	exit;
}

$userSQL = new UserSQL($sqlQuery);
$userInfo = $userSQL->getInfo($connexion->getId());

$authoritySQL = new AuthoritySQL($sqlQuery);
$authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);


$groupeInfo = false;
if ($authorityInfo['authority_group_id']){
	$groupSQL = new GroupSQL($sqlQuery);
	$groupeInfo = $groupSQL->getInfo($authorityInfo['authority_group_id']);
}

$moduleSQL = new ModuleSQL($sqlQuery);
$moduleInfo = $moduleSQL->getInfoByName("actes",$userInfo['authority_id']);
$droitModuleInfo = $moduleSQL->getInfoModuleAuthority($moduleInfo['id'],$userInfo['authority_id']);
$permUser = $moduleSQL->getInfoPerms($moduleInfo['id'],$connexion->getId());


$droit = new Droit();
if (! $droit->canAccess($moduleInfo,$userInfo,$authorityInfo,$groupeInfo,$droitModuleInfo,$permUser)){
	sortir("Accès refusé");
}


$exit_if_not_group_or_super_admin = function() use ($droit,$userInfo){
	if (! $droit->isGroupOrSuperAdmin($userInfo)){
		sortir("Accès refusé");
	}
};


$modulesInfo = $moduleSQL->getModulesForUser($userInfo);







