<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $init */
/** @var HeliosTransactionsSQL $transactionSQL $ */

[ $init,$transactionSQL ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,HeliosTransactionsSQL::class]);

$init->initHelios();

if (! $init->userIsSuperAdmin()) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$transactionSQL->delete($id);

$msg = "La transaction $id a été éradiquée ....";

if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $init->getModuleName(), null, $init->getUserInfo()['id'])) {
    $msg .= "\nErreur de journalisation.";
}

$_SESSION['error'] = $msg;
header("Location: index.php");
