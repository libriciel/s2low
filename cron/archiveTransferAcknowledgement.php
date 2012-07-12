<?php 

require_once( __DIR__ . "/../init/init.php");
$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);


$allTransactions = $actesTransactionsSQL->getArchiveFromStatus(12);

echo count($allTransactions). " transactions trouvées dans l'état <envoyé au SAE>\n";

foreach($allTransactions as $transactionInfo){
	
	echo "Transaction {$transactionInfo['unique_id']} : ";
	$asalae = new Asalae($transactionInfo);
	$message = $asalae->getAcuseReception($transactionInfo['sae_transfer_identifier']);
	if  ( ! $message ){
		echo  $asalae->getLastError() ."\n";	
	} else {
		$xml = simplexml_load_string($message);
		$xml_message = utf8_decode(strval($xml->ReplyCode) . " - " . strval($xml->Comment));
		
		$msg = "La transaction {$transactionInfo['id']} a été reçu par le SAE : \n$xml_message"; 
		$actesTransactionsSQL->updateStatus($transactionInfo['id'],15,$msg,$message);
		
		Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$transactionInfo['user_id']);
	    echo $msg . "\n";
	}
}




