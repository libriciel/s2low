#! /usr/bin/php
<?php
require_once(__DIR__ . "/../init/init.php");

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->setMinExecutionTimeInSeconds(10);
$workerScript->scriptByClassName(HeliosReceptionWorker::class, true, true);
