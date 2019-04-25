#! /usr/bin/php
<?php
declare(ticks = 1);
//Ce script ne génère pas de requêtes de base de données, on a pas besoin de le mettre dans beanstalked dans un premier temps

require_once( __DIR__ . "/../init/init.php");

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("actes-reception-fichier");
$s2lowLogger->enableStdOut(true);


$start = time();
$min_exec_time = 10;

try {
	$actesImapRetrieve = $objectInstancier->get(ActesImapRetrieve::class);
	$actesImapRetrieve->retrieve();
} catch (Exception $e){
	$s2lowLogger->critical($e->getMessage());
	$s2lowLogger->critical($e->getTraceAsString());
}

$stop = time();
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	$s2lowLogger->info( "Arret du script : $sleep ");
    sleep($sleep);
}
