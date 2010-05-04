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
 * \file install.php
 * \brief Page d'initialisation du site Tedetis
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 19.08.2006
 * 
 *
 * Page d'initialisation du site TéDéTis (non authentifié).
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */


// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Vérification si l'application est déjà configurée ou pas
if (User::dbHasUser()) {
  $_SESSION["error"] = "L'application est déjà configurée.";
  header("Location: " . WEBSITE);
  exit();
}

$him = new User();

$doc = new HTMLLayout("xhtml_home_install.tpl.php");

$doc->addHeader("<script src=\"" . WEBSITE . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : initialisation de l'application");

$html = "<div id=\"content\">\n";
$html .= "<h1>Création de l'administrateur initial de l'application</h1>\n";
$html .= "<form action=\"" . WEBSITE . "/install_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $him->getValidationTrio('name', 'givenname', 'email') . ", 'certificate', 'Certificat utilisateur', 'RisString');\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"name\"  size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Pr&eacute;nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"givenname\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"email\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Téléphone&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"telephone\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Importer le certificat utilisateur (format PEM)&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"file\" name=\"certificate\" /></td>\n";
$html .= " </tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"Ajouter l'utilisateur\" /></center>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->display();
?>
