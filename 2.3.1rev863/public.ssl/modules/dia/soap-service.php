<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

ini_set("soap.wsdl_cache_enabled", 0);

$transactionDIA = new TransactionDIA($sqlQuery);
if ($droit->isSuperAdmin($userInfo) ) { 
	$transactionDIA->setAuthority($authority_filtre);
}elseif ($droit->isAdmin($userInfo)){
	$transactionDIA->setAuthority($userInfo['authority_id']);
} else {
  $serviceUser = new ServiceUser(DatabasePool::getInstance());
  $collegues = $serviceUser->getMesCollegues($connexion->getId());
  $collegue[] = $connexion->getId();
  foreach($collegues as $info){
  	$collegue[] =  $info['id_user'];
  }
  $transactionDIA->setUserId($collegue);
}


$fileDIA = new FileDIA(DIA_UPLOAD_PATH);

$diaAction = new DIAAction($transactionDIA,$fileDIA);

$server = new SoapServer( WEBSITE . "/modules/dia/wsdl.php");
$server->setClass("DIA_SOAP",$diaAction);
$server->handle();
