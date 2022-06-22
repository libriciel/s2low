<?php
require_once( __DIR__ . "/../init/init.php");

throw new Exception("Script désactivé pour le moment. Avec le stockage objet, on peut se poser la question du ménage...");


$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$allEnvelopes = $actesTransactionsSQL->getEnvelopeToDelete();

$actesEnvelope = new ActesFiles(ACTES_FILES_UPLOAD_ROOT);

echo count($allEnvelopes). " transactions trouvées dans l'état <archivé par le SAE>\n";
$sigtermHandler = SigTermHandler::getInstance();
foreach($allEnvelopes as $envelopeInfo){
	$actesEnvelope->deleteFiles($envelopeInfo['file_path']);
	
	$msg = "Les fichiers de l'envelope {$envelopeInfo['id']} ont été détruits";
	
	$actesTransactionsSQL->updateStatus($envelopeInfo['transaction_id'],16,$msg);
	Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false,$envelopeInfo['user_id']);
	
	echo $msg."\n";
    if ($sigtermHandler->isSigtermCalled()){
        break;
    }
}
