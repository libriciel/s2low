<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$x509Certificate = new X509Certificate();

$me = new User();

if (! $me->authenticate()) {
    $jsonOutput->displayErrorAndExit("Échec de l'authentification");
}

if (! $me->isAdmin()) {
    $jsonOutput->displayErrorAndExit("Accés refusé");
}

$id = Helpers::getVarFromGet("id");

$myAuthority = new Authority($me->get("authority_id"));

$him = new User();
$him->setId($id);
$him->init();

if (! $me->isSuper()  && ! $me->canEditUser($id)) {
    $jsonOutput->displayErrorAndExit("Impossible de d'accéder à cet utilisateur. Accés refusé.");
}

foreach (array('name','givenname','login','email','telephone','status','authority_id','authority_group_id','role','certificate') as $key) {
    $result[$key] = $him->get($key);
}

$modules = Module::getActiveModulesList();
foreach ($modules as $module) {
    $result['module'][$module['id']] = $him->getPerm($module["name"]);
}
$result['other_id'] = $him->getIdFromCertData($him->get("certificate_hash"));


$jsonOutput->display($result);
