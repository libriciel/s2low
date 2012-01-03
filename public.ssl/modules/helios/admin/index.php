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


// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

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

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Utilitaires module HELIOS");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Utilitaires - HELIOS</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('export_area');\">Export liste transactions</h2>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les transactions envoyées au ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_transac_export.php\">Télécharger le fichier</a></p>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les réponses reçues du ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_transac_retour_export.php\">Télécharger le fichier</a></p>\n";


$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('window_area');\">Gestion des fenêtres de transmission</h2>\n";
$html .= "<p id=\"window_area\" style=\"display: block;\">\n";
$html .= "La transmission des données vers le serveur du ministère se fait par défaut à tout moment de la journée sans limitation de volume. Il est cependant possible de définir des fenêtres horaires où le volume de transmission autorisé sera limité à une certaine taille ou tout simplement nul.<br />\n";
$html .= "<a style=\"margin-left: 10px\" href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php\">Accéder à l'interface de définition des fenêtres</a></p>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
