<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Echec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$fauthority = Helpers::getVarFromGet("authority");
$frole =  Helpers::getVarFromGet("role");
$fname = Helpers::getVarFromGet("name");
$fgroup = Helpers::getVarFromGet("group");
$api = Helpers::getVarFromGet("api");


$myAuthority = new Authority($me->get("authority_id"));

$filter = array();
// Construction chaîne de filtrage
if ($me->isSuper()) { // Le super utilisateur voit toutes les collectivités et tous les groupes
  if (isset($fauthority) && is_numeric($fauthority)) {
	$filter[] .= "users.authority_id=" . addslashes($fauthority);
  }

  if (isset($fgroup) && is_numeric($fgroup)) {
	$filter[] .= "authorities.authority_group_id=" . addslashes($fgroup);
  }
} elseif ($me->isGroupAdmin()) {
  // Un admin de groupe ne voit forcément que les utilisateurs des collectivité appartenant à son groupe
  if (isset($fauthority) && strlen($fauthority) > 0) {
	$auth = new Authority($fauthority);
	if ($auth->isInGroup($me->get("authority_group_id"))) {
	  $filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
	}
  }
  $filter[] .= "authorities.authority_group_id='" . $me->get("authority_group_id") . "'";
} elseif ($me->isAuthorityAdmin()) {
  // Un admin d'une collectivité ne voit forcément que les utilisateurs de sa collectivité
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

// Récupération de la liste des utilisateurs en fonction du filtre
$users = $me->getUsersList($where);


$statusList = $me->get("statusTypes");
$rolesList = $me->get("roleTypes");


if ($api){
	$jsonOutput->retrictAndDisplay($users,array('id','name','givenname','email','role','status','authority_id','authority_name'));
	exit;
}
/*****************/

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : gestion des utilisateurs");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des utilisateurs";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"admin_users.php\" method=\"get\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Le rôle est&nbsp;:</td>\n";
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

  $html .= "<td class=\"title\">Collectivité&nbsp;:</td>\n";
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
$html .= " <th class=\"data\">Adresse électronique</th>\n";
$html .= " <th class=\"data\">R&ocirc;le</th>\n";
$html .= " <th class=\"data\">Etat</th>\n";
$html .= " <th class=\"data\">Collectivit&eacute;</th>\n";
$html .= " <th class=\"data\">Actions</th>\n";
$html .= "</tr>\n";

foreach ($users as $i => $user) {
  $html .= "<tr class=\"alternate" . ($i % 2 +1) . "\">\n";
  $html .= " <td>" . $user["name"] . " " . $user["givenname"] . "</td>\n";
  $html .= " <td><a href=\"mailto:" . $user["email"] . "\">" . $user["email"] . "</a></td>\n";
  $html .= " <td>" . $rolesList[$user["role"]] . "</td>\n";
  $html .= " <td>" . $statusList[$user["status"]] . "</td>\n";
  $html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $user["authority_id"] . "\">" . $user["authority_name"] . "</a></td>\n";
  $html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $user["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->buildPager($me);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

