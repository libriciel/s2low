<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isGroupAdminOrSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$ftype =  Helpers::getVarFromGet("type");
$fname = Helpers::getVarFromGet("name");
$fgroup = Helpers::getVarFromGet("group");
$api = Helpers::getVarFromGet("api");

$authority = new Authority();

$filter = array();
if ($me->isGroupAdmin()) {
  $filter[] .= "authorities.authority_group_id=" . addslashes($me->get("authority_group_id"));
}

if (isset($ftype) && strlen($ftype) > 0) {
  $filter[] .= "authorities.authority_type_id='" . addslashes($ftype) . "'";
}

if (isset($fname) && strlen($fname) > 0) {
  $filter[] .= "authorities.name ILIKE '%" . addslashes($fname) . "%'";
}

if (isset($fgroup) && is_numeric($fgroup)) {
  $filter[] .= "authorities.authority_group_id=" . addslashes($fgroup);
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

$authorities = $authority->getAuthoritiesList($where);

if ($api){
	$jsonOutput->retrictAndDisplay($authorities,array('id','name','authority_group_id','address','city','postal_code','telephone'));
	exit;
}


$doc = new HTMLLayout();
$doc->setTitle("Tedetis : gestion des collectivités");
$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des collectivités";

if ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe " . htmlspecialchars($myGroup->get("name"));
}

$html .= "</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"admin_authorities.php\" method=\"get\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Type&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("type", Authority::getAuthorityTypesIdName(), $ftype) . "</td>\n";
$html .= "<td class=\"title\">Le nom contient&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (strlen($fname) > 0) {
  $html .= " value=\"" . htmlspecialchars($fname) . "\"";
}

$html .= " /></td>\n";
$html .= "</tr>\n";
$html .= "<tr>\n";

$colspan = 4;
if ($me->isSuper()) {
  $colspan = 2;
  $html .= "<td class=\"title\">Groupe&nbsp;:</td>\n";
  $html .= "<td class=\"value\">" . $doc->getHTMLSelect("group", Group::getGroupsIdName(), $fgroup) . "</td>\n";
}

$html .= "<td colspan=\"" . $colspan . "\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php\" class=\"bouton\">Ajouter une collectivité</a></center>\n";
$html .= "<h2>Liste des collectivités</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
$html .= "<tr>\n";
$html .= " <th class=\"data\">Nom</th>\n";
$html .= " <th class=\"data\">Groupe</th>\n";
$html .= " <th class=\"data\">Type de collectivité</th>\n";
$html .= " <th class=\"data\">Adresse</th>\n";
$html .= " <th class=\"data\">Téléphone</th>\n";
$html .= " <th class=\"data\">Fax</th>\n";
$html .= " <th class=\"data\">Actions</th>\n";
$html .= "</tr>\n";

$i = 0;

foreach ($authorities as $ent) {
  $group = new Group($ent["authority_group_id"]);

  if (strlen($group->get("name")) > 0) {
	$groupName = $group->get("name");
  } else {
	$groupName = "Aucun";
  }

  $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
  $html .= " <td>" . $ent["name"] . "</td>\n";
  $html .= " <td>" . $groupName . "</td>\n";
  $html .= " <td>" . $ent["type_name"] . "</td>\n";
  $html .= " <td class=\"long_field\">" . nl2br($ent["address"]) . "<br />" . $ent["postal_code"] . " " . $ent["city"] . "</td>\n";
  $html .= " <td>" . $ent["telephone"] . "</td>\n";
  $html .= " <td>" . $ent["fax"] . "</td>\n";
  $html .= " <td><a href=\"admin_authority_edit.php?id=" . $ent["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";

  $i = ($i + 1) % 2;
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->buildPager($authority);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
