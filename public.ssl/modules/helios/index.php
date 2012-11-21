<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');


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
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}


$serviceUser = new ServiceUser(DatabasePool::getInstance());
$collegues = $serviceUser->getMesCollegues($me->getId());
$collegue[] = $me->getId();
foreach($collegues as $info){
	$collegue[] =  $info['id_user'];
}


$fstatus = Helpers :: getVarFromGet("status");
$fmin_submission_date = Helpers :: getVarFromGet("min_submission_date");
$fmax_submission_date = Helpers :: getVarFromGet("max_submission_date");
$fmin_ack_date = Helpers :: getVarFromGet("min_ack_date");
$fmax_ack_date = Helpers :: getVarFromGet("max_ack_date");
$fnum =Helpers :: getVarFromGet("num");
$fauthority = Helpers :: getVarFromGet("authority");


$doc = new HTMLLayout();

//JS

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
  var msg = "Voulez-vous vraiment affecter les " + n + " transactions sélectionnés ?\\n";
  msg += "Cette action est non réversible et est sous votre entière responsabilité";
  return confirm(msg);
}
//]]>
</script>
EOJS;
//---->JS

//!!!!ok am nevoie de JS
$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");

$doc->setTitle("Tedetis : module helios");

$doc->buildMenu($me);

//deja HELIOS!!!!
$html = "<div id=\"content\">\n";
$html .= "<h1>Helios - Dématérialisation de documents financiers</h1>\n";

$status = HeliosTransaction :: getStatusList();
$status["10"] = "En cours";
$status["all"] = "Tous les états";

//filtrage aria
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('filtering_area');\">Filtrage</h2>\n";
$html .= "<div id=\"filtering_area\" style=\"display: block;\">\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/index.php\" method=\"get\">\n";

//temp
//$html .="<br> <hr> ".phpinfo();


$html .= "<table>\n";

//fstatus: status selecté
if ($fstatus != "10" && empty ($fstatus)) {
  $fstatus = "10";
}


$html .= "<tr>\n";

$html .= "<td class=\"title\">Etat&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("status", $status, $fstatus) . "</td>\n";
$html .= "<td class=\"title\">Le nom de fichier contient&nbsp;:\n</td>";
$html .= "<td class=\"value\"><input type=\"text\" name=\"num\" size=\"20\" maxlength=\"25\"";

//$fnum: le nom du fichier contient...
if (strlen($fnum) > 0) {
  $html .= " value=\"" . $fnum . "\"";
}


$html .= " /></td>\n";

//des autres options...

//les dates
$html .= "<tr>\n";
//date minimale de postage
$html .= "<td class=\"title\">Date de postage minimale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"min_submission_date\" name=\"min_submission_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmin_submission_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_min_submission_date = new DatePicker('min_submission_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_min_submission_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_min_submission_date.toggleDatePicker(); return false;\">";

if ($fmin_submission_date) {
  //setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_submission_date)));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_submission_date_calendar\"></div></td>\n";

//la date minimale d'aquittement
$html .= "<td class=\"title\">Date d'acquittement minimale&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input id=\"min_ack_date\" name=\"min_ack_date\" type=\"hidden\" value=\"" . htmlspecialchars($fmin_ack_date) . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_min_ack_date = new DatePicker('min_ack_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_min_ack_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_min_ack_date.toggleDatePicker(); return false;\">";

if ($fmin_ack_date) {
  //setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_ack_date)));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_ack_date_calendar\"></div></td>\n";
$html .= "</tr>\n";

//fin datele minimale...

//begin date maximale
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
  //setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_submission_date)));
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
  //setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_ack_date)));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_max_ack_date_calendar\"></div></td>\n";
$html .= "</tr>\n";
//END date maximale


$html .= "<tr>\n";

//collectivité  pour superuser
$colspan = 4;
if ($me->isGroupAdminOrSuper()) {
  $html .= "<td class=\"title\">Collectivité&nbsp;:</td>\n";
  $cond = " ORDER BY authorities.name ASC";
  
  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("authority", $me->getAllPossibleAuthority(), $fauthority) . "</td>\n";

  $colspan = 2;
}

$html .= "<td colspan=\"" . $colspan . "\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "<td colspan=\"" . $colspan . "\"><a href=\"" . WEBSITE_SSL . "/modules/helios/index.php\" class=\"bouton\">Remise&nbsp;à&nbsp;zéro</a></td>\n";
$html .= "</tr>\n";

$html .= "</table>\n";
$html .= "</form>\n";
$html .= "</div>\n"; //filtrage aria


  $html .= "<div id=\"actions_area\">\n";
  $html .= "<h2>Actions</h2>\n";
if (!$me->isSuper() && $me->canEdit($module->get('name'))) {
  if ($module->getParam("paper") == "on") {
    $html .= "<p>Le système est actuellement en mode &nbsp;papier&nbsp;. Dans ce mode il est impossible de créer de nouvelle transaction. Les transferts doivent se faire par les moyens classiques (non dématèrialisé).</p>\n";
  } else {
 $html .= "<a href=\"" . WEBSITE_SSL . "/modules/helios/helios_fichier_import.php\" class=\"bouton\">Importer un fichier</a>\n";
	
  }

}
$html.="<a href=\"".WEBSITE_SSL. "/modules/helios/helios_retour.php\" class=\"bouton\" title=\"afficher la liste des réponses reçues\">Réponse d'Hélios</a>\n";
  $html .= "</div>\n";

$filter = array ();
// Construction chaine de filtrage
//if ($me->isGroupAdminOrSuper()) { // Le super utilisateur voit toutes les collectivité
if ($me->isSuper()){
   if (isset ($fauthority) && strlen($fauthority) > 0) {
    $filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
  }
} elseif($me->isGroupAdmin()){
	$all_authority_id = array_keys($me->getAllPossibleAuthority());	
  	if (isset ($fauthority) && strlen($fauthority) > 0) {
  		if (in_array($fauthority,$all_authority_id)){
    		$filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
  		} else {
  			$filter[] .=" 1 = 0 ";
  		}
  	} else {
  		$filter[] .= "users.authority_id IN (" . implode(',',$all_authority_id) . ")";
  	}
} elseif ($me->isAdmin()) { // Un admin d'une collectivité ne voit que les transactions de sa collectivité 
  $filter[] .= "users.authority_id='" . $me->get("authority_id") . "'";
} else {
  // Un utilisateur ne voit que ses propres transactions  
  //$filter[] .= "helios_transactions.user_id='" . $me->getId() . "'";
  $filter[] .= "helios_transactions.user_id IN (".implode(",",$collegue).")";
}

if (isset ($fstatus) && is_numeric($fstatus)) {
  if ($fstatus == "10") {
    // Le statut 10 signifie les transactions en cours
    //-1 = il y a des problème, just pour test dans plateform de ovh.
    $filter[] .= "(SELECT status_id FROM helios_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM helios_transactions_workflow WHERE transaction_id = atw.transaction_id) AND atw.transaction_id=helios_transactions.id ORDER BY atw.id DESC LIMIT 1) IN (1, 2, 3)";
  } else {
    $filter[] .= "(SELECT status_id FROM helios_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM helios_transactions_workflow WHERE transaction_id = atw.transaction_id) AND atw.transaction_id=helios_transactions.id ORDER BY atw.id DESC LIMIT 1) = " . addslashes($fstatus);
  }
}

if (isset ($fnum) && !empty ($fnum)) {
  $filter[] .= "helios_transactions.filename LIKE '%" . addslashes($fnum) . "%'";
}


// On ajoute les filtres relatifs aux dates
if (isset ($fmin_submission_date) && !empty ($fmin_submission_date)) {
  $filter[] .= "(SELECT date FROM helios_transactions_workflow atw WHERE helios_transactions.id = atw.transaction_id AND atw.status_id = 1) >= '" . addslashes($fmin_submission_date) . "'";
}
if (isset ($fmax_submission_date) && !empty ($fmax_submission_date)) {
  $filter[] .= "(SELECT date FROM helios_transactions_workflow atw WHERE helios_transactions.id = atw.transaction_id AND atw.status_id = 1) <= '" . addslashes($fmax_submission_date) . "'";
}
if (isset ($fmin_ack_date) && !empty ($fmin_ack_date)) {
  $filter[] .= "(SELECT date FROM helios_transactions_workflow atw WHERE helios_transactions.id = atw.transaction_id AND atw.status_id IN (4,6) LIMIT 1) >= '" . addslashes($fmin_ack_date) . "'";
}
if (isset ($fmax_ack_date) && !empty ($fmax_ack_date)) {
  $filter[] .= "(SELECT date FROM helios_transactions_workflow atw WHERE helios_transactions.id = atw.transaction_id AND atw.status_id IN (4,6) LIMIT 1) <= '" . addslashes($fmax_ack_date) . "'";
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}


$ht=new HeliosTransaction();
//$envelopes : en fait, des docs financiers..

 $envelopes = $ht->getDocumentList($where);


$i = 0;

$html .= "<h2>Liste des fichiers postés</h2>\n";


//
if (count($envelopes) > 0) {
      $owner = new User($envelopes[0]["user_id"]);
      $owner->init();

    $sortWay = (isset($_GET['sortway']) && ($_GET["sortway"] == "asc")) ? "desc" : "asc";
    
    $html .= "<table class=\"transactions_list\">\n";
  
    $html .= " <tr>\n";
    $html .= "  <th>Nom de fichier</th>\n";
    $html .= "  <th>Date de postage</th>\n";
    $html .= "  <th>Etat actuel</th>\n";
    $html .= "  <th>Suivie par</th>\n";
    if ($me->isGroupAdminOrSuper()) {
		$html .= "  <th>Collectivité</th>\n";
    }
    $html .= "  <th>Actions</th>\n";
    
    $html .= " </tr>\n";


 foreach ($envelopes as $envelope) {
  
      $transaction_id=$envelope["id"];
      $owner = new User($envelope["user_id"]);
      $owner->init();
      
      $html .= "<tr>\n";
 
 
      $html .= " <td>" . $envelope["filename"]. "</td>\n";
      $html .= " <td>" . Helpers::getDateFromBDDDate(HeliosTransactionWorkflow::getCurrentDate($transaction_id), true) ."</td>\n";
      $html .= " <td>" . HeliosTransactionWorkflow::getCurrentStatus($transaction_id) . "</td>\n";
      $html .= " <td>" . $owner->getPrettyName() . "</td>\n";
 		if ($me->isGroupAdminOrSuper()) {
			$html .= "  <td>". $envelope['authority_name'] ."</td>\n";
    	}
      $html .= " <td><a href=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" .$envelope["id"]. "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Afficher le détail\" /></a></td>\n";
      $html .= "</tr>\n";
    

    $i++;
  }

    $html .= "</table>\n";
  
} else {
  $html .= "Pas de transaction trouvée correspondant aux critères de filtrage.";
}
$html .= "</div>\n";




$doc->buildPager($ht);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>