<?php

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

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

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

if ($module->getParam("paper") == "on") {
  $_SESSION["error"] = "Mode «&nbsp;papier&nbsp;» actif. Accès interdit.";
  header("Location: " . WEBSITE_SSL . "/modules/actes/");
  exit ();
}

$related_id = Helpers::getVarFromPost("id");
if (!$related_id){
	$related_id = Helpers::getVarFromGet("id");
}

$trans = new ActesTransaction($related_id);
$trans->init();
$transactionTypes = $trans->get("transactionTypes");


$typeReponse = array(
3	=> array(4 => "Transmission de pièces complémentaires",
			3 => "Refus explicite d'envoi de pièces complémentaires"
			),
4 => array(4 => "Lettre de justification de l'acte",
			3 => "Rejet explicite d'une lettre d'observations")
);

$doc = new HTMLLayout();

$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"".WEBSITE_SSL."/custom/styles/date-picker.css\" />");
$doc->addHeader("<script src=\"".WEBSITE_SSL."/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<script src=\"".WEBSITE_SSL."/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$js =<<<EOJS
<script type="text/javascript">
//<![CDATA[
var field_nb = 1;

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

function add_attachment_field() {
  field = document.getElementById("attachments_fields");
  newfield=document.createElement("div");
  html = '     <dl class="actes_files_form">';
  html += '      <dt>Pièce jointe n°' + field_nb + ' (.pdf, .png ou .jpg)&nbsp;:\\x3C/dt>';
  html += '       <dd><input type="file" id="acte_attachments_' + field_nb + '" name="acte_attachments[]" size="40" maxlength="255" />\\x3C/dd>';
EOJS;

if (!$batchMode) {
  $js .=<<<EOJS
	html += '      <dt>Fichier signature numérique pièce jointe n°' + field_nb + ' (optionnel)&nbsp;:\\x3C/dt>';
  	html += '       <dd><input type="file" id="acte_attachments_sign_' + field_nb + '" name="acte_attachments_sign[]" size="40" maxlength="255" />\\x3C/dd>';
EOJS;
}

$js .=<<<EOJS
html += '    \\x3C/dl>';

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

$html .= "<div id=\"info_area\">\n";

$html .= "<h3>Note&nbsp;:</h3>\n";
$html .= "<p>Pour générer les signatures numériques des fichiers joints, sélectionnez d'abord les fichiers dans le formulaire ci-contre puis utilisez le bouton ci-dessous. Une nouvelle fenêtre s'ouvrira permettant de signer les fichiers.</p>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/applet/index.php\" method=\"post\" id=\"sign_form\" onsubmit=\"javascript:return open_sign_window();\">\n";
$html .= "<p><input class=\"submit_button btn btn-default\" id=\"sign_submit_button\" type=\"submit\" value=\"Générer les signatures\" />\n";
$html .= "</p></form>\n";
$html .= "</div>\n";
$doc->closeSideBar();
$doc->openContent();

// Zone contenu
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";
$html .= "<h2>Réponse à un courrier</h2>\n";


$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= $doc->getHTMLArrayline("Type de transaction", $transactionTypes[$trans->get("type")]);
$html .= $doc->getHTMLArrayline("Acte ", 
	"<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $trans->getId() . "\">"
	 . $trans->get("number") . "</a>");
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_reponse_create.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm(" . $trans->getValidationTrio('nature_code', 'number', 'decision_date', 'title', 'subject') . ", 'classif1', 'Classification', 'RisInt'";
$html .= ", 'acte_pdf_file', 'Fichier PDF contenant la réponse', 'RisString', 'acte_attachments[]', 'Pièces jointes', 'isString'";
$html .= ")) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";

$html .= "<input type='hidden' name='id' value='".$related_id."'/>";

$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";

if ($trans->get("type") == 3 || $trans->get("type") == 4){
$html .= "  <dt>Nature de l'envoi:</dt>\n";
$html .= "   <dd>" . $doc->getHTMLSelect("type_envoie",$typeReponse[$trans->get("type")], 
		Helpers :: getFromSession("type_envoie")) . "</dd>\n";
}


$html .= "  <dt>Fichier PDF contenant la réponse &nbsp;:</dt>\n";
$html .= "   <dd>\n";
$html .= "     <dl class=\"actes_files_form\">\n";
$html .= "      <dt>Fichier PDF&nbsp;:</dt>\n";
$html .= "       <dd><input type=\"file\" id=\"acte_pdf_file\" name=\"acte_pdf_file\" size=\"40\" maxlength=\"255\" /></dd>\n";
$html .= "      <dt>Fichier signature numérique (optionnel, voir ci-contre)&nbsp;:</dt>\n";
$html .= "       <dd><input type=\"file\" id=\"acte_pdf_file_sign\" name=\"acte_pdf_file_sign\" size=\"40\" maxlength=\"255\" /></dd>\n";
$html .= "     </dl>\n";
$html .= "   </dd>\n";

if ($trans->get("type") == 3) {  
	$html .= "  <dt>Pièces jointes supplémentaires&nbsp;:&nbsp;<a href=\"#tedetis\" onclick=\"javascript:add_attachment_field();\" title=\"Ajouter un champ de sélection de fichier supplémentaire\">[&nbsp;Ajouter un champ&nbsp;]</a></dt>\n";
	$html .= "   <dd id=\"attachments_fields\"></dd>\n";  
}


$html .= "</dl>\n";


$html .= "<div id=\"form_progress\"><input class=\"btn btn-default\" type=\"submit\" value=\"Créer la réponse\" /></div>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
?>
