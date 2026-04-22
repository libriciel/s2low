<?php

/**
 * \file actes_admin_window_edit.php
 * \brief Page de modification ou d'ajout d'une fenêtre de transmission
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 23.08.2006
 *
 *
 * Cette page permet de gérer les fenêtres de transmission vers le ministère
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
use S2lowLegacy\Class\DatePicker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$html = '';

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
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

// Mode modification ou pas
$mod = false;
$zeWin = new HeliosTransmissionWindow();

$modStr = "Ajout";
if (isset($id) && ! empty($id)) {
    $zeWin->setId($id);
    if ($zeWin->init()) {
        $modStr = "Modification";
        $mod = true;
    } else {
        $zeWin = new HeliosTransmissionWindow();
    }
}

$doc = new HTMLLayout();

$doc->addHeader('<script type="text/javascript" src="' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jquery.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jqueryui.js") . '"></script>');
$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Gestion des fenêtres module HELIOS");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<p id=\"back-helios-admin-btn\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_windows.php") . "\" class=\"btn btn-default\">Retour liste fenêtres</a></p>\n";
$html .= "<h2>" . $modStr . " fenêtre";

if ($mod) {
    $html .= " n° " . $zeWin->getId();
}

$html .= "</h2>\n";
$html .= "<p>Les heures de début et de fin de la fenêtre sont toujours arrondies à l'heure pleine la plus proche (10h, 15h...).</p>";
$html .= "<form class=\"form-horizontal window-edit-form\" action=\"" .
    \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_window_edit_handler.php") .
    "\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm('window_start_date', 'Date de début', 'RisDate', 'window_start_hour', 'Heure de début', 'RisString', 'window_end_date', 'Date de fin', 'RisDate', 'window_end_hour', 'Heure de fin', 'RisString', 'rate_limit', 'Volume maximum', 'RisInt');\">\n";

if ($mod) {
    $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId() . "\" />\n";
}

$start_date = \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession("window_start_date");
$start_hour = \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession("window_start_hour");
if (empty($start_date) && $mod) {
    $start_date = date('Y-m-d', \S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate($zeWin->get("window_start_date")));
    $start_hour = date('H:i:s', \S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate($zeWin->get("window_start_date")));
}

$end_date = \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('window_end_date');
$end_hour = \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('window_end_hour');
if (empty($end_date) && $mod) {
    $end_date = date('Y-m-d', \S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate($zeWin->get("window_end_date")));
    $end_hour = date('H:i:s', \S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate($zeWin->get("window_end_date")));
}

$html .= "<p>$start_date</p>";
$html .= "<p>$end_date</p>";

// Début de la fenêtre
$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-4 control-label\">Début de la fenêtre</label>\n";
$html .= "<div class=\"col-md-8\">\n";
$html .= "<span class=\"form-inline\">";
$html .= (new DatePicker("window_start_date", $start_date, "form-inline"))->show();
$html .= "<input class=\"timepicker form-inline\" href=\"#timepicker\" id=\"window_start_hour\" name=\"window_start_hour\"></input>";
$html .= "<script type=\"text/javascript\">\n $(document).ready(function(){ $('#window_start_hour').timepicker({timeFormat: 'HH:mm:ss',defaultTime: '$start_hour',minTime: '00:00',maxTime: '23:00',startTime: '00:00', interval:60,dynamic:false}); });</script>";
$html .= "</span>\n";
$html .= "   </div>\n";
$html .= "   </div>\n";

// Fin de la fenêtre
$html .= "<div class=\"form-group\">\n";
$html .= "    <label class=\"col-md-4 control-label\">Fin de la fenêtre</label>\n";
$html .= "    <div class=\"col-md-8\">\n";
$html .= "<span class=\"form-inline\">";
$html .= (new DatePicker("window_end_date", $end_date, "form-inline"))->show();
$html .= "<input class=\"timepicker form-inline\" href=\"#timepicker\" id=\"window_end_hour\" name=\"window_end_hour\" ></input>";
$html .= "<script type=\"text/javascript\">\n $(document).ready(function(){ $('#window_end_hour').timepicker({timeFormat: 'HH:mm:ss',defaultTime: '$end_hour',minTime: '00:59:59',maxTime: '23:59:59',startTime: '00:59:59', interval:60,dynamic:false}); });</script>";
$html .= "</span>\n";
$html .= "   </div>\n";
$html .= "   </div>\n";

$rate_limit = \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession("rate_limit");
if (empty($rate_limit) && $mod) {
    $rate_limit = $zeWin->get("rate_limit");
}

$html .= "<div class=\"form-group\">\n";
$html .= "    <label for=\"rate-limit\" class=\"col-md-4 control-label\">Volume maximum par heure en octets</label>\n";
$html .= "    <div class=\"col-md-4\">\n";
$html .= "        <input id=\"rate-limit\" class=\"form-control\" name=\"rate_limit\" type=\"text\" value=\"" . $rate_limit . "\"/>\n";
$html .= "        <span class=\"help-block\">0 pour interdire la transmission</span>\n";
$html .= "    </div>\n";
$html .= "</div>\n";
$html .= "<div class=\"form-group\">";
$html .= "<button type=\"submit\" class=\"col-md-offset-4 col-md-4 btn btn-default\">Soumettre</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";

if ($mod) {
    $html .= "<br />\n";
    $html .= "<form action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_window_delete.php") . "\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement cette fenêtre de transmission ?')\" method=\"post\">\n";
    $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId() . "\" />\n";
    $html .= "<input type=\"submit\" value=\"Supprimer cette fenêtre\" class=\"btn btn-danger\" />\n";
    $html .= "</form>\n";
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContainer();

$doc->buildFooter();

$doc->display();
