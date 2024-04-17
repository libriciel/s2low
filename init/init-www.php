<?php

use S2lowLegacy\Class\Connexion;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

list($objectInstancier, $html, $jsonOutput,$sqlQuery, $frontController) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, 'html', JSONoutput::class, SQLQuery::class, FrontController::class]
    );

if (empty($droit_specific)) {
    $droit_specific = array();
}

$connexion = new Connexion();
if (!$connexion->isConnected()) {
    $me = new User();

    if (!$me->authenticate()) {
        $_SESSION["error"] = "Échec de l'authentification";
        header("Location: " . Helpers::getLink("connexion-status"));
        exit();
    }
}

$userSQL = new UserSQL($sqlQuery);
$userInfo = $userSQL->getInfo($connexion->getId());

$authoritySQL = new AuthoritySQL($sqlQuery);
$authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);


$groupeInfo = false;
if ($authorityInfo['authority_group_id']) {
    $groupSQL = new GroupSQL($sqlQuery);
    $groupeInfo = $groupSQL->getInfo($authorityInfo['authority_group_id']);
}

$moduleSQL = new ModuleSQL($sqlQuery);

$droit = $objectInstancier->get(Droit::class);

if (! empty($module_name)) {
    $moduleInfo = $moduleSQL->getInfoByName($module_name, $userInfo['authority_id']);
    $droitModuleInfo = $moduleSQL->getInfoModuleAuthority($moduleInfo['id'], $userInfo['authority_id']);
    $permUser = $moduleSQL->getInfoPerms($moduleInfo['id'], $connexion->getId());

    if (! $droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific)) {
        $objectInstancier->get('S2lowRedirect')->redirect("/", "Accès refusé");
    }
}

$modulesInfo = $moduleSQL->getModulesForUser($userInfo);
