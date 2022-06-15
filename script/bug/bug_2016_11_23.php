<?php

/*
 * Les enveloppes de la collectivite 3847 posté le 22/11/2016 entre 16h02 et 16h09 ne sont plus accessibles...
 *
 */
require_once ( __DIR__."/../../init/init.php");

require_once (__DIR__."/../../public.ssl/modules/actes/class/ActesEnvelope.class.php");


$sql = "SELECT actes_envelopes.id, actes_transactions.id as transaction_id FROM actes_transactions_workflow  
		JOIN actes_transactions ON actes_transactions_workflow.transaction_id=actes_transactions.id
		JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id 
		WHERE actes_transactions.authority_id=? AND actes_transactions_workflow.date > ? AND actes_transactions_workflow.status_id=2 
		AND actes_transactions.last_status_id=2";


$all = $sqlQuery->query($sql,3847,'2016-11-22');

foreach($all as $info) {

	$env = new ActesEnvelope($info['id']);
	$env->init();
	echo "Transaction {$info['transaction_id']} :";
	if (file_exists(ACTES_FILES_UPLOAD_ROOT . "/" . $env->get('file_path'))){
		echo "[OK]";
	} else {
		echo "[KO]";
	}

	echo "\n";

}


