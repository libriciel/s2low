<?php

require_once("../../../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (! $me->isAdmin()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$serviceUser = new ServiceUser(DatabasePool::getInstance());
