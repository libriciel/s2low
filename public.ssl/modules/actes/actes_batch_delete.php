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

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

try {
    $id = Helpers::getIntFromPost("id");
} catch (Exception $e) {
    Helpers::returnAndExit(1, $e->getMessage(), Helpers::getLink("/modules/actes/actes_batch_handle.php"));
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
    $zeBatch->setId($id);
    if ($zeBatch->init()) {
        $owner = new User($zeBatch->get("user_id"));
        $owner->init();
    } else {
        Helpers::returnAndExit(1, "Erreur d'initialisation du lot.", Helpers::getLink("/modules/actes/actes_batch_handle.php"));
    }
} else {
    Helpers::returnAndExit(1, "Pas d'identifiant de lot spécifié", Helpers::getLink("/modules/actes/actes_batch_handle.php"));
}

// Vérification des permissions
if (! $me->isSuper()) {
    if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
        Helpers::returnAndExit(1, "Accès refusé", Helpers::getLink("/modules/actes/actes_batch_handle.php"));
    }
}

if (! $zeBatch->delete()) {
    $msg = "Erreur lors de la suppression du lot :\n" . $zeBatch->getErrorMsg();

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::returnAndExit(1, $msg, Helpers::getLink("/modules/actes/actes_batch_handle.php"));
} else {
    $msg = "Suppression du lot n°" . $zeBatch->getId() . ". Résultat ok.";

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::returnAndExit(0, $msg, Helpers::getLink("/modules/actes/actes_batch_handle.php"));
}
