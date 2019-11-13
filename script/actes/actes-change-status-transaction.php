<?php

/** Change le status d'une transaction donnée en paramètre
 */

require_once ( __DIR__."/../../init/init.php");

if(!(count($argv)==3)){
    echo "Nombre de paramètres incorrects.\nUsage : php actes-change-status-transaction.php idtransation status\n";
    return 1;
}

$idtransaction = (int) $argv[1]; #1;
$status = (int) $argv[2]; #16;

if(!(strval($idtransaction) == $argv[1] ) || !(strval($status) == $argv[2]) ){
    echo "Les paramètres doivent être des entiers\n";
    return 1;
}

$message = "Changement de statut realise manuellement par l'administrateur";
$objectInstancier = ObjectInstancierFactory::getObjetInstancier();

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);

$actesTransactions->updateStatus($idtransaction,$status,$message);
