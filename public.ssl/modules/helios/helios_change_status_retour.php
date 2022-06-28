<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$hr = new HeliosRetour();
$me = new User();
//l'utilisateur'

if (!$me->authenticate()) {
    $_SESSION["error"] = "Ehec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

$nomUSer = $me->get("name");
$userId = $me->getId();

if (!$module->isActive() || !$me->canAccess($module->get("name")) || $me->isGroupAdminOrSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    echo $_SESSION["error"];
    exit();
}


try {
    $retour_id = Helpers :: getIntFromGet("id");
} catch (Exception $e) {
    Helpers :: returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/helios/helios_retour.php");
}
if (isset($retour_id) && !empty($retour_id)) {
    if ($hr->changeStatus($retour_id, 1)) {
        Helpers :: returnAndExit(0, "Changement d'état effectué avec succès", WEBSITE_SSL . "/modules/helios/helios_retour.php");
    } else {
        Helpers :: returnAndExit(1, "Erreur lors du changement d'état", WEBSITE_SSL . "/modules/helios/helios_retour.php");
    }
} else {
    Helpers :: returnAndExit(1, "Pas de réponse sélectionnée pour le changement d'état", WEBSITE_SSL . "/modules/helios/helios_retour.php");
}
