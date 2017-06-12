<?php 
require_once( __DIR__ . "/../init/init.php");

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$allTransactions = $actesTransactionsSQL->getArchiveFromStatus(12);

echo count($allTransactions). " transactions ACTES trouvées dans l'état <envoyé au SAE>\n";

$actesArchiveControler = new ActesArchiveControler($sqlQuery);

foreach($allTransactions as $transactionInfo){
	$actesArchiveControler->verifArchive($transactionInfo);
}


$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$allTransactions = $heliosTransactionsSQL->getArchiveFromStatusWithSAE(9);

echo count($allTransactions). " transactions HELIOS trouvées dans l'état <envoyé au SAE>\n";

/** @var HeliosArchiveControler $heliosArchiveControler */
$heliosArchiveControler = $objectInstancier->get("HeliosArchiveControler");

foreach($allTransactions as $transactionInfo){
	$heliosArchiveControler->verifArchive($transactionInfo);
}

