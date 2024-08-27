<?php

use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */

$initialisation = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);

$initData = $initialisation->doInit();
$initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

if ($initData->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);

$filename = $recuperateur->get('file');


$heliosResponsesError = new HeliosResponsesError();

try {
    $heliosResponsesError->delete($filename);
    $_SESSION['error'] = "Le fichier $filename a été supprimé";
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}
header('Location: responses-helios-error.php');
