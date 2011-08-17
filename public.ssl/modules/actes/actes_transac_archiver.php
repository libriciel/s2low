<?php

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$transactionsInfo = $actesTransactionsSQL->getInfo($id);

if ( ! $transactionsInfo || $transactionsInfo['user_id'] != $connexion->getId()){
	sortir("Accès refusé");
}

if ($transactionsInfo['last_status_id'] != 4 && $transactionsInfo['type'] != 1) {
	$_SESSION['error'] = "Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu ».";
	header("Location: actes_transac_show.php?id=$id");
	exit;
}

$actesEnvelopeSQL = new ActesEnvelopeSQL($sqlQuery);
$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);

$actesTransactionsStatusInfo = $actesTransactionsSQL->getStatusInfo($id,4);
$actesFile = $actesTransactionsSQL->getAllFile($id);

$authoritySQL = new AuthoritySQL($sqlQuery);
$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);

if (! $authorityInfo['sae_numero_aggrement'] ){
	$_SESSION['error']  = "La collectivité ne présente pas de numéro d'aggrément";
	header("Location: actes_transac_show.php?id=$id");
	exit;
}


$file_to_send =  ACTES_FILES_UPLOAD_ROOT . "/" .  $actesEnvelopeInfo['file_path'];

$tgzExtractor = new TGZExtractor('/tmp');
$tgzExtractor->extract($file_to_send,$actesFile[1]['filename']);

$actesArchivesSEDA = new ActesArchiveSEDA("/tmp/");
$actesArchivesSEDA->setAuthorityInfo($authorityInfo['siren'],$authorityInfo['name'],$authorityInfo['sae_numero_aggrement']);
$actesArchivesSEDA->setActesFileName($actesFile[1]['filename']);
$actesArchivesSEDA->setTransactionStatusInfo($actesTransactionsStatusInfo);

array_shift ($actesFile);
array_shift ($actesFile);

foreach($actesFile as $annexe){
	$tgzExtractor->extract($file_to_send,$annexe['filename']);
	$actesArchivesSEDA->addAnnexe($annexe['filename'],$annexe['filetype']);
}

$archive_path = $actesArchivesSEDA->getArchive();
if (! $archive_path){
	$_SESSION['error'] = $actesArchivesSEDA->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}


$bordereau = $actesArchivesSEDA->getBordereau($transactionsInfo);

header("Content-type: text/xml;");

if (! $bordereau){
	$_SESSION['error'] = $actesArchivesSEDA->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}

$asalae = new Asalae($authorityInfo);
$result = $asalae->sendArchive($bordereau,$archive_path);

if (! $result){
	$_SESSION['error'] ="Erreur lors de l'archivage : " . $asalae->getLastError();
}

$_SESSION['error'] = "L'archive a été déposé";
header("Location: actes_transac_show.php?id=$id");



exit;

//TODO : 
$trans->set("archive_url", 'http://testasalae.dev.adullact.org');
$trans->save();
