<?php

use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var UserContext $userContext */

$initialisation = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);
$userContext = LegacyObjectsManager::getLegacyObjectInstancier()->get(UserContext::class);

$initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if ($userContext->userInfo['role'] != 'SADM') {
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
exit_wrapper();
