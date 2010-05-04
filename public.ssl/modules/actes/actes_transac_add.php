<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à  la
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
 * associés au chargement,  à  l'utilisation,  à la modification et/ou au
 * développement et à  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à  
 * manipuler et qui le réserve donc à  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à  charger  et  tester  l'adéquation  du
 * logiciel à  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/

/**
 * \file actes_transac_add.php
 * \brief Page de d'ajout d'une transaction Actes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 27.07.2006
 * 
 *
 * Cette page affiche un formulaire permettant d'ajouter
 * une transaction Actes.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
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

// Collectivité de l'utilisateur courant
$myAuthority = new Authority($me->get("authority_id"));

// Parametres pour le traitement par lot
$batchFileId = Helpers :: getVarFromGet("batchfile");

// Détermination si traitement par lot ou pas
$batchMode = false;
if (isset ($batchFileId) && is_numeric($batchFileId)) {
  $zeBatchFile = new ActesBatchFile($batchFileId);
  if ($zeBatchFile->init()) {
    $zeBatch = new ActesBatch($zeBatchFile->get("batch_id"));
    if ($zeBatch->init()) {
      $owner = new User($zeBatch->get("user_id"));
      $owner->init();

      // Vérification des permissions sur le lot
      if (($me->isAuthorityAdmin && $me->get("authority_id") == $owner->get("authority_id")) || ($me->getId() == $owner->getId())) {
        $batchMode = true;
      }
    }
  }
}


$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

$trans = new ActesTransaction();

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

        function getFullPath(obj)
        {
            if(obj)
            {
                //ie
                if (window.navigator.userAgent.indexOf("MSIE")>=1)
                {
                    obj.select();
                    return document.selection.createRange().text;
                }
                //firefox
                else if(window.navigator.userAgent.indexOf("Firefox")>=1)
                {
                    if(obj.files)
                    {
                        return obj.files[0].getAsDataURL();
                    }
                    return obj.value;
                }
                return obj.value;
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
	  zeInput.setAttribute("value", getFullPath(acteFile));
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

$doc->setTitle("Tedetis : Actes - Ajout d'une transaction");

$doc->buildMenu($me);

$html = "<div id=\"info_area\">\n";



if ( ( ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY == false) || (!ActesClassification :: hasTodayRequest($myAuthority->getId()))) {


  // Zone d'information
  // Affichage du lien pour demande de mise à jour classification matières sous-matières
  if ($dateClassif = ActesClassification :: getLastRevisionDate($myAuthority->getId(), false)) {
    $html .= "La classification matières et sous-matières utilisée pour votre collectivité est la version du " . Helpers :: getDateFromBDDDate($dateClassif) . ".<br />\n";
    $html .= "Pour forcer la mise à jour de cette classification depuis le serveur du ministère, veuillez utiliser le bouton ci-dessous&nbsp;:<br />\n";
  } else {
    $html .= "Il n'existe pas encore de classification matières et sous-matières associée à votre collectivité.<br />\n";
    $html .= "Pour forcer la récupération de cette classification depuis le serveur du ministère, veuillez utiliser le bouton ci-dessous&nbsp;:<br />\n";
  }

  $html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_classification_request.php\" method=\"post\" onsubmit=\"return confirm('Voulez-vous vraiment créer une transaction de demande de classification ?');\">\n";
  $html .= "<div class=\"button_area\"><input class=\"submit_button\" type=\"submit\" value=\"Mise à jour classification\" /></div>\n";
  $html .= "</form>\n";
  $html .= "<br />\n";
}

if (!$batchMode) {
  $html .= "<h3>Note&nbsp;:</h3>\n";
  $html .= "<p>Pour générer les signatures numériques des fichiers joints, sélectionnez d'abord les fichiers dans le formulaire ci-contre puis utilisez le bouton ci-dessous. Une nouvelle fenêtre s'ouvrira permettant de signer les fichiers.</p>\n";
  $html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/applet/index.php\" method=\"post\" id=\"sign_form\" onsubmit=\"javascript:return open_sign_window();\">\n";
  $html .= "<p><input class=\"submit_button\" id=\"sign_submit_button\" type=\"submit\" value=\"Générer les signatures\" />\n";
  $html .= "</p></form>\n";
}

$html .= "</div>\n";

// Zone contenu
$html .= "<div id=\"content\">\n";
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p style=\"text-align:center\"><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"bouton\">Retour liste transactions</a></p>\n";
$html .= "<h2>Création d'une transaction Actes</h2>\n";

if ($batchMode) {
  $html .= "Transmission d'acte depuis le lot «&nbsp;" . htmlspecialchars($zeBatch->get("description")) . "&nbsp;»<br />";
  $html .= "Fichier courant&nbsp;: " . htmlspecialchars($zeBatchFile->getDisplayName()) . "<br />\n";
}

$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_create.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm(" . $trans->getValidationTrio('nature_code', 'number', 'decision_date', 'title', 'subject') . ", 'classif1', 'Classification', 'RisInt','decision_date', 'Date de la décision', 'isDatePasse'";

if (!$batchMode) {
  $html .= ", 'acte_pdf_file', 'Fichier PDF contenant l\'acte', 'RisString', 'acte_attachments[]', 'Pièces jointes', 'isString'";
}

$html .= ")) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";

if ($batchMode) {
  $html .= "<input type=\"hidden\" name=\"batchfile\" value=\"" . $zeBatchFile->getId() . "\" />\n";
}

$decision_date = Helpers :: getFromSession("decision_date");

$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";
$html .= "  <dt>Nature de l'acte&nbsp;:</dt>\n";
$html .= "   <dd>" . $doc->getHTMLSelect("nature_code", $transNatures, Helpers :: getFromSession("nature_code")) . "</dd>\n";
$html .= "  <dt>Classification&nbsp;:</dt>\n";
$html .= "   <dd>";
$html .= "   <a href=\"#tedetis\" onclick=\"javascript:window.open('" . WEBSITE_SSL . "/common/select_popup.php?type=classification', 'Select_attribut', 'location=0,scrollbars=1,menubar=0,status=0,toolbar=0,directories=0,width=512,height=500');\" id=\"classification_text\">";

$classif1 = Helpers :: getFromSession("classif1", false);
if (!empty ($classif1)) {
  $classif = array ();
  for ($i = 1; $i <= 5; $i++) {
    $classif[] = Helpers :: getFromSession("classif" . $i, false);
  }

  $html .= implode(".", $classif);
} else {
  $html .= "[&nbsp;Choisir la classification&nbsp;]";
}

$html .= "</a>\n";
$html .= "   <input type=\"hidden\" id=\"classif1\" name=\"classif1\" value=\"" . Helpers :: getFromSession("classif1") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif2\" name=\"classif2\" value=\"" . Helpers :: getFromSession("classif2") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif3\" name=\"classif3\" value=\"" . Helpers :: getFromSession("classif3") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif4\" name=\"classif4\" value=\"" . Helpers :: getFromSession("classif4") . "\" />\n";
$html .= "   <input type=\"hidden\" id=\"classif5\" name=\"classif5\" value=\"" . Helpers :: getFromSession("classif5") . "\" />\n";
$html .= "   </dd>\n";
$html .= "  <dt>Numéro de l'acte (15 caractères maxi, chiffres, lettres en majuscule ou _)&nbsp;:</dt>\n";
$html .= "   <dd><input type=\"text\" name=\"number\" value=\"";

$number = Helpers :: getFromSession("number");

if ($batchMode) {
  $number = $zeBatch->get("num_prefix") . "_" . $zeBatch->getNextSuffix();
}

$html .= htmlspecialchars($number) . "\" size=\"30\" maxlength=\"15\" title=\"15 caractères maxi, chiffres, lettres en majuscule ou _\"/></dd>\n";
$html .= "  <dt>Date de la décision&nbsp;:</dt>\n";
$html .= "   <dd>\n";
$html .= "    <input id=\"decision_date\" name=\"decision_date\" type=\"hidden\" value=\"" . $decision_date . "\"/>\n";
$html .= "    <script type=\"text/javascript\">\n";
$html .= "    //<![CDATA[\n";
$html .= "    obj_decision_date = new DatePicker('decision_date', 'fr');\n";
$html .= "    //]]>\n";
$html .= "    </script>\n";

$html .= "    <a href=\"#datepicker\" id=\"datepicker_decision_date_link\" class=\"datepicker_link\" onclick=\"javascript:obj_decision_date.toggleDatePicker(); return false;\">";

if ($decision_date) {
  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
  $html .= strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($decision_date));
} else {
  $html .= "[&nbsp;Choisir une date&nbsp;]";
}
$html .= "</a>\n";
$html .= "    <div class=\"date_picker\" style=\"display: none;\" id=\"datepicker_decision_date_calendar\"></div>\n";
$html .= "   </dd>\n";
$html .= "  <dt>Objet&nbsp;:</dt>\n";
$html .= "   <dd><textarea cols=\"60\" rows=\"7\" name=\"subject\">" . Helpers :: getFromSession("subject") . "</textarea></dd>\n";

if (!$batchMode) {
  $html .= "  <dt>Fichier PDF contenant l'acte&nbsp;:</dt>\n";
  $html .= "   <dd>\n";
  $html .= "     <dl class=\"actes_files_form\">\n";
  $html .= "      <dt>Fichier PDF&nbsp;:</dt>\n";
  $html .= "       <dd><input type=\"file\" id=\"acte_pdf_file\" name=\"acte_pdf_file\" size=\"40\" maxlength=\"255\" /></dd>\n";
  $html .= "      <dt>Fichier signature numérique (optionnel, voir ci-contre)&nbsp;:</dt>\n";
  $html .= "       <dd><input type=\"file\" id=\"acte_pdf_file_sign\" name=\"acte_pdf_file_sign\" size=\"40\" maxlength=\"255\" /></dd>\n";
  $html .= "     </dl>\n";
  $html .= "   </dd>\n";
}
$html .= "  <dt>Pièces jointes supplémentaires&nbsp;:&nbsp;<a href=\"#tedetis\" onclick=\"javascript:add_attachment_field();\" title=\"Ajouter un champ de sélection de fichier supplémentaire\">[&nbsp;Ajouter un champ&nbsp;]</a></dt>\n";
$html .= "   <dd id=\"attachments_fields\"></dd>\n";


// adresses emails de diffusion
$org = new Authority($me->get("authority_id"));
$broadcast_email = $org->get("default_broadcast_email");
#echo "TEST : ".  $org->get("default_broadcast_email") . "/" . $org->get("broadcast_email");
if ($broadcast_email != NULL) {
  $broadcast_email .= ",";
  $defaut = true;
} else
  $defaut = false;
$broadcast_email .= ACTES_COMMON_BROADCAST_EMAILS . "," . $org->get("broadcast_email");
$broadcast_email = explode(",", $broadcast_email);

$html .= "     <dt>Diffusion automatique de la notification : <input type=\"checkbox\" checked=\"checked\" name=\"show_broadcast_email\" onclick=\"hide_bloc('broadcast_email');\"/></dt>\n";
$html .= "     <dd><div id=\"broadcast_email\" style=\"visibility:visible\">\n";
$html .= "     <label>Emission des documents sources : <input type=\"checkbox\" class=\"checkbox\" name=\"send_sources\" checked='checked' /></label><br/>\n";

foreach ($broadcast_email as $email) {
  if ($defaut) {
    $checked = 'checked="checked"';
    $defaut = false;
  } else
    $checked = '';
    if ($email != "" && $email != NULL)
  		$html .= "      &nbsp;&nbsp;&nbsp;&nbsp;<label><em><input type=\"checkbox\" class=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</em></label><br />\n";
}
$html .= "     </div></dd>\n";

if ($batchMode) {
  $html .= "   <label><input type=\"checkbox\" class=\"checkbox\" name=\"process_next_batch_file\" checked=\"checked\" />&nbsp;Passer au fichier suivant dans le lot après création de cette transaction</label>\n";
}

$html .= "</dl>\n";
#$html .= "</div>\n";

$html .= "<div id=\"form_progress\"><input class=\"submit_button\" type=\"submit\" value=\"Créer la transaction\" /></div>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>
