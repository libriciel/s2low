<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématérialisation de l'administration.
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement,
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php

/**
 * \file helios_admin_window_edit_handler.php
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
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransmissionWindow.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

// Récupération des variables du POST
$id = Helpers::getVarFromPost("id");
$window_start_date = Helpers::getVarFromPost("window_start_date", true);
$window_start_hour = Helpers::getVarFromPost("window_start_hour", true);
$window_end_date = Helpers::getVarFromPost("window_end_date", true);
$window_end_hour = Helpers::getVarFromPost("window_end_hour", true);
$rate_limit = Helpers::getVarFromPost("rate_limit", true);

// Mode modification ou pas
$zeWin = new HeliosTransmissionWindow();
$mod = false;

if (isset($id) && ! empty($id)) {
    $zeWin->setId($id);
    if (! $zeWin->init()) {
        $_SESSION["error"] = "Erreur lors de la modification de la fenêtre.";
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php");
        exit();
    } else {
        $mod = true;
    }
}

$window_start_stamp = HeliosTransmissionWindow::roundDate($window_start_date, $window_start_hour);
$window_end_stamp = HeliosTransmissionWindow::roundDate($window_end_date, $window_end_hour);

// Contrôle si la date de fin est antérieure à la date de début
if ($window_start_stamp > $window_end_stamp) {
    $_SESSION["error"] = "La date de fin est antérieure à la date de début.";
    if ($zeWin->isNew()) {
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php");
    } else {
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $zeWin->getId());
    }
    exit();
}

$zeWin->set("window_start_stamp", $window_start_stamp);
$zeWin->set("window_end_stamp", $window_end_stamp);
$zeWin->set("rate_limit", $rate_limit);

if (($id = $zeWin->hasCollision()) !== false) {
    $_SESSION["error"] = "La fenêtre interfère avec une ou plusieurs fenêtres déjà définies&nbsp;:<br />\nFenêtre numéro " . implode(', ', $id);

    if ($zeWin->isNew()) {
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php");
    } else {
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $zeWin->getId());
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
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php");
    } else {
        header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $zeWin->getId());
    }
    exit();
} else {
    $msg = ($mod) ? "Modification" : "Création";
    $msg .= " fenêtre de transmission n°" . $zeWin->getId() . ". Résultat ok.";
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    Helpers::purgeTempSession();
    header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $zeWin->getId());
    exit();
}
