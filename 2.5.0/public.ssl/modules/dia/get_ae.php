<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id',1);

$transactionDIA = new TransactionDIA($sqlQuery);
$dia_info = $transactionDIA->getInfo($id);

if (!$dia_info){
	Helpers :: returnAndExit(1, "Cette DIA n'existe pas", WEBSITE_SSL."/modules/dia/" );
}
if (!$dia_info['accuse_enregistrement']){
	Helpers :: returnAndExit(1, "Cette AE n'existe pas", WEBSITE_SSL."/modules/dia/" );
}
$fileDIA = new FileDIA(DIA_UPLOAD_PATH);

$fileDIA->sendAE($id,$dia_info['accuse_enregistrement']);
