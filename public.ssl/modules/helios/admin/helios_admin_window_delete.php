<?php

/**
 * \file helios_admin_window_delete.php
 * \brief Page de suppression d'une fenêtre
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 23.08.2006
 *
 *
 * Cette page effectue la suppression d'une fenêtre
 * de transmission de la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Instanciation du module courant
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("helios");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

// Récupération des variables du POST
$id = Helpers::getVarFromPost("id");

if (isset($id)) {
    $zeWin = new HeliosTransmissionWindow($id);
    if ($zeWin->delete()) {
        $msg = "Suppression de la fenêtre de transmission " . $zeWin->getId() . ". Résultat ok.";
        if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), $module->get("name"), $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: " . Helpers::getLink("/modules/helios/admin/helios_admin_windows.php"));
        exit();
    } else {
        $msg = "Erreur lors de la tentative de suppression de la fenêtre de transmission<br />" . $zeWin->getErrorMsg();
        if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), $module->get("name"), $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: " . Helpers::getLink("/modules/helios/admin/helios_admin_windows.php"));
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de fenêtre de transmission spécifié";
    header("Location: " . Helpers::getLink("/modules/helios/admin/helios_admin_windows.php"));
    exit();
}
