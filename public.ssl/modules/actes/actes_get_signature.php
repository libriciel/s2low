<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$fileId = Helpers::getVarFromGet("id");

$myAuthority = new Authority($me->get("authority_id"));


$mode = "file";

$zeFile = new ActesIncludedFile($fileId);

$env = $zeFile->get("envelope");


$ownerId = $env->get("user_id");

$owner = new User($ownerId);
$owner->init();


$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "actes");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit();
}
header("Content-type: text/plain");

echo $zeFile->get('signature');
