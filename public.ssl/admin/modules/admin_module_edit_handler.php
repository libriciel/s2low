<?php

/**
 * \file admin_module_edit_handler.php
 * \brief Page de modification de modules
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 *
 *
 * Cette page permet d'activer ou désactiver un module et ajouter
 * ou modifier ses paramètres.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  BF      26.07.2006  Modifications du handler
 */

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Éhec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

//! Recuperation des variables du POST
//! Recuperation des informations sur le module
$id = Helpers::getVarFromPost("id");
$status = Helpers::getVarFromPost("status");

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
$modules = null;

if (isset($id) && ! empty($id)) {
    $modules = $moduleSQL->getById($id);
    if (! $modules) {
        $_SESSION["error"] = "Erreur lors de la modification de la collectivité";
        header("Location: " . Helpers::getLink("/admin/authorities/admin_modules.php"));
        exit();
    }
} else {
    $modules = new Module();
}

$modules->status = $status;

$isNew = $modules->id === null;
if (! $moduleSQL->save($modules)) {
    $msg = "Erreur lors de l'enregistrement du module";
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    header("Location: " . Helpers::getLink("/admin/modules/admin_modules.php"));
    exit();
} else {
    $msg = $isNew ? "Création" : "Modification";
    $msg .= " du module " . $modules->name . " (id=" . $modules->id . "). Résultat ok.";
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    header("Location: " . Helpers::getLink("/admin/modules/admin_module_edit.php?id=") . $modules->id);
}

exit();
