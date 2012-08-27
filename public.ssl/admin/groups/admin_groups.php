<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Ehec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$fname = Helpers::getVarFromGet("name");
$api = Helpers::getVarFromGet("api");

$group = new Group();

$filter = array();
if (isset($fname) && strlen($fname) > 0) {
  $filter[] .= "authority_groups.name ILIKE '%" . addslashes($fname) . "%'";
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

$statusList = $me->get("statusTypes");


$groups = $group->getGroupsList($where);

if ($api){
	$jsonOutput->display($groups);
	exit;
}

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