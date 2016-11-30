<?php 
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id',1);

$transactionDIA = new TransactionDIA($sqlQuery);
$transactionDIA->delete($id);

$fileDIA = new FileDIA(DIA_UPLOAD_PATH);
$fileDIA->delete($id);

Helpers :: returnAndExit(0, "DIA supprimée", WEBSITE_SSL."/modules/dia/index.php" );
