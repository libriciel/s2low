<?php

/** Change le status d'une transaction donnée en paramètre
 */

require_once ( __DIR__."/../../init/init.php");

$nbParams = count($argv) - 1;

if($nbParams!=2){
    echo "Nombre de paramètres incorrect. ($nbParams fourni(s), 2 attendus)\nUsage : php $argv[0] idtransation status\n";
    return -1;
}

$idTransaction = (int) $argv[1];
$status = (int) $argv[2];

if(!(strval($idTransaction) == $argv[1] ) || !(strval($status) == $argv[2]) ){
    echo "Les paramètres doivent être des entiers\n";
    return -2;
}

$message = "Changement de statut realise manuellement par l'administrateur";
$objectInstancier = ObjectInstancierFactory::getObjetInstancier();

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);

$actesTransactions->updateStatus($idTransaction,$status,$message);

return 0;
