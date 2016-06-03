<?php

require_once(dirname(__FILE__)."/../../../init/init-www-actes.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_GET);

$id = $recuperateur->getInt('id');

$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);


$info = $actesTransactionSQL->getStatusInfo($id,4);

if (! $info){
	$_SESSION['error'] = "Cette transaction n'existe pas";
	header("Location: index.php");
}


header("Content-type: application/xml");
echo $info['flux_retour'];


