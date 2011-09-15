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
		$xml_message = utf8_decode(strval($xml->ReplyCode) . " - " . strval($xml->Comment));
		
		if ($nodeName == 'ArchiveTransferAcceptance'){
			$url = $asalae->getURL( $transactionInfo['sae_wsdl'], strval($xml->Archive->ArchivalAgencyArchiveIdentifier));
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE : \n$xml_message";
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],13,$msg,$message);
			$actesTransactionsSQL->setArchiveURL($transactionInfo['id'],$url);			
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n$xml_message";			
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],14,$msg,$message);
		}
		Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$transactionInfo['user_id']);
	    echo $msg . "\n";
	}
}

