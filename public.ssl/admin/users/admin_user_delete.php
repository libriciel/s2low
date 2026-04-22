<?php

/**
 * \file admin_user_delete.php
 * \brief Page effectuant la suppression d'un utilisateur
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 16.03.2006
 *
 *
 * Cette page supprime un utilisateur de la base de données
 * Elle prend un paramètre id dans la requête HTTP POST designant
 * l'utilisateur à supprimer.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   19.07.2006  Adaptation pour Tedetis
 */

// Suppression utilisateur désactivée
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;

header("Location: " . WEBSITE);

// Suppression utilisateur désactivée en production
if (MODE == "prod") {
    header("Location: " . WEBSITE);
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isAdmin()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_POST["id"]) ? $_POST["id"] : null;

if (isset($id) && ! empty($id)) {
    $him = new User($id);

    if (! $me->canEditUser($id)) {
        $_SESSION["error"] = "Accès refusé pour la suppression de cet utilisateur";
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
        exit();
    } else {
        if ($him->delete()) {
            $msg = "Suppression de l'utilisateur " . $him->getPrettyName() . ". Résultat ok.";
            if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
                $msg .= "\nErreur de journalisation.";
            }

            $_SESSION["error"] = nl2br($msg);
            header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
            exit();
        } else {
            $msg = "Erreur lors de la tentative de suppression de l'utilisateur\n" . $him->getErrorMsg();
            if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
                $msg .= "\nErreur de journalisation.";
            }

            $_SESSION["error"] = nl2br($msg);
            header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
            exit();
        }
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant utilisateur spécifié";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
    exit();
}
