<?php

require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesTransmissionWindow.class.php');

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
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Gestion des fenêtres module ACTES");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/actes/admin/actes_admin_window_edit.php\" class=\"bouton\">Ajouter une fenêtre</a></center>\n";
$html .= "<h2>Liste des fenêtres existantes</h2>\n";

$win = new ActesTransmissionWindow();
$windows = $win->getWindowsList();

if (count($windows) > 0) {
  $html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
  $html .= "<tr>\n";
  $html .= " <th class=\"data\">Numéro</th>\n";
  $html .= " <th class=\"data\">Début</th>\n";
  $html .= " <th class=\"data\">Fin</th>\n";
  $html .= " <th class=\"data\">Débit horaire</th>\n";
  $html .= " <th class=\"data\">Actions</th>\n";
  $html .= "</tr>\n";
  $i = 0;

  foreach ($windows as $window) {
	$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
	$html .= " <td>" . $window["id"] . "</td>\n";
	$html .= " <td>" . Helpers::getDateFromBDDDate($window["start"], true) . "</td>\n";
	$html .= " <td>" . Helpers::getDateFromBDDDate($window["end"], true) . "</td>\n";
	$html .= " <td>" . $window["rate_limit"] . "</td>\n";
	$html .= " <td><a href=\"" . WEBSITE_SSL . "/modules/actes/admin/actes_admin_window_edit.php?id=" . $window["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
	$html .= "</tr>\n";

	$i = ($i + 1) % 2;
  }

  $html .= "</table>\n";
} else {
  $html .= "Pas de fenêtre de transmission définie.";
}

$html .= "</div>\n";

$doc->buildPager($win);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
