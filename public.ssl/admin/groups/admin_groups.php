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
 * \file admin_groups.php
 * \brief Page d'accueil de la section modification ou ajout de groupes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 12.01.2007
 * 
 *
 * Cette page affiche la liste des groupes et permet de les
 * modifier ou d'en ajouter.
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
  $_SESSION["error"] = "ï¿½hec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$fname = isset($_GET["name"]) ? $_GET["name"] : null;

$group = new Group();

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : gestion des groupes de collectivités");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des groupes de collectivités</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"admin_groups.php\" method=\"get\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Le nom contient&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (strlen($fname) > 0) {
  $html .= " value=\"" . htmlspecialchars($fname) . "\"";
}

$html .= " /></td>\n";
$html .= "</tr>\n";
$html .= "<tr>\n";
$html .= "<td colspan=\"4\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/admin/groups/admin_group_edit.php\" class=\"bouton\">Ajouter un groupe</a></center>\n";
$html .= "<h2>Liste des groupes de collectivités</h2>\n";
$html .= "<div class=\"data_table\">\n";

$filter = array();
// Construction chaï¿½e de filtrage
if (isset($fname) && strlen($fname) > 0) {
  $filter[] .= "authority_groups.name ILIKE '%" . addslashes($fname) . "%'";
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

$statusList = $me->get("statusTypes");
$groups = $group->getGroupsList($where);

if (is_array($groups)) {
  $html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
  $html .= "<tr>\n";
  $html .= " <th class=\"data\">Nom</th>\n";
  $html .= " <th class=\"data\">État</th>\n";
  $html .= " <th class=\"data\">Actions</th>\n";
  $html .= "</tr>\n";

  $i = 0;

  foreach ($groups as $ent) {
	$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
	$html .= " <td>" . $ent["name"] . "</td>\n";
	$html .= " <td>" . $statusList[$ent["status"]] . "</td>\n";
	$html .= " <td><a href=\"admin_group_edit.php?id=" . $ent["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
	$html .= "</tr>\n";
	
	$i = ($i + 1) % 2;
  }

  $html .= "</table>\n";
} else {
  $html .= "Pas de groupe correspondant aux critères de filtrage";
}

$html .= "</div>\n";
$html .= "</div>\n";

$doc->buildPager($group);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>