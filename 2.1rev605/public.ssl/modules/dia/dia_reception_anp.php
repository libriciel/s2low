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
$tmp_name = $fileDIA->saveFromUpload('anp');
if (!$tmp_name){
	Helpers :: returnAndExit(1, $fileDIA->getLastError(), WEBSITE_SSL."/modules/dia/dia_detail.php?id=$id" );
}
$fileDIA->setANP($tmp_name,$id);



$filename = $_FILES['anp']['name'];
$filesize = $_FILES['anp']['size'];

$transactionDIA->addANP($id,$filename);

Helpers :: returnAndExit(0, "ANP importée", WEBSITE_SSL."/modules/dia/dia_detail.php?id=$id" );
