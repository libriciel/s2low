<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var ActesTransactionsSQL $actesTransactionSQL */

[$initialisation,$actesTransactionSQL] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,ActesTransactionsSQL::class]);

$initData = $initialisation->doInit(Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$recuperateur = new Recuperateur($_GET);

$id = $recuperateur->getInt('id');


$module = new Module();
if (!$module->initByName('actes')) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . Helpers::getLink('connexion-status'));
    exit();
}

// TODO : vérifier si ce n'est pas redondant avec ce qui se passe dans doInit
if (!$module->isActive() || !$me->canAccess($module->get('name'))) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    $_SESSION['error'] = "Erreur d'initialisation de la transaction.";
    header('Location: ' . Helpers::getLink('/modules/actes/index.php'));
    exit();
}

$envelope = new ActesEnvelope($trans->get('envelope_id'));
$envelope->init();

$owner = new User($envelope->get('user_id'));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, 'actes');

if (! $permission->canView($me, $owner)) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . Helpers::getLink('/modules/actes/index.php'));
    exit();
}

$info = $actesTransactionSQL->getStatusInfoWithFluxRetour($id, ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

if (! $info) {
    $_SESSION['error'] = "Cette transaction n'existe pas";
    header('Location: index.php');
}


header('Content-type: application/xml');
echo $info['flux_retour'];
