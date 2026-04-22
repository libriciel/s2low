<?php

declare(strict_types=1);

// Configuration
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

/** @var PesAllerRetriever $pesAllerRetriever */
$pesAllerRetriever = LegacyObjectsManager::getLegacyObjectInstancier()->get(PesAllerRetriever::class);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName('helios')) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get('name'))) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$transaction_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet('id');


$trans = new HeliosTransaction();

if (isset($transaction_id) && ! empty($transaction_id)) {
    $trans->setId($transaction_id);
    if ($trans->init()) {//obtine inregistrarea ce corespunde
        $owner = new User($trans->get('user_id')); //!!!!! din HeliosTransaction
        $owner->init();
    } else {
        $_SESSION['error'] = "Erreur d'initialisation de la transaction.";
        header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
        exit();
    }
} else {
    $_SESSION['error'] = "Pas d'identifiant de transaction spécifié";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
    exit();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, 'helios');

if (! $permission->canView($me, $owner)) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
    exit();
}



$myAuthority = new Authority($me->get('authority_id'));

$entity = new HeliosTransaction($transaction_id);
$filename = $entity->getFilenameForId($transaction_id);
$sha1 = $entity->getSha1ForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();

try {
    $filepath = $pesAllerRetriever->getPath($sha1);
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la récupération du fichier : ' . $e->getMessage();
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_transac_show.php?id=$transaction_id"));
    exit();
}

if (!$filepath) {
    $_SESSION['error'] = 'Erreur lors de la récupération du fichier ';
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_transac_show.php?id=$transaction_id"));
    exit();
}

\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser($filepath, $filename, 'text/xml');
