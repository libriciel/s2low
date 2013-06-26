<?php

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$actesArchiveControler = new ActesArchiveControler($sqlQuery);
$id_d = $actesArchiveControler->sendArchive($connexion->getId(),$id);

if (! $id_d){
	$_SESSION['error'] = $actesArchiveControler->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}

$msg = "Envoie de la transaction $id à Pastell";

$_SESSION['error'] = "L'archive a été déposée";
	
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$connexion->getId())) {
	$_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: actes_transac_show.php?id=$id");

