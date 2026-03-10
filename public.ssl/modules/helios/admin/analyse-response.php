<?php

use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecu;
use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var HeliosAnalyseFichierRecu $heliosAnalyseFichierRecu */
/** @var \S2lowLegacy\Class\UserContext $userContext */

[$initialisation,$sqlQuery,$heliosAnalyseFichierRecu, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class,HeliosAnalyseFichierRecu::class, UserContext::class],);

$initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if ($userContext->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);
$filename = $recuperateur->get('file');

$heliosResponsesError = new HeliosResponsesError();
$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$_SESSION['error'] = '';

ob_start();
try {
    $filepath = $heliosResponsesError->getFilepath($filename);

    $heliosResponseRoot = LegacyObjectsManager::getLegacyObjectInstancier()->getParameter('app.helios_responses_root');
    $heliosOcreFilePath = LegacyObjectsManager::getLegacyObjectInstancier()->getParameter('app.helios_ocre_file_path');

    $heliosAnalyseFichierRecu->analyseOneFile($filepath, $heliosResponseRoot, $heliosOcreFilePath, true);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

$message = ob_get_contents();
ob_end_clean();
$_SESSION['error'] .= '<br/>' . nl2br($message);

header('Location: responses-helios-error.php');
exit();
