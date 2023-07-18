<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $init */
/** @var HeliosTransactionsSQL $transactionSQL $ */

[ $init,$heliosTransactionSQL ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,HeliosTransactionsSQL::class]);

$init->initHelios();


if (! $init->userIsSuperAdmin()) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');
$message = $recuperateur->get('message');



$message = "Transaction passée manuellement en erreur - $message";
$heliosTransactionSQL->updateStatus($id, -1, $message);
Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios', false, $init->getUserInfo()['id']);


$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: helios_transac_show.php?id=$id");
