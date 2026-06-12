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
/** @var \S2lowLegacy\Class\Initialisation $initialisation */
list(
    $workerScript,
    $actesScriptHelper,
    $actesTransactionsSQL,
    $initialisation
    ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [WorkerScript::class,ActesScriptHelper::class,ActesTransactionsSQL::class, Initialisation::class]
    );


$actionHtml = "";


function return_error_api_api_multi($error_message)
{
    $return_error = Helpers :: getVarFromGet('url_return') ?: WEBSITE_SSL;
    header_wrapper("Location:  $return_error");
    exit_wrapper();
}

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName('actes');
if (!$module) {
    return_error_api_api_multi("Erreur d'intialisation du module");
}

$connexion = new Connexion();
$me = new User();

if (!$me->authenticate()) {
    return_error_api_api_multi("Échec de l'authentification");
}

if (!$module->isActive() || !$me->checkDroit($module->get('name'), 'TT')) {
    return_error_api_api_multi('Accès refusé');
}


$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    return_error_api_api_multi(
        "La télétransmission nécessite un certificat RGS<br/>Erreur : {$rgsConnexion->getLastMessage()}"
    );
}

if (empty($_GET['id'])) {
    return_error_api_api_multi("Pas d'identifiant de transaction spécifié");
}

if (is_array($_GET['id'])) {
    $id_list = $_GET['id'];
} else {
    $id_list = array($_GET['id']);
}

foreach ($id_list as $id) {
    $trans = new ActesTransaction();
    $trans->setId($id);
    if (! $trans->init()) {
        continue;
    }

    $envelope = new ActesEnvelope($trans->get('envelope_id'));
    $envelope->init();

    $owner = new User($envelope->get('user_id'));
    $owner->init();

    $serviceUser = new ServiceUser(DatabasePool::getInstance());
    $permission = new ModulePermission($serviceUser, 'actes');

    if (! $permission->canView($me, $owner)) {
        continue;
    }

    $msg = "La transaction a été postée par l'agent télétransmetteur {$me->getPrettyName()}";

    $info = $actesTransactionsSQL->getInfo($id);
    if ($info['last_status_id'] != 17) {
        continue;
    }

    $actesTransactionsSQL->updateStatus($id, ActesStatusSQL::STATUS_POSTE, $msg);

    $workerScript->putJobByClassName(ActesAntivirusWorker::class, $id);

    $msg4journal = $actesScriptHelper->getMessage($id, $msg);

    Log::newEntry(LOG_ISSUER_NAME, $msg4journal, 1, false, 'USER', "actes", false, $connexion->getId());
}

$return_ok = Helpers :: getVarFromGet('url_return');
header_wrapper("Location:  $return_ok");
exit_wrapper();
