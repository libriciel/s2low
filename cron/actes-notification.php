<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

$actesNotification = $objectInstancier->get('ActesNotification');
$actesNotification->sendAutomaticNotification();

$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}