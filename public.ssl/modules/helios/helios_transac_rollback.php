<?php

require_once(dirname(__FILE__)."/../../../init/init-www-helios.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');

$transactionSQL = new HeliosTransactionsSQL($sqlQuery);

$transactionInfo = $transactionSQL->getInfo($id);

if (! $transactionInfo['signature_technique']){
	$_SESSION['error'] = "Ce document ne contient pas de signature technique";
	header("Location: helios_transac_show.php?id=$id");
	exit;
}

$file_path = HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1'];

$xadesSignature = new XadesSignature(XMLSEC1_PATH,new PKCS12(),new X509Certificate(),RGS_VALIDCA_PATH);

$result = "/tmp/s2low_tmp_rollback_".mt_rand(0,getrandmax())."xml";
$xadesSignature->deleteSignature($file_path,$result);

$sha1 = sha1_file($result);
$file_size = filesize($result);

rename($result,HELIOS_FILES_UPLOAD_ROOT."/$sha1");

$transactionSQL->setSignatureTechnique($id,$sha1,$file_size,0);

$message = "La transaction $id est de nouveau à l'état posté.";

$transactionSQL->updateStatus($id,HeliosTransactionsSQL::POSTE,$message);
$transactionSQL->setNomFic($id,NULL);

unlink($file_path);


$_SESSION['error'] = $message;
header("Location: helios_transac_show.php?id=$id");