<?php

// Configuration
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\User;

$html = '';

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->checkDroit($module->get("name"), 'CS')) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    $_SESSION["error"] = "Importer une enveloppe : votre certificat n'est pas conforme au RGS, vous ne pouvez pas télétransmettre !";
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit;
}


$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : Actes - Import d'une enveloppe");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

// Zone contenu
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . Helpers::getLink("/modules/actes/index.php") . "\" class=\"bouton\">Retour liste transactions</a></p>\n";
$html .= "<h2>Import d'une enveloppe</h2>\n";
$html .= "<form class=\"form-horizontal import-file-form\" action=\"" . Helpers::getLink("/modules/actes/actes_transac_submit.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm('enveloppe', 'Fichier enveloppe', 'RisString')) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n");
$html .= "<div class=\"form-group\">";
$html .= "<label for=\"enveloppe\" class=\"col-md-6 control-label\">Indiquez le fichier archive de l'enveloppe à importer (taille maximum " . ACTES_ARCHIVE_MAX_SIZE . " octets)</label>\n";
$html .= "<div class=\"col-md-6\">";
$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . ACTES_ARCHIVE_MAX_SIZE . "\" />\n";
$html .= "<input type=\"file\" id=\"enveloppe\" name=\"enveloppe\"/>";
$html .= "</div>\n";
$html .= "</div>\n";
$html .= "<div id=\"form_progress\" class=\"form-group\"><div class=\"col-md-2\"><button class=\"btn btn-primary\" type=\"submit\" value=\"\" />Importer l'enveloppe</button></div></div>\n";
$html .= "</form>\n";
$html .= "</div>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
