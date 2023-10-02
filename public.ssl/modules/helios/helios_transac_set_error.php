<?php

use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(dirname(__FILE__) . "/../../../init/init-www-helios.php");


if (! $droit->isSuperAdmin($userInfo)) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');
$message = $recuperateur->get('message');

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);

$message = "Transaction passée manuellement en erreur - $message";
$heliosTransactionSQL->updateStatus($id, -1, $message);
Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios', false, $userInfo['id']);


$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: helios_transac_show.php?id=$id");
