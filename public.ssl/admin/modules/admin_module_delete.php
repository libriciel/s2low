<?php

/**
 * \file admin_module_delete.php
 * \brief Page de suppression d'un module
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 *
 *
 * Ce script effecue la suppression du module dont l'identifiant est passé
 * dans la variaible POST id
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (!$me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = (isset($_POST["id"])) ? $_POST["id"] : null;


if (isset($id)) {
    $module = new Module($id);
    if ($module->delete()) {
        $msg = "Suppression du module " . $module->get("name") . ". Résultat ok.";
        if (!Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: admin_modules.php");
        exit();
    } else {
        $msg = "Erreur lors de la tentative de suppression du module<br />" . $module->getErrorMsg();
        if (!Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: admin_modules.php");
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de module spécifié";
    header("Location: admin_modules.php");
    exit();
}
