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

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des fenêtres de transmission</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php\" class=\"bouton\">Retour liste fenêtres</a></center>\n";
$html .= "<h2>" . $modStr . " fenêtre";

if ($mod) {
  $html .= " n° " . $zeWin->getId();
}

$html .= "</h2>\n";
$html .= "Les heures de début et de fin de la fenêtre sont toujours arrondies à l'heure pleine la plus proche (10h, 15h...).";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm('window_start_date', 'Date de début', 'RisDate', 'window_start_hour', 'Heure de début', 'RisString', 'window_end_date', 'Date de fin', 'RisDate', 'window_end_hour', 'Heure de fin', 'RisString', 'rate_limit', 'Volume maximum', 'RisInt');\">\n";

if ($mod) {
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId() . "\" />\n";
}
$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";

// Début de la fenêtre
$html .= "  <dt>Début de la fenêtre&nbsp;:</dt>\n";
$html .= "   <dd>\n";

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

$html .= "    <a href=\"#datepicker\" id=\"datepicker_window_start_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_start_date.toggleDatePicker(); return false;\">";

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

$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_window_start_date_calendar\"></div>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"timepicker_window_start_hour_clock\"></div>\n";
$html .= "   </dd>\n";

// Fin de la fenêtre
$html .= "  <dt>Fin de la fenêtre&nbsp;:</dt>\n";
$html .= "   <dd>\n";
$html .= "    <input id=\"window_end_date\" name=\"window_end_date\" type=\"hidden\" value=\"" . $end_date . "\"/>\n";
$html .= "    <input id=\"window_end_hour\" name=\"window_end_hour\" type=\"hidden\" value=\"" . $end_hour . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_window_end_date = new DatePicker('window_end_date', 'fr');\n";
$html .= "    obj_window_end_hour = new TimePicker('window_end_hour', 'fr');\n";
$html .= "    obj_window_end_hour.enableSeconds();\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_window_end_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_window_end_date.toggleDatePicker(); return false;\">";

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

$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_window_end_date_calendar\"></div>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"timepicker_window_end_hour_clock\"></div>\n";
$html .= "   </dd>\n";

$rate_limit = Helpers::getFromSession("rate_limit");
if (empty($rate_limit) && $mod) {
  $rate_limit = $zeWin->get("rate_limit");
}

$html .= "  <dt>Volume maximum par heure en octets (0 pour interdire la transmission)&nbsp;:</dt>\n";
$html .= "   <dd>\n";
$html .= "    <input name=\"rate_limit\" type=\"text\" value=\"" . $rate_limit . "\"/>\n";
$html .= "   </dd>\n";
$html .= "  </dl>\n";
$html .= " </div>\n";
$html .= "<center><input class=\"submit_button\" type=\"submit\" value=\"Soumettre\" /></center>\n";
$html .= "</form>\n";

if ($mod) {
  $html .= "<br />\n";
  $html .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/admin/helios_admin_window_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement cette fenêtre de transmission ?')\" method=\"post\">\n";
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeWin->getId(). "\" />\n";
  $html .= "<input type=\"submit\" value=\"Supprimer cette fenêtre\" class=\"bouton-danger\" />\n";
  $html .= "</form>\n";
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
