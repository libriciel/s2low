<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;

$logger = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(LoggerInterface::class);

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$logger->critical("Test du déclenchement d'une erreur critique", ['user' => $me->getPrettyName(),'user_id' => $me->getId()]);

$_SESSION["error"] = "Une erreur critique a été déclenchée. Envoi d'un mail à " . EMAIL_ADMIN_TECHNIQUE;
header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/utilities/"));
exit;
