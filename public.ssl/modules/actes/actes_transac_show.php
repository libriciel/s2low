<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$actionHtml = "";

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$id = Helpers :: getVarFromGet("id");
if (empty($id) ){
	$_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
	header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
	exit ();
}


$trans = new ActesTransaction();
$trans->setId($id);
if ( ! $trans->init()) {
    $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
    header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
    exit ();
}

$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$owner = new User($envelope->get("user_id"));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser,"actes");

if ( ! $permission->canView($me,$owner)){
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
	exit ();
}

$myAuthority = new Authority($me->get("authority_id"));
$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

$status_list = ActesTransaction :: getStatusList();
$workflow = $trans->fetchWorkflow();


$transactionTypes = $trans->get("transactionTypes") ;
$transStatus = $trans->getCurrentStatus();

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : visualisation d'une transaction");

$doc->buildMenu($me);


$html .= "<div id=\"content\">\n";

$html .= "<p style='text-align:center'><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"bouton\">Retour liste transactions</a></p>\n";

$html .= "<h2>Visualisation d'une transaction</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= $doc->getHTMLArrayline("Type de transaction", $transactionTypes[$trans->get("type")]);
if ($trans->get("type_reponse")){
	$html .= $doc->getHTMLArrayline("Type de réponse",ActesTransaction::getTypeReponse($trans->get("type"),$trans->get("type_reponse")));
}

$html .= $doc->getHTMLArrayline("Dossier suivie par", htmlspecialchars($owner->get("givenname") . " " . $owner->get("name")));

// Contenu différent en fonction du type de transaction
switch ($trans->get("type")) {
  case 1 :
    $html .= $doc->getHTMLArrayline("Nature de l'acte", $transNatures[$trans->get("nature_code")]);
    $html .= $doc->getHTMLArrayline("Numéro de l'acte", htmlspecialchars($trans->get("number")));
    $html .= $doc->getHTMLArrayline("Date de la décision", Helpers :: getDateFromBDDDate($trans->get("decision_date")));
    $html .= $doc->getHTMLArrayline("Objet", nl2br(htmlspecialchars($trans->get("subject"))));
    $html .= $doc->getHTMLArrayline("Classification matières/sous-matières", htmlspecialchars($trans->get("classification")));
    $html .= $doc->getHTMLArrayline("Identifiant unique", htmlspecialchars($trans->get("unique_id")));

    $arch_url = $trans->get("archive_url");

    if (!empty ($arch_url)) {
      $url = "<a href=\"" . $trans->get("archive_url") . "\">" . htmlspecialchars($trans->get("archive_url")) . "</a>";
    } else {
      $url = "Non définie";
    }
    $html .= $doc->getHTMLArrayline("URL d'archivage", $url);
    if ($trans->get("sae_transfer_identifier")) {
		$html .= $doc->getHTMLArrayline("Identifiant de transfert d'archivage", htmlspecialchars($trans->get("sae_transfer_identifier")));
    }
    

    if ($trans->get("broadcasted") == 't')
      $notification = "Notifiée à " . $trans->get("broadcast_emails");
    else
      $notification = "Non notifiée";
    $html .= $doc->getHTMLArrayline("Notification", $notification);
    break;
    
  case 2 :
  case 3 :
  case 4 :
  case 5 : 
    $related_trans = new ActesTransaction($trans->get("related_transaction_id"));
    $related_trans->init();
    
    
    $html .= $doc->getHTMLArrayline("Date de réception du document  ", $related_trans->get("decision_date"));
    
    if ($related_trans->get("related_transaction_id")){
		    	
    	$files = $related_trans->fetchFilesList();
		foreach ($files as $file) {
    		$html .= $doc->getHTMLArrayline("Document reçu   ",
    			"<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">" . $file["posted_filename"] . "</a>"
    	 	);
		}
    	
    	$related_trans = new ActesTransaction($related_trans->get("related_transaction_id"));
        $related_trans->init();	
		$html .= $doc->getHTMLArrayline("Date d'envoi   ", $related_trans->get("decision_date"));
		$html .= $doc->getHTMLArrayline("Acte ", "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("number") . "</a>");		
		
    } else {
    	  $html .= $doc->getHTMLArrayline("Acte ", "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("number") . "</a>");
    }
	
    
    
    break;
    
  case 6 :
    $related_trans = new ActesTransaction($trans->get("related_transaction_id"));
    $related_trans->init();

    $html .= $doc->getHTMLArrayline("Acte à annuler", "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("unique_id") . "</a>");
    break;

  case 7 :

    break;
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

// Fichier archive présent ou non ?
$status = $trans->getCurrentStatus();
$archiveDeleted = false;
/*if ($trans->get("type") != 1 || $status > 4 || $status <= 0) {
  $archiveDeleted = true;
}*/
if ($trans->get("type") == 6 ||
 	$trans->get("type") == 7 ||
 	 $status <= 0 || 
 	 $status == 5 ||
 	 $status == 6 ||
 	 $status == 16 
 	 
 	 ) {
	$archiveDeleted = true;
}
// Fichiers contenus dans la transaction
$html .= "<h3>Fichiers contenus dans l'archive transmise (";

$archiveName = htmlspecialchars(basename($envelope->get("file_path")));
$html .= ($archiveDeleted) ? $archiveName : "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?env=" . $trans->get("envelope_id") . "\" title=\"Télécharger l'archive .tar.gz\">" . $archiveName . "</a>";

$html .= ")</h3>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"file_list\">\n";
$html .= " <tr>\n";
$html .= "  <th>Fichier</th>\n";
$html .= "  <th>Type de fichier</th>\n";
$html .= "  <th>Taille du fichier</th>\n";
$html .= " </tr>\n";

$files = $trans->fetchFilesList();

if (is_array($files)) {
  foreach ($files as $file) {
    $html .= " <tr>\n";
    $html .= "  <td class=\"long_field\">";

    $html .= "<dl>\n";

    if (strlen($file["posted_filename"]) > 0) {
      $html .= "<dt>Nom original&nbsp;:</dt>\n";
      $html .= "<dd>";

      if ($archiveDeleted){
		$html .=  $file["posted_filename"];
      } else {
            $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">" . $file["posted_filename"] . "</a>" ; 
            $html .= "&nbsp;&nbsp;";
            
            
            //TODO Horrible hack....
            foreach ($workflow as $stage) {
            
            	if ($stage['status_id'] == 4){
            		
            		if (preg_match("#\.pdf$#",$file["posted_filename"])||preg_match("#\.PDF$#",$file["posted_filename"])) {
	            		$html.= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?tampon=true&file=" . $file["id"] . "\" title=\"Télécharger le fichier avec tampon\">";
						$html.="<img alt=\"pdf\" src=\"../../custom/images/pdf.gif\"></a>";
            		}
            	}
            }
			
			$html .= "</dd>";
      }
     }

    $html .= "<dt>Nom métier&nbsp;:</dt>\n";
    $html .= "<dd>";

    if (strlen($file["posted_filename"]) <= 0 && !$archiveDeleted) {
      $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">" . $file["name"] . "</a>";
    } else {
      $html .= $file["name"];
    }

    $html .= "</dd>";
    $html .= "</dl>\n";
    $html .= "</td>\n";
    $html .= "  <td>" . $file["mimetype"] . "</td>\n";
    $html .= "  <td>" . $file["size"] . " octets</td>\n";
    $html .= " </tr>\n";
  }
} else {
  $html .= " <tr>\n";
  $html .= "  <td colspan=\"3\">Pas de fichier trouvé</td>";
  $html .= " </tr>\n";
}

$html .= "</table>\n";
$html .= "</div>\n";
// Affichage du Workflow

$html .= "<h3>Cycle de vie de la transaction</h3>\n";

if (count($workflow) > 0) {
  $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"workflow_list\">\n";
  $html .= " <tr>\n";
  $html .= "  <th>État</th>\n";
  $html .= "  <th>Date</th>\n";
  $html .= "  <th>Message</th>\n";
  $html .= " </tr>\n";

     // modifié par TH 18-04-2008 ajouter un petit icon de pdf lien ver le ficher .pdf qund on est bien sur 
    // etat="aquitement reçu.
  	$create_pdf_html ="<a href=\"actes_create_pdf.php?trans_id=".$id."&user_id=".$me->getId()."\">";
	$create_pdf_html.="<img alt=\"pdf\" src=\"../../custom/images/pdf.gif\"></a>";
	
	//---fin de modification
	
  foreach ($workflow as $stage) {
    $html .= "<tr>\n";

    //modified by TH 18-04-2008
	//---------begin

	//$id = Helpers :: getVarFromGet("id"); with this identifier, we can easily findout all the infomation in the acte.

	
   if ($stage["status_id"]==4 )
    {
    	
    	$html .= "  <td>" . $status_list[$stage["status_id"]].$create_pdf_html."</td>\n";
    }
    else
    	$html .= "  <td>" . $status_list[$stage["status_id"]] . "</td>\n";
    	
	//---------end
 
    $html .= "  <td>" . Helpers :: getDateFromBDDDate($stage["date"], true) . "</td>\n";
    $html .= "  <td class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
    $html .= " </tr>\n";
  }

  $html .= "</table>\n";
  $html .= "</div>\n";
} else {
  $html .= "Le cycle de vie est vide pour cette transaction.\n";
}

$courrier = $trans->getCourrierInfo();
if (count($courrier) != 0){
	$html .= "<h3>Document reçu relatif à l'acte</h3>\n";
	 $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"workflow_list\">\n";
  $html .= " <tr>\n";
  $html .= "  <th>Type</th>\n";
  $html .= "  <th>sens</th>\n";
  $html .= "  <th>action</th>\n";
  $html .= " </tr>\n";
	foreach ($courrier as $id=>$info) {
		  $html .= " <tr>\n";
  		$html .= "  <td>".$transactionTypes[$info["type"]]."</td>\n";
  		$html .= "  <td>".$info["sens"]."</td>\n";
  		$html .= "  <td>
  		<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" .$id . "\"><img alt=\"pdf\" src=\"../../custom/images/erreur.png\"> </a></td>\n";
  		$html .= " </tr>\n";
	}
	
  $html .= "</table>\n";
  $html .= "</div>\n";
	
}


if (!$me->isSuper() && $me->canEdit($module->get("name")) &&  $permission->canWrite($me,$owner) ) {
  $actionHtml = "";

  // Formulaire de notification a posteriori
  // Affichés quand la transaction a été acquittée par le MIAT et non notifiée
  if ($trans->get("type") == 1 && $transStatus == 4 && $trans->get("broadcasted") == 'f') {
    // adresses emails de diffusion
    

    $org = new Authority($me->get("authority_id"));
    $defaultbroadcast_email = $org->get("default_broadcast_email");
    if ($defaultbroadcast_email != NULL)
        $defaultbroadcast_email = explode(",", $defaultbroadcast_email);


    $broadcast_email = ACTES_COMMON_BROADCAST_EMAILS . "," . $org->get("broadcast_email");
    $broadcast_email = explode(",", $broadcast_email);

    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_notify.php\" method=\"post\">\n";
    $actionHtml .= "<p><input type=\"submit\" class=\"submit_button\" value=\"Notifier la transaction\" /><br/>\n";
    $actionHtml .= "      <label>Emission des documents sources : <input type=\"checkbox\" class=\"checkbox\" name=\"send_sources\" checked='checked' /></label><br/>\n";

	foreach ($defaultbroadcast_email as $email) {
    	$checked = 'checked="checked" disabled="disabled"';
    	if ($email != "" && $email != NULL)
        	$actionHtml .= "      &nbsp;&nbsp;&nbsp;&nbsp;<label><em><input type=\"checkbox\" class=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</em></label><br />\n";
	}

    foreach ($broadcast_email as $email) {
        $checked = '';
      if ($email != "" && $email != NULL)
        $actionHtml .= "      &nbsp;&nbsp;&nbsp;&nbsp;<label><em><input type=\"checkbox\" class=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</em></label><br />\n";
    }

    
    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "</p></form>\n";
    $actionHtml .= "</div>\n";
  }
}

//On vérifie qu'il n'y a pas de demande d'annulation en cours
if (!$trans->hasPendingCancelTrans()) {
    // Boutons de cloture de la transaction
    // Affichés quand la transaction a été acquittée par le MIAT
    if ($trans->get("type") == 1 && $transStatus == 4) {
  
        if ($trans->canValidate()) {
		$actionHtml .= "<div class=\"action\">\n";
		$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ?\nCette action est non réversible et est sous votre entière responsabilité.' . "');\" method=\"post\">\n";
		$actionHtml .= "<p>Acte validé par le ministère&nbsp;:&nbsp;";
		$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
		$actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"valid\" />\n";
		$actionHtml .= "<input type=\"submit\" class=\"submit_button\" value=\"Passer la transaction en état «&nbsp;Validée&nbsp;»\" />\n";
		$actionHtml .= "</p></form>\n";
		$actionHtml .= "</div>\n";
	}//fin if verfiie canValidate
  
        $actionHtml .= "<div class=\"action\">\n";
        $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ?\nCette action est non réversible et est sous votre entière responsabilité.' . "')\" method=\"post\">\n";
        $actionHtml .= "<p>Acte refusé par le ministère&nbsp;:&nbsp;";
        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"invalid\" />\n";
        $actionHtml .= "<input type=\"submit\" class=\"submit_button\" value=\"Passer la transaction en état «&nbsp;Refusée&nbsp;»\" />\n";
        $actionHtml .= "</p></form>\n";
        $actionHtml .= "</div>\n";
    }//fin if qui verifie type == 1 et status == 4

    $actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
    $transactionsInfo = $actesTransactionsSQL->getInfo($trans->getId());
    $authoritySQL = new AuthoritySQL($sqlQuery);
    $authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);

    if ($trans->get("type") == 1 && in_array($transStatus,array(4,14)) && $trans->canValidate() && $authorityInfo['sae_wsdl']) {
	  $actionHtml .= "<div class=\"action\">\n";
          $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_archiver.php\"  method=\"post\">\n";
          $actionHtml .= "<p>Archivage SEDA&nbsp;:&nbsp;";
          $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
          $actionHtml .= "<input type=\"submit\" class=\"submit_button\" value=\"Versement manuel\" />\n";
          $actionHtml .= "</p></form>\n";
  
          if (MODE == "dev"){
              $actionHtml .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_testbordereau.php?id=".$trans->getId()."\" >(mode_dev) Voir le bordereau</a>\n";
          }//fin if verifie MODE == dev
  
          $actionHtml .= "</div>\n";
     }//fin if type == 1 , status = 4 ou 14, transaction canvalidate et configuration pour le sae
}//fin if qui verifie qu'il n'y a pas d'annulation en cours

// Bouton d'annulation en fonction du type et de l'état
// Doit être une transaction de transmission d'acte
// et être dans l'état Acquittement reçu
if ($trans->get("type") == 1 && $transStatus == 4) {
  $actionHtml .= "<div class=\"action\">\n";
  if (!$trans->hasPendingCancelTrans()) {
    if ($module->getParam("paper") == "on") {
      $actionHtml .= "<label>Annulation&nbsp;:&nbsp;Mode «&nbsp;papier&nbsp;» actif. Pas d'annulation possible.</label>";
    } else {
      $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_cancel.php\" onsubmit=\"return confirm('Voulez-vous vraiment annuler cette transaction ?')\" method=\"post\">\n";
      $actionHtml .= "<p>Annulation&nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Annuler cette transaction\" class=\"bouton-danger\" />\n";
      $actionHtml .= "</p></form>\n";
    }
  } else {
    $actionHtml .= "Une demande d'annulation est en cours pour cet acte.";
  }
  $actionHtml .= "</div>\n";
}

// Boutons de réponse à un courrier

if (($transStatus == 7 || $transStatus == 8) && $trans->get("type") != 5) {
      $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_repondre.php\" method=\"post\">\n";
      $actionHtml .= "<p>Répondre &nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Répondre à ce document\" class=\"submit_button\" />\n";
      $actionHtml .= "</p></form>\n";
}

if ($transStatus == 17){
	  $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_post_confirm.php\" method=\"post\">\n";
      $actionHtml .= "<p>Valider &nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Poster ce document\" class=\"submit_button\" />\n";
      $actionHtml .= "</p></form>\n";
}


if ($transStatus > 3) {
  $actionHtml .= "<div class=\"action\">\n";
  $actionHtml .= "Horodatage : <a onclick=\"window.open(this.href); return false;\" href=\"" . WEBSITE_SSL . "/common/logs_view.php?module=actes&amp;severity=a&amp;message=" . $trans->getId() . "\" title=\"Rechercher les logs relatifs à l'acte n°" . $trans->getId()  . " et sa signature\" >Rechercher les logs relatifs à l'acte</a>\n";
  $actionHtml .= "</div>\n";
}

if ($me->isSuper()) {
       $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_delete.php\" onsubmit=\"return confirm('Cette transaction sera héradiqué DEFINITIVEMENT de la base sans espoir de retour?')\" method=\"post\">\n";
      $actionHtml .= "<p>Effacer de la base de donnée (TRES DANGEREUX)&nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Effacer de la base de données\" class=\"bouton-danger\" />\n";
      $actionHtml .= "</p></form>\n";
}

if (isset($actionHtml) && $permission->canWrite($me,$owner)) {
  $html .= "<h3>Actions</h3>\n";
  $html .= $actionHtml;
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

