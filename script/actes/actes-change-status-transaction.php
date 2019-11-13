<?php

/** Change le status d'une transaction donnée en paramètre
 */

require_once ( __DIR__."/../../init/init.php");
$idtransaction = 1;
$status = 16;
$message = "Changement de statut realise manuellement par l'administrateur";
$objectInstancier = ObjectInstancierFactory::getObjetInstancier();

$actesTransactions = $objectInstancier->get(ActesTransactionsSQL::class);

$actesTransactions->updateStatus($idtransaction,$status,$message);
