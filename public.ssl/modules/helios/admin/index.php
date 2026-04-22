<?php

/**
 * \file public.ssl/modules/helios/admin/index.php
 * \brief Page d'accueil de la section administration du module helios
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 10.08.2006
 *
 *
 * Cette page affiche des fonctions utilitaires
 * pour le module helios.
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

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Utilitaires module HELIOS");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Utilitaires - HELIOS</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('export_area');\">Export liste transactions</h2>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les transactions envoyées au ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_transac_export.php") . "\">Télécharger le fichier</a></p>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les réponses reçues du ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_transac_retour_export.php") . "\">Télécharger le fichier</a></p>\n";


$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('window_area');\">Gestion des fenêtres de transmission</h2>\n";
$html .= "<p id=\"window_area\" style=\"display: block;\">\n";
$html .= "La transmission des données vers le serveur du ministère se fait par défaut à tout moment de la journée sans limitation de volume. Il est cependant possible de définir des fenêtres horaires où le volume de transmission autorisé sera limité à une certaine taille ou tout simplement nul.<br />\n";
$html .= "<a style=\"margin-left: 10px\" href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/admin/helios_admin_windows.php") . "\">Accéder à l'interface de définition des fenêtres</a></p>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
