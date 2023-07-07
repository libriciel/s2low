<?php

use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$transId = (int) Helpers::getVarFromGet("transaction");

$transaction['id'] = $transId;
$transaction['resultat'] = "KO";
$transaction['message']  = "";
$transaction['status']  = "";

$heliosAPIResponse = new HeliosAPIResponse();

if (! $transId) {
    $transaction['message']  = "Pas de numéro de transaction.";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}


$module = new Module();
if (! $module->initByName("helios")) {
    $transaction['message']  = "Erreur d'initialisation du module";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}

$me = new User();
if (! $me->authenticate()) {
    $transaction['message']  = "Échec de l'authentification";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}


if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
    $transaction['message']  = "Accès refusé";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}

$zeTrans = new HeliosTransaction();
$zeTrans->setId($transId);

if (! $zeTrans->init()) {
    $transaction['message']  = "Numéro de transaction invalide";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}

$owner = new User($zeTrans->get("user_id"));
$owner->init();
if (
    ! ($me->get("authority_id") == $owner->get("authority_id")) &&
            $me->canAccess($module->get("name"))
) {
    $transaction['message']  = "Accès refusé";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}

$transaction['status'] = $zeTrans->getCurrentStatus();

if (! $transaction['status']) {
    $transaction['message']  = "Erreur consultation statut";
    $heliosAPIResponse->displayAndExit($transaction, "transaction");
}

$transaction['resultat'] = "OK";
$status_averifier = array("4","6","8");

$cloudStorage = LegacyObjectsManager::getLegacyObjectInstancier()
    ->get(CloudStorageFactory::class)->getInstanceByClassName(PESAcquitCloudStorage::class);

if (in_array($transaction['status'], $status_averifier)) {  #TODO : on garde ce comportement pour respecter le legacy
    if (!$cloudStorage->getPath($transaction['id'])) {      # Mais l'état 3 n'est pas logique en cas d'absence du PES
        $transaction['status'] = '3';                       # Acquit ...
    }
}

if (! $transaction['message']) {
    $transaction['message'] = HeliosTransactionWorkflow::getCurrentStatus($transId);
}

$heliosAPIResponse->displayAndExit($transaction, "transaction");
