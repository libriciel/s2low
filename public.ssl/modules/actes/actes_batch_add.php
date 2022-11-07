<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (!$module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();


$css = '';
$js = '';

$js .= "
    <script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jquery.js") . "\"></script>
    <script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jqueryfileupload.js") . "\"></script>\n
    <script  type=\"text/javascript\" src=\"/javascript/jfu/js/locale.js\"></script>\n
    <script  type=\"text/javascript\" src=\"/javascript/demo.js\"></script>\n
";

$doc->addHeader($css . $js);

$html = "<h1>ACTES - Traitement par lots</h1>\n";
$html .= "<h2>Cr&eacute;ation d'un lot</h1>\n";

$html .= " <div class='noMultipleSelect'>" . ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE . "</div>\n";

$html .= " <div class=\"jfu_controls\">\n";

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new \Twig\Environment($loader);

$html .= $twig->render('batch_creator.html.twig', [
    "userid" => $me->getId(),
    "website_ssl" => WEBSITE_SSL
]);


$html .= "</div>";

$doc->addBody($html);

$doc->closeContent();

$doc->closeContainer();

$doc->buildFooter();

$doc->display();
