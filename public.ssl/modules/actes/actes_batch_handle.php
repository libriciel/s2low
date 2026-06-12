<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
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

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

$batchesList = $zeBatch->getBatchesListForUser($me->getId());

$doc = new HTMLLayout();

$js = "<script type=\"text/javascript\">\n";
$js .= "  function redirect_to_create_form(select_form) {\n";
$js .= "    batch_file_id = select_form.options[select_form.selectedIndex].value;\n";
$js .= "    document.location='" . Helpers::getLink("/modules/actes/actes_transac_add.php?batchfile=' + batch_file_id;\n");
$js .= "  }\n";
$js .= "</script>\n";

$doc->addHeader($js);

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->buildPager($zeBatch);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>ACTES - Traitement par lots</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . Helpers::getLink("/modules/actes/index.php") . "\" class=\"bouton\">Retour liste transactions</a></p>\n";
if (! $me->isSuper() && $me->canEdit($module->get('name'))) {
    $html .= "<div id=\"actions_area\">\n";
    $html .= "<h2>Actions</h2>\n";
    $html .= "<a class=\"btn btn-primary\" href=\"" . Helpers::getLink("/modules/actes/actes_batch_add.php\">Créer un nouveau lot</a>\n");
    $html .= "</div>\n";
}

$html .= "<h2>Liste des lots de transactions</h2>\n";

if (is_array($batchesList) && count($batchesList) > 0) {
    $html .= "<div id=\"lot-area\">\n";
    $html .= "<table class=\"data-table table table-striped\" summary=\"Ce tableau présente respectivement un lien vers le détail, une description, la date, le nombre de fichiers non traités et un lien vers les actions disponibles de chaque lot\">";
    $html .= "<caption>Liste des lots de transactions<caption>\n";
    $html .= "<thead>\n";
    $html .= "<tr>\n";
    $html .= " <th id=\"lot\" class=\"data\">Lot</th>\n";
    $html .= " <th id=\"description\" class=\"data\">Description</th>\n";
    $html .= " <th id=\"date\" class=\"data\">Date de création</th>\n";
    $html .= " <th id=\"file-remaining\" class=\"data\">Fichiers restants</th>\n";
    $html .= " <th id=\"treatment\" class=\"data\">Traiter le fichier&nbsp;:</th>\n";
    $html .= "</tr>\n";
    $html .= "</thead>\n";
    $html .= "<tbody>\n";

    $i = 0;

    foreach ($batchesList as $batchData) {
        $batch = new ActesBatch($batchData["id"]);
        $batch->init();

        $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
        $html .= " <td headers=\"lot\"><a href=\"" . Helpers::getLink("/modules/actes/actes_batch_show.php?id=" . $batch->getId() . "\" title=\"Visualiser les détails du lot n°" . $batch->getId() . "\">" . get_hecho($batch->getId()) . "</a></td>\n");
        $html .= " <td headers=\"description\">" . get_hecho($batch->get("description")) . "</td>\n";
        $html .= " <td headers=\"date\">" . Helpers::getDateFromBDDDate($batch->get("submission_date"), true) . "</td>\n";
        $html .= " <td headers=\"file-remaining\">" . $batch->getUnprocessedFilesCount() . "</td>\n";
        $html .= " <td  headers=\"treatment\" class=\"long_field\">";

        if ($batch->getUnprocessedFilesCount() > 0) {
            $html .= $doc->getHTMLSelect("batch_files", $batch->getUnprocessedFilesIdName(), null, " onchange=\"javascript:redirect_to_create_form(this);\"");
        } else {
            $html .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_batch_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement ce lot ?')\" method=\"post\">\n");
            $html .= "<p>Tous les fichiers sont traités&nbsp;:\n";
            $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $batch->getId() . "\" />\n";
            $html .= "<input type=\"submit\" value=\"Supprimer le lot\" />\n";
            $html .= "</p></form>\n";
        }

        $html .= "</td>\n";
        $html .= "</tr>\n";

        $i = ($i + 1) % 2;
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    $html .= "</div>\n";
} else {
    $html .= "Pas de lot trouvé.";
}

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
