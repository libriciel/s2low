<?php

/**
 * \file helios_admin_windows.php
 * \brief Page de gestion des fenêtres de transmission
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

// Instanciation du module courant
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("helios");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}
$win = new HeliosTransmissionWindow();
$windows = $win->getWindowsList();

$doc = new HTMLLayout();

$doc->setTitle("Gestion des fenêtres module HELIOS");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->buildPager($win);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<h2>Actions</h2>\n";
$html .= "<p><a href=\"" . Helpers::getLink("/modules/helios/admin/helios_admin_window_edit.php") . "\" class=\"btn btn-primary\">Ajouter une fenêtre</a></p>\n";
$html .= "<h2>Liste des fenêtres existantes</h2>\n";

if (count($windows) > 0) {
    $html .= "<table class=\"data-table table table-striped\" summary=\"\">";
    $html .= "<thead>\n";
    $html .= "<tr>\n";
    $html .= " <th id=\"id\">Numéro</th>\n";
    $html .= " <th id=\"start\">Début</th>\n";
    $html .= " <th id=\"end\">Fin</th>\n";
    $html .= " <th id=\"rate-limit\">Débit horaire</th>\n";
    $html .= " <th id=\"actions\">Actions</th>\n";
    $html .= "</tr>\n";
    $html .= "</thead>\n";
    $html .= "</tbody>\n";

    foreach ($windows as $window) {
        $html .= "<tr>\n";
        $html .= " <td headers=\"id\">" . $window["id"] . "</td>\n";
        $html .= " <td headers=\"start\">" . Helpers::getDateFromBDDDate($window["start"], true) . "</td>\n";
        $html .= " <td headers=\"end\">" . Helpers::getDateFromBDDDate($window["end"], true) . "</td>\n";
        $html .= " <td headers=\"rate-limit\">" . $window["rate_limit"] . "</td>\n";
        $html .= " <td headers=\"actions\"><a href=\"" . Helpers::getLink("/modules/helios/admin/helios_admin_window_edit.php?id=" . $window["id"]) . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
        $html .= "</tr>\n";
    }

    $html .= "</tbody>\n";
    $html .= "</table>\n";
} else {
    $html .= "Pas de fenêtre de transmission définie.";
}

$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContainer();

$doc->buildFooter();

$doc->display();
