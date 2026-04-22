<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$html = '';

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (!$me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

$mod = false;
$zeModule = new Module();

if (isset($id) && !empty($id)) {
    $zeModule->setId($id);
    if ($zeModule->init()) {
        $mod = true;
    }
}

if (! $mod) {
    $_SESSION["error"] = "Pas d'identifiant de module spécifié.";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_modules.php"));
    exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"/" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));

$doc->setTitle("Modification d'un module");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des modules</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a href=\"admin_modules.php\" class=\"btn btn-default\">Retour liste modules</a></p>\n";
$html .= "<h2>Modification d'un module</h2>\n";
$html .= "<form action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_module_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm(" . $zeModule->getValidationTrio('status') . ");\">\n");
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

//! On récupère la liste des paramètres du modules dans le tableau module_params
$module_params = $zeModule->getModuleParams();
 $tr_style = "alternate1";
if (count($module_params) > 0) {
  //! Le module a un ou plusieurs paramètres, on affiche la table des paramètres


    $html .= "<h2>Modification/Suppression des param&egrave;tres</h2>\n";
    $html .= "<div class=\"data_table\">\n";
    $html .= "<table class=\"data\">\n";
    $html .= "<tr>\n";
    $html .= "  <th class=\"data\">Suppression</th>\n";
    $html .= "  <th class=\"data\">Nom du param&egrave;tre</th>\n";
    $html .= "  <th class=\"data\">Valeur du param&egrave;tre</th>\n";
    $html .= "  <th class=\"data\">Description du param&egrave;tre</th>\n";
    $html .= "</tr>\n";

    foreach ($module_params as $param) {
        $html .= "<tr class=\"" . $tr_style . "\">\n";
        $html .= "  <td class=\"td-input\"><input type=\"hidden\" name=\"param_id[]\" value=\"" . $param["id"] . "\"/><input type=\"checkbox\" name=\"param_to_suppr[]\" value=\"" . $param["id"] . "\" /></td>";
        $html .= "  <td class=\"td-input\"><input type=\"text\" name=\"param_name[]\" value=\"" . get_hecho($param["name"]) . "\" /></td>\n";
        $html .= "  <td class=\"td-input\"><input type=\"text\" size=\"15\" maxlength=\"70\" name=\"param_value[]\" value=\"" . get_hecho($param["value"]) . "\" /></td>\n";
        $html .= "  <td class=\"td-input\"><input type=\"text\" size=\"40\" maxlength=\"70\" name=\"param_description[]\" value=\"" . get_hecho($param["description"]) . "\" /></td>\n";
        $html .= "</tr>\n";
        $tr_style = ($tr_style == "alternate1") ? "alternate2" : "alternate1";
    }
    $html .= "</table>\n";
    $html .= "</div>\n";
}

$html .= "<h2>Ajout d'un param&egrave;tre</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= "<tr>\n";
$html .= "  <th class=\"data\">Nom du param&egrave;tre</th>\n";
$html .= "  <th class=\"data\">Valeur du param&egrave;tre</th>\n";
$html .= "  <th class=\"data\">Description du param&egrave;tre</th>\n";
$html .= "</tr>\n";
$html .= "<tr class=\"" . $tr_style . "\">\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"new_param_name\" /></td>\n";
$html .= "  <td class=\"td-input\"><input size=\"15\" maxlength=\"70\" type=\"text\" name=\"new_param_value\" /></td>\n";
$html .= "  <td class=\"td-input\"><input size=\"40\" maxlength=\"70\" type=\"text\" name=\"new_param_description\" /></td>\n";
$html .= "</tr>\n";
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
