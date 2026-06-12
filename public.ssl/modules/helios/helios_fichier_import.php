<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("helios");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->checkDroit($module->get("name"), 'CS')) {
    $_SESSION["error"] = "Accès refusée";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$rgsConnexion = LegacyObjectsManager::getLegacyObjectInstancier()->get(RgsConnexion::class);
if (! $rgsConnexion->isRgsConnexion()) {
    $_SESSION["error"] = "Votre certificat n'est pas conforme au RGS, vous ne pouvez pas télétransmettre !";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
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

$doc->setTitle("Tedetis : Helios - Import d'une enveloppe");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Helios - Dématérialisation de documents comptables</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . Helpers::getLink("/modules/helios/index.php") . "\" class=\"bouton\">Retour liste transactions</a></p>\n";

ob_start();
?>
<h2 id ="import_desc">Import d'un fichier</h2>
<form class="form-horizontal import-file-form form col-md-offset-1" method="POST" enctype="multipart/form-data" action="/modules/helios/helios_script_reception.php" >
    <table class="data-table table table-striped" aria-describedby="import_desc" >
        <tr>
            <th scope="col">
                <label for="enveloppe" class="control-label">Fichier XML</label>
            </th>
            <td>
                <input class="" type="file" id="enveloppe" name="enveloppe" accept=".xml"/>
            </td>
        </tr>
        <tr>
            <th scope="col">&nbsp;</th>
            <td>
                <button class="btn btn-primary" type="submit" value="">Importer le fichier</button>
            </td>
        </tr>
    </table>
</form>

<?php
$html .= ob_get_contents();
ob_end_clean();



$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();

