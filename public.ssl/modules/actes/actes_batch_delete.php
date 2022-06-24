<?php

/**
 * \file actes_batch_delete.php
 * \brief Page de suppression d'un lot de fichier transaction Actes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 14.02.2007
 *
 *
 * Ce scipt permet de supprimer un lot ainsi que tous les fichiers associés
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

try {
    $id = Helpers::getIntFromPost("id");
} catch (Exception $e) {
    Helpers::returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
    $zeBatch->setId($id);
    if ($zeBatch->init()) {
        $owner = new User($zeBatch->get("user_id"));
        $owner->init();
    } else {
        Helpers::returnAndExit(1, "Erreur d'initialisation du lot.", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
    }
} else {
    Helpers::returnAndExit(1, "Pas d'identifiant de lot spécifié", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}

// Vérification des permissions
if (! $me->isSuper()) {
    if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
        Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
    }
}

if (! $zeBatch->delete()) {
    $msg = "Erreur lors de la suppression du lot :\n" . $zeBatch->getErrorMsg();

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
} else {
    $msg = "Suppression du lot n°" . $zeBatch->getId() . ". Résultat ok.";

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}
