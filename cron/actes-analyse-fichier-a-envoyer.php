#! /usr/bin/php
<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

$actesAnalyseFichierController = $objectInstancier->get("ActesAnalyseFichierController");
$actesAnalyseFichierController->validateAllEnveloppe();


$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
    echo "Arret du script : $sleep \n";
    sleep($sleep);
}


