<?php
require_once( __DIR__ . "/../init/init.php");

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$allTransactions = $actesTransactionsSQL->getArchiveFromStatus(12);

echo count($allTransactions). " transactions ACTES trouvees dans l'etat <envoye au SAE>\n";

$actesArchiveControler = new ActesArchiveControler($sqlQuery);

foreach($allTransactions as $transactionInfo){
	$actesArchiveControler->verifArchive($transactionInfo);
}