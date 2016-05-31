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

if (! $me->isSuper() || ! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
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

$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");
$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Gestion des fenêtres module HELIOS");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<p id=\"back-helios-admin-btn\"><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php\" class=\"btn btn-default\">Retour liste fenêtres</a></p>\n";
$html .= "<h2>" . $modStr . " fenêtre";

if ($mod) {
  $html .= " n° " . $zeWin->getId();
}

$html .= "</h2>\n";
$html .= "<p>Les heures de début et de fin de la fenêtre sont toujours arrondies à l'heure pleine la plus proche (10h, 15h...).</p>";
$html .= "<form class=\"form-horizontal window-edit-form\" action=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm('window_start_date', 'Date de début', 'RisDate', 'window_start_hour', 'Heure de début', 'RisString', 'window_end_date', 'Date de fin', 'RisDate', 'window_end_hour', 'Heure de fin', 'RisString', 'rate_limit', 'Volume maximum', 'RisInt');\">\n";

if ($mod) {
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId() . "\" />\n";
}

// Début de la fenêtre
$html .= "<div class=\"form-group\">\n";
$html .= "    <label class=\"col-md-4 control-label\">Début de la fenêtre</label>\n";
$html .= "    <div class=\"col-md-8\">\n";

$start_date = Helpers::getFromSession("window_start_date");
$start_hour = Helpers::getFromSession("window_start_hour");
if (empty($start_date) && $mod) {
  $start_date = date('Y-m-d', Helpers::getTimestampFromBDDDate($zeWin->get("window_start_date")));
  $start_hour = date('H:i:s', Helpers::getTimestampFromBDDDate($zeWin->get("window_start_date")));
}

$end_date = Helpers::getFromSession("window_end_date");
$end_hour = Helpers::getFromSession("window_end_hour");
if (empty($end_date) && $mod) {
  $end_date = date('Y-m-d', Helpers::getTimestampFromBDDDate($zeWin->get("window_end_date")));
  $end_hour = date('H:i:s', Helpers::getTimestampFromBDDDate($zeWin->get("window_end_date")));
}

$html .= "    <input id=\"window_start_date\" name=\"window_start_date\" type=\"hidden\" value=\"" . $start_date . "\"/>\n";
$html .= "    <input id=\"window_start_hour\" name=\"window_start_hour\" type=\"hidden\" value=\"" . $start_hour . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_window_start_date = new DatePicker('window_start_date', 'fr');\n";
$html .= "    obj_window_start_hour = new TimePicker('window_start_hour', 'fr');\n";
$html .= "    obj_window_start_hour.enableSeconds();\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <span class=\"form-control\"><a href=\"#datepicker\" id=\"datepicker_window_start_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_start_date.toggleDatePicker(); return false;\">";

if ($start_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers::ansiDateToTimestamp($start_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a> à ";
$html .= "<a href=\"#timepicker\" id=\"timepicker_window_start_hour_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_start_hour.toggleTimePicker(); return false;\">";

if ($start_hour) {
  $html .= Helpers::getPrettyHours($start_hour);
} else {
  $html .= "[&nbsp;Choisir une heure&nbsp;]";
}

$html .= "</a>\n</span>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_window_start_date_calendar\"></div>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"timepicker_window_start_hour_clock\"></div>\n";
$html .= "   </div>\n";
$html .= "   </div>\n";

// Fin de la fenêtre
$html .= "<div class=\"form-group\">\n";
$html .= "    <label class=\"col-md-4 control-label\">Fin de la fenêtre</label>\n";
$html .= "    <div class=\"col-md-8\">\n";
$html .= "    <input id=\"window_end_date\" name=\"window_end_date\" type=\"hidden\" value=\"" . $end_date . "\"/>\n";
$html .= "    <input id=\"window_end_hour\" name=\"window_end_hour\" type=\"hidden\" value=\"" . $end_hour . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_window_end_date = new DatePicker('window_end_date', 'fr');\n";
$html .= "    obj_window_end_hour = new TimePicker('window_end_hour', 'fr');\n";
$html .= "    obj_window_end_hour.enableSeconds();\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <span class=\"form-control\"><a href=\"#datepicker\" id=\"datepicker_window_end_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_end_date.toggleDatePicker(); return false;\">";

if ($end_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers::ansiDateToTimestamp($end_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a> à ";
$html .= "<a href=\"#timepicker\" id=\"timepicker_window_end_hour_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_end_hour.toggleTimePicker(); return false;\">";

if ($start_hour) {
  $html .= Helpers::getPrettyHours($end_hour);
} else {
  $html .= "[&nbsp;Choisir une heure&nbsp;]";
}

$html .= "</a>\n</span>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_window_end_date_calendar\"></div>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"timepicker_window_end_hour_clock\"></div>\n";
$html .= "   </div>\n";
$html .= "   </div>\n";

$rate_limit = Helpers::getFromSession("rate_limit");
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
  $html .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement cette fenêtre de transmission ?')\" method=\"post\">\n";
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId(). "\" />\n";
  $html .= "<input type=\"submit\" value=\"Supprimer cette fenêtre\" class=\"btn btn-danger\" />\n";
  $html .= "</form>\n";
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContainer();

$doc->buildFooter();

$doc->display();

?>
