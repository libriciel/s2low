<?php

// Configuration
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

$cloudStorageFactory = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(CloudStorageFactory::class);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

try {
    $transaction_id = Helpers :: getIntFromGet("id", true);
} catch (Exception $e) {
    $_SESSION["error"] = $e->getMessage();
    header("Location: " . WEBSITE_SSL);
    exit();
}


if (! $transaction_id) {
    $_SESSION["error"] = "Id non trouvé";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$trans = new HeliosTransaction();

if (isset($transaction_id) && ! empty($transaction_id)) {
    $trans->setId($transaction_id);
    if ($trans->init()) {//obtine inregistrarea ce corespunde
        $owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
        $owner->init();
    } else {
        $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
        header("Location: " . Helpers::getLink("/modules/helios/index.php"));
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "helios");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}




$myAuthority = new Authority($me->get("authority_id"));

$entity = new HeliosTransaction($transaction_id);
$filename = $entity->getAcquitFilenameForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();

try {
    $pesAcquitCloudStorage = $cloudStorageFactory->getInstanceByClassName(PESAcquitCloudStorage::class);
    $path = $pesAcquitCloudStorage->getPath($transaction_id);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $e->getMessage();
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}


if (!$entity->sendAcquit(trim($filename))) {
    $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $entity->getErrorMsg();
  //header("Location: " . WEBSITE_SSL);
    exit();
}
