#! /usr/bin/php
<?php
//Ce script ne génère pas de requêtes de base de données, on a pas besoin de le mettre dans beanstalked dans un premier temps

require_once( __DIR__ . "/../init/init.php");


$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("actes-analyse-fichier-recu");
$s2lowLogger->enableStdOut(true);

$start = time();
$s2lowLogger->info("Debut ".date("Y-m-d H:i:s",$start));
$min_exec_time = 10;



try {
	$actesAnalyseFichierRecuController = $objectInstancier->get(ActesAnalyseFichierRecuController::class);
	$actesAnalyseFichierRecuController->analyseAll();
} catch (Exception $e){
    $s2lowLogger->critical($e->getMessage());
    $s2lowLogger->critical($e->getTraceAsString());
}


$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	$s2lowLogger->info( "Arret du script : $sleep");
    sleep($sleep);
}


