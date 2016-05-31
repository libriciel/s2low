<?php 

require_once(dirname(__FILE__)."/../../../init/init-www-helios.php");


if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');
$message = $recuperateur->get('message');

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$heliosTransactionSQL->updateStatus($id,-1,"Transaction passée manuellement en erreur - $message");


$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: helios_transac_show.php?id=$id");