<?php 

require_once( __DIR__ . "/../init/init.php");
$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$allTransactions = $actesTransactionsSQL->getArchiveFromStatus(15);

echo count($allTransactions). " transactions trouvées dans l'état <reçu par le SAE>\n";

foreach($allTransactions as $transactionInfo){
	
	echo "Transaction {$transactionInfo['unique_id']} : ";
	$asalae = new Asalae($transactionInfo);
	$message = $asalae->getReply($transactionInfo['unique_id']);

	if  ( ! $message ){
		echo  $asalae->getLastError() ."\n";	
	} else {
		$xml = simplexml_load_string($message);
		$nodeName = strval($xml->getName());
		if ($nodeName == 'ArchiveTransferAcceptance'){
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE ";
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],13,$msg,$message);
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n" . strval($xml->ReplyCode) . " - " . strval($xml->Comment);			
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],14,$msg,$message);
		}
		Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false);
	    echo $msg . "\n";
	}
}

