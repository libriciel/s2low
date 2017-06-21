<?php

require_once( __DIR__ . "/../../../init/init-www-helios.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

/** @var HeliosArchiveControler $heliosArchiveControler */
$heliosArchiveControler = $objectInstancier->get("HeliosArchiveControler");
$id_d = $heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($connexion->getId(),$id);

if (! $id_d){
	$_SESSION['error'] = "Erreur: " . $heliosArchiveControler->getLastError();
	header("Location: helios_transac_show.php?id=$id");
	exit;
}

$msg = "Programmation de l'envoi de la transaction $id à Pastell";

$_SESSION['error'] = $msg;
	
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$connexion->getId())) {
	$_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: helios_transac_show.php?id=$id");

