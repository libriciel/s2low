<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName('actes')) {
    echo "KO\nErreur d'initialisation du module";
    exit_wrapper();
}
$me = new User();

if (! $me->authenticate()) {
    echo "KO\nÉchec de l'authentification";
    exit_wrapper();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canAccess($module->get('name'))) {
    echo "KO\nAccès refusé";
    exit_wrapper();
}

$myAuthority = new Authority($me->get('authority_id'));

// Recuperation des variables du GET
$transId = intval(\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet('transaction'));
$transUniqueId = intval(\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet('unique_id'));

if (! empty($transUniqueId)) {
    $transId = ActesTransaction::getTransactionFromUniqueId($transUniqueId);
}


if (! empty($transId)) {
    $zeTrans = new ActesTransaction();
    $zeTrans->setId($transId);
} else {
    echo "KO\nNuméro de transaction invalide.";
    exit_wrapper();
}

if ($zeTrans->init()) {
    $owner = new User($zeTrans->get('user_id'));
    $owner->init();
} else {
    echo "KO\nNuméro de transaction invalide.";
    exit_wrapper();
}

$zeEnv = new ActesEnvelope($zeTrans->get('envelope_id'));
if (! $zeEnv->init()) {
    echo "KO\nEnveloppe invalide.";
    exit_wrapper();
}

// Vérification des permissions spécifiques à la transaction
if (! $me->isSuper()) {
    if (
        ! ($me->getId() == $zeEnv->get('user_id'))
        &&
        ! ($me->isAuthorityAdminFor($owner->get('authority_id')))
        &&
        ! ($me->isArchivistFor($owner->get('authority_id')))
    ) {
        echo "KO\nAccès refusé";
        exit_wrapper();
    }
}

$workflow = $zeTrans->fetchWorkflow();


$has_file = false;
foreach ($workflow as $stage) {
    if (! in_array($stage['status_id'], [1,2,3])) {
        $has_file = true;
    }
}
if (! $has_file) {
    echo "KO\nPas d'acquittement recu";
    exit_wrapper();
}


$files = $zeTrans->fetchFilesList();

echo json_encode($files);
