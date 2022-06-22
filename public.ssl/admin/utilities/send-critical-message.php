<?php

require_once(__DIR__ . "/../../../init/init.php");


$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$logger->critical("Test du déclenchement d'une erreur critique", ['user' => $me->getPrettyName(),'user_id' => $me->getId()]);

$_SESSION["error"] = "Une erreur critique a été déclenchée. Envoi d'un mail à " . EMAIL_ADMIN_TECHNIQUE;
header("Location: " . WEBSITE_SSL . "/admin/utilities/");
