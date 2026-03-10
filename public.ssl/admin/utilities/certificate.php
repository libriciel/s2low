<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SQLQuery;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var \S2lowLegacy\Class\UserContext $userContext */
[$initialisation, $sqlQuery, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class, UserContext::class]);


if (!$userContext->me->isSuper()) {
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
