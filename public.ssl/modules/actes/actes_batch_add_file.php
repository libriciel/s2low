<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$logger = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(LoggerInterface::class);


// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->canAccess($module->get("name"))) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$batchId = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromPost("batch_id");

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
