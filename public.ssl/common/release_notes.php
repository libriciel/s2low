<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\ParsedownExtended;

/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle("Logiciel S²LOW : Notes de publication");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();
$doc->openContent();

$html = "<p> On trouvera  après la note de dernière version la liste des limitations connues pour cette version</p>";
$html .= "<h1>Logiciel S²LOW - notes de publication</h1>\n<br/>";

$Parsedown = new ParsedownExtended(2);

$html .= $Parsedown->text(
    file_get_contents(
        __DIR__ . "/../../CHANGELOG.md"
    )
);

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
