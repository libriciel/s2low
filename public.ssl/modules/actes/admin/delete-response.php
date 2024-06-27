<?php

use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var \S2lowLegacy\Class\actes\ActesResponsesError $actesResponsesError */
[$initialisation, $actesResponsesError] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, ActesResponsesError::class]);

$initData = $initialisation->doInit(Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if ($initData->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);

$filename = $recuperateur->get('file');

try {
    $actesResponsesError->delete($filename);
    $_SESSION['error'] = "Le fichier $filename a été supprimé";
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}
header('Location: responses-actes-error.php');
