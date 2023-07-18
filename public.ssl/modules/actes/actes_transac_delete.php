<?php

use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $init */
/** @var TransactionSQL $transactionSQL */
[$init,$transactionSQL] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class,TransactionSQL::class]
    );

$init->initActes();

if (! $init->userIsSuperAdmin()) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');


$transactionSQL->delete($id);

$msg = "La transaction $id a été éradiquée ....";


if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $init->getModuleName(), null, $init->getUserId())) {
    $msg .= "\nErreur de journalisation.";
}

$_SESSION['error'] = $msg;
header("Location: index.php");
