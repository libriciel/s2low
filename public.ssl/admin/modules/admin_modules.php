<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$api = Helpers::getVarFromGet("api");

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}


$zeMod = new Module();
$modules = $zeMod->getModulesList();
$statusList = $zeMod->get("statusTypes");

if($me->isGroupAdminOrSuper() && $api){
	$jsonOutput->retrictAndDisplay($modules,array('id','name','description'));
	exit;
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Gestion des modules");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des modules</h1>\n";
$html .= "<h2>Liste des modules</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
$html .= "<tr>\n";
$html .= " <th class=\"data\">Nom</th>\n";
$html .= " <th class=\"data\">Description</th>\n";
$html .= " <th class=\"data\">État</th>\n";
$html .= " <th class=\"data\">Actions</th>\n";
$html .= "</tr>\n";

$i = 0;

foreach ($modules as $module) {
  $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
  $html .= " <td>" . $module["name"] . "</td>\n";
  $html .= " <td>" . $module["description"] . "</td>\n";
  $html .= " <td>" . $statusList[$module["status"]] . "</td>\n";
  $html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/modules/admin_module_edit.php?id=" . $module["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";

  $i = ($i + 1) % 2;
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
