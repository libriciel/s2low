#! /usr/bin/php
<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$actesAntivirus = $objectInstancier->get(ActesAntivirus::class);

$s2lowLogger->setName($actesAntivirus->getQueueName()."-script");
$s2lowLogger->enableStdOut();

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->script($actesAntivirus);
