<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

$jsonOutput = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);

$api = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("api");

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}


$zeMod = new Module();
$modules = $zeMod->getModulesList("ORDER BY id ASC");
$statusList = $zeMod->get("statusTypes");

if ($me->isGroupAdminOrSuper() && $api) {
    $jsonOutput->retrictAndDisplay($modules, array('id','name','description'));
    exit;
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Gestion des modules");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des modules</h1>\n";
$html .= "<h2>Liste des modules</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data-table table table-striped \">";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"name\">Nom</th>\n";
$html .= " <th id=\"description\">Description</th>\n";
$html .= " <th id=\"status\">État</th>\n";
$html .= " <th id=\"actions\">Actions</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

$i = 0;

foreach ($modules as $module) {
    $html .= "<tr>\n";
    $html .= " <td headers=\"name\">" . $module["name"] . "</td>\n";
    $html .= " <td headers=\"description\">" . $module["description"] . "</td>\n";
    $html .= " <td headers=\"status\">" . $statusList[$module["status"]] . "</td>\n";
    $html .= " <td  headers=\"actions\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_module_edit.php?id=" . $module["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n");
    $html .= "</tr>\n";
}
$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
