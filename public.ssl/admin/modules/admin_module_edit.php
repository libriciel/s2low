<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$html = '';

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (!$me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
$zeModule = null;

if (isset($id) && !empty($id)) {
    $zeModule = $moduleSQL->getById($id);
    if ($zeModule) {
        $mod = true;
    }
}

if (! $mod) {
    $_SESSION["error"] = "Pas d'identifiant de module spécifié.";
    header("Location: " . Helpers::getLink("/admin/modules/admin_modules.php"));
    exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"/" . Helpers::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));

$doc->setTitle("Modification d'un module");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des modules</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a href=\"admin_modules.php\" class=\"btn btn-default\">Retour liste modules</a></p>\n";
$html .= "<h2>Modification d'un module</h2>\n";
$html .= "<form action=\"" . Helpers::getLink("/admin/modules/admin_module_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm(" . $zeModule->getValidationTrio('status') . ");\">\n");
$html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeModule->getId() . "\" />\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">" . get_hecho($zeModule->get("name")) . "</td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Description&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">" . get_hecho($zeModule->get("description")) . "</td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">État&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";

$html .= $doc->getHTMLSelect("status", $zeModule->get("statusTypes"), $zeModule->get("status"));

$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";

$html .= "<br />\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"";
$html .= "Valider les modifications";
$html .= "\" /></center>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
