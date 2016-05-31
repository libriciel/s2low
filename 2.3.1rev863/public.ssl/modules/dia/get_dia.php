<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id',1);

$transactionDIA = new TransactionDIA($sqlQuery);
$dia_info = $transactionDIA->getInfo($id);

if (!$dia_info){
		Helpers :: returnAndExit(1, "Cette DIA n'existe pas", WEBSITE_SSL."/modules/dia/" );
}

$fileDIA = new FileDIA(DIA_UPLOAD_PATH);

$fileDIA->send($id,$dia_info['filename']);

if ($dia_info['last_status_id'] == TransactionDIA::RECU) {
	$transactionDIA->updateStatus($id,TransactionDIA::RECUPERE,"DIA récupéré sur le site web");
}