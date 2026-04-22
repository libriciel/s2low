<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Connexion;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

/** @var WorkerScript $workerScript */
/** @var ActesScriptHelper $actesScriptHelper */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
/** @var Initialisation $initialisation */
list(
    $workerScript,
    $actesScriptHelper,
    $actesTransactionsSQL,
    $initialisation
    ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [WorkerScript::class, ActesScriptHelper::class, ActesTransactionsSQL::class, Initialisation::class]
    );

$initData = $initialisation->doInit();
$initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$actionHtml = '';

// Instanciation du module courant
$module = new Module();
if (!$module->initByName('actes')) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$connexion = new Connexion();

$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}


if (!$module->isActive() || !$me->checkDroit($module->get('name'), 'TT')) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    $_SESSION['error'] = 'La télétransmission nécessite un certificat RGS' .
    "<br/>Erreur : {$rgsConnexion->getLastMessage()}";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
    exit_wrapper();
}

$id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('id');
if (empty($id)) {
    $_SESSION['error'] = "Pas d'identifiant de transaction spécifié";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
    exit();
}


$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    $_SESSION['error'] = "Erreur d'initialisation de la transaction.";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
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
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
    exit();
}

$msg = "La transaction a été postée par l'agent télétransmetteur {$me->getPrettyName()}";


$info = $actesTransactionsSQL->getInfo($id);
if ($info['last_status_id'] != ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE) {
    $_SESSION['error'] = "La transaction n'est pas dans le statut « En attente d'être posté»";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
    exit();
}

$actesTransactionsSQL->updateStatus($id, ActesStatusSQL::STATUS_POSTE, $msg);

$workerScript->putJobByClassName(ActesAntivirusWorker::class, $id);

$msg4journal = $actesScriptHelper->getMessage($id, $msg);

if (
    ! Log::newEntry(
        LOG_ISSUER_NAME,
        $msg4journal,
        1,
        false,
        'USER',
        "actes",
        false,
        $connexion->getId()
    )
) {
    $msg .= "\nErreur de journalisation.\n";
}
\S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, $msg, \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/actes_transac_show.php?id=') . $id);
