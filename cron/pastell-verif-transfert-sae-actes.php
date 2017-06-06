<?php
require_once( __DIR__ . "/../init/init.php");

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$date = date("Y-m-d",strtotime("-30 days"));

$allTransactions = $actesTransactionsSQL->getLastArchiveFromStatus(12,$date);

echo count($allTransactions). " transactions ACTES trouvees dans l'etat <envoye au SAE>\n";

$actesArchiveControler = new ActesArchiveControler($sqlQuery);

foreach($allTransactions as $transactionInfo){
	$actesArchiveControler->verifArchive($transactionInfo);
}