<?php
require_once(dirname(__FILE__)."/../../../../init/init-www-actes.php");

if (! $droit->isSuperAdmin($userInfo)){
	sortir("Accès refusé");
}


$menuHTML = new MenuHTML();

$doc = new HTMLLayout();

$doc->setTitle("Utilitaires module ACTES");


$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));

$html = "<div id=\"content\">\n";
$html .= "<h1>Utilitaires - ACTES</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('export_area');\">Export liste transactions</h2>\n";
$html .= "<p id=\"export_area\" style=\"display: block;\">Utilisez le lien ci-dessous pour obtenir un fichier au format CSV de toutes les transactions envoyées au ministère&nbsp;:<br />";
$html .= "<a style=\"margin-left: 10px\" href=\"" . WEBSITE_SSL . "/modules/actes/admin/actes_admin_transac_export.php\">Télécharger le fichier</a></p>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('window_area');\">Gestion des fenêtres de transmission</h2>\n";
$html .= "<p id=\"window_area\" style=\"display: block;\">\n";
$html .= "La transmission des données vers le serveur du ministère se fait par défaut à tout moment de la journée sans limitation de volume. Il est cependant possible de définir des fenêtres horaires où le volume de transmission autorisé sera limité à une certaine taille ou tout simplement nul.<br />\n";
$html .= "<a style=\"margin-left: 10px\" href=\"" . WEBSITE_SSL . "/modules/actes/admin/actes_admin_windows.php\">Accéder à l'interface de définition des fenêtres</a></p>\n";

ob_start();
?>
<h2>Outils de test</h2>
<a href='<?php echo WEBSITE_SSL?>/modules/actes/test/enveloppe_generate.php'>Générer une enveloppe</a>
<?php 
$html .= ob_get_contents();
ob_end_clean();
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();
$doc->display();


