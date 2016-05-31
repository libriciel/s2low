<?php
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
$me = new User();
if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

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
} else {
  $html .= " au <a href=\"mailto:" . WEBMASTER . "\">webmaster</a>";
}

$html .= ".<br />\n";

$html .= "</p>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

