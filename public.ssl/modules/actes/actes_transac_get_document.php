<?php

use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

header_wrapper("Content-type: text/plain");

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
}

if ($me->isSuper() || !$module->isActive() || !$me->canAccess($module->get("name"))) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$transId = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("id");
$unique_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("unique_id");

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
