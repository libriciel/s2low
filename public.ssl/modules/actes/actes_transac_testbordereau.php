<?php

require_once( __DIR__ . "/../../../init/init-www-actes.php");
$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id');

$actesArchiveControler = new ActesArchiveControler();

list($actesTransactionsSQL,$transactionsInfo,$bordereau,$archive_path) = $actesArchiveControler->getBordereau($id);

if (! $bordereau){
	$_SESSION['error'] = $actesArchivesSEDA->getLastError();
	header("Location: actes_transac_show.php?id=$id");
	exit;
}


header("Content-type: text/xml");

echo $bordereau;