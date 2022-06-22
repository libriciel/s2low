<?php 

require_once(dirname(__FILE__)."/../../../init/init-www-helios.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$transactionSQL = new HeliosTransactionsSQL($sqlQuery);
$transactionSQL->delete($id);

$msg = "La transaction $id a été éradiquée ....";

if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module_name, null,$userInfo['id'])) {
	$msg .= "\nErreur de journalisation.";
}

$_SESSION['error'] = $msg;
header("Location: index.php");