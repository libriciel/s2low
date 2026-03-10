<?php

use Libriciel\LibActes\Utils\XSDValidationException;
use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuController;
use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var ActesResponsesError $actesResponsesError */
/** @var ActesAnalyseFichierRecuController $actesAnalyseFichierRecuController */
[
    $initialisation,
    $actesResponsesError,
    $actesAnalyseFichierRecuController,
    $userContext
] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, ActesResponsesError::class, ActesAnalyseFichierRecuController::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if ($userContext->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);
$filename = $recuperateur->get('file');


$_SESSION['error'] = '';

ob_start();
try {
    $filepath = $actesResponsesError->getFilepath($filename);

    $actesAnalyseFichierRecuController->analyseOneFile($filepath);
    $tmpDir = new TmpFolder();
    $tmpDir->delete($filepath);
} catch (XSDValidationException $e) {
    $_SESSION['error'] = $message = $e->getMessage() . ' ' . implode(';', $e->displayValidationErrors());
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

$message = ob_get_contents();
ob_end_clean();

if (!$message) {
    $message = 'Le fichier a été analysé';
}

$_SESSION['error'] .= '<br/>' . nl2br($message);

header('Location: responses-actes-error.php');
exit_wrapper();
