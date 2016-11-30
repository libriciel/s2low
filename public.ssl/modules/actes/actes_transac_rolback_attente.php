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
$actesTransactionSQL->updateStatus($id,2,"Transaction repassee manuellement en attente de transmission");


$_SESSION['error'] = "La transaction $id a ete passee en attente de transmission.";
header("Location: actes_transac_show.php?id=$id");