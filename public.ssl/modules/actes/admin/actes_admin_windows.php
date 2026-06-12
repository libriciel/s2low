<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$html = '';

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
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

$doc = new HTMLLayout();
$win = new ActesTransmissionWindow();
$windows = $win->getWindowsList();

$doc->setTitle("Gestion des fenêtres module ACTES");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->buildPager($win);
$doc->closeSideBar();
$doc->openContent();


$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<h2>Actions</h2>\n";
$html .= "<a href=\"" . Helpers::getLink("/modules/actes/admin/actes_admin_window_edit.php") . "\" class=\"btn btn-primary\">Ajouter une fenêtre</a>\n";
$html .= "<h2>Liste des fenêtres existantes</h2>\n";

if (count($windows) > 0) {
    $html .= "<table class=\"data-table table table-striped\" summary=\"\">";
    $html .= "<thead>\n";
    $html .= "<tr>\n";
    $html .= " <th id=\"number\">Numéro</th>\n";
    $html .= " <th id=\"start-date\">Début</th>\n";
    $html .= " <th id=\"end-date\">Fin</th>\n";
    $html .= " <th id=\"rate\">Débit horaire</th>\n";
    $html .= " <th class=\"data\">Actions</th>\n";
    $html .= "</tr>\n";
    $html .= "</thead>\n";
    $html .= "</tbody>\n";
    $i = 0;

    foreach ($windows as $window) {
        $html .= "<tr>\n";
        $html .= " <td>" . $window["id"] . "</td>\n";
        $html .= " <td>" . Helpers::getDateFromBDDDate($window["start"], true) . "</td>\n";
        $html .= " <td>" . Helpers::getDateFromBDDDate($window["end"], true) . "</td>\n";
        $html .= " <td>" . $window["rate_limit"] . "</td>\n";
        $html .= " <td><a href=\"" . Helpers::getLink("/modules/actes/admin/actes_admin_window_edit.php?id=" . $window["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n");
        $html .= "</tr>\n";

        $i = ($i + 1) % 2;
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
} else {
    $html .= "Pas de fenêtre de transmission définie.";
}


$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
