<?php

require_once(dirname(__FILE__)."/../../../init/init-www-actes.php");
//require_once(__DIR__."/../../../class/actes/ActesTransactionsSQL.class.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');


$actes = $objectInstancier->get(ActesArchiveControler::class);

try {
	$actes->sendArchive($id);
	$_SESSION['error'] = "La transaction $id a été envoyé au SAE";
} catch (Exception $e){
	$_SESSION['error'] = "Impossible d'envoyer la transaction au SAE : " . $e->getMessage();
}

header("Location: actes_transac_show.php?id=$id");