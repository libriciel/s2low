<?php
require_once( __DIR__ . "/../init/init.php");

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$allTransactions = $heliosTransactionsSQL->getArchiveFromStatusWithSAE(9);

echo count($allTransactions). " transactions HELIOS trouvees dans l'etat <envoye au SAE>\n";

/** @var HeliosArchiveControler $heliosArchiveControler */
$heliosArchiveControler = $objectInstancier->get("HeliosArchiveControler");

foreach($allTransactions as $transactionInfo){
	$heliosArchiveControler->verifArchive($transactionInfo);
}