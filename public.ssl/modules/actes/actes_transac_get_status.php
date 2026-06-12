<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName('actes');
if (!$module) {
    echo "KO\nErreur d'initialisation du module";
    exit_wrapper();
}

$me = new User();

if (! $me->authenticate()) {
    echo "KO\nÉchec de l'authentification";
    exit_wrapper();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get('name'))) {
    echo "KO\nAccès refusé";
    exit_wrapper();
}

$myAuthority = new Authority($me->get('authority_id'));

// Recuperation des variables du GET
$transId = intval(Helpers::getVarFromGet('transaction'));
$transUniqueId = Helpers::getVarFromGet('unique_id');

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
if (
    ! ($me->getId() == $zeEnv->get('user_id'))
    &&
    !$me->isAuthorityAdminFor($owner->get('authority_id'))
    &&
    ! $me->isArchivistFor($owner->get('authority_id'))
) {
        echo "KO\nAccès refusé";
    exit_wrapper();
}

// Récupération statut
$status = $zeTrans->getCurrentStatus();
if ($status !== false) {
    echo "OK\n" . $status . "\n";
    echo $zeTrans->getFluxRetour($status);
    if ($status == -1) {
        echo mb_convert_encoding($zeTrans->getCurrentMesssage(), 'ISO-8859-1');
    }
} else {
    echo "KO\nErreur consultation statut.";
}
