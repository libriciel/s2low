<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\User;

$logger = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowLogger::class);


// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$batchId = Helpers::getIntFromPost("batch_id");

$zeBatch = new ActesBatch($batchId, true);
$zeBatch->init();
$zeBatch->initStorage();

$upload_handler = new \S2lowLegacy\Class\BatchUploadHandler(
    $zeBatch,
    [
    'accept_file_types' => '/\.(pdf)$/i',
    'print_response' => false,
    'upload_dir' => ACTES_BATCHES_UPLOAD_ROOT . "/"
    ]
);

echo json_encode($upload_handler->get_response());
return 0;
