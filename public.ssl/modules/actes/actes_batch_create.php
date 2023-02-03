<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\User;

/** @var S2lowLogger $logger */
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
