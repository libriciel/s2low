<?php

use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\S2lowRedirect;
use S2lowLegacy\Class\UserContext;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var S2lowRedirect $s2lowRedirect */
/** @var UserContext $userContext */
/** @var HTMLLayoutFactory $htmlLayoutFactory */
[$initialisation,$droit,$s2lowRedirect, $userContext, $htmlLayoutFactory] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,S2lowRedirect::class,UserContext::class,HTMLLayoutFactory::class]);

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
    $s2lowRedirect->redirect('/', 'Accès refusé');
}


$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle('Utilitaires module ACTES');

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();
$doc->openContent();

$html = "<div id=\"content\">\n";
$html .= "<h1>Utilitaires - ACTES</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('export_area');\">Export liste transactions</h2>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les transactions envoyées au ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . Helpers::getLink("/modules/actes/admin/actes_admin_transac_export.php\">Télécharger le fichier</a></p>\n");
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('window_area');\">Gestion des fenêtres de transmission</h2>\n";
$html .= "<p id=\"window_area\" style=\"display: block;\">\n";
$html .= "La transmission des données vers le serveur du ministère se fait par défaut à tout moment de la journée sans limitation de volume. Il est cependant possible de définir des fenêtres horaires où le volume de transmission autorisé sera limité à une certaine taille ou tout simplement nul.<br />\n";
$html .= "<a style=\"margin-left: 10px\" href=\"" . Helpers::getLink("/modules/actes/admin/actes_admin_windows.php\">Accéder à l'interface de définition des fenêtres</a></p>\n");

ob_start();

$html .= ob_get_contents();
ob_end_clean();
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
