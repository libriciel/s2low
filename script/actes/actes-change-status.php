<?php



require_once( __DIR__."/../../init/init.php");

function printStatus(array $actesStatuts){
    $message = "status_id doit être un entier appartenant à la liste suivante :\n";
    $message.= "    status_id\t|\tStatut\n";
    $message.= "----------------|---------------------------------------\n";
    foreach ($actesStatuts as $key => $actesStatut) {
        $message .= "\t$key\t|\t($actesStatut)\n";
    }
    return $message;
}

function checkChange($transaction_id,$status_id,ActesTransactionsSQL $actesTransactions,$actesStatuts){
    $ancienStatut = $actesStatuts[$actesTransactions->getlaststatusforid($transaction_id)];
    $nouveauStatut=$actesStatuts[$status_id];

    echo "La transaction $transaction_id passera de \"$ancienStatut\" à \"$nouveauStatut\"\n";
    echo "Etes-vous sûr de vouloir continuer ? Tapez O pour continuer : ";

    $stdin = fopen('php://stdin', 'r');

    $response = fgetc($stdin);
    if ($response != 'O') {
        echo "Annulé.\n";
        return false;
    }
    return true;
}

$s2LowLogger = $objectInstancier->get(S2lowLogger::class);
$s2LowLogger->enableStdOut();

$actesStatuts = $objectInstancier->get(ActesStatusSQL::class)->getAllStatus();

if ($argc != 3){
    $s2LowLogger->error("Nombre de paramètres incorrect. ( 2 Attendus, ".($argc -1)." renseigné(s) )" );
	$s2LowLogger->error("Usage {$argv[0]} transaction_id new_status_id");
	$s2LowLogger->error("{$argv[0]} : permet de modifier le statut d'une transaction");
	echo printStatus($actesStatuts);
	exit(-1);
}

$transaction_id = (int) $argv[1];
$status_id = $argv[2];

if(!strval($transaction_id) == $argv[1]){
    $s2LowLogger->error("transaction_id doit être un entier");
    exit(-2);
}

if(!array_key_exists($status_id,$actesStatuts)){
    $s2LowLogger->error("status_id incorrect");
    echo printStatus($actesStatuts);
    exit(-3);
}

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);

if(!$actesTransactions->getInfo($transaction_id)){
    $s2LowLogger->error("transaction_id incorrect : aucune transaction trouvée");
    exit(-4);
}


if(checkChange($transaction_id,$status_id,$actesTransactions,$actesStatuts)){
    $actesTransactions->updateStatus($transaction_id,$status_id,"Modification manuelle du statut");
    $s2LowLogger->info("Modification de la transaction $transaction_id : status $status_id");
}

exit(0);
