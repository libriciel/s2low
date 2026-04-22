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

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

try {
    $id = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromPost("id");
} catch (Exception $e) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, $e->getMessage(), \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
    $zeBatch->setId($id);
    if ($zeBatch->init()) {
        $owner = new User($zeBatch->get("user_id"));
        $owner->init();
    } else {
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du lot.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
    }
} else {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Pas d'identifiant de lot spécifié", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
}

// Vérification des permissions
if (! $me->isSuper()) {
    if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
    }
}

if (! $zeBatch->delete()) {
    $msg = "Erreur lors de la suppression du lot :\n" . $zeBatch->getErrorMsg();

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, $msg, \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
} else {
    $msg = "Suppression du lot n°" . $zeBatch->getId() . ". Résultat ok.";

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, $msg, \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
}
