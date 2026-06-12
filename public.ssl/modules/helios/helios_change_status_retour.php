<?php

// Instanciation du module courant
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("helios");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$hr = new HeliosRetour();
$me = new User();
//l'utilisateur'

if (!$me->authenticate()) {
    $_SESSION["error"] = "Ehec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
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
    Helpers :: returnAndExit(1, $e->getMessage(), Helpers::getLink("/modules/helios/helios_retour.php"));
}
if (isset($retour_id) && !empty($retour_id)) {
    if ($hr->changeStatus($retour_id, 1)) {
        Helpers :: returnAndExit(0, "Changement d'état effectué avec succès", Helpers::getLink("/modules/helios/helios_retour.php"));
    } else {
        Helpers :: returnAndExit(1, "Erreur lors du changement d'état", Helpers::getLink("/modules/helios/helios_retour.php"));
    }
} else {
    Helpers :: returnAndExit(1, "Pas de réponse sélectionnée pour le changement d'état", Helpers::getLink("/modules/helios/helios_retour.php"));
}
