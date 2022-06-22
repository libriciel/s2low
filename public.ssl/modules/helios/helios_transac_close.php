<?php
require_once( __DIR__ . "/../../../init/init-www-helios.php");

$liste_id = Helpers::getVarFromPost("liste_id");

$heliosArchiveControler = $objectInstancier->get(HeliosPrepareEnvoiSAE::class);

$msg = "";
    
foreach ($liste_id as $id) {
	$id_d = $heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($connexion->getId(),$id);
	if ($id_d) {
		$msg = "Programmation de l'envoi de la transaction $id à Pastell";
	} else {
		$msg .= "Erreur lors de l'envoi de la transaction $id à Pastell: " . $heliosArchiveControler->getLastError() ."\n";
	}
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$connexion->getId())) {
		$msg .= "Erreur de journalisation.\n";
	}
}


$_SESSION['error'] = nl2br($msg);
header("Location: index.php");	
