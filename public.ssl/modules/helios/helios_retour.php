<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosRetour.class.php');
// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Echec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$HR = new HeliosRetour();

$fstatus = Helpers :: getVarFromGet("status");
$fmin_submission_date = Helpers :: getVarFromGet("min_submission_date");
$fmax_submission_date = Helpers :: getVarFromGet("max_submission_date");
$fnum = Helpers :: getVarFromGet("num");
$fauthority = Helpers :: getVarFromGet("authority");


$doc = new HTMLLayout();
$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");
$doc->setTitle("Module helios : message retour ");
$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Helios - Dématérialisation de documents financiers</h1>\n";

//filtrage aria
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('filtering_area');\">Filtrage</h2>\n";
$html .= "<div id=\"filtering_area\" style=\"display: block;\">\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/helios_retour.php\" method=\"get\">\n";

$html .= "<table>\n";

if (empty($fstatus)) $fstatus = 0; //par defaut état selectionnée

$html .= "<tr>\n";

$html .= "<td class=\"title\">Etat&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("status", array(0 => "non lu", 1 => "lu", 2 => "tous les états"), $fstatus) . "</td>\n";
$html .= "<td class=\"title\">Le nom de fichier contient&nbsp;:\n</td>";
$html .= "<td class=\"value\"><input type=\"text\" name=\"num\" size=\"20\" maxlength=\"25\"";

//$fnum: le nom du fichier contient...
if (strlen($fnum) > 0) {
  $html .= " value=\"" . $fnum . "\"";
}


$html .= " /></td>\n";

//des autres options...
$colspan = 2;
//les dates
$html .= "<tr>\n";
//date minimale de postage
$html .= "<td class=\"title\">Date de réception minimale&nbsp;:</td>\n";
$html .= "<td colspan=\"" . $colspan . "\" class=\"value\"><input id=\"min_submission_date\" name=\"min_submission_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmin_submission_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_min_submission_date = new DatePicker('min_submission_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_min_submission_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_min_submission_date.toggleDatePicker(); return false;\">";

if ($fmin_submission_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_submission_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_submission_date_calendar\"></div></td>\n";
$html .= "<tr>\n";

$html .= "<tr>\n";
$html .= "<td class=\"title\">Date de réception maximale&nbsp;:</td>\n";
$html .= "<td colspan=\"" . $colspan . "\" class=\"value\"><input id=\"max_submission_date\" name=\"max_submission_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmax_submission_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_max_submission_date = new DatePicker('max_submission_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_max_submission_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_max_submission_date.toggleDatePicker(); return false;\">";

if ($fmax_submission_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_submission_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_max_submission_date_calendar\"></div></td>\n";


//colectivitïvité  pour superuser
if ($me->isSuper()) {
  $html .= "<td class=\"title\">Collectivité&nbsp;:</td>\n";

  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("authority", Authority :: getAuthoritiesIdName(), $fauthority) . "</td>\n";

}
$html .= "<td><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "<td><a href=\"" . WEBSITE_SSL . "/modules/helios/helios_retour.php\" class=\"bouton\">Remise&nbsp;à&nbsp;zéro</a></td>\n";
$html .= "</tr>\n";

$html .= "</table>\n";
$html .= "</form>\n";
$html .= "</div>\n"; //filtrage aria


//actions area
  $html .= "<div id=\"actions_area\">\n";
  $html .= "<h2>Actions</h2>\n";
  if ($module->getParam("paper") == "on") {
    $html .= "<p>Le système est actuellement en mode &nbsp;papier&nbsp;. Dans ce mode il est impossible de créer de nouvelle transaction. Les transferts doivent se faire par les moyens classiques.</p>\n";
  } else {
 
	$html.="<a href=\"".WEBSITE_SSL. "/modules/helios/index.php\" class=\"bouton\" title=\"afficher la liste des transactions\">Retour liste transactions</a>\n";
  }
  $html .= "</div>\n";


$filter = array ();
// Construction chaine de filtrage
//filtre sur état
if (isset ($fstatus) && is_numeric($fstatus) && $fstatus != 2) {//si = 2 : tous les états
   $filter[] .= "status = $fstatus";
}

//collectivité (si sadmin)
if (!$me->isSuper()) { // Le super utilisateur voit les reponses de toutes les collectivité
   // Un utilisateur ne voit que les reponses de sa collectivité
  $filter[] .= "helios_retour.siren='" . $me->getUserSiren(). "'";
}else {
  if (isset($fauthority) && !empty($fauthority) )
  	$filter[] .= "helios_retour.siren='" . Authority::getSirenFromId($fauthority) . "'";
}
// On ajoute les filtres relatifs aux dates
if (isset ($fmin_submission_date) && !empty ($fmin_submission_date)) {
  $filter[] .= "date >= '" . addslashes($fmin_submission_date) . "'";
}
if (isset ($fmax_submission_date) && !empty ($fmax_submission_date)) {
  $filter[] .= "date <= '" . addslashes($fmax_submission_date) . "'";
}
//on ajoute filtre sur nom fichier
if (isset ($fnum) && !empty ($fnum)) {
  $filter[] .= "filename LIKE '%" . addslashes($fnum) . "%'";
}

$where = "";
if (count($filter) > 0) {
  $where = " WHERE " . implode($filter, " AND ");
}


$etat = array(
		0 => "non lu",
		1 => "lu"
		);


$envelops=$HR->getDocumentList($where);

$html .= "<h2>Liste des messages retours</h2>\n";
if (count($envelops) > 0) {
    $html .= "<table class=\"transactions_list\">\n";
  
    $html .= " <tr>\n";
    $html .= "  <th>Nom du fichier</th>\n";
    $html .= "  <th>Date de réception</th>\n";
    $html .= "  <th>Etat</th>\n";
    $html .= "  <th>Action</th>\n";
    $html .= " </tr>\n";
	 	foreach ($envelops as $envelope) {
	  
	      $retour_id = $envelope["id"];
	
	      $html .= "<tr>\n";
	      $html .= " <td> <a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_response.php?id=" .$retour_id. "\" title=\"Télécharger l'acquittement\">".$envelope["filename"]."</a> </td> \n";
	      $html .= " <td>" . Helpers::getDateFromBDDDate($envelope["date"], true)."</td>\n";
	      $html .= " <td>" . $etat[$envelope["status"]]."</td>\n";
	      $html .= " <td> ";
	      if ($envelope["status"] == 0 && !$me->isSuper())
	      	$html .= "<a href=\"" . WEBSITE_SSL . "/modules/helios/helios_change_status_retour.php?id=" .$retour_id. "\" title=\"passer à l'état lu\" class=\"icon\"> <img alt=\"ok\" src=\"../../custom/images/icone_ok.gif\"> </a> ";
	      $html .= "</td>\n";
	      $html .= "</tr>\n";
		}
	$html .= "</table>\n";
}else{
	$html .= "Pas de transaction trouvée correspondant aux critères de filtrage.";
}
$html .= "</div>\n";

$doc->buildPager($HR);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();