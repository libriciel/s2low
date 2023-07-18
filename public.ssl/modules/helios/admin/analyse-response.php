<?php

use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecu;
use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $init */
/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
/** @var HeliosResponsesError $heliosResponsesError */
/** @var HeliosTransactionsSQL $heliosTransactionSQL */
/** @var AuthoritySQL $authoritySQL */
/** @var HeliosRetourSQL $heliosRetourSQL */
/** @var AuthoritySiretSQL $authoritySiretSQL */
/** @var HeliosAnalyseFichierRecu $heliosAnalyseFichierRecu */

[
    $init,
    $heliosTransactionsSQL,
    $heliosResponsesError,
    $heliosTransactionSQL,
    $authoritySQL,
    $heliosRetourSQL,
    $authoritySiretSQL,
    $heliosAnalyseFichierRecu
] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [
            Initialisation::class,
            HeliosTransactionsSQL::class,
            HeliosResponsesError::class,
            HeliosTransactionsSQL::class,
            AuthoritySQL::class,
            HeliosRetourSQL::class,
            AuthoritySiretSQL::class,
            HeliosAnalyseFichierRecu::class
        ]
    );

$init->initHelios();

if (!$init->userIsSuperAdmin()) {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);
$filename = $recuperateur->get('file');




$_SESSION['error'] = "";

ob_start();
try {
    $filepath = $heliosResponsesError->getFilepath($filename);

    $heliosAnalyseFichierRecu->analyseOneFile($filepath, HELIOS_RESPONSES_ROOT, HELIOS_OCRE_FILE_PATH, true);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

$message = ob_get_contents();
ob_end_clean();
$_SESSION['error'] .= "<br/>" . nl2br($message);

header("Location: responses-helios-error.php");
