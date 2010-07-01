<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à   la
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
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file admin_group_edit.php
 * \brief Page de modification ou d'ajout d'un groupe
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 16.01.2007
 * 
 *
 * Cette page affiche un formulaire permettant d'ajouter ou de modifier
 * un groupe.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

// Mode modification ou pas
$mod = false;
$group = new Group();

$modStr = "Ajout";
if (isset($id)) {
  $group->setId($id);
  if ($group->init()) {
    $modStr = "Modification";
    $mod = true;
  } else {
    $group = new Group();
  }
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé.";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . WEBSITE_SSL . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : " . $modStr . " groupe de collectivité");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion groupe de collectivités</h1>\n";

$html .= "<center><a href=\"" . WEBSITE_SSL . "/admin/groups/admin_groups.php\" class=\"bouton\">Retour liste groupes</a></center>\n";

$html .= "<h2>" . $modStr . " groupe de collectivités</h2>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/admin/groups/admin_group_edit_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $group->getValidationTrio('name', 'status') . ")\">\n";

if ($mod) {
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $group->getId() . "\" />\n";
  $html .= "<input type=\"hidden\" name=\"mode\" value=\"modify\" />\n";
} else {
  $html .= "<input type=\"hidden\" name=\"mode\" value=\"create\" />\n";
}

$html .= "<div class=\"data_table\">\n";
$html .= "<table style=\"width: 100%\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"name\" value=\"";
$html .= ($mod) ? htmlspecialchars($group->get("name")) : Helpers::getFromSession("name");
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">État&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";

$status = ($mod) ? $group->get("status") : Helpers::getFromSession("status");

$html .= $doc->getHTMLSelect("status", $group->get("statusTypes"), $status);
$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Liste des SIREN autorisés&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";
$html .= "  <input type=\"file\" name=\"siren_file\" size=\"30\" maxlength=\"255\" />";
$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"";
$html .= ($mod) ? "Valider les modifications" : "Ajouter le groupe";
$html .= "\" /></center>\n";
$html .= "</form>\n";

if ($mod && $group->isEmpty($group->getId())) {
  $html .= "<form action=\"" . WEBSITE_SSL . "/admin/groups/admin_group_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement ce groupe ?')\" method=\"post\">\n";
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $group->getId(). "\" />\n";
  $html .= "<input type=\"submit\" value=\"Supprimer ce groupe\" class=\"bouton-danger\" />\n";
  $html .= "</form>\n";
}

$sirenList = $group->getAuthorizedSiren();

$html .= "<h2>Liste des SIREN autorisés pour ce groupe</h2>\n";

if (count($sirenList) > 0) {
  $html .= "<ul>\n";
  foreach ($sirenList as $siren) {
	$html .= "<li>" . $siren . "</li>\n";
  }
  $html .= "</ul>\n";
} else {
  $html .= "Pas de SIREN autorisé.";
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>