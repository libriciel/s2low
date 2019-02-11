<?php


require_once( __DIR__."/../../init/init.php");


if ($argc < 2){
	echo "Usage {$argv[0]} authority_id\n";
	echo "{$argv[0]} : permet de supprimer toutes les préparation d'envoi SAE (id=19)\n";
	exit(-1);
}

$authority_id = $argv[1];

$actesTransactionsSQL = $objectInstancier->get(ActesTransactionsSQL::class);

$transaction_list = $actesTransactionsSQL->getListByStatusAndAuthority(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,$authority_id,0,100000);


echo count($transaction_list). " transactions à abandonnées\n";
foreach($transaction_list as $transaction_info){

	$id = $transaction_info['id'];
	$message = "Abandon de l'envoi de la transaction $id au SAE";
	echo $message . "\n";
	$actesTransactionsSQL->updateStatus(
		$id,
		ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
		$message
	);

}

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->rebuildQueue($objectInstancier->get(ActesEnvoiSaeWorker::class));
