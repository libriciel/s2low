<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

/** @var LoggerInterface $logger */
$logger = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(LoggerInterface::class);


// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$description = Helpers::getVarFromPost("description");
$num_prefix = Helpers::getVarFromPost("num_prefix");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

$zeBatch->save();

header("Content-Type: application/json");
echo json_encode(["id" => $zeBatch->getId()]);
exit();
