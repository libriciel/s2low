<?php 
require_once( __DIR__ . "/../init/init.php");

function checkBatchStop() {
    $stop_files = glob('/tmp/batch.stop');
    if ($stop_files) {
        echo "Interruption par fichier flag.\n";
        die(1);
    }
}

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$allEnvelopes = $actesTransactionsSQL->getEnvelopeToDelete();

$actesEnvelope = new ActesEnvelope(ACTES_FILES_UPLOAD_ROOT);

$count = count($allEnvelopes);
echo $count . " transactions trouvées dans l'état <archivé par le SAE>\n";

$index = 0;
foreach($allEnvelopes as $envelopeInfo){
    checkBatchStop();
    $index++;
	$actesEnvelope->deleteFiles($envelopeInfo['file_path']);
	
	$msg = "Les fichiers de l'envelope {$envelopeInfo['id']} ont été détruits";
	
	$actesTransactionsSQL->updateStatus($envelopeInfo['transaction_id'],16,$msg);
	Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$envelopeInfo['user_id']);
	
	echo "Actes ($index/$count) - $msg.\n";	
}

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$allTransaction = $heliosTransactionsSQL->getTransactionToDelete();
$heliosFile = new HeliosFiles(HELIOS_FILES_UPLOAD_ROOT); 

$count = count($allTransaction);
echo $count . " transactions Helios trouvées dans l'état <archivé par le SAE>\n";
$index = 0;
foreach($allTransaction as $transactionInfo){
    checkBatchStop();
    $index++;
	$heliosFile->deleteFiles($transactionInfo);
	
	$msg = "Les fichiers de la transaction {$transactionInfo['id']} ont été détruits";
	
	$heliosTransactionsSQL->updateStatus($transactionInfo['id'],12,$msg);
	Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "helios", false,$transactionInfo['user_id']);
	
	echo "Helios ($index/$count) - $msg.\n";	
}
