<?php

require_once(__DIR__ . "/../init/init.php");

require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("actes-notification");
$s2lowLogger->enableStdOut(true);

$start = time();
$s2lowLogger->info("Debut " . date("Y-m-d H:i:s", $start));
$min_exec_time = 10;

try {
    $actesNotification = $objectInstancier->get(ActesNotification::class);
    $actesNotification->sendAutomaticNotification();
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
