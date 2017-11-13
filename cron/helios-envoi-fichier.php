#! /usr/bin/php
<?php

/* ATTENTION - NE PAS UTILISER EN PRODUCTION */
/* le serveur FTP de la DGFIP ne supporte pas l'envoi et la réception simultané */
/* Il faut utiliser helios-reception-envoi à la place */


require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;


/** @var HeliosEnvoiControler $heliosEnvoiControler */
$heliosEnvoiControler = $objectInstancier->get("HeliosEnvoiControler");
$heliosEnvoiControler->sendAllTransactions();

//touch(HELIOS_VALIDATION_UPSTART_TOUCH_FILE);
$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
