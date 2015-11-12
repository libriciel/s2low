<?php
require_once( __DIR__ . "/../../../init/init-www-helios.php");

$liste_id = Helpers::getVarFromPost("liste_id");

$heliosArchiveControler = new HeliosArchiveControler($sqlQuery);

$msg = "";
    
foreach ($liste_id as $id) {
	$id_d = $heliosArchiveControler->sendArchive($connexion->getId(),$id);
	if ($id_d) {
		$msg .= "Envoie de la transaction $id à Pastell\n";
	} else {
		$msg .= "Erreur lors de l'envoi de la transaction $id à Pastell: " . $heliosArchiveControler->getLastError() ."\n";
	}
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$connexion->getId())) {
		$msg .= "Erreur de journalisation.\n";
	}
}


$_SESSION['error'] = $msg;
header("Location: index.php");	
