<?php

// Configuration
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

$pesAcquitCloudStorage = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(PESAcquitCloudStorage::class);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header_wrapper("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit_wrapper();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}

try {
    $transaction_id = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromGet("id", true);
} catch (Exception $e) {
    $_SESSION["error"] = $e->getMessage();
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}


if (! $transaction_id) {
    $_SESSION["error"] = "Id non trouvé";
    header_wrapper("Location: " . WEBSITE_SSL);
    exit_wrapper();
}


$trans = new HeliosTransaction();

if (isset($transaction_id) && ! empty($transaction_id)) {
    $trans->setId($transaction_id);
    if ($trans->init()) {//obtine inregistrarea ce corespunde
        $owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
        $owner->init();
    } else {
        $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
        header_wrapper("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php"));
        exit_wrapper();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header_wrapper("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php"));
    exit_wrapper();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "helios");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header_wrapper("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php"));
    exit_wrapper();
}




$myAuthority = new Authority($me->get("authority_id"));

$entity = new HeliosTransaction($transaction_id);
$filename = $entity->getAcquitFilenameForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();

try {
    $path = $pesAcquitCloudStorage->getPath($transaction_id);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $e->getMessage();
    header_wrapper("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php"));
    exit_wrapper();
}

if (!$entity->sendAcquit(trim($filename))) {
    $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $entity->getErrorMsg();
    exit_wrapper();
}
