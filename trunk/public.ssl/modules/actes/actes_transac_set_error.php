<?php 

require_once(dirname(__FILE__)."/../../../init/init-www-actes.php");
//require_once(__DIR__."/../../../class/actes/ActesTransactionsSQL.class.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);
$actesTransactionSQL->updateStatus($id,-1,"Transaction passée manuellement en erreur");


$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: actes_transac_show.php?id=$id");