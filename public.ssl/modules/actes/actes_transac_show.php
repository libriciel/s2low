<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

require_once( __DIR__ . "/../../../init/init-www-actes.php");


$actesTypePJSQL = $objectInstancier->get('ActesTypePJSQL');

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

$id = intval(Helpers :: getVarFromGet("id"));
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


$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"".WEBSITE_SSL."/custom/styles/date-picker.css\" />");
$doc->addHeader("<script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.min.js\"></script>");
$doc->addHeader("<script src=\"".WEBSITE_SSL."/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");



$doc->setTitle("Tedetis : visualisation d'une transaction");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<p id=\"back-transaction-btn\"><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";

$html .= "<h2>Visualisation d'une transaction</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Type de transaction", $transactionTypes[$trans->get("type")]);
if ($trans->get("type_reponse")){
	$html .= $doc->getHTMLArrayline("Type de réponse",ActesTransaction::getTypeReponse($trans->get("type"),$trans->get("type_reponse")));
}

$html .= $doc->getHTMLArrayline("Dossier suivi par", get_hecho($owner->get("givenname") . " " . $owner->get("name")));

// Contenu différent en fonction du type de transaction
switch ($trans->get("type")) {
  case 1 :
    $html .= $doc->getHTMLArrayline("Nature de l'acte", $transNatures[$trans->get("nature_code")]);
    $html .= $doc->getHTMLArrayline("Numéro de l'acte", get_hecho($trans->get("number")));
    $html .= $doc->getHTMLArrayline("Date de la décision", Helpers :: getDateFromBDDDate($trans->get("decision_date")));
    $html .= $doc->getHTMLArrayline("Objet", nl2br(get_hecho($trans->get("subject"))));
    $html .= $doc->getHTMLArrayline("Documents papiers complémentaires",$trans->getDocumentPapier()?"OUI":"NON");

	$classification = get_hecho($trans->get("classification"));
	if(  $trans->get("classification_string")){
		$classification .= " - " .$trans->get("classification_string");
	}

    $html .= $doc->getHTMLArrayline("Classification matières/sous-matières",$classification);
    $html .= $doc->getHTMLArrayline("Identifiant unique", get_hecho($trans->get("unique_id")));

    $arch_url = $trans->get("archive_url");

    if (!empty ($arch_url)) {
      $url = "<a href=\"" . $trans->get("archive_url") . "\">" . get_hecho($trans->get("archive_url")) . "</a>";
    } else {
      $url = "Non définie";
    }
    $html .= $doc->getHTMLArrayline("URL d'archivage", $url);
    if ($trans->get("sae_transfer_identifier")) {
        $html .= $doc->getHTMLArrayline("Identifiant Pastell (archivage)", get_hecho($trans->get("sae_transfer_identifier")));
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
		$html .= $doc->getHTMLArrayline("Date de décision de l'acte initial   ", $related_trans->get("decision_date"));
		$html .= $doc->getHTMLArrayline("Acte initial", "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("number") . "</a>");

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

 	 $status == 5 ||
 	 $status == 6 ||
 	 $status == 16

 	 ) {
	$archiveDeleted = true;
}
// Fichiers contenus dans la transaction
$html .= "<h2>Fichiers contenus dans l'archive transmise </h2>";

$archiveName = get_hecho(basename($envelope->get("file_path")));

$html .= "<div class=\"data_table\">\n";
$files = $trans->fetchFilesList();

if (is_array($files)) {
$html .= "<table class=\"table table-bordered table-striped\">\n";
$html .= " <thead>\n";
$html .= " <tr>\n";
$html .= "  <th id=\"file\">Fichier</th>\n";
$html .= "  <th id=\"type\">Type de fichier</th>\n";
$html .= "  <th id=\"size\">Taille du fichier</th>\n";
$html .= " </tr>\n";
$html .= " </thead>\n";
$html .= " <tbody>\n";


  foreach ($files as $file_num => $file) {
    $html .= " <tr>\n";
    $html .= "  <td headers=\"file\" class=\"long_field\">";

    $html .= "<dl>\n";

    if (strlen($file["posted_filename"]) > 0) {
      $html .= "<dt>Nom original&nbsp;:</dt>\n";
      $html .= "<dd>";

      if ($archiveDeleted){
		$html .=  $file["posted_filename"];
      } else {
            $html .= $file["posted_filename"] . "<br/><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\"> [Télécharger le fichier original]</a>" ;
            $html .= "&nbsp;&nbsp;";


            //TODO Horrible hack....
            foreach ($workflow as  $stage) {

            	if ($stage['status_id'] == 4){

            		if (preg_match("/\.pdf$/i",$file["posted_filename"])) {

						$name="tampon_date";
						$date = date("Y-m-d");

						ob_start();
						?>
						<br/>

						<a class='telecharger_tampon' href="/modules/actes/actes_download_file.php?tampon=true&file=<?php echo $file["id"] ?>" title="Télécharger le fichier avec tampon">
						[Télécharger le fichier tamponné]</a>
						<input id="<?php echo $name ?>" type="hidden">

						<?php if($file_num == 1) : ?>

						<script type="text/javascript">
							obj_<?php echo $name?> = new DatePicker('<?php echo $name?>', 'fr');
						</script>
						<a href="#datepicker"
						   id="datepicker_<?php echo $name?>_link"
						   class="datepicker_link"
						   onclick="javascript:obj_<?php echo $name?>.toggleDatePicker(); return false;">
								(date d'affichage)
						</a>
						<div class="date_picker" style="display: none;" id="datepicker_<?php echo $name?>_calendar">
						</div>


						<script type="text/javascript">
							$(document).ready(function () {
								$(".telecharger_tampon").click(function () {
									var href = $(this).attr('href')  + "&date_affichage=" + $("#tampon_date").val();
									$(this).attr('href',href);
								});
							})
						</script>

						<?php endif; ?>
						<?php
						$html .= ob_get_contents();
						ob_end_clean();

            		}
            	}
            }

			$html .= "</dd>";
      }
     }

    $html .= "<dt>Nom métier&nbsp;:</dt>\n";
    $html .= "<dd>";

    if (strlen($file["posted_filename"]) <= 0 && !$archiveDeleted) {
      $html .= $file["name"]."<br/><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">[Télécharger]</a>";
    } else {
      $html .= $file["name"];
    }

    $html .= "</dd>";
    $html .= "</dl>\n";
    if ($file['sign']) {
    	$html .= "<dt>Signature</dt>";
    	$html .= "<dd><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_get_signature.php?id=" . $file["id"] . "\" title=\"Télécharger le fichier\">Ce document est signé électroniquement</a></dd>";
    }

    if ($file['code_pj']){
        $html .= '<dt>Type de pièce jointe :</dt>';
        $html .= "<dd>". get_hecho($actesTypePJSQL->getLibelle($file['code_pj'])?:$file['code_pj'])."</dd>";
    }

    $html .= "</td>\n";
    $html .= "  <td headers=\"type\" >" . $file["mimetype"] . "</td>\n";
    $html .= "  <td headers=\"size\" >" . $file["size"] . " octets</td>\n";
    $html .= " </tr>\n";

  }
      $html .= "</tbody>\n";
      $html .= "</table>\n";
} else {
  $html .= "  <p>Pas de fichier trouvé</p>";
}

$html .= ($archiveDeleted) ? $archiveName : "Archive transmise : <a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?env=" . $trans->get("envelope_id") . "\" title=\"Télécharger l'archive .tar.gz\">" . $archiveName . "</a>";

if ($me->isSuper()) {
	$link = WEBSITE_SSL ."/modules/actes/actes_transac_validate.php?transaction_id=$id";
	$html .= "<br/><a href='$link'>Validation de l'archive</a>";
}

$html .= "</div>\n";
// Affichage du Workflow

$html .= "<h2>Cycle de vie de la transaction</h2>\n";

if (count($workflow) > 0) {
  $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"table-striped table table-bordered\">\n";
  $html .= " <thead>\n";
  $html .= " <tr>\n";
  $html .= "  <th id=\"status\">État</th>\n";
  $html .= "  <th id=\"date\">Date</th>\n";
  $html .= "  <th id=\"message\">Message</th>\n";
  $html .= " </tr>\n";
  $html .= " </thead>\n";
  $html .= " <tbody>\n";
  	$create_pdf_html ="&nbsp;<a href=\"actes_create_pdf.php?trans_id=".$id."&user_id=".$me->getId()."\">";
	$create_pdf_html.="<br/>[Télécharger]</a>";

    $create_pdf_html.="<br/><a href='actes_transac_get_ARActe.php?id=$id'>[Afficher l'ARActe]</a> ";


	//---fin de modification

  foreach ($workflow as $stage) {
    $html .= "<tr>\n";

   if (in_array($stage["status_id"],array(4,11) ))
    {

    	$html .= "  <td headers=\"status\">" . $status_list[$stage["status_id"]].$create_pdf_html."</td>\n";
    }
    else
    	$html .= "  <td headers=\"status\">" . $status_list[$stage["status_id"]] . "</td>\n";

	//---------end

    $html .= "  <td headers=\"date\">" . Helpers :: getDateFromBDDDate($stage["date"], true) . "</td>\n";
    $html .= "  <td headers=\"message\" class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
    $html .= " </tr>\n";
  }
  $html .= " </tbody>\n";
  $html .= "</table>\n";
  $html .= "</div>\n";
} else {
  $html .= "Le cycle de vie est vide pour cette transaction.\n";
}

$courrier = $trans->getCourrierInfo();
if (count($courrier) != 0){
	$html .= "<h2>Document reçu relatif à l'acte</h2>\n";
	 $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"table data-table table-striped table-bordered\">\n";
  $html .= " <thead>\n";
  $html .= " <tr>\n";
  $html .= "  <th id=\"type\">Type</th>\n";
  $html .= "  <th id=\"sens\">sens</th>\n";
  $html .= "  <th id=\"actions\">action</th>\n";
  $html .= " </tr>\n";
  $html .= " </thead>\n";
  $html .= " <tbody>\n";
	foreach ($courrier as $id=>$info) {
		  $html .= " <tr>\n";
  		$html .= "  <td headers=\"type\">".$transactionTypes[$info["type"]]."</td>\n";
  		$html .= "  <td headers=\"sens\">".$info["sens"]."</td>\n";
  		$html .= "  <td headers=\"actions\">
  		<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" .$id . "\"><img alt=\"pdf\" src=\"../../custom/images/erreur.png\"> </a></td>\n";
  		$html .= " </tr>\n";
	}

  $html .= "</tbody>\n";
  $html .= "</table>\n";
  $html .= "</div>\n";

}


if (!$me->isSuper() && $me->checkDroit($module->get("name"),'CS') &&  $permission->canWrite($me,$owner) ) {
  $actionHtml = "";

  // Formulaire de notification a posteriori
  // Affichés quand la transaction a été acquittée par le MIAT et non notifiée
  if ($trans->get("type") == 1 && $transStatus == 4 && $trans->get("broadcasted") == 'f') {
    // adresses emails de diffusion


    $org = new Authority($me->get("authority_id"));
    $defaultbroadcast_email = $org->get("default_broadcast_email");
    if ($defaultbroadcast_email != NULL) {
		$defaultbroadcast_email = explode(",", $defaultbroadcast_email);
	} else {
		$defaultbroadcast_email = array();
    }

    $broadcast_email = ACTES_COMMON_BROADCAST_EMAILS . "," . $org->get("broadcast_email");
    $broadcast_email = explode(",", $broadcast_email);

    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form class=\"form\" action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_notify.php\" method=\"post\">\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Notifier la transaction\" />\n";
    $actionHtml .= "<div class=\"form-group\">\n<label class=\"control-label\">Emission des documents sources : </label><input type=\"checkbox\" class=\"inline-checkbox\" name=\"send_sources\" checked='checked' />\n</div>\n";

	foreach ($defaultbroadcast_email as $email) {
    	$checked = 'checked="checked" disabled="disabled"';
    	if ($email != "" && $email != NULL)
        	$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-offset-1 control-label\" for=\"$email\"><input id=\"$email\" type=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
	}

    foreach ($broadcast_email as $email) {
        $checked = '';
      if ($email != "" && $email != NULL)
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-offset-1 control-label\" for=\"$email\"><input type=\"checkbox\"  name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
    }


    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "</form>\n";
    $actionHtml .= "</div>\n";
  }
}

//On vérifie qu'il n'y a pas de demande d'annulation en cours
if (!$trans->hasPendingCancelTrans()) {
    // Boutons de cloture de la transaction
    // Affichés quand la transaction a été acquittée par le MIAT
    if ($trans->get("type") == 1 && $transStatus == 4 && !  $me->isGroupAdminOrSuper()) {

        if ($trans->canValidate()) {
		$actionHtml .= "<div class=\"action\">\n";
		$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ? Cette action est non réversible et est sous votre entière responsabilité.' . "');\" method=\"post\">\n";
		$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Acte validé par le ministère : </label>\n";
		$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
		$actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"valid\" />\n";
		$actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Passer la transaction en état « Validée »\" />\n";
		$actionHtml .= "</div>\n</form>\n";
		$actionHtml .= "</div>\n";
	}//fin if verfiie canValidate

        $actionHtml .= "<div class=\"action\">\n";
        $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ? Cette action est non réversible et est sous votre entière responsabilité.' . "')\" method=\"post\">\n";
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Acte refusé par le ministère : </label>\n";
        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"invalid\" />\n";
        $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Passer la transaction en état «&nbsp;Refusée&nbsp;»\" />\n";
        $actionHtml .= "</div>\n</form>\n";
        $actionHtml .= "</div>\n";
    }//fin if qui verifie type == 1 et status == 4

    $actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
    $transactionsInfo = $actesTransactionsSQL->getInfo($trans->getId());
    $authoritySQL = new AuthoritySQL($sqlQuery);
    $authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);

    if ($trans->get("type") == 1 && in_array($transStatus,array(4,5,14,20)) && $trans->canValidate() ) {
	     $actionHtml .= "<div class=\"action\">\n";
          $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_archiver.php\"  method=\"post\" id='form_send_sae'>\n";
          $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
          $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
          $actionHtml .= "<input type=\"submit\" id='button_send_sae' class=\"btn btn-primary\" value=\"Versement manuel\" />\n";
          $actionHtml .= "</div>\n</form>\n";
          $actionHtml .= "</div>\n";
        ob_start();
        ?>
        <script type="text/javascript" src="/javascript/jfu/js/jquery.min.js"></script>

        <script>

    $(document).ready(function(){
        $("#form_send_sae").submit(function(){
            $("#button_send_sae").val("Versement en cours");
            $("#button_send_sae").attr('disabled','disabled');
        });
    });

</script>


        <?php
        $actionHtml .= ob_get_contents();
        ob_end_clean();


     }//fin if type == 1 , status = 4 ou 14, transaction canvalidate et configuration pour le sae
}//fin if qui verifie qu'il n'y a pas d'annulation en cours

// Bouton d'annulation en fonction du type et de l'état
// Doit être une transaction de transmission d'acte
// et être dans l'état Acquittement reçu
if ($trans->get("type") == 1 && $transStatus == 4  && $me->checkDroit("actes", "TT") && !  $me->isGroupAdminOrSuper()) {
  $actionHtml .= "<div class=\"action\">\n";
  if (!$trans->hasPendingCancelTrans()) {
    if ($module->getParam("paper") == "on") {
      $actionHtml .= "<label>Annulation&nbsp;:&nbsp;Mode «&nbsp;papier&nbsp;» actif. Pas d'annulation possible.</label>";
    } else {
      $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_cancel.php\" onsubmit=\"return confirm('Voulez-vous vraiment annuler cette transaction ?')\" method=\"post\">\n";
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Annulation : </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Annuler cette transaction\" class=\"btn btn-danger\" />\n";
      $actionHtml .= "</div></form>\n";
    }
  } else {
    $actionHtml .= "Une demande d'annulation est en cours pour cet acte.";
  }
  $actionHtml .= "</div>\n";
}

// Boutons de réponse à un courrier

if ( in_array($transStatus, array(7,8,21)) && $trans->get("type") != 5  && $me->checkDroit("actes", "CS")) {
      $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_repondre.php\" method=\"post\">\n";
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Répondre : </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Répondre à ce document\" class=\"btn btn-primary\" />\n";
      $actionHtml .= "</div></form>\n";
}

if ($transStatus == 17 && $me->checkDroit("actes", "TT")){
	  $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_post_confirm.php\" method=\"post\">\n";
      $actionHtml .= "<p>Valider &nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Poster ce document\" class=\"submit_button\" />\n";
      $actionHtml .= "</p></form>\n";
}

$actionHtml .= "<div class=\"action\">\n";
$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Horodatage : </label>\n<a onclick=\"window.open(this.href); return false;\" href=\"" . WEBSITE_SSL . "/common/logs_view.php?module=actes&amp;severity=-1&amp;message=" . $trans->getId() . "\" title=\"Rechercher les logs relatifs à l'acte n°" . $trans->getId()  . " et sa signature\" >Rechercher les logs relatifs à l'acte</a>\n";
$actionHtml .= "</div>\n</div>\n";

if ($me->isSuper()) {
       $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_delete.php\" onsubmit=\"return confirm('Cette transaction sera éradiquée DEFINITIVEMENT de la base sans espoir de retour?')\" method=\"post\">\n";
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Effacer de la base de donnée (TRES DANGEREUX) : </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Effacer de la base de données\" class=\"btn btn-danger\" />\n";
      $actionHtml .= "</div></form>\n";

      $actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_set_error.php\" onsubmit=\"return confirm('Cette transaction sera passée en erreur ')\" method=\"post\">\n";
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer la transaction en erreur </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Passer la transaction en erreur\" class=\"btn btn-warning\" />\n";
      $actionHtml .= "</div></form>\n";



	if (in_array($transStatus,array(3,-1))  && $trans->get("type") == 1) {

		$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_rolback_attente.php\" onsubmit=\"return confirm('Êtes-vous certain de vouloir faire cela ? ')\" method=\"post\">\n";
		$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer à En attente de transmission </label>\n";
		$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
		$actionHtml .= "<input type=\"submit\" value=\"Passer en attente de transmission\" class=\"btn btn-warning\" />\n";
		$actionHtml .= "</div></form>\n";

		$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_rolback_attente.php\" onsubmit=\"return confirm('Êtes-vous certain de vouloir faire cela ? ')\" method=\"post\">\n";
		$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer à Poster </label>\n";
		$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
		$actionHtml .= "<input type=\"hidden\" name=\"status_id\" value=\"1\" />\n";
		$actionHtml .= "<input type=\"submit\" value=\"Passer à Poster\" class=\"btn btn-warning\" />\n";
		$actionHtml .= "</div></form>\n";
	}

	if ($transStatus == ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE){
		$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_send_sae.php\"  method=\"post\">\n";
		$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Forcer l'envoi synchrone au SAE :</label>\n";
		$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
		$actionHtml .= "<input type=\"submit\" value=\"Forcer l'envoi au SAE\" class=\"btn btn-warning\" />\n";
		$actionHtml .= "</div></form>\n";

	}


}


if (isset($actionHtml)) {
  $html .= "<h2>Actions</h2>\n";
  $html .= $actionHtml;
}


if ($transStatus == 18 && $me->checkDroit("actes", "CS")){

	$actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);
	$tab_included_files = $actesIncludedFileSQL->getSendFile($id);


	$tab_included_files = array_slice($tab_included_files,0,1);
	$libersignController = new LibersignController($objectInstancier);


	$html .= "<h2>Signature de l'acte</h2>";
	ob_start();

	$libersignController->displayLibersignJS();

	?>

	<script>
		$(window).load(function() {

			$(document).ready(function () {

				$("#box_result").hide();

				var siginfos = [];

				<?php foreach($tab_included_files as $i => $included_file) : ?>
				siginfos.push({
					hash: "<?php echo $included_file['sha1'] ?>",
					format: "CMS"
				});
				<?php endforeach;?>

				$(".libersign").libersign({
					iconType: "glyphicon",
					signatureInformations: siginfos
				}).on('libersign.sign', function (event, signatures) {
					<?php foreach($tab_included_files as $i => $included_file) : ?>
					$("#signature_<?php echo $i + 1?>").val(signatures[<?php echo $i ?>]);
					<?php endforeach;?>
					$("#form_sign").submit();
				});

			});
		});
	</script>

	<div id='box_signature' class='box' style="width:920px" >
		<h3>Signature</h3>
		<div class="libersign"></div>
	</div>

	<form action='<?php echo WEBSITE_SSL?>modules/actes/actes_transac_sign.php' id='form_sign' method='post'>
		<input type='hidden' name='id' id='form_sign_id' value='<?php echo $id?>'/>
		<input type='hidden' name='nb_signature'  value='<?php echo count($tab_included_files)?>'/>
		<?php foreach($tab_included_files as $i => $included_file) : ?>
			<input type='hidden' name='signature_id_<?php echo $i +1?>' value='<?php echo $included_file['id']?>' />
			<input type='hidden' name='signature_<?php echo $i +1?>' id='signature_<?php echo $i +1?>' value=''/>
		<?php endforeach;?>
	</form>




	<?php
		$html.= ob_get_contents();
		ob_end_clean();
        $html .="<h3>Ne plus signer</h3>";
        $html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_post_without_signature.php\" onsubmit=\"return confirm('L\'acte ne sera pas signé. Êtes-vous certain de vouloir le poster sans signature ? ')\" method=\"post\">\n";
        $html .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Ne plus signer l'acte et le poster</label>\n";
        $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $html .= "<input type=\"submit\" value=\"Télétransmettre sans signature\" class=\"btn btn-warning\" />\n";
        $html .= "</div></form>\n";
}


$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContainer();

$doc->buildFooter();

$doc->display();

