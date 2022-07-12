<?php

require_once __DIR__ . "/../../../../init/init.php";
$mailSecuriseNotification = LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Services\MailSecurises\MailSecuriseNotification::class);

$module = new Module();
if (!$module->initByName("mail")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));
