<?php

/**
 * \file admin_authority_delete.php
 * \brief Page effectuant la suppression d'une collectivité
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 *
 *
 * Cette page supprime une collectivité de la base de données
 * Elle prend un paramètre id dans la requête HTTP POST designant
 * la collectivité à supprimer.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Suppression collectivité désactivée en production
if (MODE == "prod") {
    header("Location: " . WEBSITE);
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

// Seul un super administrateur peut effectuer cette action
if (! $me->isGroupAdminOrSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = Helpers::getVarFromPost("id");

if (isset($id) && ! empty($id)) {
    $authority = new Authority($id);

    if ($me->isGroupAdmin() && ! $authority->isInGroup($me->get("authority_group_id"))) {
        $_SESSION["error"] = "Accès refusé pour la collectivité spécifiée";
        header("Location: " . Helpers::getLink("/admin/authorities/admin_authorities.php"));
        exit();
    }

    if ($authority->delete()) {
        $msg = "Suppression de la collectivité " . $authority->get("name") . ". Résultat ok.";
        if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: " . Helpers::getLink("/admin/authorities/admin_authorities.php"));
        exit();
    } else {
        $msg = "Erreur lors de la tentative de suppression de la collectivité<br />" . $authority->getErrorMsg();
        if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
            $msg .= "\nErreur de journalisation.";
        }

        $_SESSION["error"] = nl2br($msg);
        header("Location: " . Helpers::getLink("/admin/authorities/admin_authorities.php"));
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de collectivité spécifié";
    header("Location: " . Helpers::getLink("/admin/authorities/admin_authorities.php"));
    exit();
}
