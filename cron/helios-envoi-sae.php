#! /usr/bin/php
<?php

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

require_once( __DIR__ . "/../init/init.php");


$heliosArchiveControler = $objectInstancier->get("HeliosArchiveControler");
$heliosArchiveControler->sendAllArchive();


$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
