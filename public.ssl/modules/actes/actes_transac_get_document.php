<?php

use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;

header_wrapper("Content-type: text/plain");

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isSuper() || !$module->isActive() || !$me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$transId = Helpers::getVarFromGet("id");
$unique_id = Helpers::getVarFromGet("unique_id");

if ($unique_id) {
    $transId = ActesTransaction::getTransactionFromUniqueId($unique_id);
}

if (!$transId) {
    echo "KO\nNuméro de transaction invalide.";
    exit_wrapper();
}

$zeTrans = new ActesTransaction();
$zeTrans->setId($transId);

if ($zeTrans->init()) {
    $owner = new User($zeTrans->get("user_id"));
    $owner->init();
} else {
    echo "KO\nNuméro de transaction invalide.";
    exit_wrapper();
}

$permission = new ModulePermission(new ServiceUser(DatabasePool::getInstance()), "actes");
if (!$permission->canView($me, $owner)) {
    echo "KO\nAccès refusé";
    exit_wrapper();
}

if ($zeTrans->isType(TypeTransaction::TransmissionActe)) {
    $lesTransaction = $zeTrans->getCourrierInfo();
    foreach ($lesTransaction as $id => $value) {
        $t = new ActesTransaction();
        $t->setId($id);
        $status = $t->getCurrentStatus();
        echo $value['type'] . "-$status-$id\n";
    }
} else {
    $envId = $zeTrans->get("envelope_id");
    header_wrapper("Location: actes_download_file.php?env=$envId");
}
