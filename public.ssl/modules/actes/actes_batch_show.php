<?php

/**
 * \file actes_batch_show.php
 * \brief Page d'affichage d'un lot de fichier transaction Actes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 13.02.2007
 *
 *
 * Cette page affiche les détails d'un lot de transaction Actes et
 * permet de demander sa suppression
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (! $module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

try {
    $id = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromGet("id");
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur d'initialisation du lot.";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
    $zeBatch->setId($id);
    if ($zeBatch->init()) {
        $owner = new User($zeBatch->get("user_id"));
        $owner->init();
    } else {
        $_SESSION["error"] = "Erreur d'initialisation du lot.";
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de lot spécifié";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php"));
    exit();
}

// Vérification des permissions
if (! $me->isSuper()) {
    if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
        $_SESSION["error"] = "Accès refusé";
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php"));
        exit();
    }
}

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : visualisation d'un lot");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<div id=\"content\">\n";
$html .= "<h1>Visualisation du lot " . $zeBatch->getId() . " </h1>";
$html .= "<p id=\"back-transaction-btn\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_handle.php\" class=\"btn btn-default\">Retour liste lots</a></p>\n");
$html .= "<h2>Détails du lot</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Numéro du lot", $zeBatch->getId());
$html .= $doc->getHTMLArrayline("Description", get_hecho($zeBatch->get("description")));
$html .= $doc->getHTMLArrayline("Préfixe numéro interne", get_hecho($zeBatch->get("num_prefix")));
$html .= $doc->getHTMLArrayline("Date de création", \S2lowLegacy\Class\Helpers\DateHelper::getDateFromBDDDate($zeBatch->get("submission_date"), true));
$html .= $doc->getHTMLArrayline("Nombre total de fichiers", $zeBatch->getAllFilesCount());
$html .= $doc->getHTMLArrayline("Nombre de fichiers traités", ($zeBatch->getAllFilesCount() - $zeBatch->getUnprocessedFilesCount()));
$html .= $doc->getHTMLArrayline("Nombre de fichiers restant à traiter", $zeBatch->getUnprocessedFilesCount());
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

// Fichiers contenus dans le lot
$html .= "<h3>Fichiers contenus dans le lot</h3>";
$html .= "<div class=\"data_table\">\n";
$batchFiles = $zeBatch->getBatchFiles();

if (is_array($batchFiles) && count($batchFiles) > 0) {
    $html .= "<table class=\"table data-table table-striped\">\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th id=\"file\">Fichier</th>\n";
    $html .= "  <th id=\"size\">Taille</th>\n";
    $html .= "  <th id=\"signature\">Signature numérique</th>\n";
    $html .= "  <th id=\"status\">Statut</th>\n";
    $html .= "  <th id=\"actions\">Traiter</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";
    foreach ($batchFiles as $batchFile) {
        $html .= " <tr>\n";
        $html .= "  <td headers=\"file\" class=\"long_field\">";
        $html .= ($batchFile->isProcessed()) ? $batchFile->getDisplayName() : "<a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_download_file.php?file=" . $batchFile->getId() . "&amp;type=batch\" title=\"Télécharger le fichier\">" . get_hecho($batchFile->getDisplayName()) . "</a>");
        $html .= "</td>\n";
        $html .= "  <td headers=\"size\" >" . $batchFile->get("filesize") . "</td>\n";
        $html .= "  <td headers=\"signature\" >";
        $html .= (mb_strlen($batchFile->get("signature")) > 0) ? "Présente" : "Non présente";
        $html .= "</td>\n";
        $html .= "  <td headers=\"status\" >";
        $html .= ($batchFile->isProcessed()) ? "<a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=" . $batchFile->get("transaction_id")) . "\" title=\"Voir la transaction issue de ce fichier\">Traité</a>" : "Non traité";
        $html .= "</td>\n";
        $html .= ( ! $batchFile->isProcessed()) ? "  <td headers=\"actions\" ><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_add.php?batchfile=" . $batchFile->getId()) . "\" class=\"icon\" title=\"Créer la transaction correspondant à ce fichier\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"Icone traitement\" /></a></td>\n" : "<td headers=\"actions\" >&nbsp;</td>";
        $html .= " </tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
} else {
    $html .= "  <p>Pas de fichier trouvé</p>";
}

$nb_fichier = count($batchFiles);
$html .= "</div>\n";
$html .= "<form action=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_batch_delete.php\" onsubmit=\"return confirm('Il reste $nb_fichier fichier(s) à traiter dans ce lot. Souhaitez-vous réellement supprimer ce lot ?')\" method=\"post\">\n");
$html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeBatch->getId() . "\" />\n";
$html .= "<input type=\"submit\" value=\"Supprimer ce lot\" class=\"btn btn-warning\" />\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
