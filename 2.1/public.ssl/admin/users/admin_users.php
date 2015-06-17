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

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->buildPager($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des utilisateurs";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<div id=\"actions-area\">\n";
$html .= "<h2>Actions</h2>";
$html .= "<a href=\"admin_user_edit.php\" class=\"btn btn-primary\">Ajouter un utilisateur</a>\n";
$html .= "</div>\n";
$html .= "<div id=\"filtering-area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"admin_users.php\" method=\"get\" class=\"form-horizontal\"> \n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"role\" class=\"col-md-3 control-label\">Le rôle est</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("role", $me->get("roleTypes"), $frole) . "</div>\n";
$html .= "<label for=\"name\" class=\"col-md-3 control-label\">Le nom contient</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"name\" class=\"form-control\" type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (strlen($fname) > 0) {
  $html .= " value=\"" . get_hecho($fname) . "\"";
}

$html .= " /></div>\n";
$html .= "</div>\n";

if ($me->isGroupAdminOrSuper()) {
  if ($me->isGroupAdmin()) {
	$cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
	$colspan = 2;
  } else {
	$cond = " ORDER BY authorities.name ASC";
  }
  $html .= "<div class=\"form-group\">\n";
  $html .= "<label for=\"authority\" class=\"col-md-3 control-label\">Collectivité</label>\n";
  $html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("authority", Authority::getAuthoritiesIdName($cond), $fauthority) . "</div>\n";
  $html .= "</div>\n";
}

if ($me->isSuper()) {
  $html .= "<div class=\"form-group\">\n";
  $html .= "<label for=\"group\" class=\"col-md-3 control-label\">Groupe</label>\n";
  $html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("group", Group::getGroupsIdName(), $fgroup) . "</div>\n";
  $html .= "</div>\n";
}
$html .= "<div class=\"form-group\">\n";
$html .= "<button class=\"btn btn-default col-md-offset-3 col-md-3\" type=\"submit\">Filtrer</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div>\n";
$html .= "<h2>Liste des utilisateurs</h2>\n";
$html .= "<div id=\"user-list\">\n";
$html .= "<table class=\"data-table table table-striped\" summary=\"\">";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"name\">Nom</th>\n";
$html .= " <th id=\"email\">Adresse électronique</th>\n";
$html .= " <th id=\"role\">R&ocirc;le</th>\n";
$html .= " <th id=\"status\">Etat</th>\n";
$html .= " <th id=\"authority\">Collectivit&eacute;</th>\n";
$html .= " <th id=\"actions\">Actions</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

foreach ($users as $i => $user) {
  $html .= "<tr>\n";
  $html .= " <td headers=\"name\">" . $user["name"] . " " . $user["givenname"] . "</td>\n";
  $html .= " <td headers=\"email\"><a href=\"mailto:" . $user["email"] . "\">" . $user["email"] . "</a></td>\n";
  $html .= " <td headers=\"role\">" . $rolesList[$user["role"]] . "</td>\n";
  $html .= " <td headers=\"status\">" . $statusList[$user["status"]] . "</td>\n";
  $html .= " <td headers=\"authority\"><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $user["authority_id"] . "\">" . $user["authority_name"] . "</a></td>\n";
  $html .= " <td headers=\"actions\"><a href=\"" . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $user["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";
}

$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

