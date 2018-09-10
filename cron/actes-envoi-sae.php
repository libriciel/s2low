#! /usr/bin/php
<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

if (empty($argv[1])){
    $authority_id = 0;
} else {
    $authority_id = $argv[1];
}


/** @var ActesArchiveControler $actesArchiveControler */
$actesArchiveControler = $objectInstancier->get(ActesArchiveControler::class);
$actesArchiveControler->sendAllArchive($authority_id);


$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
