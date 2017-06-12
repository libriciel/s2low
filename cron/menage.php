<?php 
require_once( __DIR__ . "/../init/init.php");

throw new Exception("Avec le stockage objet, on peut se poser la question du ménage...");


$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$allEnvelopes = $actesTransactionsSQL->getEnvelopeToDelete();

$actesEnvelope = new ActesFiles(ACTES_FILES_UPLOAD_ROOT);

echo count($allEnvelopes). " transactions trouvées dans l'état <archivé par le SAE>\n";

foreach($allEnvelopes as $envelopeInfo){
	$actesEnvelope->deleteFiles($envelopeInfo['file_path']);
	
	$msg = "Les fichiers de l'envelope {$envelopeInfo['id']} ont été détruits";
	
	$actesTransactionsSQL->updateStatus($envelopeInfo['transaction_id'],16,$msg);
	Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$envelopeInfo['user_id']);
	
	echo $msg."\n";	
}

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$allTransaction = $heliosTransactionsSQL->getTransactionToDelete();
$heliosFile = new HeliosFiles(HELIOS_FILES_UPLOAD_ROOT); 

echo count($allTransaction) . " transactions Helios trouvées dans l'état <archivé par le SAE>\n";
foreach($allTransaction as $transactionInfo){
	$heliosFile->deleteFiles($transactionInfo);
	
	$msg = "Les fichiers de la transaction {$transactionInfo['id']} ont été détruits";
	
	$heliosTransactionsSQL->updateStatus($transactionInfo['id'],12,$msg);
	Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$transactionInfo['user_id']);
	
	echo $msg."\n";	
}
