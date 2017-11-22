#! /usr/bin/php
<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

/** @var HeliosEnvoiControler $heliosEnvoiControler */
$heliosEnvoiControler = $objectInstancier->get("HeliosEnvoiControler");
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
