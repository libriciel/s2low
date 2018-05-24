<?php

require_once( __DIR__ . "/../init/init.php");

try {
	$transactionSQL = new ActesTransactionsSQL($sqlQuery);
	$id_list = $transactionSQL->getTransactionForAntiVirus();

	$queue = new \Pheanstalk\Pheanstalk("beanstalkd");


	foreach ($id_list as $id) {


		$queue->useTube('actes-antivirus')->put($id);

		echo "\n";
	}

} catch (Exception $e){
	echo "Impossible de reconstruire la file : ".$e->getMessage();
}