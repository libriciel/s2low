<?php
/**
 * Si la servlet est executé deux fois en parallèle, alors, il est possible que des transactions Actes soit envoyé deux fois à la préfecture.
 * 
 * Ce script detecte et supprime les état doublon et l'erreur
 * 
 */

$do = false;


require_once ( __DIR__."/../../init/init.php");
require_once (SITEROOT . '/class/include.class.php');


$sql = "SELECT DISTINCT atw1.transaction_id FROM actes_transactions_workflow atw1 " .
		" JOIN actes_transactions_workflow atw2 " .
		" ON atw1.transaction_id=atw2.transaction_id " .
		" AND atw1.id != atw2.id ".
		" AND atw1.status_id=atw2.status_id AND atw1.status_id=3 ".
		" AND atw1.date> '2016-04-01' AND atw2.date>'2016-04-1'" .
		" ORDER BY atw1.transaction_id ";

$transaction_id_list = $sqlQuery->queryOneCol($sql);

foreach($transaction_id_list as $transaction_id){

	echo "[$transaction_id] Analyse de la transaction\n";

	$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date DESC";
	$info = $sqlQuery->query($sql,$transaction_id);

	if ($info[0]['status_id'] != -1){
		echo "[$transaction_id] Le dernier état de la transaction n'est pas en erreur...\n";
		continue;
	}

	$is_acquitter = false;
	foreach($info as $line){
		if ($line['status_id'] == 4){
			$is_acquitter = true;
			break;
		}
		if (! in_array($line['status_id'],array(-1,4,3))){
			echo "[$transaction_id] Etat inconnu : {$line['status_id']}\n";
			continue 2;
		}
	}
	if (! $is_acquitter){
		echo "[$transaction_id] La transaction n'a pas été acquitté\n";
		continue;
	}

	foreach($info as $line){
		if ($line['status_id'] == 4){
			break;
		}
		echo "[$transaction_id] Suppression de l'état {$line['status_id']} ({$line['date']})\n";
		if ($do) {
			$sql = "DELETE FROM actes_transactions_workflow WHERE id=? AND transaction_id=?";
			$sqlQuery->query($sql, $line['id'], $transaction_id);
		}

	}

	echo "[$transaction_id] Mise à jour du statut de la transaction à 4\n";
	if ($do){
		$sql = "UPDATE actes_transactions SET last_status_id = ? WHERE id=?";
		$sqlQuery->query($sql, 4,$transaction_id);
	} 
	echo "[$transaction_id] -> acquitté\n";
	
}