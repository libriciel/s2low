<?php

use S2lowLegacy\Class\actes\ActesClassificationCreation;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
$initialisation = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);

$initData = $initialisation->doInit();
$initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if ($initData->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);
$authority_id = $recuperateur->getInt('authority_id');
$force = $recuperateur->getInt('force');


$authority = new Authority($authority_id);
$authority->init();

$classificationCreation = new ActesClassificationCreation();
$classificationCreation->unsetFrequencyRestriction();


$result = $classificationCreation->createEnveloppe($authority, null, $force);
\S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
    ! $result,
    $classificationCreation->getLastMessage(),
    \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_authority_edit.php?id=$authority_id")
);
