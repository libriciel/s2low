#! /usr/bin/php
<?php

require_once( __DIR__ . "/../init/init.php");
$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;




require_once SITEROOT."/public.ssl/modules/actes/class/ActesEnvelope.class.php";
require_once SITEROOT."/public.ssl/modules/actes/class/ActesTransaction.class.php";


$transactionSQL = new ActesTransactionsSQL($sqlQuery);
$id_list = $transactionSQL->getTransactionForAntiVirus();

print_r($id_list);



foreach($id_list as $id){
	echo "Traitement transaction $id : ";
	$zeTrans = new ActesTransaction();
	$zeTrans->setId($id);
	$zeTrans->init();
	
	$zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
	$zeEnv->init();
	
	$archive_path =  ACTES_FILES_UPLOAD_ROOT."/". $zeEnv->get('file_path');
	
	if ($zeEnv->checkArchiveSanity($archive_path)){
		
		$transactionSQL->setAntivirusCheck($id);
		
		echo "OK";
		
	} else {
		$message = $zeEnv->getErrorMsg();
		echo "Virus Found : $message";
		$transactionSQL->updateStatus($id, -1, $message);
	}
	
	echo "\n";
}



touch(ANTIVIRUS_UPSTART_TOUCH_FILE);
$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
