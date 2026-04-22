<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\MessageAdminSQL;

/** @var MessageAdminSQL $messageAdminSQL */
$messageAdminSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(MessageAdminSQL::class);

$me = new User();
if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}
$messageAdmin = $messageAdminSQL->getPublishedMessage();

$doc = new HTMLLayout();

$myAuthority = new Authority($me->get("authority_id"));

$doc->setTitle(WEBSITE_TITLE);

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = " <h1>Espace de télétransmission</h1>\n";
$html .= "<p>Vous êtes connecté avec le rôle";

if ($me->isSuper()) {
    $html .= " de super administrateur";
} elseif ($me->isGroupAdmin()) {
    $myGroup = new Group($me->get("authority_group_id"));
    $html .= " d'administrateur du groupe " . $myGroup->get("name");
} elseif ($me->isAdmin()) {
    $html .= " d'administrateur de la collectivité " . $myAuthority->get("name");
} else {
    $html .= " d'utilisateur de la collectivité " . $myAuthority->get("name");
}

$html .= ".<br />\n";

$html .= "Le menu de gauche vous donne accès aux opérations permises par ce rôle.<br /><br />\n";
$html .= "Le «&nbsp;Journal des événements&nbsp;» consigne l'ensemble des événements relatifs à vos opérations sur le site.<br /><br />";

if (defined("HOTLINE_NUM")) {
    $html .= "La hotline de support est disponible pour toute question au " . HOTLINE_NUM . ".<br /><br />\n";
}

$html .= "Merci de signaler tout problème rencontré sur la plate-forme ";


if (defined("SUPPORT_URL")) {
    $html .= " sur le <a href=\"" . SUPPORT_URL . "\">site support</a> réservé à cet effet";
} elseif (defined("PHRASE_SUPPORT")) {
    $html .= PHRASE_SUPPORT; //"au gestionnaire de votre plateforme (CDG, ADM, syndicat, Adullact Projet, etc).";
} else {
    $html .= " au <a href=\"" . WEBMASTER . "\">webmaster</a>";
}

$html .= ".<br />\n";

$html .= "</p>\n";

if ($messageAdmin->message_id) {
    ob_start();
    $messageAdmin->displayMessage();
    $html .= ob_get_clean();
}


if ($me->isSuper()) {
    $html .= "<h2>Fonctions super administrateur</h2>";
    $html .= "<a href='admin/index.php' class='btn  btn-primary'>Console d'administration</a>";
}


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
