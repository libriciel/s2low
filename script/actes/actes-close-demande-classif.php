<?php


require_once(__DIR__ . "/../../init/init.php");


$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);

$sql = "
SELECT transaction_id FROM actes_transactions  
JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id 
AND actes_transactions_workflow.status_id=3
WHERE last_status_id=3 AND type='7' AND date<'2019-04-12' 
";

$all_transaction = $sqlQuery->query($sql);

foreach ($all_transaction as $info) {
	echo $info['transaction_id'];
	$actesTransactionSQL->updateStatus($info['transaction_id'], -1, "Transaction passé en erreur");
	echo " [OK]\n";
	exit;
}
