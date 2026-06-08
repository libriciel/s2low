<?php

use S2low\Enum\IncomingFileHandlingStatus;
use S2low\ProcessingResults\MoveFileToErrorDirectoryFactory;
use S2lowLegacy\Class\helios\IncomingFileProcessor;
use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var IncomingFileProcessor $incomingFileProcessor */
/** @var \S2low\Infrastructure\Directory $errorDirectory */
/** @var MoveFileToErrorDirectoryFactory $processingResultFactory */

[$initialisation,$sqlQuery,$incomingFileProcessor, $errorDirectory, $processingResultFactory] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class,IncomingFileProcessor::class, 'app.heliosErrorDirectory', MoveFileToErrorDirectoryFactory::class]);

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
$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$processingResultFactory->disableMailAndLoggingOnError();

$_SESSION['error'] = '';

ob_start();
    $filePath = $errorDirectory->getPath($filename);
if (!is_file($filePath)) {
    throw new RuntimeException("Fichier introuvable : $filePath");
}
    $file = new SplFileObject(realpath($filePath));
    $result = $incomingFileProcessor->process($file);
if ($result->getStatus() === IncomingFileHandlingStatus::AnalysisFailed) {
    $_SESSION['error'] = $result->getMessage();
}

$message = ob_get_contents();
ob_end_clean();
$_SESSION['error'] .= '<br/>' . nl2br($message);

header('Location: responses-helios-error.php');
exit();
