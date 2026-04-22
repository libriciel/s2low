<?php

/**
 * \file actes_admin_window_edit_handler.php
 * \brief Page de traitement des modifications ou ajout des fenêtres
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 23.08.2006
 *
 *
 * Cette page effectue le traitement d'ajout ou de modification d'une
 * fenêtre de transmission dans la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

list($workerScript, $worker) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [WorkerScript::class,ActesEnvoiFichierWorker::class]
    );

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

// Récupération des variables du POST
$id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("id");
$window_start_date = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("window_start_date", true);
$window_start_hour = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("window_start_hour", true);
$window_end_date = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("window_end_date", true);
$window_end_hour = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("window_end_hour", true);
$rate_limit = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("rate_limit", true);

// Mode modification ou pas
$zeWin = new ActesTransmissionWindow();
$mod = false;

if (isset($id) && ! empty($id)) {
    $zeWin->setId($id);
    if (! $zeWin->init()) {
        $_SESSION["error"] = "Erreur lors de la modification de la fenêtre.";
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_windows.php"));
        exit();
    } else {
        $mod = true;
    }
}

$window_start_stamp = ActesTransmissionWindow::roundDate($window_start_date, $window_start_hour);
$window_end_stamp = ActesTransmissionWindow::roundDate($window_end_date, $window_end_hour);

// Contrôle si la date de fin est antérieure à la date de début
if ($window_start_stamp > $window_end_stamp) {
    $_SESSION["error"] = "La date de fin est antérieure à la date de début.";
    if ($zeWin->isNew()) {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php"));
    } else {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php?id=") . $zeWin->getId());
    }
    exit();
}

$zeWin->set("window_start_stamp", $window_start_stamp);
$zeWin->set("window_end_stamp", $window_end_stamp);
$zeWin->set("rate_limit", $rate_limit);

if (($id = $zeWin->hasCollision()) !== false) {
    $_SESSION["error"] = "La fenêtre interfère avec une ou plusieurs fenêtres déjà définies&nbsp;:<br />\nFenêtre numéro " . implode(', ', $id);

    if ($zeWin->isNew()) {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php"));
    } else {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php?id=") . $zeWin->getId());
    }
    exit();
}

if (! $zeWin->save()) {
    $msg = "Erreur lors de l'enregistrement de la fenêtre :\n" . $zeWin->getErrorMsg();
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);

    if ($zeWin->isNew()) {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php"));
    } else {
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php?id=") . $zeWin->getId());
    }
    exit();
} else {
    $msg = ($mod) ? "Modification" : "Création";
    $msg .= " fenêtre de transmission n°" . $zeWin->getId() . ". Résultat ok.";
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    \S2lowLegacy\Class\Helpers\SessionHelper::purgeTempSession();

    $workerScript->rebuildQueue($worker);

    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/admin/actes_admin_window_edit.php?id=") . $zeWin->getId());
    exit();
}
