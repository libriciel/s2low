#! /usr/bin/php
<?php
declare(ticks = 1);

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

require_once( __DIR__ . "/../init/init.php");

if (empty($argv[1])){
	$authority_id = 0;
} else {
	$authority_id = $argv[1];
}


$heliosArchiveControler = $objectInstancier->get(HeliosArchiveControler::class);
$heliosArchiveControler->sendAllArchive($authority_id);


$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
