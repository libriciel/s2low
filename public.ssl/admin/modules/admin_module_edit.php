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
 * \file admin_module_edit.php
 * \brief Page d'ajout ou de modification de modules
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 * 
 *
 * Cette page permet d'activer ou désactiver un module et ajouter
 * ou modifier ses paramètres.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  BF	    26.07.2006  Modifications du formulaire 
 */

//! Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

$me = new User();

if (!$me->authenticate()) {
	$_SESSION["error"] = "Échec de l'authentification";
	header("Location: " . WEBSITE);
	exit ();
}

if (!$me->isSuper()) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit ();
}

$id = isset ($_GET["id"]) ? $_GET["id"] : null;

$mod = false;
$zeModule = new Module();

if (isset ($id) && !empty ($id)) {
  $zeModule->setId($id);
  if ($zeModule->init()) {
	$mod = true;
  }
}

if (! $mod) {
  $_SESSION["error"] = "Pas d'identifiant de module spécifié.";
  header("Location: " . WEBSITE_SSL . "/admin/modules/admin_modules.php");
  exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"/" . WEBSITE_SSL . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Modification d'un module");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des modules</h1>\n";
$html .= "<center><a href=\"admin_modules.php\" class=\"bouton\">Retour liste modules</a></center>\n";
$html .= "<h2>Modification d'un module</h2>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/admin/modules/admin_module_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm(" . $zeModule->getValidationTrio('status') . ");\">\n";
$html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeModule->getId() . "\" />\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">" . htmlspecialchars($zeModule->get("name")) . "</td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Description&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">" . htmlspecialchars($zeModule->get("description")) . "</td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">État&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";

$html .= $doc->getHTMLSelect("status", $zeModule->get("statusTypes"), $zeModule->get("status"));

$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";

//! On récupère la liste des paramètres du modules dans le tableau module_params
$module_params = $zeModule->getModuleParams();

if (count($module_params)>0) {
  //! Le module a un ou plusieurs paramètres, on affiche la table des paramètres
  $tr_style = "alternate1";

  $html .= "<h2>Modification/Suppression des param&egrave;tres</h2>\n";
  $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"data\">\n";
  $html .= "<tr>\n";
  $html .= "  <th class=\"data\">Suppression</th>\n";
  $html .= "  <th class=\"data\">Nom du param&egrave;tre</th>\n";
  $html .= "  <th class=\"data\">Valeur du param&egrave;tre</th>\n";
  $html .= "  <th class=\"data\">Description du param&egrave;tre</th>\n";
  $html .= "</tr>\n";

  foreach ($module_params as $param) {
	$html .= "<tr class=\"" . $tr_style . "\">\n";
	$html .= "  <td class=\"td-input\"><input type=\"hidden\" name=\"param_id[]\" value=\"" . $param["id"] . "\"/><input type=\"checkbox\" name=\"param_to_suppr[]\" value=\"".$param["id"]."\" /></td>";
	$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"param_name[]\" value=\"" . htmlspecialchars($param["name"]) . "\" /></td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" size=\"15\" maxlength=\"70\" name=\"param_value[]\" value=\"" . htmlspecialchars($param["value"]) . "\" /></td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" size=\"40\" maxlength=\"70\" name=\"param_description[]\" value=\"" . htmlspecialchars($param["description"]) . "\" /></td>\n";
	$html .= "</tr>\n";
	$tr_style = ($tr_style == "alternate1") ? "alternate2" : "alternate1";
  }
  $html .= "</table>\n";
  $html .= "</div>\n";
}

$html .= "<h2>Ajout d'un param&egrave;tre</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= "<tr>\n";
$html .= "  <th class=\"data\">Nom du param&egrave;tre</th>\n";
$html .= "  <th class=\"data\">Valeur du param&egrave;tre</th>\n";
$html .= "  <th class=\"data\">Description du param&egrave;tre</th>\n";
$html .= "</tr>\n";
$html .= "<tr class=\"".$tr_style."\">\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"new_param_name\" /></td>\n";
$html .= "  <td class=\"td-input\"><input size=\"15\" maxlength=\"70\" type=\"text\" name=\"new_param_value\" /></td>\n";
$html .= "  <td class=\"td-input\"><input size=\"40\" maxlength=\"70\" type=\"text\" name=\"new_param_description\" /></td>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"";
$html .= "Valider les modifications";
$html .= "\" /></center>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>