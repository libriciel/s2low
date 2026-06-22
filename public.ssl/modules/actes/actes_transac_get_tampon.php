<?php

/**
 * @deprecated
 *
 * Cette fonction est deprecated, on doit utiliser :
 * - actes_transac_get_files_list.php pour récupérer la liste des fichiers attachés à un acte.
 * - actes_download_file.php?file=id&tampon=true pour récupérer le fichier ou le fichier tamponné
 *
 */

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
    echo "KO\nErreur d'initialisation du module";
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    echo "KO\nÉchec de l'authentification";
    exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
    echo "KO\nAccès refusé";
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du GET
try {
    $transId = Helpers::getIntFromGet("transaction", true);
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
$transUniqueId = Helpers::getVarFromGet("unique_id");

if (isset($transUniqueId) && ! empty($transUniqueId)) {
    $transId = ActesTransaction::getTransactionFromUniqueId($transUniqueId);
}


if (isset($transId) && ! empty($transId)) {
    $zeTrans = new ActesTransaction();
    $zeTrans->setId($transId);
} else {
    echo "KO\nNuméro de transaction invalide.";
    exit();
}

if ($zeTrans->init()) {
    $owner = new User($zeTrans->get("user_id"));
    $owner->init();
} else {
    echo "KO\nNuméro de transaction invalide.";
    exit();
}

$zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
if (! $zeEnv->init()) {
    echo "KO\nEnveloppe invalide.";
    exit();
}

// Vérification des permissions
if (! $me->isSuper()) {
    if (! ($me->isAnyAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $zeEnv->get("user_id") && $me->canAccess($module->get("name")))) {
        echo "KO\nAccès refusé";
        exit();
    }
}

$workflow = $zeTrans->fetchWorkflow();


$has_file = false;
foreach ($workflow as $stage) {
    if ($stage['status_id'] == 4) {
        $has_file = true;
    }
}
if (! $has_file) {
    echo "KO\nPas d'acquittement recu";
    exit();
}


$files = $zeTrans->fetchFilesList();
foreach ($files as $file) {
    if (preg_match("/pdf$/i", $file["posted_filename"])) {
        header("Location:  actes_download_file.php?file={$file['id']}&tampon=true");
        exit;
    }
}
