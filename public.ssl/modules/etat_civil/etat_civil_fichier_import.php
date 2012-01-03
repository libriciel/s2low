<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
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
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file etat_civil_transac_import.php
 * \brief Page permettannt l'import d'une enveloppe .tar.gz
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 11.08.2006
 * 
 *
 * Cette page affiche un formulaire permettant d'importer
 * une archive enveloppe.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("etat_civil")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive()||!$me->canEdit($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

if ($module->getParam("paper") == "on") {
  $_SESSION["error"] = "Mode &nbsp;papier&nbsp; actif. Accès interdit.";
  header("Location: " . WEBSITE_SSL . "/modules/etat_civil/");
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : etat_civil - Import d'une enveloppe");

$doc->buildMenu($me);

// Zone contenu 
//TO DO : imposer une taille max. pour le fichier
$html .= "<div id=\"content\">\n";
$html .= "<h1>Dématérialisation de documents concernant l'état civil</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/etat_civil/\" class=\"bouton\">Retour liste transactions</a></center>\n";
$html .= "<h2>Import d'un fichier</h2>\n";
$html .="<form method=\"POST\" enctype=\"multipart/form-data\" ";
$html .=" action=\"" . WEBSITE_SSL . "/modules/etat_civil/etat_civil_script_reception.php\" > ";
$html .="<input type=\"FILE\" name=\"enveloppe\"> <br>";
$html .="<input class=\"submit_button\" type=\"submit\" value=\" Importer un fichier\" >";
$html .="</form>";



/*
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_submit.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"form\" onsubmit=\"javascript:if (validateForm('enveloppe', 'Fichier enveloppe', 'RisString')) {toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";
$html .= "Indiquez le fichier archive de l'enveloppe à importer (taille maximum 20Mo)&nbsp;:<br />\n";
$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"20971520\" />\n";
$html .= "<input type=\"file\" id=\"enveloppe\" name=\"enveloppe\" size=\"40\" maxlength=\"255\" /><br />\n";
$html .= "<div id=\"form_progress\"><input class=\"submit_button\" type=\"submit\" value=\"Importer l'enveloppe\" /></div>\n";
$html .= "</form>\n";
*/

//pour le teste de l'API

$html .= "<h2>Teste (API) de récuperation du status du fichier à partir de la transaction</h2>\n";
$html .="<form method=\"GET\"";
$html .=" action=\"" . WEBSITE_SSL . "/modules/etat_civil/etat_civil_transac_get_status.php\" > ";
$html .="<input type=\"text\" name=\"transaction\" value=\"0\"> <br>";
$html .="<input class=\"submit_button\" type=\"submit\" value=\"Recuperer le status crt\" >";
$html .="</form>";
// sf teste



$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>
