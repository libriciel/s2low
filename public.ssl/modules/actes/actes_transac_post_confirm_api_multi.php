<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesScriptHelper;
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

/** @var \S2lowLegacy\Class\Initialisation $init */
/** @var WorkerScript $workerScript */
/** @var ActesScriptHelper $actesScriptHelper */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
list($init, $workerScript, $actesScriptHelper,$actesTransactionsSQL) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class, WorkerScript::class,ActesScriptHelper::class,ActesTransactionsSQL::class]
    );

$init->initActes();

$actionHtml = "";


function return_error_api($error_message)
{
    $return_error = Helpers :: getVarFromGet("url_return") ?: WEBSITE_SSL;
    header("Location:  $return_error");
    exit;
}

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    return_error_api("Erreur d'intialisation du module");
}

$connexion = new Connexion();
$me = new User();

if (!$me->authenticate()) {
    return_error_api("Échec de l'authentification");
}

if (!$module->isActive() || !$me->checkDroit($module->get("name"), 'TT')) {
    return_error_api("Accès refusé");
}


$rgsConnexion = new RgsConnexion();
if (! $rgsConnexion->isRgsConnexion()) {
    return_error_api("La télétransmission nécessite un certificat RGS<br/>Erreur : {$rgsConnexion->getLastMessage()}");
}

if (empty($_GET['id'])) {
    return_error_api("Pas d'identifiant de transaction spécifié");
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

    $envelope = new ActesEnvelope($trans->get("envelope_id"));
    $envelope->init();

    $owner = new User($envelope->get("user_id"));
    $owner->init();

    $serviceUser = new ServiceUser(DatabasePool::getInstance());
    $permission = new ModulePermission($serviceUser, "actes");

    if (! $permission->canView($me, $owner)) {
        continue;
    }

    $msg = "La transaction a été postée par l'agent télétransmetteur {$me->getPrettyName()}";

    $info = $actesTransactionsSQL->getInfo($id);
    if ($info['last_status_id'] != 17) {
        continue;
    }

    $actesTransactionsSQL->updateStatus($id, 1, $msg);

    $workerScript->putJobByClassName(ActesAntivirusWorker::class, $id);

    $msg4journal = $actesScriptHelper->getMessage($id, $msg);

    Log::newEntry(LOG_ISSUER_NAME, $msg4journal, 1, false, 'USER', "actes", false, $connexion->getId());
}

$return_ok = Helpers :: getVarFromGet("url_return");
header("Location:  $return_ok");
exit;
