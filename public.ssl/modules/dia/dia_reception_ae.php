<?php 
require_once( __DIR__ . "/../../../init/init-www-dia.php");


$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');


$transactionDIA = new TransactionDIA($sqlQuery);
$dia_info = $transactionDIA->getInfo($id);

if (!$dia_info){
		Helpers :: returnAndExit(1, "Cette DIA n'existe pas", WEBSITE_SSL."/modules/dia/" );
}


$fileDIA = new FileDIA(DIA_UPLOAD_PATH);
$tmp_name = $fileDIA->saveFromUpload('ae');
if (!$tmp_name){
	Helpers :: returnAndExit(1, $fileDIA->getLastError(), WEBSITE_SSL."/modules/dia/dia_detail.php?id=$id" );
}
$fileDIA->setAR($tmp_name,$id);



$filename = $_FILES['ae']['name'];
$filesize = $_FILES['ae']['size'];

$transactionDIA->addAE($id,$filename);

Helpers :: returnAndExit(0, "AE importée", WEBSITE_SSL."/modules/dia/dia_detail.php?id=$id" );
