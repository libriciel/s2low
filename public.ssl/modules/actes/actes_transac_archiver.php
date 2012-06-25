<?php

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$actesArchiveControler = new ActesArchiveControler();

list($actesTransactionsSQL,$transactionsInfo,$bordereau,$archive_path) = $actesArchiveControler->getBordereau($id);

if (! $bordereau){
	$_SESSION['error'] = $actesArchivesSEDA->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}

$asalae = new Asalae($authorityInfo);
$result = $asalae->sendArchive($bordereau,$archive_path);

if (! $result){
	$_SESSION['error'] ="Erreur lors de l'archivage : " . $asalae->getLastError();
} else {
	$msg = "Envoie de la transaction {$transactionsInfo['id']}  au SAE ({$authorityInfo['sae_wsdl']})";
	
	$actesTransactionsSQL->updateStatus($transactionsInfo['id'],12,$msg);
	
	$_SESSION['error'] = "L'archive a été déposé";
	
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$userInfo['id'])) {
      $_SESSION['error'] .= "\nErreur de journalisation.\n";
    }
}

header("Location: actes_transac_show.php?id=$id");

