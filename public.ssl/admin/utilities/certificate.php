<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SQLQuery;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
[$initialisation, $sqlQuery] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class]);

$initData = $initialisation->doInit();


$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}

if (!$me->isSuper()) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}


$recuperateur = new Recuperateur($_GET);
$type = $recuperateur->get('type');
$name = $recuperateur->get('name');

$name = basename($name);

if ($type == 'rgs') {
    $file = RGS_VALIDCA_PATH . "/$name";
} else {
    $file = EXTENDED_VALIDCA_PATH . "/$name";
}

if (!file_exists($file)) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: certitificate_list.php');
    exit();
}

header_wrapper('Content-type: text/plain');
header_wrapper("Content-disposition: attachment; filename=$name");

readfile($file);
