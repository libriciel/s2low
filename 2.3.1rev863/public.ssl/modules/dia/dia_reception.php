<?php 
require_once( __DIR__ . "/../../../init/init-www-dia.php");


$zip_path = "/tmp/test.zip";

move_uploaded_file($_FILES['dia']['tmp_name'], $zip_path);


$authoritySQL = new AuthoritySQL($sqlQuery);
$userSQL = new UserSQL($sqlQuery);
$transactionDIA = new TransactionDIA($sqlQuery);
$fileDIA = new FileDIA(DIA_UPLOAD_PATH);

$pec_reception = new PEC_Reception($authoritySQL,$userSQL,$transactionDIA,$fileDIA);

$pec_reception->saveDIAInDeliveryFolder(DIA_DELIVERY_PATH, $zip_path);

Helpers :: returnAndExit(0, "DIA importée", WEBSITE_SSL."/modules/dia/index.php" );
