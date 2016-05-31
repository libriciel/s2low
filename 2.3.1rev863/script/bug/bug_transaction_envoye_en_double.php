<?php
exit;
/**
 * Si la servlet est executé deux fois en parallèle, alors, il est possible que des transactions Actes soit envoyé deux fois à la préfecture.
 * 
 * Ce script detecte et supprime les état doublon et l'erreur
 * 
 */

$do = false;


require_once ( __DIR__."/../../init/init.php");
require_once (SITEROOT . '/class/include.class.php');


$sql = "SELECT atw1.transaction_id FROM actes_transactions_workflow atw1 " .
		" JOIN actes_transactions_workflow atw2 " .
		" ON atw1.transaction_id=atw2.transaction_id " .
		" AND atw1.id != atw2.id ".
		" AND atw1.status_id=atw2.status_id AND atw1.status_id=3 ";

$transaction_id_list = $sqlQuery->queryOneCol($sql);

foreach($transaction_id_list as $transaction_id){
	
	$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date DESC LIMIT 3";
	
	$info = $sqlQuery->query($sql,$transaction_id);
	
	if ($info[0]['status_id'] != -1 || $info[1]['status_id'] != 3){
		echo "La transaction $transaction_id est transmise deux fois, mais les deux dernier états ne sont pas (..., transmis, en erreur)\n";
		continue;
	}
	
	if ($info[2]['status_id'] != 4){
		echo "La transaction $transaction_id est transmise deux fois, mais le dernier état avant sa retransmission n'est pas acquitté\n";
		continue;
	}
	
	if ($do){
		$sql = "DELETE FROM actes_transactions_workflow WHERE id=? AND transaction_id=? LIMIT 1";
		$sqlQuery->query($sql,$info[0]['id'],$transaction_id);
		$sqlQuery->query($sql,$info[1]['id'],$transaction_id);
		$sql = "UPDATE actes_transactions SET last_status_id=? WHERE id=?";
		$sqlQuery->query($sql, 4,$transaction_id);
	} 
	echo "Transaction $transaction_id -> acquitté\n";
	
}