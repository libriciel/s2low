<?php

use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\DatePicker;

list($actesTypePJSQL) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ActesTypePJSQL::class]
    );

$html = '';

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->checkDroit($module->get("name"), 'CS')) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

// Collectivité de l'utilisateur courant
$myAuthority = new Authority($me->get("authority_id"));

// Parametres pour le traitement par lot
$batchFileId = Helpers :: getVarFromGet("batchfile");
$zeBatch = null;
$zeBatchFile = null;

// Détermination si traitement par lot ou pas
$batchMode = false;
if (isset($batchFileId) && is_numeric($batchFileId)) {
    $zeBatchFile = new ActesBatchFile($batchFileId);
    if ($zeBatchFile->init()) {
        $zeBatch = new ActesBatch($zeBatchFile->get("batch_id"));
        if ($zeBatch->init()) {
            $owner = new User($zeBatch->get("user_id"));
            $owner->init();

          // Vérification des permissions sur le lot
            if (($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) || ($me->getId() == $owner->getId())) {
                $batchMode = true;
            }
        }
    }
}


$type_pj_list = json_encode($actesTypePJSQL->getAllByNature());
$type_pj_list_matiere1 = json_encode($actesTypePJSQL->getAllByNatureMatiere1());
$type_pj_default = json_encode($actesTypePJSQL->getAllDefaultNature());
$type_pj_list_par_nature = json_encode($actesTypePJSQL->getListByNature());

$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

$trans = new ActesTransaction();

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . Helpers::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));
$doc->addHeader("<script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jquery.js") . "\"></script>");
$doc->addHeader("<script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jqueryui.js") . "\"></script>");

$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

$(function(){
  
    $("#show_broadcast_email").click(function(){
         $("#broadcast_email").toggle();   	
    });
    
    $("#removeField").click(function(){
        var actes_pj = $(".actes_pj");
        actes_pj.last().remove();   
        /* on recalcule pas le selecteur, mais on teste bien que actes_pj est vide */
        if (actes_pj.length === 1){ 
            $("#removeField").hide();
        }
        return false;       
    }).hide();
    
  $("#addField").click(function(){
      var field_nb = $(".actes_pj").length + 1 ;
      var html = $(
          '<div class="actes_pj">' +
                '<div class="form-group col-md-offset-1">' +
                    '<label for="acte_attachments_' + field_nb + '" class="">' +
                        'Pièce jointe n°' + field_nb + ':' +
                    '</label>' +
                         '<div class="form-group col-sm-offset-1" >' +   
                         '<label>Type de pièce jointe</label><br/>' +
                         '<select class="select_type_pj" id="actes_attachments_type_'+field_nb+'" name="type_pj[]">' +
                         '</select></div>' +
                        '<div class="form-group col-md-offset-1" >' +
                        '<label>Fichier (.pdf, .xml, .png ou .jpg)</label><input type="file" id="acte_attachments_' + field_nb + '" name="acte_attachments[]" size="40" maxlength="255" />' +
                        '</div>'+
                '</div>' +
         '</div>'
      );
      
      $("#attachments_fields").append(html);
      $("#removeField").show();
      
      setTypePJ($("#actes_attachments_type_"+field_nb));
      
  });
  
  var setTypePJParNature = function(selector){
      selector.empty();
      var nature_code = $("#nature_code").val();
       if (nature_code){
          $.each(type_pj_liste_par_nature[nature_code],function(key,value){
              selector
                 .append($("<option></option>")
                            .attr("value",key)
                            .text(value)); 
          });
     } else {
            selector
                 .append($("<option></option>")
                            .attr("value","")
                            .text("Veuillez sélectionner la nature de l'acte")); 
     }
  }
  
  var setTypePJ = function(selector){
          console.log(selector);
          return setTypePJParNature(selector);
  };


 
 
  var type_pj = $type_pj_list;
  var type_pj_default = $type_pj_default;
  var type_pj_matiere1 = $type_pj_list_matiere1;
  var type_pj_liste_par_nature = $type_pj_list_par_nature
  
  var selector_onchange =  "#nature_code, #classification_text";
 
  selector_onchange = "#nature_code";      
  
  $(selector_onchange).on('change', function() {
		$(".select_type_pj").each(function(){				   
				setTypePJ($(this))					            
		});        
  });        


});

$(document).ready(function (){
      $("#nature_code").trigger('change');
});


//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->setTitle("Tedetis : Actes - Ajout d'une transaction");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
if (( ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY == false) || (!ActesClassification :: hasTodayRequest($myAuthority->getId()))) {
  // Zone d'information
  // Affichage du lien pour demande de mise à jour classification matières sous-matières
    $html .= "<div  class=\"bs-callout bs-callout-info\">\n";
    if ($dateClassif = ActesClassification :: getLastRevisionDate($myAuthority->getId(), false)) {
        $html .= "<p>La classification matières et sous-matières utilisée pour votre collectivité est la version du " . Helpers :: getDateFromBDDDate($dateClassif) . ".<br/>\n";
        $html .= "Pour forcer la mise à jour de cette classification depuis le serveur du ministère, veuillez utiliser le bouton ci-dessous :</p>\n";
    } else {
        $html .= "<p>Il n'existe pas encore de classification matières et sous-matières associée à votre collectivité.</p>\n";
        $html .= "<p>Pour forcer la récupération de cette classification depuis le serveur du ministère, veuillez utiliser le bouton ci-dessous :</p>\n";
    }

    $html .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_classification_request.php\" method=\"post\" onsubmit=\"return confirm('Voulez-vous vraiment créer une transaction de demande de classification ?');\">\n");
    $html .= "<div class=\"button_area\"><input class=\"submit_button btn btn-default\" type=\"submit\" value=\"Mise à jour classification\" /></div>\n";
    $html .= "</form>\n";
}

$html .= "</div>\n";
$doc->addBody($html);

$doc->closeSideBar();
$doc->openContent();

// Zone contenu
$html = "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a href=\"" . Helpers::getLink("/modules/actes/index.php") . "\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";

$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    $html .= "<div class='alert alert-warning'>Votre certificat n'est pas conforme au RGS, vous ne pourrez pas télétransmettre !</div>";
}



$html .= "<h2>Création d'une transaction Actes</h2>\n";

if ($batchMode) {
    $html .= "<div class=\"alert alert-info\"> Transmission d'acte depuis le lot «&nbsp;" . get_hecho($zeBatch->get("description")) . "&nbsp;»<br />";
    $html .= "Fichier courant&nbsp;: " . get_hecho($zeBatchFile->getDisplayName()) . "<br />\n</div>";
}

$html .= "<form id=\"add-transac-content\" role=\"form\" class=\"form col-md-offset-1\" action=\"" .
    Helpers::getLink(
        "/modules/actes/actes_transac_create.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm(" .
        $trans->getValidationTrio('nature_code', 'number', 'decision_date', 'title', 'subject') .
        ", 'classif1', 'Classification', 'RisInt','decision_date', 'Date de la décision', 'isDatePasse'"
    );



if (!$batchMode) {
    $html .= ", 'acte_pdf_file', 'Fichier PDF contenant l\'acte', 'RisString', 'acte_attachments[]', 'Pièces jointes', 'isString'";
}

$html .= ")) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";
$html .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . ACTES_ARCHIVE_MAX_SIZE . '" /> ';

if ($batchMode) {
    $html .= "<input type=\"hidden\" name=\"batchfile\" value=\"" . $zeBatchFile->getId() . "\" />\n";
}

$html .= " <div class=\"form-group\">\n";
$html .= "  <label for=\"nature_code\" class=\"control-label\"> Nature de l'acte : </label>\n";
$html .=   $doc->getHTMLSelect("nature_code", $transNatures, Helpers :: getFromSession("nature_code"), "id='nature_code'") ;
$html .= " </div>";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label for=\"classification_text\" class=\"control-label\">Classification : </label>\n";
$html .= "   <a class=\"form-control\" href=\"#tedetis\" onclick=\"javascript:window.open('" . Helpers::getLink("/common/select_popup.php?type=classification', 'Selectattribut', 'location=0,scrollbars=1,menubar=0,status=0,toolbar=0,directories=0,width=512,height=500');\" id=\"classification_text\">");

$classif1 = Helpers :: getFromSession("classif1", false);
if (!empty($classif1)) {
    $classif = array ();
    for ($i = 1; $i <= 5; $i++) {
        $classif[] = Helpers :: getFromSession("classif" . $i, false);
    }

    $html .= implode(".", $classif);
} else {
    $html .= "Choisir la classification";
}

$html .= "</a>\n";
$html .= "   <input type=\"hidden\" id=\"classif1\" name=\"classif1\" value=\"" . Helpers :: getFromSession("classif1") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif2\" name=\"classif2\" value=\"" . Helpers :: getFromSession("classif2") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif3\" name=\"classif3\" value=\"" . Helpers :: getFromSession("classif3") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif4\" name=\"classif4\" value=\"" . Helpers :: getFromSession("classif4") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif5\" name=\"classif5\" value=\"" . Helpers :: getFromSession("classif5") . "\" />\n";
$html .= "   </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "   <label for=\"act-number\" class=\"control-label\"> Numéro de l'acte (15 caractères maxi, chiffres, lettres en majuscule ou _)</label>\n";
$html .= "   <input id=\"act-number\" class=\"form-control\" type=\"text\" name=\"number\" value=\"";

$number = Helpers :: getFromSession("number");

if ($batchMode) {
    $number = $zeBatch->get("num_prefix") . "_" . $zeBatch->getNextSuffix();
}

$html .= get_hecho($number) . "\" size=\"30\" maxlength=\"15\" title=\"15 caractères maxi, chiffres, lettres en majuscule ou _\"/>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "   <label for=\"decision_date\" class=\"control-label\">Date de la décision : </label>\n";

$decision_date = Helpers :: getFromSession("decision_date");

$datePicker = new DatePicker("decision_date", $decision_date);
$html .= $datePicker->show();

$document_papier_checked = Helpers :: getFromSession("document_papier") ? 'checked="checked"' : "";

$html .= <<<"EOL"
    <div class="form-group">
        <label for="document_papier" class="control-label">Envoi de documents papiers complémentaires : </label>
        <input type="checkbox" name="document_papier" $document_papier_checked />
    </div>
EOL;


$html .= " <div class=\"form-group\">\n";
$html .= "  <label for=\"subject\" class=\"control-label\">Objet : </label>\n";
$html .= "   <textarea id=\"subject\" class=\"form-control\" cols=\"60\" rows=\"7\" name=\"subject\">" . Helpers :: getFromSession("subject") . "</textarea></div>\n";


$html .= " <div class=\"form-group\">\n";
$html .= "   <fieldset>\n";
$html .= "   <div class=\"row-legend\">\n";
$html .= "   <legend>Fichier PDF ou XML contenant l'acte : </legend></div>\n";
$html .= "     <div class=\"actes_files_form\">\n";
$html .= "       <div class=\"form-group col-md-offset-1 \">\n";
$html .= "         <label>Type de pièce jointe</label><br/>";
$html .= "            <select class=\"select_type_pj\" id=\"actes_attachments_type\" name=\"type_acte\"></select>";
$html .= "       </div>\n";
if (! $batchMode) {
    $html .= "       <div class=\"form-group col-md-offset-1 \">\n";
    $html .= "         <label for=\"acte_pdf_file\" class=\"control-label\">Fichier (.pdf ou .xml)</label>\n";
    $html .= "         <input type=\"file\" id=\"acte_pdf_file\" class=\"control-form\" name=\"acte_pdf_file\"/>\n";
    $html .= "       </div>\n";
}
$html .= "     </div>\n";
$html .= "   </fieldset>\n";
$html .= " </div>\n";

$html .= "<div class=\"form-group\">\n";
$html .= "  <fieldset>\n";
$html .= "   <div class=\"row-legend\">\n";
$html .= "  <legend>Pièces jointes supplémentaires : <a id='addField' href=\"#tedetis\" title=\"Ajouter un champ de sélection de fichier supplémentaire\">Ajouter une pièce jointe</a></legend></div>\n";
$html .= "   <div id=\"attachments_fields\"></div>\n";
$html .= " 		<a href='#' id='removeField'>Supprimer la dernière pièce jointe</a>";
$html .= " </fieldset>\n";
$html .= "</div>\n";

// adresses emails de diffusion
$org = new Authority($me->get("authority_id"));
$defaultbroadcast_email = $org->get("default_broadcast_email");
if ($defaultbroadcast_email != null) {
    $defaultbroadcast_email = explode(",", $defaultbroadcast_email);
} else {
    $defaultbroadcast_email = array();
}



$broadcast_email = ACTES_COMMON_BROADCAST_EMAILS . "," . $org->get("broadcast_email");
$broadcast_email = explode(",", $broadcast_email);

$html .= "     <div class=\"form-group\"><label for=\"show_broadcast_email\" class=\"control-label\">Diffusion automatique de la notification : </label><input id=\"show_broadcast_email\" type=\"checkbox\" checked=\"checked\" name=\"show_broadcast_email\"/></div>\n";
$html .= "     <div id=\"broadcast_email\" style=\"visibility:visible\">\n";
$html .= "     <div class=\"form-group\"><label for=\"send_sources\" class=\"control-label\">Emission des documents sources : </label><input id=\"send_sources\" type=\"checkbox\" name=\"send_sources\" checked='checked' /></div>\n";

foreach ($defaultbroadcast_email as $email) {
    $checked = 'checked="checked" disabled="disabled"';
    if ($email != "" && $email != null) {
                $html .= "      <div class=\"form-group\"><label for=\"$email\" class=\"col-md-offset-1 control-label email-checkbox\"><input id=\"$email\" type=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
    }
}

foreach ($broadcast_email as $email) {
    $checked = '';
    if ($email != "" && $email != null) {
        $html .= "      <div class=\"form-group\"><label for=\"$email\" class=\"col-md-offset-1 control-label email-checkbox\"><input id=\"$email\" type=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
    }
}
$html .= "     </div>\n";

if ($batchMode) {
    $html .= "   <div class=\"form-group\"><label for=\"next\" class=\"control-label email-checkbox\">Passer au fichier suivant dans le lot après création de cette transaction : </label><input id=\"next\" type=\"checkbox\" name=\"process_next_batch_file\" checked=\"checked\" /></div>\n";
}

$html .= "<div id=\"form_progress\" class=\"form-group\"><button class=\"col-md-offset-5 btn btn-primary\" type=\"submit\">Créer la transaction</button></div>\n";
$html .= "</form>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
