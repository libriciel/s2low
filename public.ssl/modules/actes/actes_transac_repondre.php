<?php

use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

list($actesTypePJSQL) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ActesTypePJSQL::class]
    );
$html = '';
$batchMode = false;

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

$related_id = Helpers::getVarFromPost("id");
if (!$related_id) {
    $related_id = Helpers::getVarFromGet("id");
}

$trans = new ActesTransaction($related_id);
$trans->init();
$transactionTypes = $trans->get("transactionTypes");


$typeReponse = array(
3   => array(4 => "Transmission de pièces complémentaires",
            3 => "Refus explicite d'envoi de pièces complémentaires"
            ),
4 => array(4 => "Lettre de justification de l'acte",
            3 => "Rejet explicite d'une lettre d'observations")
);

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . Helpers::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));



$type_pj_list = $actesTypePJSQL->getListByNature()[$trans->get('nature_code')];


$option_pj = "";
foreach ($type_pj_list as $code_pj => $libelle_pj) {
    $option_pj .= "<option value='$code_pj'>$libelle_pj</option>";
}

$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[
var field_nb = 1;

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

function add_attachment_field() {
  field = document.getElementById("attachments_fields");
  newfield=document.createElement("div");
  newfield.className="actes_files_form row";
  
  	html = "<div class=\"form-group \">";
	html += "         <label class=\"col-md-3  control-label\">Type de la pièce jointe n°" + field_nb+ " </label>";
	html += "          <div class=\"col-md-3\"><select class=\"select_type_pj\" name=\"type_pj[]\">";
	html += "$option_pj";

	html +="</select></div>";
	html += "       </div><br/>";
  
  html += '        <div class="form-group">';
  html += '         <label for="acte_attachments_' + field_nb + '" class="col-md-3 control-label">Pièce jointe n°' + field_nb + ' (.pdf, .png ou .jpg)\\x3C/label>';
  html += '         <div class="col-md-3"><input type="file" id="acte_attachments_' + field_nb + '" name="acte_attachments[]" size="40" maxlength="255" />\\x3C/div>';
  html += '       \\x3C/div>';
html += '    \\x3C/div>';
html += '    \\x3C/div>';
html += '    \\x3C/div>';

  newfield.innerHTML = html;
  field.appendChild(newfield);
  field_nb++;
}

function switch_sign_fields(checkbox) {
  acte = document.getElementById("acte_pdf_file_sign");

  acte.disabled = checkbox.checked;

  for (i = 1; i < field_nb; i++) {
	attachment = document.getElementById("acte_attachments_sign_" + i);
	attachment.disabled = checkbox.checked;
  }
}

function open_sign_window() {
  var zeForm = document.getElementById("sign_form");
  var signSubmitButton = document.getElementById("sign_submit_button");

  var acteFile = document.getElementById("acte_pdf_file");

  // Purge des éléments hidden qui existeraient déjà (évite les doublons en cas de clics multiples)
  zeForm.innerHTML = '<input class="submit_button" id="sign_submit_button" type="submit" value="Générer les signatures" />';

  // Le fichier de l'acte est obligatoire
  if (acteFile) {
	if (acteFile.value.length <= 0) {
	  alert("Choisissez au moins un fichier pour l'acte.");
	  return false;
	} else {
	  zeInput = document.createElement('input');
	  zeInput.setAttribute("type", "hidden");
	  zeInput.setAttribute("id", "form_sign_file_1");
	  zeInput.setAttribute("name", "files[]");
	  zeInput.setAttribute("value", acteFile.value);
	  zeForm.appendChild(zeInput);
	}
  }

  // Traitement des pièces jointes
  for (i = 1; i < field_nb; i++) {
	elt = document.getElementById("acte_attachments_" + i);

	if (elt && elt.value.length > 0) {
	  zeInput = document.createElement('input');
	  zeInput.setAttribute("type", "hidden");
	  zeInput.setAttribute("id", "form_sign_file_" + i);
	  zeInput.setAttribute("name", "files[]");
	  zeInput.setAttribute("value", elt.value);
	  zeForm.appendChild(zeInput)
	}
  }

  window.open('about:blank', 'sign_files', 'location=0,scrollbars=1,menubar=0,status=0,toolbar=0,directories=0,width=800,height=600');

  zeForm.target = 'sign_files';

  return true;
}

// affichage/masquage d'un bloc
function hide_bloc(bloc_id){
	document.getElementById(bloc_id).style.visibility=document.getElementById(bloc_id).style.visibility=="hidden"?"visible":"hidden";;
}  





//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->setTitle("Tedetis : Actes - Réponse à un document");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);

$html .= "<div  class=\"bs-callout bs-callout-info\">\n";

$html .= "<h3>Note&nbsp;:</h3>\n";
$html .= "<p>Pour générer les signatures numériques des fichiers joints, sélectionnez d'abord les fichiers dans le formulaire ci-contre puis utilisez le bouton ci-dessous. Une nouvelle fenêtre s'ouvrira permettant de signer les fichiers.</p>\n";
$html .= "<form action=\"" . Helpers::getLink("/modules/actes/applet/index.php\" method=\"post\" id=\"sign_form\" onsubmit=\"javascript:return open_sign_window();\">\n");
$html .= "<p><input class=\"submit_button btn btn-default\" id=\"sign_submit_button\" type=\"submit\" value=\"Générer les signatures\" />\n";
$html .= "</p></form>\n";
$html .= "</div>\n";
$doc->addBody($html);
$doc->closeSideBar();
$doc->openContent();

// Zone contenu
$html = "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a href=\"" . Helpers::getLink("/modules/actes/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n");
$html .= "<h2>Réponse à un courrier</h2>\n";


$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Type de transaction", $transactionTypes[$trans->get("type")]);
$html .= $doc->getHTMLArrayline(
    "Acte ",
    "<a href=\"" . Helpers::getLink("/modules/actes/actes_transac_show.php?id=" . $trans->getId() . "\">")
     . $trans->get("number") . "</a>"
);
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

$html .= "<form id=\"reply-transac-content\" role=\"form\" class=\"form\" action=\"" . Helpers::getLink("/modules/actes/actes_transac_reponse_create.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm(" . $trans->getValidationTrio('nature_code', 'number', 'decision_date', 'title', 'subject') . ", 'classif1', 'Classification', 'RisInt'");
$html .= ", 'acte_pdf_file', 'Fichier PDF contenant la réponse', 'RisString', 'acte_attachments[]', 'Pièces jointes', 'isString'";
$html .= ")) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";

$html .= "<input type='hidden' name='id' value='" . $related_id . "'/>";

if ($trans->isType(TypeTransaction::DemandePieceComplementaire) || $trans->isType(TypeTransaction::LettreDObservation)) {
    $html .= "  <div class=\"form-group\">\n";
    $html .= "  <label for=\"type_envoie\" class=\"control-label\"> Nature de l'envoi: </label>\n";
    $html .=  $doc->getHTMLSelect(
        "type_envoie",
        $typeReponse[$trans->get("type")],
        Helpers :: getFromSession("type_envoie")
    ) . "\n";
    $html .= " </div>";
} else {
    $html .= "<input type='hidden' name='type_envoie' value='1'/>";
}

$html .= " <div class=\"form-group\">\n";
$html .= "   <fieldset>\n";
$html .= "   <div class=\"row-legend\">\n";
$html .= "   <legend>Fichier PDF contenant la réponse :</legend></div>\n";
$html .= "     <div class=\"actes_files_form\">\n";
$html .= "       <div class=\"form-group \">\n";
$html .= "         <label class=\"col-md-3  control-label\">Type de pièce jointe</label>";
$html .= "          <div class=\"col-md-3\"><select class=\"select_type_pj\" id=\"actes_attachments_type\" name=\"type_acte\">";

foreach ($type_pj_list as $code_pj => $libelle_pj) {
    $html .= "<option value='$code_pj'>$libelle_pj</option>";
}

$html .= "</select></div>";
$html .= "       </div><br/>";
$html .= "       <div class=\"form-group\">\n";
$html .= "         <label for=\"acte_pdf_file\" class=\" col-md-3  control-label\">Fichier PDF, JPG ou PNG : </label>\n";
$html .= "         <div class=\"col-md-3\"><input type=\"file\" id=\"acte_pdf_file\" class=\"control-form\" name=\"acte_pdf_file\"/></div>\n";
$html .= "       </div>\n";
$html .= "   </fieldset>\n";
$html .= " </div>\n";

if ($trans->isType(TypeTransaction::DemandePieceComplementaire)) {
    $html .= "<div class=\"form-group\">\n";
    $html .= "  <fieldset>\n";
    $html .= "   <div class=\"row-legend\">\n";
    $html .= "  <legend>Pièces jointes supplémentaires : <a href=\"#tedetis\" onclick=\"javascript:add_attachment_field();\" title=\"Ajouter un champ de sélection de fichier supplémentaire\">Ajouter un champ</a></legend></div>\n";
    $html .= "   <div id=\"attachments_fields\"></div>\n";
    $html .= " </fieldset>\n";
    $html .= "</div>\n";
}


$html .= "<div id=\"form_progress\" class=\"form-group\"><button class=\"col-md-offset-5 btn btn-primary\" type=\"submit\">Créer la réponse</button></div>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
