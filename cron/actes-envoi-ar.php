#! /usr/bin/php
<?php
require_once(__DIR__ . "/../init/init.php");

list($objectInstancier, $s2lowLogger) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class,S2lowLogger::class]
    );

$s2lowLogger->setName("actes-envoi-ar");
$s2lowLogger->enableStdOut(true);

$start = time();
$s2lowLogger->info("Debut " . date("Y-m-d H:i:s", $start));
$min_exec_time = 10;

try {
    $actesEnvoiAR = $objectInstancier->get(ActesEnvoiAR::class);
    $actesEnvoiAR->sendAllAR();
} catch (Exception $e) {
    $s2lowLogger->critical($e->getMessage());
    $s2lowLogger->critical($e->getTraceAsString());
}


$stop = time();
$s2lowLogger->info("Fin " . date("Y-m-d H:i:s", $stop));
$sleep = $min_exec_time - ($stop - $start);
if ($sleep > 0) {
    $s2lowLogger->info("Arret du script : $sleep");
    sleep($sleep);
}
