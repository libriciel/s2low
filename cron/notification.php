<?php 
require_once (dirname(__FILE__)."/../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesNotification.class.php');


$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

$db = DatabasePool::getInstance();
$actesNotification = new ActesNotification($db);
$actesNotification->sendAutomaticNotification();

$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}