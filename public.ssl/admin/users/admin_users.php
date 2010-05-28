<?php
/*
 * T�D�TIS - Copyright 2006 Alternance-Soft
 * Contributeur : J�r�me Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant �  la
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
 * associés au chargement,  �  l'utilisation,  �  la modification et/ou au
 * développement et �  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe �  
 * manipuler et qui le réserve donc �  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités �  charger  et  tester  l'adéquation  du
 * logiciel �  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * � l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder �  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file admin_users.php
 * \brief Page d'accueil de la section modification ou ajout d'utilisateur
 * \author J�r�me Schell <j.schell@alternancesoft.com>
 * \date 16.03.2006
 * 
 *
 * Cette page affiche la liste des utilisateurs et permet de les
 * modifier ou d'en ajouter.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   17.07.2006  Adaptation pour Tedetis
 */


// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "�chec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Acc�s refus�";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$fauthority = Helpers::getVarFromGet("authority");
$frole =  Helpers::getVarFromGet("role");
$fname = Helpers::getVarFromGet("name");
$fgroup = Helpers::getVarFromGet("group");

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : gestion des utilisateurs");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des utilisateurs";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivit� �&nbsp;" . $myAuthority->get("name") . "&nbsp;�";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe �&nbsp;" . $myGroup->get("name") . "&nbsp;�";
}

$html .= "</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"admin_users.php\" method=\"get\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Le r�le est&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("role", $me->get("roleTypes"), $frole) . "</td>\n";
$html .= "<td class=\"title\">Le nom contient&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (strlen($fname) > 0) {
  $html .= " value=\"" . htmlspecialchars($fname) . "\"";
}

$html .= " /></td>\n";
$html .= "</tr>\n";
$html .= "<tr>\n";

$colspan = 4;
if ($me->isGroupAdminOrSuper()) {
  if ($me->isGroupAdmin()) {
	$cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
	$colspan = 2;
  } else {
	$cond = " ORDER BY authorities.name ASC";
  }

  $html .= "<td class=\"title\">Collectivit�&nbsp;:</td>\n";
  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("authority", Authority::getAuthoritiesIdName($cond), $fauthority) . "</td>\n";
}

if ($me->isSuper()) {
  $html .= "<td class=\"title\">Groupe&nbsp;:</td>\n";
  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("group", Group::getGroupsIdName(), $fgroup) . "</td>\n";
  $html .= "</tr>\n";
  $html .= "<tr>\n";
}

$html .= "<td colspan=\"" . $colspan . "\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";
$html .= "<center><a href=\"admin_user_edit.php\" class=\"bouton\">Ajouter un utilisateur</a></center>\n";
$html .= "<h2>Liste des utilisateurs</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
$html .= "<tr>\n";
$html .= " <th class=\"data\">Nom</th>\n";
$html .= " <th class=\"data\">Adresse �lectronique</th>\n";
$html .= " <th class=\"data\">R&ocirc;le</th>\n";
$html .= " <th class=\"data\">�tat</th>\n";
$html .= " <th class=\"data\">Collectivit&eacute;</th>\n";
$html .= " <th class=\"data\">Actions</th>\n";
$html .= "</tr>\n";

$filter = array();
// Construction cha�ne de filtrage
if ($me->isSuper()) { // Le super utilisateur voit toutes les collectivit�s et tous les groupes
  if (isset($fauthority) && is_numeric($fauthority)) {
	$filter[] .= "users.authority_id=" . addslashes($fauthority);
  }

  if (isset($fgroup) && is_numeric($fgroup)) {
	$filter[] .= "authorities.authority_group_id=" . addslashes($fgroup);
  }
} elseif ($me->isGroupAdmin()) {
  // Un admin de groupe ne voit forc�ment que les utilisateurs des collectivit� appartenant � son groupe
  if (isset($fauthority) && strlen($fauthority) > 0) {
	$auth = new Authority($fauthority);
	if ($auth->isInGroup($me->get("authority_group_id"))) {
	  $filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
	}
  }
  $filter[] .= "authorities.authority_group_id='" . $me->get("authority_group_id") . "'";
} elseif ($me->isAuthorityAdmin()) {
  // Un admin d'une collectivit� ne voit forc�ment que les utilisateurs de sa collectivit�
  $filter[] .= "users.authority_id='" . $me->get("authority_id") . "'";
}

if (isset($frole) && strlen($frole) > 0) {
  $filter[] .= "users.role='" . addslashes($frole) . "'";
}

if (isset($fname) && strlen($fname) > 0) {
  $filter[] .= "users.name ILIKE '%" . addslashes($fname) . "%'";
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

// R�cup�ration de la liste des utilisateurs en fonction du filtre
$users = $me->getUsersList($where);
$i = 0;

$statusList = $me->get("statusTypes");
$rolesList = $me->get("roleTypes");

foreach ($users as $user) {
  $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
  $html .= " <td>" . $user["givenname"] . " " . $user["name"] . "</td>\n";
  $html .= " <td><a href=\"mailto:" . $user["email"] . "\">" . $user["email"] . "</a></td>\n";
  $html .= " <td>" . $rolesList[$user["role"]] . "</td>\n";
  $html .= " <td>" . $statusList[$user["status"]] . "</td>\n";
  $html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $user["authority_id"] . "\">" . $user["authority_name"] . "</a></td>\n";
  $html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $user["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";

  $i = ($i + 1) % 2;
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->buildPager($me);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
