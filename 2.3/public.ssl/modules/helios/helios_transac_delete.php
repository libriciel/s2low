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


$_SESSION['error'] = "La transaction $id a été éradiquée ....";
header("Location: index.php");