<?php

use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

list($jsonOutput) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->getArray(
    [JSONoutput::class]
);
$html = '';
$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isAdmin()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

// Mode modification ou pas
$mod = false;
$group = new Group();

$modStr = "Ajout";
if (isset($id)) {
    $group->setId($id);
    if ($group->init()) {
        $modStr = "Modification";
        $mod = true;
    } else {
        $group = new Group();
    }
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé.";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));

$doc->setTitle("Tedetis : " . $modStr . " groupe de collectivité");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion groupe de collectivités</h1>\n";

$html .= "<p id=\"back-transaction-btn\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/groups/admin_groups.php\" class=\"btn btn-default\">Retour liste groupes</a></p>\n");

$html .= "<h2>" . $modStr . " groupe de collectivités</h2>\n";
$html .= "<form class=\"form form-horizontal\" action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/groups/admin_group_edit_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $group->getValidationTrio('name', 'status') . ")\">\n");

if ($mod) {
    $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $group->getId() . "\" />\n";
    $html .= "<input type=\"hidden\" name=\"mode\" value=\"modify\" />\n";
} else {
    $html .= "<input type=\"hidden\" name=\"mode\" value=\"create\" />\n";
}
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"name\" class=\"col-md-3 control-label\">Nom</label>\n";
$html .= "  <div class=\"col-md-4\"><input id=\"name\" class=\"form-control\" type=\"text\" name=\"name\" value=\"";
$html .= ($mod) ? get_hecho($group->get("name")) : \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession("name");
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"status\" class=\"col-md-3 control-label\">État</label>\n";
$html .= "  <div class=\"col-md-4\">";

$status = ($mod) ? $group->get("status") : \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession("status");

$html .= $doc->getHTMLSelect("status", $group->get("statusTypes"), $status);
$html .= "  </div>\n";
$html .= " </div>\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"siren-file\" class=\"col-md-3 control-label\">Liste des SIREN autorisés</label>\n";
$html .= "  <div class=\"col-md-4\">";
$html .= "  <input id=\"siren-file\" type=\"file\" name=\"siren_file\" size=\"30\" maxlength=\"255\" />";
$html .= "  </div>\n";
$html .= " </div>\n";

$html .= "<div class=\"form-group\">\n";
$html .= "<button type=\"submit\" class=\"col-md-offset-3 col-md-4 btn btn-default\">";
$html .= ($mod) ? "Valider les modifications" : "Ajouter le groupe";
$html .= "</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";

if ($mod && $group->isEmpty($group->getId())) {
    $html .= "<form action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/groups/admin_group_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement ce groupe ?')\" method=\"post\">\n");
    $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $group->getId() . "\" />\n";
    $html .= "<input type=\"submit\" value=\"Supprimer ce groupe\" class=\"btn btn-danger\" />\n";
    $html .= "</form>\n";
}

$sirenList = $group->getAuthorizedSiren();
$api = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("api");

if ($api) {
        $jsonOutput->display($sirenList);
        exit;
}

$html .= "<h2>Liste des SIREN autorisés pour ce groupe</h2>\n";

if ($mod) {
      $html .= "<form class=\"form form-horizontal\" action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/groups/add-siren-controler.php\" method=\"post\">\n");
      $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $group->getId() . "\" />\n";
      $html .= "<div class=\"form-group\">\n";
    $html .= "  <label for=\"add-siren\" class=\"col-md-3 control-label\">Ajouter un numéro SIREN</label>\n";
    $html .= "  <div class=\"col-md-4\">";
    $html .= "  <input id=\"add-siren\" class=\"form-control\" name=\"siren\" size=\"30\" maxlength=\"255\" />";
        $html .= "  </div>";
    $html .= "  <button class=\"btn btn-default col-md-2\" type='submit'>Ajouter</button>";
        $html .= "  </div>";
    $html .= "</form>\n";
}

if (count($sirenList) > 0) {
    $html .= "<ul>\n";
    foreach ($sirenList as $siren) {
        if (!empty($siren)) {
            $html .= "<li>" . $siren . "</li>\n";
        }
    }
    $html .= "</ul>\n";
} else {
    $html .= "Pas de SIREN autorisé.";
}

$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
