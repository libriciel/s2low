<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
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

if (! $module->isActive() || ! $me->canAccess($module->get('name'))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$envId = Helpers::getVarFromGet("env");
$fileId = Helpers::getVarFromGet("file");
$type = Helpers::getVarFromGet("type");
$tampon = Helpers::getVarFromGet("tampon");
$date_affichage = Helpers::getVarFromGet("date_affichage");

$myAuthority = new Authority($me->get("authority_id"));

$mode = "env";
$zeFile = null;
$env = null;

if (isset($fileId) && is_numeric($fileId)) {
    $mode = "file";

    if ($type == "batch") {
        $zeFile = new ActesBatchFile($fileId);
    } else {
        $zeFile = new ActesIncludedFile($fileId);

        $env = $zeFile->get("envelope");

        if ($tampon) {
            $zeFile->setTampon($date_affichage);
        }
    }
} elseif (isset($envId) && is_numeric($envId)) {
    $env = new ActesEnvelope($envId);
    $env->init();
} else {
    $_SESSION["error"] = "Paramètre manquant.";
    header("Location: " . WEBSITE_SSL);
    exit();
}

if ($type == "batch") {
    $zeBatch = new ActesBatch($zeFile->get("batch_id"));
    $zeBatch->init();
    $ownerId = $zeBatch->get("user_id");
} else {
    $ownerId = $env->get("user_id");
}

$owner = new User($ownerId);
$owner->init();


$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "actes");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit();
}

if ($mode == "file") {
    $entity = $zeFile;
} else {
    $entity = $env;
}

if (! $entity->sendfile()) {
    $_SESSION["error"] = "Erreur d'envoi du fichier : " . $entity->getErrorMsg();
    header("Location: " . WEBSITE_SSL);
    exit();
}
