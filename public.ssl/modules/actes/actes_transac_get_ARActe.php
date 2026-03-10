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
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var ActesTransactionsSQL $actesTransactionSQL */
/** @var UserContext $userContext  */

[$initialisation, $actesTransactionSQL, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, ActesTransactionsSQL::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$recuperateur = new Recuperateur($_GET);

$id = $recuperateur->getInt('id');


$trans = new ActesTransaction();
$trans->setId($id);
if (!$trans->init()) {
    Helpers::returnAndExit(
        1,
        "Erreur d'initialisation de la transaction.",
        Helpers::getLink('/modules/actes/index.php')
    );
}

$envelope = new ActesEnvelope($trans->get('envelope_id'));
$envelope->init();

$owner = new User($envelope->get('user_id'));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, 'actes');

if (!$permission->canView($userContext->me, $owner)) {
    Helpers::returnAndExit(1, 'Accès refusé', Helpers::getLink('/modules/actes/index.php'));
}

$info = $actesTransactionSQL->getStatusInfoWithFluxRetour($id, ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

if (!$info) {
    Helpers::returnAndExit(1, "Cette transaction n'existe pas", 'index.php');
}

header_wrapper('Content-type: application/xml');
echo $info['flux_retour'];
