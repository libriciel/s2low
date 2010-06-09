<?php


/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã   la
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
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe Ã   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * Ã  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php


/**
 * \file public.ssl/modules/actes/index.php
 * \brief Page d'accueil du module ACTES
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 27.07.2006
 * 
 *
 * Cette page affiche la liste des transactions du module
 * ACTES et permet de les modifier ou d'en créer de nouvelles
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
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
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$fauthority = Helpers :: getVarFromGet("authority");
$fnature = Helpers :: getVarFromGet("nature");
$ftype = Helpers :: getVarFromGet("type");
$fnum = Helpers :: getVarFromGet("num");
$fstatus = Helpers :: getVarFromGet("status");
$fmin_submission_date = Helpers :: getVarFromGet("min_submission_date");
$fmax_submission_date = Helpers :: getVarFromGet("max_submission_date");
$fmin_ack_date = Helpers :: getVarFromGet("min_ack_date");
$fmax_ack_date = Helpers :: getVarFromGet("max_ack_date");

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$js =<<<EOJS
<script type="text/javascript">
//<![CDATA[
function show_all() {
  toggle_all("block", "-");
}

function hide_all() {
  toggle_all("none", "+");
}

function toggle_all(style, symbol) {
  done = false;
  i = 0;

  while (! done) {
	var content = document.getElementById("envelope_content_" + i);
	var expander = document.getElementById("expander_" + i);

	if (content && expander) {
	  content.style.display = style;
	  expander.innerHTML = symbol;
	} else {
	  done = true;
	}
	i++;
  }
}

function toggle_envelope_content(id) {
  var content = document.getElementById("envelope_content_" + id);
  var expander = document.getElementById("expander_" + id);

  if (content.style.display == "block") {
	content.style.display = "none";
	expander.innerHTML = "+";
  } else {
	content.style.display = "block";
	expander.innerHTML = "-";
  }  
}

function GereChkbox(conteneur, a_faire) {
  var blnEtat=null;
  var Tab = document.getElementsByTagName("input");
  for(var i = 0; i < Tab.length; i++){  
	Chckbox=Tab[i];
	if (Chckbox.getAttribute("type")=="checkbox") {
		blnEtat = (a_faire=='0') ? false : (a_faire=='1') ? true : (Chckbox.checked) ? false : true;
		Chckbox.checked=blnEtat;
	}
  }
}

function afficheWarning(){
  var n = 0;
  var liste = document.getElementsByTagName("input");
  for(var i = 0; i < liste.length; i++){
    if (liste[i].getAttribute("type")=="checkbox"){
      if(liste[i].checked) n++;
    }
  }
  var msg = "Voulez-vous vraiment affecter les " + n + " transactions sélectionnées ?\\n";
  msg += "Cette action est non réversible et est sous votre entière responsabilité.";
  return confirm(msg);
}
//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");

$doc->setTitle("Tedetis : module actes");

$doc->buildMenu($me);

// Initialisation de variables
$trans = new ActesTransaction();
$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();
$transTypes = $trans->get("transactionTypes");
$transTypes["0"] = "Tous les types";
$status = ActesTransaction :: getStatusList();
$status["10"] = "En cours";
$status["all"] = "Tous les états";
//

$html = "<div id=\"content\">\n";
$html .= "<h1>ACTES - Dématèrialisation du contrôle de légalité</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('filtering_area');\">Filtrage</h2>\n";
$html .= "<div id=\"filtering_area\" style=\"display: block;\">\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/index.php\" method=\"get\">\n";
$html .= "<table>\n";

if ($ftype != "0" && empty($ftype)) {
  $ftype = "1";
}

# Le statut par défaut est "En cours" (10)
if ($fstatus != "10" && $fstatus != "all" && ! is_numeric($fstatus)) {
  $fstatus = "10";
}

$html .= "<tr>\n";
$html .= "<td class=\"title\">Type de transaction&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("type", $transTypes, $ftype) . "</td>\n";
$html .= "<td class=\"title\">Nature d'actes&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("nature", $transNatures, $fnature) . "</td>\n";

$html .= "</tr>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">état&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("status", $status, $fstatus) . "</td>\n";
$html .= "<td class=\"title\">Le numéro contient&nbsp;:\n</td>";
$html .= "<td class=\"value\"><input type=\"text\" name=\"num\" size=\"20\" maxlength=\"25\"";

if (strlen($fnum) > 0) {
  $html .= " value=\"" . $fnum . "\"";
}



$html .= " /></td>\n";

$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "<td class=\"title\">Date de postage minimale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"min_submission_date\" name=\"min_submission_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmin_submission_date) . "\"/>\n";
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
  // On définit une date par défaut pour accelerer les appels...
   // Il faudra trouver la source réelle du problème...
  $fmin_submission_date='1977-01-01';
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_submission_date_calendar\"></div></td>\n";

$html .= "<td class=\"title\">Date d'acquittement minimale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"min_ack_date\" name=\"min_ack_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmin_ack_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_min_ack_date = new DatePicker('min_ack_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_min_ack_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_min_ack_date.toggleDatePicker(); return false;\">";

if ($fmin_ack_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_ack_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_ack_date_calendar\"></div></td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "<td class=\"title\">Date de postage maximale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"max_submission_date\" name=\"max_submission_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmax_submission_date) . "\"/>\n";
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

$html .= "<td class=\"title\">Date d'acquittement maximale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"max_ack_date\" name=\"max_ack_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmax_ack_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_max_ack_date = new DatePicker('max_ack_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_max_ack_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_max_ack_date.toggleDatePicker(); return false;\">";

if ($fmax_ack_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_ack_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_max_ack_date_calendar\"></div></td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";

$colspan = 4;
if ($me->isSuper()) {
  $html .= "<td class=\"title\">Collectivité&nbsp;:</td>\n";
  $cond = " ORDER BY authorities.name ASC";
  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("authority", Authority :: getAuthoritiesIdName($cond), $fauthority) . "</td>\n";

  $colspan = 2;
}

$html .= "<td colspan=\"" . $colspan . "\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "<td colspan=\"" . $colspan . "\"><a href=\"" . WEBSITE_SSL . "/modules/actes/index.php\" class=\"bouton\">Remise&nbsp;à &nbsp;zéro</a></td>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</form>\n";
$html .= "</div>\n";

if (!$me->isSuper() && $me->canEdit($module->get('name'))) {
  $html .= "<div id=\"actions_area\">\n";
  $html .= "<h2>Actions</h2>\n";
  if ($module->getParam("paper") == "on") {
    $html .= "<p>Le système est actuellement en mode &nbsp;papier&nbsp;. Dans ce mode il est impossible de créer de nouvelle transaction. Les transferts doivent se faire par les moyens classiques (non dématèrialisés).</p>\n";
  } else {
    $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_add.php\" class=\"bouton\">Créer une transaction</a>\n";
    $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_import.php\" class=\"bouton\">Importer une enveloppe</a>\n";
    $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_handle.php\" class=\"bouton\">Traitement par lots</a>\n";
  }
  $html .= "</div>\n";
}
$filter = array ();
// Construction chaîne de filtrage
if ($me->isSuper()) { // Le super utilisateur voit toutes les collectivités
  if (isset ($fauthority) && strlen($fauthority) > 0) {
    $filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
  }
}
elseif ($me->isAdmin()) { // Un admin d'une collectivité ne voit que les transactions de sa collectivité
  $filter[] .= "users.authority_id='" . $me->get("authority_id") . "'";
} else {
  // Un utilisateur ne voit que ses propres transactions ou de ces collègues de services....
  $serviceUser = new ServiceUser(DatabasePool::getInstance());
  $collegues = $serviceUser->getMesCollegues($me->getId());
  $id_col = $me->getId();
  foreach($collegues as $info){
  	$id_col .=  ", " . $info['id_user'];
  }
  
  $filter[] .= "actes_envelopes.user_id IN ($id_col)";
  
}


if (isset ($fnature) && is_numeric($fnature)) {
  $filter[] .= "actes_transactions.nature_code='" . addslashes($fnature) . "'";
}

if (isset ($ftype) && is_numeric($ftype) && $ftype > 0) {
  $filter[] .= "actes_transactions.type='" . addslashes($ftype) . "'";
}

if (isset ($fstatus) && is_numeric($fstatus)) {
  if ($fstatus == "10") {
    // Le statut 10 signifie les transactions en cours
    
  	//modifié par HTan, pour bug 190=> lenteur de la plateforme.
  	// je supprimer date = (SELECT MAX(date) FROM actes_transactions_workflow WHERE transaction_id = atw.transaction_id) 
  	// parce que:
  	// 		1. il a propose de faire ca.
  	//    2. les transaction_id est un autoinc, donc; le plus recent  a forcment une date MAX. (date est un timestamp).  
    $filter[] .= "(SELECT status_id FROM actes_transactions_workflow atw WHERE 
										atw.transaction_id=actes_transactions.id ORDER BY atw.id DESC LIMIT 1) IN (1, 2, 3, 4)";
  } else {
    $filter[] .= "(SELECT status_id FROM actes_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM actes_transactions_workflow WHERE transaction_id = atw.transaction_id) AND atw.transaction_id=actes_transactions.id ORDER BY atw.id DESC LIMIT 1) = " . addslashes($fstatus);
  }
}

if (isset ($fnum) && !empty ($fnum)) {
	 $filter[] .= "actes_transactions.number LIKE '%" . addslashes($fnum) . "%'";
}

// On ajoute les filtres relatifs aux dates
if (isset ($fmin_submission_date) && !empty ($fmin_submission_date)) {
  $filter[] .= "(SELECT date FROM actes_transactions_workflow atw WHERE actes_transactions.id = atw.transaction_id AND ( atw.status_id = 1 OR atw.status_id = 7 ) ) >= '" . addslashes($fmin_submission_date) . "'";
}
if (isset ($fmax_submission_date) && !empty ($fmax_submission_date)) {
  $filter[] .= "(SELECT date FROM actes_transactions_workflow atw WHERE actes_transactions.id = atw.transaction_id AND ( atw.status_id = 1 OR atw.status_id = 7 ) ) <= '" . addslashes($fmax_submission_date) . "'";
}
if (isset ($fmin_ack_date) && !empty ($fmin_ack_date)) {
  $filter[] .= "(SELECT date FROM actes_transactions_workflow atw WHERE actes_transactions.id = atw.transaction_id AND atw.status_id = 4) >= '" . addslashes($fmin_ack_date) . "'";
}
if (isset ($fmax_ack_date) && !empty ($fmax_ack_date)) {
  $filter[] .= "(SELECT date FROM actes_transactions_workflow atw WHERE actes_transactions.id = atw.transaction_id AND atw.status_id = 4) <= '" . addslashes($fmax_ack_date) . "'";
}

//Permettait de palier le problème d'indexation
//$filter[] .= "(SELECT date FROM actes_transactions_workflow atw WHERE actes_transactions.id = atw.transaction_id AND atw.status_id = 1) >= '1977-01-01'";

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

$env = new ActesEnvelope();
if (isset ($_GET["type"] ))
        $envelopes = $env->getEnvelopesList($where);
$i = 0;

$html .= "<h2>Liste des enveloppes de transactions</h2>\n";

if (count($envelopes) > 0) {
  $html .= "<form id=\"div_chck\" onsubmit=\"return afficheWarning()\" action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_close.php\" method=\"post\">\n";
  $html .= "<div><a href=\"#tedetis\" onclick=\"javascript:show_all();\" title=\"Déplier toutes les enveloppes\">[&nbsp;Tout déplier&nbsp;]</a>\n";
  $html .= "<a href=\"#tedetis\" onclick=\"javascript:hide_all();\" title=\"Replier toutes les enveloppes\">[&nbsp;Tout replier&nbsp;]</a></div>\n";
  $html .= "<dl class=\"envelopes_list\">\n";

  foreach ($envelopes as $envelope) {
    $transactions = ActesEnvelope :: getTransactionsForEnvelope($envelope["id"]);

    if ($me->isAdmin()) {
      $owner = new User($envelope["user_id"]);
      $owner->init();
    }

    $sortWay = ($_GET["sortway"] == "asc") ? "desc" : "asc";

    $html .= "<dt><a href=\"#tedetis\" onclick=\"toggle_envelope_content(" . $i . ");\" id=\"expander_" . $i . "\" class=\"expander\">-</a> Enveloppe nÂ°";
    $html .= "<a href=\"" . Helpers :: getURLWithParam(array (
      "order" => "id",
      "sortway" => $sortWay
    )) . "\" title=\"Trier par identifiant\">" . $envelope["id"] . "</a> déposée le ";
    $html .= "<a href=\"" . Helpers :: getURLWithParam(array (
      "order" => "submission_date",
      "sortway" => $sortWay
    )) . "\" title=\"Trier par date de dépôt\">" . Helpers :: getDateFromBDDDate($envelope["submission_date"], true) . "</a> contenant " . count($transactions);
    $html .= (count($transactions) > 1) ? " transactions" : " transaction";
    
    if ($me->isSuper()) {
      $zeAuthority = new Authority($owner->get("authority_id"));
      $html .= " de la collectivité " . htmlspecialchars($zeAuthority->get("name"));
    } 
    
    $html .= "</dt>\n";
    $html .= "<dd id=\"envelope_content_" . $i . "\" class=\"envelope_content\" style=\"display: block\">\n";
    $html .= "<table class=\"transactions_list\">\n";
    $html .= " <tr>\n";
    $html .= "  <th>Sél.</th>\n";
    $html .= "  <th>Type de transaction</th>\n";
    $html .= "  <th>Numéro de l'acte</th>\n";
   	$html .= "  <th>Numéro Interne de l'acte</th>\n";
    $html .= "  <th>Objet</th>\n";
    $html .= "  <th>Nature</th>\n";
    $html .= "  <th>Etat</th>\n";
    //$html .= "  <th>Identifiant unique</th>\n";
    $html .= " <th>courrier ministère</th>";
    $html .= " <th>Suivie par</th>";
    $html .= "  <th>Actions</th>\n";
    $html .= " </tr>\n";

    foreach ($transactions as $transaction) {
      $transactionTypes = $transaction->get("transactionTypes");

      $html .= "<tr>\n";
      $html .= " <td>";
      if ($transaction->get("type") == 1 && $transaction->getCurrentStatus() == 4) {
        $html .= "<input type=\"checkbox\" name=\"liste_id[]\" value=\"";
        $html .= htmlspecialchars($transaction->getId()) . "\" id=\"checkbox";
        $html .= htmlspecialchars($transaction->getId()) . "\" />";
      } else {
        $html .= "&nbsp;";
      }
      $html .= "</td>\n";
      $html .= " <td>" . $transactionTypes[$transaction->get("type")] . "</td>\n";
      $html .= " <td>".$transaction->getId()."</td> \n";
      $html .= " <td>" . htmlspecialchars($transaction->get("number")) . "</td>\n";
      $html .= " <td class=\"long_field\">" . nl2br(htmlspecialchars(Helpers :: truncateString($transaction->get("subject")))) . "</td>\n";
      $html .= " <td>" . $transaction->get("nature_descr") . "</td>\n";
      $html .= " <td>" . $status[$transaction->getCurrentStatus()] . "</td>\n";
      //$html .= " <td>" . $transaction->get("unique_id") . "</td>\n";
      $html .= " <td>";
      
      foreach ($transaction->getCourrierInfo() as $id => $info){

      	
      	$html.="<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" .$id . "\"> " . 
      	 $transactionTypes[$info["type"]] ." (". $info["sens"] .") </a><br/>";
      }
      
      $html .= "</td>";
      $html .= "<td>".$envelope['givenname']." ".$envelope['name'] ."</td>";
      $html .= " <td>\n";
      
      
      
	  $html .= "   <a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $transaction->getId() . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Afficher le dÃ©tail\" /></a>";
    

	  if ($transaction->get("archive_url")) {
		$html .= "   <a href=\"" . $transaction->get("archive_url") . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/icone_archivage.png\" alt=\"image_archivage\" title=\"AccÃ©der Ã  l'archivage de cette transaction\" /></a>";
	  }
	  
	  $html .= " </td>\n";
      $html .= "</tr>\n";
    }

    $html .= "</table>\n";
    $html .= "</dd>\n";
    $i++;
  }

  $html .= "</dl>\n";
  $html .= "<div><a href=\"#tedetis\" onclick=\"GereChkbox('div_chck','1');\" title=\"Tout sélectionner\">[&nbsp;Tout sélectionner&nbsp;]</a>\n";
  $html .= "<a href=\"#tedetis\" onclick=\"GereChkbox('div_chck','0');\" title=\"Tout désélectionner\">[&nbsp;Tout desélectionner&nbsp;]</a>\n";
  $html .= "<a href=\"#tedetis\" onclick=\"GereChkbox('div_chck','2');\" title=\"Inverser la sélection\">[&nbsp;Inverser la sélection&nbsp;]</a></div>\n";

  // Cloture des transaction sélectionnées
  $html .= "<div class=\"action\">\n";
  $html .= "Passer les transactions sélectionnées en état&nbsp;<select name=\"status\"><option value=\"valid\">Validé</option><option value=\"invalid\">Refusé</option></select>";
  $html .= "<input type=\"submit\" class=\"submit_button\" value=\"Exécuter\"/>\n";
  $html .= "</div>\n</form>";
} else {
	if (!isset($_GET["type"]))
        $html .= "Remplissez les critères de filtrage pour afficher vos transactions.";
    else
  		$html .= "Pas de transaction trouvée correspondant aux critères de filtrage.";
}
$html .= "</div>\n";

$doc->buildPager($env);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>
