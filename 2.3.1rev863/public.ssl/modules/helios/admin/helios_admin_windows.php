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
$html .= "<p><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php\" class=\"btn btn-primary\">Ajouter une fenêtre</a></p>\n";
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
	$html .= " <td headers=\"actions\"><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $window["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
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

?>
