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
    Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$description = Helpers::getVarFromPost("intitule");
$num_prefix = Helpers::getVarFromPost("prefixe");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

$zeBatch->save();

header("Content-Type: application/json");
echo json_encode(["id" => $zeBatch->getId()]);
exit();
