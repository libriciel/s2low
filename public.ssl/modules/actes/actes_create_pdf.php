<?php

use S2lowLegacy\Class\actes\BordereauPdfGenerator;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

$bordereauPdfGenerator = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(BordereauPdfGenerator::class);

$id = Helpers :: getVarFromGet("trans_id");

if (empty($id)) {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header_wrapper("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit_wrapper();
}

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header_wrapper("Location: " . Helpers::getLink("connexion-status"));
    exit_wrapper();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}


$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
    header_wrapper("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit_wrapper();
}

$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$owner = new User($envelope->get("user_id"));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "actes");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header_wrapper("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit_wrapper();
}

//passer les paramètre
$bordereauPdfGenerator->generate($id, "acquittement.pdf", false);
