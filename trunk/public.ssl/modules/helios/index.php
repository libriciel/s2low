<?php

require_once( __DIR__ . "/../../../init/init-www-helios.php");

require_once (SITEROOT . '/class/helios/HeliosTransactionsListe.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

$recuperateur = new Recuperateur($_GET);


$sortWay =   $recuperateur->get("sortway","desc");
$order = $recuperateur->get('order','id');
$page_number = $recuperateur->getInt('page',1);
$taille_page =  $recuperateur->getInt('count',10);


$fmin_submission_date =  $recuperateur->get("min_submission_date");
$fmax_submission_date =  $recuperateur->get("max_submission_date");
$fmin_ack_date = $recuperateur->get("min_ack_date");
$fmax_ack_date =  $recuperateur->get("max_ack_date");


if (isset( $_GET['status']) && $_GET['status'] === '0'){
	$fstatus = 0;
} else {
	$fstatus =  $recuperateur->get("status",HeliosTransactionsListe::EN_COURS);
}

$fauthority = $recuperateur->get("authority");
$fnum =  $recuperateur->get("num");


$heliosTransactionsListe = new HeliosTransactionsListe($sqlQuery);

if ($droit->isSuperAdmin($userInfo) ) {
	$heliosTransactionsListe->setAuthority($fauthority);
}elseif ($droit->isAdmin($userInfo)){
	$heliosTransactionsListe->setAuthority($userInfo['authority_id']);
} else {
	$serviceUser = new ServiceUser(DatabasePool::getInstance());
	$collegues = $serviceUser->getMesCollegues($connexion->getId());
	$collegue[] = $connexion->getId();
	foreach($collegues as $info){
		$collegue[] =  $info['id_user'];
	}
	$heliosTransactionsListe->setUserId($collegue);
}
$heliosTransactionsListe->setOrder($order,$sortWay);
$heliosTransactionsListe->setPageNumber($page_number,$taille_page);
$heliosTransactionsListe->setDateMinSubmission($fmin_submission_date);
$heliosTransactionsListe->setDateMaxSubmission($fmax_submission_date);
$heliosTransactionsListe->setDateMinAck($fmin_ack_date);
$heliosTransactionsListe->setDateMaxAck($fmax_ack_date);
$heliosTransactionsListe->setStatus($fstatus);
$heliosTransactionsListe->setObjet($fnum);


$nb_transactions = $heliosTransactionsListe->getNbTransaction();



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


$envelopes = $heliosTransactionsListe->getAll();


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
  var msg = "Voulez-vous vraiment affecter les " + n + " transactions sélectionnés ? ";
  msg += "Cette action est non réversible et est sous votre entière responsabilité";
  return confirm(msg);
}
//]]>
</script>
EOJS;


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();
$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");

$doc->setTitle("Tedetis : module helios");

$doc->openContainer();

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number,$nb_transactions,$taille_page));

$doc->closeSideBar();


$doc->openContent();


ob_start();
?>
	<script type="text/javascript" src="/javascript/jfu/js/jquery.min.js"></script> 
	<script type="text/javascript" src="/javascript/zselect.js"></script>   
	<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>   
<?php 
$html = ob_get_contents();
ob_end_clean();

//deja HELIOS!!!!
$html .= "<h1>Helios - Dématérialisation de documents financiers</h1>\n";

  $html .= "<div id=\"actions_area\">\n";
  $html .= "<h2>Actions</h2>\n";
if (!$me->isSuper() && $me->canEdit($module->get('name'))) {
  if ($module->getParam("paper") == "on") {
    $html .= "<p>Le système est actuellement en mode &nbsp;papier&nbsp;. Dans ce mode il est impossible de créer de nouvelle transaction. Les transferts doivent se faire par les moyens classiques (non dématèrialisé).</p>\n";
  } else {
 $html .= "<a class=\"btn btn-primary\" href=\"" . WEBSITE_SSL . "/modules/helios/helios_fichier_import.php\" >Importer un fichier</a>\n";
	
  }

}
$html.="<a class=\"btn btn-primary\" href=\"".WEBSITE_SSL. "/modules/helios/helios_retour.php\" title=\"afficher la liste des réponses reçues\">Réponse d'Hélios</a>\n";
  $html .= "</div>\n";

$status = HeliosTransaction :: getStatusList();
$status["999"] = "En cours";
$status["all"] = "Tous les états";

$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('filtering-area');\">Filtrage</h2>\n";
$html .= "<div id=\"filtering-area\">\n";
$html .= "<form  role=\"form\" class=\"form-horizontal\" action=\"" . WEBSITE_SSL . "/modules/helios/index.php\" method=\"get\">\n";

$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-3 control-label\" for=\"status\">Etat</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("status", $status, $fstatus) . "</div>\n";
$html .= "<label class=\"col-md-3 control-label\" for=\"filename-contain\">Le nom de fichier contient</label>";
$html .= "<div class=\"col-md-3\"><input id=\"filename-contain\" class=\"form-control\" type=\"text\" name=\"num\" size=\"20\" maxlength=\"25\"";

if (strlen($fnum) > 0) {
  $html .= " value=\"" . $fnum . "\"";
}


$html .= " /></div>\n</div>\n";

//les dates
//date minimale de postage
$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-3\" for=\"min_submission_date\">Date de postage minimale</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"min_submission_date\" name=\"min_submission_date\" type=\"hidden\" value=\"" . get_hecho($fmin_submission_date) . "\"/>\n";
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
  $html .= "Choisir une date";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_submission_date_calendar\"></div>\n</div>\n";

//la date minimale d'aquittement
$html .= "<label class=\"col-md-3\" for=\"min_ack_date\">Date d'acquittement minimale</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"min_ack_date\" name=\"min_ack_date\" type=\"hidden\" value=\"" . get_hecho($fmin_ack_date) . "\"/>\n";
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
  $html .= "Choisir une date";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_min_ack_date_calendar\"></div></div>\n";
$html .= "</div>\n";

//fin datele minimale...

//begin date maximale
$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-3\" for=\"max_submission_date\">Date de postage maximale</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"max_submission_date\" name=\"max_submission_date\" type=\"hidden\" value=\"" . get_hecho($fmax_submission_date) . "\"/>\n";
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
  $html .= "Choisir une date";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_max_submission_date_calendar\"></div></div>\n";

$html .= "<label class=\"col-md-3\" for=\"max_ack_date\">Date d'acquittement maximale</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"max_ack_date\" name=\"max_ack_date\" type=\"hidden\" value=\"" . get_hecho($fmax_ack_date) . "\"/>\n";
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
  $html .= "Choisir une date";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_max_ack_date_calendar\"></div></div>\n";
$html .= "</div>\n";
//END date maximale

//collectivité  pour superuser

if ($me->isGroupAdminOrSuper()) {

  $html .= "<div class=\"form-group\"><label for=\"authority\" class=\"col-md-3 control-label\">Collectivité</label>\n";
  $cond = " ORDER BY authorities.name ASC";
  
  $html .= "<div class=\"col-md-3\">" ; 
  

  ob_start(); ?>
	<select class="form-control zselect_authorities" name="authority">
		<option value="">Toutes</option>
		<?php foreach ( $me->getAllPossibleAuthority() as $key => $val) : ?>
			<option value="<?php hecho($key) ?>"  <?php echo (strcmp($key, $fauthority) == 0) ? " selected='selected'" : ""; ?>>
				<?php hecho($val)?> 
			</option>
		<?php endforeach; ?>
	</select>
  <?php 
  $html .= ob_get_contents();
  ob_end_clean();
  
  
  
  
  $html.="</div>\n</div>\n";

}
$html .= "<div class=\"form-group\">";
$html .= "    <button type=\"submit\" class=\"col-md-offset-3 col-md-3 btn btn-default\">Filtrer</button>";
$html .= "    <a href=\"" . WEBSITE_SSL . "/modules/helios/index.php\" class=\"col-md-offset-3 col-md-3 btn btn-default\">Remise à zéro</a>";
$html .= "</div>";

$html .= "</form>\n";
$html .= "</div>\n"; //filtrage aria


$i = 0;

$html .= "<h2>Liste des fichiers postés</h2>\n";

if (count($envelopes) > 0) {
      $owner = new User($envelopes[0]["user_id"]);
      $owner->init();

    $sortWay = (isset($_GET['sortway']) && ($_GET["sortway"] == "asc")) ? "desc" : "asc";

    
    $html .= '<form id="div_chck" onsubmit="return afficheWarning()" action="'.WEBSITE_SSL.'/modules/helios/helios_transac_close.php" method="post">';
    
    
    $html .= "<table class=\"transactions_list\">\n";
  

    $html .= "<div id=\"transaction-area\">\n";    
    $html .= "<table id=\"transaction-list\" class=\"data-table table table-striped\" summary=\"Ce tableau présente respectivement le nom de fichier, la date, le statut, l'auteur et un lien vers les actions disponibles de chaque fichier Helios posté\">\n";
    $html .= " <caption>Liste des fichiers Helios postés en fonction des choix de filtrage</caption>\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";

    $html .= "<th>Sél.</th>\n";
    $html .= "  <th id=\"filename\">Nom de fichier</th>\n";
    $html .= "  <th id=\"date\">Date de postage</th>\n";
    $html .= "  <th id=\"status\">Etat actuel</th>\n";
    $html .= "  <th id=\"authority-name\">Suivie par</th>\n";
    if ($me->isGroupAdminOrSuper()) {
		$html .= "  <th>Collectivité</th>\n";
    }
    $html .= "  <th id=\"action\">Actions</th>\n";
    
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";

$sel_ok = array();
 foreach ($envelopes as $envelope) {
  
      $transaction_id=$envelope["id"];
      
      $html .= "<tr><td>\n";
		if (in_array($envelope['last_status_id'],array(8,13))) {
			$html .= '<input type="checkbox" name="liste_id[]" value="' .
						get_hecho($envelope['id']) .
						'" id="checkbox'.$envelope['id'].'" />';
			$sel_ok[$envelope['last_status_id']] = true;
		} else {
			$html .="&nbsp;";
		}
 		$html .="</td>";
      $html .= " <td headers=\"filename\">" . $envelope["filename"]. "</td>\n";
      $html .= " <td headers=\"date\">" . Helpers::getDateFromBDDDate($envelope['submission_date'], true) ."</td>\n";
      $html .= " <td headers=\"status\">" . $envelope['message'] . "</td>\n";
      $html .= " <td headers=\"authority-name\">" . $envelope['givenname'] ." ". $envelope['name'] . "</td>\n";
 		if ($me->isGroupAdminOrSuper()) {
			$html .= "  <td>". $envelope['authority_name'] ."</td>\n";
    	}
      $html .= " <td headers=\"action\"><a href=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" .$envelope["id"]. "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Afficher le détail\" /></a></td>\n";
      $html .= "</tr>\n";
    

    $i++;
  }

    $html .= "</tbody>\n";
    $html .= "</table>\n";
   
    if (isset($sel_ok[8])){
    	$html.= "<input type='submit' class='btn btn-default' value='Envoyer la séléction au SAE'/>";
    }
    
    $html.="</form><br/><br/>";
    
    if (isset($sel_ok[13])){
    ob_start();
    ?>	 
    		
    
			       		<form id='form-sign' action="<?php echo WEBSITE_SSL ?>/modules/helios/helios_batch_sign.php" method="post">
                   			<input id='signer_button' type='submit' class='btn btn-default' value="Signer les transactions sélectionnées">
                   		</form>
                   		<script type='text/javascript'>
                   		$(document).ready(function() {              
                   			$("#signer_button").click(function(){
								$("input:checkbox:checked").each(function() {
									$("#form-sign").append("<input type='hidden' name='liste_id[]' value='" + $(this).val() + "' />");
								});
								$("#form-sign").submit();
								return false;
							})
                   		});
                   		</script>
                   		
                
<?php 
	$html .= ob_get_contents();
	ob_end_clean();


    }
    $html .= "</div>\n";
    
} else {
  $html .= "Pas de transaction trouvée correspondant aux critères de filtrage.";
}

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
