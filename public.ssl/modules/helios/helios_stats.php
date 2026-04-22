<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = LegacyObjectsManager::getObject(HeliosTransactionsSQL::class);

$module = new Module();
if (! $module->initByName("helios")) {
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

$myAuthority = new Authority($me->get("authority_id"));


// Récupération de la liste des enveloppes en fonction de l'utilisateur en cours
$author_filter = "";
if ($me->isAuthorityAdmin()) {
    $author_filter = "users.authority_id=" . $me->get("authority_id");
} elseif ($me->isGroupAdmin()) {
    $author_filter = "authorities.authority_group_id=" . $me->get("authority_group_id");
} elseif (! $me->isAdmin()) {
    $author_filter = "users.id=" . $me->getId();
}

// Transactions depuis toujours
$allTrans = $heliosTransactionsSQL->countTransactions($author_filter);
$allTransmitted = $heliosTransactionsSQL->countTransactions($author_filter, true);
$allVol = $heliosTransactionsSQL->countTransactionVol($author_filter);
$allVolTransmitted =  $heliosTransactionsSQL->countTransactionVol($author_filter, true);

// Transactions du mois
$monthTrans = $heliosTransactionsSQL->countTransactions($author_filter, false, true);
$monthTransmitted = $heliosTransactionsSQL->countTransactions($author_filter, true, true);
$monthVol = $heliosTransactionsSQL->countTransactionVol($author_filter, false, true);
$monthVolTransmitted = $heliosTransactionsSQL->countTransactionVol($author_filter, true, true);

// transactions de l'année
$yearTrans = $heliosTransactionsSQL->countTransactions($author_filter, false, false, true);
$yearTransmitted = $heliosTransactionsSQL->countTransactions($author_filter, true, false, true);
$yearVol = $heliosTransactionsSQL->countTransactionVol($author_filter, false, false, true);
$yearVolTransmitted = $heliosTransactionsSQL->countTransactionVol($author_filter, true, false, true);

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Hélios - Statistiques");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>HELIOS - Dématérialisation de documents financiers</h1>\n";
$html .= "<h2>Statistiques des transactions Hélios";

if ($me->isSuper()) {
        $html .= " pour l'ensemble des collectivités/utilisateurs";
} elseif ($me->isAuthorityAdmin()) {
    $html .= " pour la collectivité " . $myAuthority->get("name");
} elseif ($me->isGroupAdmin()) {
    $TempGroup = new Group($me->get("authority_group_id"));
    $TempGroup->init();
        $html .= " pour le groupe " . $TempGroup->get("name");
} else {
    $html .= " pour l'utilisateur " . $me->getPrettyName();
}

$html .= "</h2>\n";
$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";
$html .= "  <dt>Depuis le début du mois&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " . $monthTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " . $monthTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " . $monthVol . " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " . $monthVolTransmitted . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>Depuis le début de l'année&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " . $yearTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " . $yearTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " . $yearVol . " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " . $yearVolTransmitted . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>En totalité&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " . $allTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " . $allTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " . $allVol . " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " . $allVolTransmitted . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= " </dl>\n";
$html .= "</div>\n";
$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
