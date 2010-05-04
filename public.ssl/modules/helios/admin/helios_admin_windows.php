<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
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

$doc = new HTMLLayout();

$doc->setTitle("Gestion des fenêtres module HELIOS");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php\" class=\"bouton\">Ajouter une fenêtre</a></center>\n";
$html .= "<h2>Liste des fenêtres existantes</h2>\n";

$win = new HeliosTransmissionWindow();
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
	$html .= " <td><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit.php?id=" . $window["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
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
