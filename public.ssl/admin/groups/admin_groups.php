<?php

use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

/** @var HTMLLayoutFactory $htmlLayoutFactory */
list($jsonOutput, $htmlLayoutFactory) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [JSONoutput::class, HTMLLayoutFactory::class],
    );
$html = '';
$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Ehec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
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
if (isset($fname) && mb_strlen($fname) > 0) {
    $filter[] = "authority_groups.name ILIKE '%" . addslashes($fname) . "%'";
}

$where = "";
if (count($filter) > 0) {
    $where = "WHERE " . implode(" AND ", $filter);
}

$statusList = $me->get("statusTypes");


$groups = $group->getGroupsList($where);

if ($api) {
    $jsonOutput->display($groups);
    exit;
}

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle("Tedetis : gestion des groupes de collectivités");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->buildPager($group);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des groupes de collectivités</h1>\n";
$html .= "<h2>Actions</h2>\n";
$html .= "<a href=\"" . Helpers::getLink("/admin/groups/admin_group_edit.php\" class=\"btn btn-primary\">Ajouter un groupe</a>\n");
$html .= "<a href=\"" . Helpers::getLink("/admin/groups/list_groups.php\" class=\"btn btn-primary\">Liste des groupes</a>\n");
$html .= "<div id=\"filtering-area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form class=\"form form-horizontal\" action=\"admin_groups.php\" method=\"get\">\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"name-contain\" class=\"col-md-2 control-label\">Le nom contient</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"name-contain\" class=\"form-control\" type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (mb_strlen($fname ?? '') > 0) {
    $html .= " value=\"" . get_hecho($fname) . "\"";
}

$html .= " /></div>\n";
$html .= "</div>\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<button class=\"btn btn-default col-md-offset-2 col-md-3\" type=\"submit\">Filtrer</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";
$html .= "<h2>Liste des groupes de collectivités</h2>\n";
$html .= "<div class=\"data_table\">\n";

if (is_array($groups)) {
    $html .= "<table class=\"data-table table table-striped \">";
    $html .= "<thead>\n";
    $html .= "<tr>\n";
    $html .= " <th id=\"name\">Nom</th>\n";
    $html .= " <th id=\"status\">État</th>\n";
    $html .= " <th id=\"action\">Actions</th>\n";
    $html .= "</tr>\n";
    $html .= "</thead>\n";
    $html .= "<tbody>\n";

    $i = 0;

    foreach ($groups as $ent) {
        $html .= "<tr>\n";
        $html .= " <td headers=\"name\">" . get_hecho($ent["name"]) . "</td>\n";
        $html .= " <td headers=\"status\">" . $statusList[$ent["status"]] . "</td>\n";
        $html .= " <td headers=\"actions\"><a href=\"admin_group_edit.php?id=" . $ent["id"] . "\" class=\"icon\"><img src=\"" . get_url("/custom/images/erreur.png") . "\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
        $html .= "</tr>\n";
    }

    $html .= "</tbody>\n";
    $html .= "</table>\n";
} else {
    $html .= "<p>Pas de groupe correspondant aux critères de filtrage</p>";
}

$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
