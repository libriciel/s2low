<?php

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$actesPrepareEnvoiSAE = $objectInstancier->get(ActesPrepareEnvoiSAE::class);
$result = $actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA($connexion->getId(),$id);

if (! $result){
	$_SESSION['error'] = "Erreur: " . $actesPrepareEnvoiSAE->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}

$msg = "Programmation de l'envoi de la transaction $id à Pastell";

$_SESSION['error'] = $msg;
	
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$connexion->getId())) {
	$_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: actes_transac_show.php?id=$id");

