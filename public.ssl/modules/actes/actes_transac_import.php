<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
 * termes.
*/
?>
<?php
/**
 * \file actes_transac_import.php
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
if (! $module->initByName("actes")) {
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
  $_SESSION["error"] = "Mode «&nbsp;papier&nbsp;» actif. Accès interdit.";
  header("Location: " . WEBSITE_SSL . "/modules/actes/");
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

$doc->setTitle("Tedetis : Actes - Import d'une enveloppe");

$doc->buildMenu($me);

// Zone contenu
$html .= "<div id=\"content\">\n";
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p style='text-align:center'><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"bouton\">Retour liste transactions</a></p>\n";
$html .= "<h2>Import d'une enveloppe</h2>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_submit.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm('enveloppe', 'Fichier enveloppe', 'RisString')) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";
$html .= "<p>Indiquez le fichier archive de l'enveloppe à importer (taille maximum 20Mo)&nbsp;:<br />\n";
$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"20971520\" />\n";
$html .= "<input type=\"file\" id=\"enveloppe\" name=\"enveloppe\" size=\"40\" maxlength=\"255\" /><br /></p>\n";
$html .= "<div id=\"form_progress\"><input class=\"submit_button\" type=\"submit\" value=\"Importer l'enveloppe\" /></div>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>
