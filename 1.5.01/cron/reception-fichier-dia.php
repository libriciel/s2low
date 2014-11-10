#! /usr/bin/php
<?php

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 60;

require_once(__DIR__."/../init/init.php");

$authoritySQL = new AuthoritySQL($sqlQuery);
$userSQL = new UserSQL($sqlQuery);
$transactionDIA = new TransactionDIA($sqlQuery);
$fileDIA = new FileDIA(DIA_UPLOAD_PATH);

$pec_reception = new PEC_Reception($authoritySQL,$userSQL,$transactionDIA,$fileDIA);

$pec_reception->go(DIA_DELIVERY_PATH);


touch(DIA_UPSTART_TOUCH_FILE);
$stop = time();
echo "Debut ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
