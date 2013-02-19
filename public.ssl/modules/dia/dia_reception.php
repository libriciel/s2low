<?php 
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$fileDIA = new FileDIA(DIA_UPLOAD_PATH);
$tmp_name = $fileDIA->saveFromUpload('dia');
if (!$tmp_name){
	Helpers :: returnAndExit(1, $fileDIA->getLastError(), WEBSITE_SSL."/modules/dia/dia_transaction_add.php" );
}

$filename = $_FILES['dia']['name'];
$filesize = $_FILES['dia']['size'];




$transactionDIA = new TransactionDIA($sqlQuery);
$dia_id = $transactionDIA->createDIA($connexion->getId(), $filename, $filesize);
$fileDIA->rename($tmp_name,$dia_id);
$fileDIA->createAE($dia_id);

$transactionDIA->addAE($dia_id,"ae.xml");
Helpers :: returnAndExit(0, "DIA importée", WEBSITE_SSL."/modules/dia/dia_detail.php?id=$dia_id" );
