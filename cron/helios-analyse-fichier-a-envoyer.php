#! /usr/bin/php
<?php

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

require_once( __DIR__ . "/../init/init.php");

$heliosEnvoiControler = new HeliosEnvoiControler($sqlQuery);
$heliosEnvoiControler->setDoNotVerifyNomFicUnicity(HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY);
$heliosEnvoiControler->validateAllTransactions();

touch(HELIOS_VALIDATION_UPSTART_TOUCH_FILE);
$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
