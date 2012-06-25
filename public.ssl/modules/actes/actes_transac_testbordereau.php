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

/*libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadXML($bordereau);

$r = $dom->schemaValidate("/home/eric/adullact/documentation/slow/Bouton Asalae/schema/archives_echanges_v0-2_archivetransfer.xsd");
echo ">>$r<<";

print_r(libxml_get_errors());*/

header("Content-type: text/xml");

echo $bordereau;