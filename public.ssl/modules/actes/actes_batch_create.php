<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

/** @var LoggerInterface $logger */
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

$description = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("description");
$num_prefix = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("num_prefix");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

$zeBatch->save();

header("Content-Type: application/json");
echo json_encode(["id" => $zeBatch->getId()]);
exit();
