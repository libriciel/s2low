<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Connexion;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

/** @var WorkerScript $workerScript */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
/** @var ActesScriptHelper $actesScriptHelper */
/** @var Connexion $connexion */
list($workerScript, $actesTransactionsSQL, $actesScriptHelper, $connexion ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [WorkerScript::class, ActesTransactionsSQL::class, ActesScriptHelper::class,Connexion::class]
    );

$actionHtml = '';


function return_error_api($error_message)
{
    $return_error = Helpers :: getVarFromGet('url_return');
    $return_error = str_replace('%%ERROR%%', 1, $return_error);
    $return_error = str_replace('%%MESSAGE%%', $error_message, $return_error);
    header_wrapper("Location:  $return_error");
    exit_wrapper();
}


// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName('actes');
if (!$module) {
    return_error_api("Erreur d'intialisation du module");
}

$me = new User();

if (!$me->authenticate()) {
    return_error_api("Échec de l'authentification");
}

if (!$module->isActive() || !$me->checkDroit($module->get('name'), 'TT')) {
    return_error_api('Accès refusé');
}

$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    return_error_api("La télétransmission nécessite un certificat RGS<br/>Erreur : {$rgsConnexion->getLastMessage()}");
}



$id = Helpers :: getVarFromGet('id');
if (empty($id)) {
    return_error_api("Pas d'identifiant de transaction spécifié");
}


$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    return_error_api("Erreur d'initialisation de la transaction.");
}

$envelope = new ActesEnvelope($trans->get('envelope_id'));
$envelope->init();

$owner = new User($envelope->get('user_id'));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, 'actes');

if (! $permission->canView($me, $owner)) {
    return_error_api('Accès refusé');
}

$msg = "La transaction a été postée par l'agent télétransmetteur {$me->getPrettyName()}";

$info = $actesTransactionsSQL->getInfo($id);
if ($info['last_status_id'] != ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE) {
    return_error_api("La transaction n'est pas dans le statut « En attente d'être posté»");
}

$actesTransactionsSQL->updateStatus($id, ActesStatusSQL::STATUS_POSTE, $msg);
$workerScript->putJobByClassName(ActesAntivirusWorker::class, $id);

$msg4journal = $actesScriptHelper->getMessage($id, $msg);

Log::newEntry(LOG_ISSUER_NAME, $msg4journal, 1, false, 'USER', 'actes', false, $connexion->getId());

$return_ok = Helpers :: getVarFromGet('url_return');
$return_ok = str_replace('%%ERROR%%', 0, $return_ok);
$return_ok = str_replace('%%MESSAGE%%', '', $return_ok);

header_wrapper("Location:  $return_ok");
exit_wrapper();
