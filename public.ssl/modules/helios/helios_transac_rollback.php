<?php

require_once(dirname(__FILE__)."/../../../init/init-www-helios.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');

$transactionSQL = new HeliosTransactionsSQL($sqlQuery);

$transactionInfo = $transactionSQL->getInfo($id);

$message = "La transaction $id est de nouveau à l'état posté.";

$transactionSQL->updateStatus($id,HeliosTransactionsSQL::POSTE,$message);
$transactionSQL->setNomFic($id,NULL);

$_SESSION['error'] = $message;
header("Location: helios_transac_show.php?id=$id");